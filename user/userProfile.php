

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>


<?php
// ================== DB Connections ==================
include __DIR__ . '/../dbconnection/dbEmployee.php';
$empConn = $conn;
include __DIR__ . '/../dbconnection/mainDB.php';
$shiftConn = $conn;

// ================== Firebase Delete Helper ==================
function deleteFromFirebase($firebaseUid) {
    if (!$firebaseUid) return;

    $serviceAccountPath = __DIR__ . '/../firebase-admin-key.json';
    if (!file_exists($serviceAccountPath)) {
        error_log(" Firebase service account file missing.");
        return;
    }
    $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);

    // Build JWT for Google OAuth2
    $jwtHeader = rtrim(strtr(base64_encode(json_encode(['alg' => 'RS256','typ' => 'JWT'])), '+/', '-_'), '=');
    $now = time();
    $jwtClaim = rtrim(strtr(base64_encode(json_encode([
        'iss' => $serviceAccount['client_email'],
        'scope' => 'https://www.googleapis.com/auth/identitytoolkit',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600
    ])), '+/', '-_'), '=');

    $signatureInput = $jwtHeader.'.'.$jwtClaim;
    openssl_sign($signatureInput, $signature, openssl_pkey_get_private($serviceAccount['private_key']), 'sha256WithRSAEncryption');
    $jwt = $signatureInput.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

    // Exchange JWT for access token
    $ch = curl_init("https://oauth2.googleapis.com/token");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/x-www-form-urlencoded"],
        CURLOPT_POSTFIELDS => http_build_query([
            "grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer",
            "assertion" => $jwt
        ])
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $tokenData = json_decode($response, true);

    if (!isset($tokenData['access_token'])) {
        error_log(" Firebase token error: " . $response);
        return;
    }
    $accessToken = $tokenData['access_token'];

    // Call Firebase REST API to delete user
    $url = "https://identitytoolkit.googleapis.com/v1/projects/{$serviceAccount['project_id']}/accounts:delete";
    $payload = json_encode(['localId' => $firebaseUid]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $accessToken",
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => $payload
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $resData = json_decode($response, true);
    if (isset($resData['error'])) {
        error_log(" Firebase deletion failed: " . $response);
    } else {
        error_log("Firebase user $firebaseUid deleted successfully.");
    }
}

// ================== Handle Delete ==================
if (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];

    // 0. Get firebase_uid before deleting
    $stmt = $shiftConn->prepare("SELECT firebase_uid FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $deleteId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $firebaseUid = $row['firebase_uid'] ?? null;
    $stmt->close();

    // 1. Unlink employee
    $stmt = $empConn->prepare("UPDATE employees SET user_id = NULL WHERE user_id = ?");
    $stmt->bind_param("s", $deleteId);
    $stmt->execute();
    $stmt->close();

    // 2. Delete user roles
    $stmt = $shiftConn->prepare("DELETE FROM user_roles WHERE user_id = ?");
    $stmt->bind_param("s", $deleteId);
    $stmt->execute();
    $stmt->close();

    // 3. Delete user profile
    $stmt = $shiftConn->prepare("DELETE FROM user_profiles WHERE user_id = ?");
    $stmt->bind_param("s", $deleteId);
    $stmt->execute();
    $stmt->close();

    // 4. Delete user
    $stmt = $shiftConn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $deleteId);
    $stmt->execute();
    $stmt->close();

    // 5. Delete from Firebase
    deleteFromFirebase($firebaseUid);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ================== Handle Update ==================
$imageUploadError = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id    = $_POST['user_id'];
    $first_name = $_POST['first_name'];
    $last_name  = $_POST['last_name'];
    $phone      = $_POST['phone'];
    $address    = $_POST['address'];
    $timezone   = $_POST['timezone'];
    $locale     = $_POST['locale'];

    if (isset($_FILES['reference_image']) && $_FILES['reference_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['reference_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/reference_image/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $ext = pathinfo($_FILES['reference_image']['name'], PATHINFO_EXTENSION);
            $filename = $user_id . '.' . $ext;
            $filePath = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['reference_image']['tmp_name'], $filePath)) {
                $reference_image = 'uploads/reference_image/' . $filename;
                $stmt = $shiftConn->prepare("UPDATE users SET reference_image=? WHERE user_id=?");
                $stmt->bind_param("ss", $reference_image, $user_id);
                $stmt->execute();
                $stmt->close();
            } else {
                $imageUploadError = " Failed to move uploaded image.";
            }
        } else {
            $imageUploadError = " Error uploading image. Error code: " . $_FILES['reference_image']['error'];
        }
    }

    $stmt = $shiftConn->prepare("
        UPDATE user_profiles 
        SET first_name=?, last_name=?, phone=?, address=?, timezone=?, locale=? 
        WHERE user_id=?
    ");
    $stmt->bind_param("sssssss", $first_name, $last_name, $phone, $address, $timezone, $locale, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ================== Fetch Records ==================
$res = $shiftConn->query("
    SELECT u.user_id, u.reference_image, 
           p.first_name, p.last_name, p.phone, p.address, p.timezone, p.locale
    FROM users u
    LEFT JOIN user_profiles p ON u.user_id = p.user_id
");
?>




<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Time and Attendance</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="icon" type="image/png" href="../picture/logo2.png" />

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      lucide.createIcons();
    });
  </script>
</head>
<body class="h-screen overflow-hidden">

  <!-- FLEX LAYOUT: Sidebar + Main -->
  <div class="flex h-full">

    <!-- Sidebar -->
    <?php include '../sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-y-auto">

      <!-- Main Top Header (inside content) -->
      <main class="p-6 space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between border-b py-6">
          <!-- Left: Title -->
          <h2 class="text-xl font-semibold text-gray-800" id="main-content-title">User Management</h2>


          <!-- ito yung profile ng may login wag kalimutan lagyan ng session yung profile.php para madetect nya if may login or wala -->
<?php include '../profile.php'; ?>

        </div>
<!-- Second Header: Submodules -->


<?php 
include 'userNavbar.php'; ?>




<div class="bg-white shadow-md rounded-2xl p-6 md:p-10 w-full mx-auto mt-10 mb-10">
    <h2 class="text-2xl font-bold mb-6">Employee Account</h2>

    <a href="createUser.php" class="inline-block mb-6">
        <button class="px-6 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition">
            Create Account
        </button>
    </a>

    <div class="bg-white shadow-lg rounded-2xl p-4 md:p-6">
        <h2 class="text-2xl font-bold mb-4">User Profiles</h2>

        <!-- Responsive Table Wrapper -->
        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300 rounded-lg overflow-hidden">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="p-2 text-left">User ID</th>
                        <th class="p-2 text-left">First Name</th>
                        <th class="p-2 text-left">Last Name</th>
                        <th class="p-2 text-left">Profile Image</th>
                        <th class="p-2 text-left">Phone</th>
                        <th class="p-2 text-left">Address</th>
                        <th class="p-2 text-left">Timezone</th>
                        <th class="p-2 text-left">Locale</th>
                        <th class="p-2 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $res->fetch_assoc()): ?>
                    <tr class="border-t hover:bg-gray-50">
                        <td class="p-2 whitespace-nowrap"><?= htmlspecialchars($row['user_id']) ?></td>
                        <td class="p-2 whitespace-nowrap"><?= htmlspecialchars($row['first_name']) ?></td>
                        <td class="p-2 whitespace-nowrap"><?= htmlspecialchars($row['last_name']) ?></td>
                        <td class="p-2">
                            <?php if (!empty($row['reference_image'])): ?>
                            <img src="/public_html/<?= htmlspecialchars($row['reference_image']) ?>" 
                                 alt="Profile Image" 
                                 class="w-12 h-12 rounded-full object-cover cursor-pointer preview-img"
                                 data-src="/public_html/<?= htmlspecialchars($row['reference_image']) ?>" />
                            <?php else: ?>
                            <span class="text-gray-400">No image</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-2 whitespace-nowrap"><?= !empty($row['phone']) ? htmlspecialchars($row['phone']) : '-' ?></td>
                        <td class="p-2 whitespace-nowrap"><?= !empty($row['address']) ? htmlspecialchars($row['address']) : '-' ?></td>
                        <td class="p-2 whitespace-nowrap"><?= htmlspecialchars($row['timezone']) ?></td>
                        <td class="p-2 whitespace-nowrap"><?= htmlspecialchars($row['locale']) ?></td>
                        <td class="p-2 flex flex-col sm:flex-row sm:space-x-2 gap-2 justify-center">
                            <button 
                                onclick='openEditModal(<?= json_encode($row) ?>)' 
                                class="bg-gray-800 hover:bg-gray-900 text-white hover:text-yellow-500 px-4 py-2 rounded w-full sm:w-auto">
                                Edit
                            </button>
                            <button 
                                onclick="openDeleteModal('<?= $row['user_id'] ?>')" 
                                class="px-3 py-2 rounded bg-red-500 text-white hover:bg-red-600 w-full sm:w-auto">
                                Delete
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div id="imagePreviewModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden z-50">
        <div class="relative">
            <button id="closeImagePreview" class="absolute top-2 right-2 text-white text-2xl">&times;</button>
            <img id="imagePreview" src="" class="max-w-[90vw] max-h-[90vh] rounded shadow-lg" alt="Preview">
        </div>
    </div>
</div>

<script>
    // Image Preview Logic
    document.querySelectorAll('.preview-img').forEach(img => {
        img.addEventListener('click', function() {
            const src = this.getAttribute('data-src');
            const modal = document.getElementById('imagePreviewModal');
            const preview = document.getElementById('imagePreview');
            preview.src = src;
            modal.classList.remove('hidden');
        });
    });

    document.getElementById('closeImagePreview').addEventListener('click', function() {
        document.getElementById('imagePreviewModal').classList.add('hidden');
    });
</script>

  <!-- Edit Modal -->
   
  <div id="editModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-lg w-96 p-6">
      <form method="POST" enctype="multipart/form-data" class="space-y-3">

      <h2 class="text-lg font-bold mb-4">Edit User Profile</h2>
      <form method="POST" class="space-y-3">
        <input type="hidden" name="user_id" id="edit_user_id">
        <div>
          <label class="block">First Name</label>
          <input type="text" name="first_name" id="edit_first_name" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block">Last Name</label>
          <input type="text" name="last_name" id="edit_last_name" class="w-full border p-2 rounded">
        </div>
       <div>
  <label class="block mb-1">Profile Image</label>
  <img id="edit_reference_image_preview" 
       src="" 
       class="w-24 h-24 rounded-full mb-2 object-cover" 
       alt="Profile Image Preview">
  <input type="file" name="reference_image" id="edit_reference_image" accept="image/*" class="w-full border p-2 rounded">
</div>


      <div>
  <label class="block">Phone</label>
  <input 
    type="tel" 
    name="phone" 
    id="edit_phone" 
    class="w-full border p-2 rounded" 
    maxlength="11" 
    placeholder="09XXXXXXXXX" 
    required
  >
</div>

<script>
document.getElementById("edit_phone").addEventListener("input", function() {
  // Remove all non-numeric characters
  this.value = this.value.replace(/\D/g, "");
  // Limit to 11 digits
  if (this.value.length > 11) {
    this.value = this.value.slice(0, 11);
  }
});
</script>

        <div>
          <label class="block">Address</label>
          <textarea name="address" id="edit_address" class="w-full border p-2 rounded"></textarea>
        </div>
        <div>
          <label class="block">Timezone</label>
          <input type="text" name="timezone" id="edit_timezone" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block">Locale</label>
          <input type="text" name="locale" id="edit_locale" class="w-full border p-2 rounded">
        </div>
        <div class="flex justify-end space-x-2 pt-3">
          <button type="button" onclick="closeEditModal()" class="px-3 py-1 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
          <button type="submit" name="update_user" class="px-3 py-1 rounded bg-green-500 text-white hover:bg-green-600">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Modal -->
  <div id="deleteModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-lg w-96 p-6">
      <h2 class="text-lg font-bold mb-4">Are you Sure?</h2>
      <p class="mb-6 text-gray-600">The selected record will be permanently deleted.</p>
      <div class="flex justify-end space-x-3">
        <button onclick="closeDeleteModal()" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
        <a id="confirmDeleteBtn" href="#" class="px-4 py-2 rounded bg-red-600 hover:bg-red-700 text-white">Delete</a>
      </div>
    </div>
  </div>

<script>
function openEditModal(data) {
  document.getElementById("edit_user_id").value = data.user_id;
  document.getElementById("edit_first_name").value = data.first_name ?? "";
  document.getElementById("edit_last_name").value = data.last_name ?? "";
  document.getElementById("edit_phone").value = data.phone ?? "";
  document.getElementById("edit_address").value = data.address ?? "";
  document.getElementById("edit_timezone").value = data.timezone ?? "";
  document.getElementById("edit_locale").value = data.locale ?? "";

  // Update reference image preview
  const preview = document.getElementById("edit_reference_image_preview");
  if (data.reference_image) {
    preview.src = "/public_html/" + data.reference_image; // adjust path if needed
  } else {
    preview.src = "https://via.placeholder.com/150?text=No+Image"; // placeholder
  }
document.getElementById("edit_reference_image").addEventListener("change", function(event) {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById("edit_reference_image_preview").src = e.target.result;
    };
    reader.readAsDataURL(file);
  }
});

  document.getElementById("editModal").classList.remove("hidden");
}


function closeEditModal() {
  document.getElementById("editModal").classList.add("hidden");
}

function openDeleteModal(userId) {
  document.getElementById("confirmDeleteBtn").href = "?delete=" + userId;
  document.getElementById("deleteModal").classList.remove("hidden");
}

function closeDeleteModal() {
  document.getElementById("deleteModal").classList.add("hidden");
}
</script>

</body>
</html>







</body>
</html>

