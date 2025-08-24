
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
          <h2 class="text-xl font-semibold text-gray-800" id="main-content-title">Shift and Schedule</h2>


          <!-- ito yung profile ng may login wag kalimutan lagyan ng session yung profile.php para madetect nya if may login or wala -->
<?php 
include '../profile.php'; 
?>

        </div>

<?php 
include 'shiftnavbar.php';
 ?>


<div class="bg-white shadow-md rounded-2xl p-10 w-full mx-auto mt-10 mb-10">
    <h2 class="text-2xl font-bold mb-6">Request a Shift</h2>

<?php
// Include DB connections
include __DIR__ . '/../dbconnection/dbEmployee.php';
$empConn = $conn; // Employee DB
include __DIR__ . '/../dbconnection/mainDB.php';
$shiftConn = $conn; // Shift DB

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id   = bin2hex(random_bytes(16));
    $employee_id  = $_POST['employee_id'] ?? '';
    $shift_id     = $_POST['shift_id'] ?? null;
    $request_date = $_POST['request_date'] ?? '';
    $request_type = $_POST['request_type'] ?? '';
    $notes        = $_POST['notes'] ?? '';

    if ($employee_id && $request_date && $request_type) {

        // Lookup request_type_id
        $typeStmt = $shiftConn->prepare("SELECT type_id FROM request_types WHERE type_name = ?");
        $typeStmt->bind_param("s", $request_type);
        $typeStmt->execute();
        $typeResult = $typeStmt->get_result();
        $typeRow = $typeResult->fetch_assoc();
        $request_type_id = $typeRow['type_id'] ?? null;

        if (!$request_type_id) {
            die("Invalid request type selected.");
        }

        // Lookup status_id for "pending"
        $statusStmt = $shiftConn->prepare("SELECT status_id FROM request_statuses WHERE status_name = 'pending'");
        $statusStmt->execute();
        $statusResult = $statusStmt->get_result();
        $statusRow = $statusResult->fetch_assoc();
        $status_id = $statusRow['status_id'] ?? null;

        if (!$status_id) {
            die("Pending status not found.");
        }

        // Force shift_id to NULL if request type is day_off or shift not selected
        if ($request_type === "day_off" || empty($shift_id)) {
            $shift_id = null;
        }

        // Insert request
        $stmt = $shiftConn->prepare("
            INSERT INTO employee_shift_requests 
            (request_id, employee_id, shift_id, request_date, request_type_id, status_id, notes, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->bind_param(
            "sssssss",
            $request_id,
            $employee_id,
            $shift_id,
            $request_date,
            $request_type_id,
            $status_id,
            $notes
        );

        if ($stmt->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
            exit();
        } else {
            header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode($stmt->error));
            exit();
        }
    } else {
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode("Please fill in all required fields."));
        exit();
    }
}

// Fetch employees for dropdown
$employees = $empConn->query("
    SELECT employee_id, CONCAT(first_name, ' ', last_name) AS fullname 
    FROM employees 
    ORDER BY first_name, last_name
");

// Fetch shifts for dropdown
$shifts = $shiftConn->query("
    SELECT shift_id, shift_code, name, start_time, end_time 
    FROM shifts 
    ORDER BY start_time
");

// Fetch existing requests for listing
$requests = $shiftConn->query("
    SELECT r.request_id, r.employee_id, r.shift_id, r.request_date, r.notes, r.created_at,
           rt.type_name AS request_type, rs.status_name AS status,
           s.shift_code, s.name AS shift_name, s.start_time, s.end_time
    FROM employee_shift_requests r
    LEFT JOIN shifts s ON r.shift_id = s.shift_id
    LEFT JOIN request_types rt ON r.request_type_id = rt.type_id
    LEFT JOIN request_statuses rs ON r.status_id = rs.status_id
    ORDER BY r.created_at DESC
");
?>

<!-- Messages -->
<?php if (isset($_GET['success'])): ?>
<div class="bg-green-100 text-green-700 p-3 mb-4 rounded">
    ✅ Request submitted successfully!
</div>
<?php elseif (isset($_GET['error'])): ?>
<div class="bg-red-100 text-red-700 p-3 mb-4 rounded">
    ❌ Error: <?php echo htmlspecialchars($_GET['error']); ?>
</div>
<?php endif; ?>

<script>
if (window.location.search.includes("success") || window.location.search.includes("error")) {
    const newUrl = window.location.origin + window.location.pathname;
    window.history.replaceState({}, document.title, newUrl);
}
</script>

<!-- Request Form -->
<form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-6">
  <!-- Employee -->
  <div>
      <label class="block mb-1 font-medium">Employee *</label>
      <select name="employee_id" id="employee_id" required class="border rounded w-full p-2">
          <option value="">-- Select Employee --</option>
          <?php while ($e = $employees->fetch_assoc()): ?>
              <option value="<?= $e['employee_id'] ?>"><?= htmlspecialchars($e['fullname']) ?></option>
          <?php endwhile; ?>
      </select>
  </div>

  <!-- Request Date -->
  <div>
      <label class="block mb-1 font-medium">Request Date *</label>
      <input type="date" name="request_date" required class="border rounded w-full p-2">
  </div>

  <?php
// Fetch request types dynamically
$requestTypes = $shiftConn->query("SELECT type_name FROM request_types WHERE is_active = 1 ORDER BY type_name");
?>
<div>
    <label class="block mb-1 font-medium">Request Type *</label>
    <select name="request_type" id="request_type" required class="border rounded w-full p-2" onchange="toggleShiftSelect()">
        <option value="">-- Select Type --</option>
        <?php while ($rt = $requestTypes->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($rt['type_name']) ?>">
                <?= ucwords(str_replace('_', ' ', htmlspecialchars($rt['type_name']))) ?>
            </option>
        <?php endwhile; ?>
    </select>
</div>

  <!-- Shift -->
  <div id="shift_select_wrapper">
      <label class="block mb-1 font-medium">Shift</label>
      <select name="shift_id" class="border rounded w-full p-2">
          <option value="">-- Select Shift (if applicable) --</option>
          <?php while ($s = $shifts->fetch_assoc()): ?>
              <option value="<?= $s['shift_id'] ?>">
                  <?= htmlspecialchars($s['shift_code'] . " - " . $s['name']) ?>
                  (<?= substr($s['start_time'],0,5) ?> - <?= substr($s['end_time'],0,5) ?>)
              </option>
          <?php endwhile; ?>
      </select>
  </div>

  <!-- Notes -->
  <div class="md:col-span-2">
      <label class="block mb-1 font-medium">Notes</label>
      <textarea name="notes" rows="2" class="border rounded w-full p-2"></textarea>
  </div>

  <!-- Submit -->
  <div class="md:col-span-2 flex justify-end">
      <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
          📌 Submit Request
      </button>
  </div>
</form>

<script>
function toggleShiftSelect() {
    const type = document.getElementById('request_type').value;
    const wrapper = document.getElementById('shift_select_wrapper');
    wrapper.style.display = (type === 'day_off' || type === '') ? 'none' : 'block';
}
toggleShiftSelect();
</script>


<?php
// Build employee lookup from employee DB
$employeeLookup = [];
$empResult = $empConn->query("SELECT employee_id, CONCAT(first_name, ' ', last_name) AS fullname FROM employees");
while ($emp = $empResult->fetch_assoc()) {
    $employeeLookup[$emp['employee_id']] = $emp['fullname'];


}$requests = $shiftConn->query("
    SELECT 
        r.request_id, 
        r.employee_id, 
        r.shift_id, 
        r.request_date, 
        r.notes, 
        r.created_at,
        rt.type_name AS request_type,
        rs.status_name AS status,
        s.shift_code, 
        s.name AS shift_name, 
        s.start_time, 
        s.end_time
    FROM employee_shift_requests r
    LEFT JOIN shifts s ON r.shift_id = s.shift_id
    LEFT JOIN request_types rt ON r.request_type_id = rt.type_id
    LEFT JOIN request_statuses rs ON r.status_id = rs.status_id
    ORDER BY r.created_at DESC
");


?>
<!-- Requests Table -->
<div class="bg-white shadow-md rounded-2xl p-6 w-full mx-auto mt-10">
    <h2 class="text-xl font-bold mb-4">Submitted Requests</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-200 rounded-lg">
            <thead>
                <tr class="bg-gray-100 text-left">
                    <th class="px-4 py-2 border">Employee</th>
                    <th class="px-4 py-2 border">Request Date</th>
                    <th class="px-4 py-2 border">Type</th>
                    <th class="px-4 py-2 border">Shift</th>
                    <th class="px-4 py-2 border">Notes</th>
                    <th class="px-4 py-2 border">Status</th>
                    <th class="px-4 py-2 border">Submitted</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($requests->num_rows > 0): ?>
                    <?php while ($row = $requests->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <!-- Employee Name (lookup from $employeeLookup) -->
                            <td class="px-4 py-2 border">
                                <?php 
                                    $empName = $employeeLookup[$row['employee_id']] ?? "Unknown";
                                    echo htmlspecialchars($empName);
                                ?>
                            </td>

                            <!-- Request Date -->
                            <td class="px-4 py-2 border">
                                <?php echo htmlspecialchars($row['request_date']); ?>
                            </td>

                            <!-- Request Type -->
                            <td class="px-4 py-2 border capitalize">
                                <?php echo htmlspecialchars($row['request_type']); ?>
                            </td>

                            <!-- Shift -->
                            <td class="px-4 py-2 border">
                                <?php 
                                    if ($row['shift_id']) {
                                        echo htmlspecialchars($row['shift_code'] . " - " . $row['shift_name']) 
                                            . " (" . substr($row['start_time'],0,5) 
                                            . " - " . substr($row['end_time'],0,5) . ")";
                                    } else {
                                        echo "-";
                                    }
                                ?>
                            </td>

                            <!-- Notes -->
                            <td class="px-4 py-2 border">
                                <?php echo htmlspecialchars($row['notes'] ?? ""); ?>
                            </td>

                            <!-- Status -->
                            <td class="px-4 py-2 border">
                                <?php if ($row['status'] === "pending"): ?>
                                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-sm">Pending</span>
                                <?php elseif ($row['status'] === "approved"): ?>
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">Approved</span>
                                <?php elseif ($row['status'] === "rejected"): ?>
                                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-sm">Rejected</span>
                                <?php else: ?>
                                    <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-sm">Unknown</span>
                                <?php endif; ?>
                            </td>

                            <!-- Submitted Date -->
                            <td class="px-4 py-2 border text-sm text-gray-600">
                                <?php echo htmlspecialchars($row['created_at']); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-gray-500">No requests submitted yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleShiftSelect() {
    const type = document.getElementById("request_type").value;
    const shiftWrapper = document.getElementById("shift_select_wrapper");
    if (type === "day_off") {
        shiftWrapper.style.display = "none";
    } else {
        shiftWrapper.style.display = "block";
    }
}
// Initialize on page load
toggleShiftSelect();
</script>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const userDropdownToggle = document.getElementById("userDropdownToggle");
      const userDropdown = document.getElementById("userDropdown");

      userDropdownToggle.addEventListener("click", function () {
        userDropdown.classList.toggle("hidden");
      });

      // Close dropdown when clicking outside
      document.addEventListener("click", function (event) {
        if (!userDropdown.contains(event.target) && !userDropdownToggle.contains(event.target)) {
          userDropdown.classList.add("hidden");
        }
      });
    });
  </script>
</body>
</html>
