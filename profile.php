<?php

include __DIR__ . '/dbconnection/mainDb.php';

// Fetch user data for header (only if logged in)
$fullName = "Guest";
$roleName = "";
$profileImage = "/default-avatar.png"; // fallback

if (!empty($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Get profile
    $stmt = $conn->prepare("
        SELECT u.username, u.reference_image, r.name AS role_name, p.first_name, p.last_name
        FROM users u
        LEFT JOIN user_roles ur ON u.user_id = ur.user_id
        LEFT JOIN roles r ON ur.role_id = r.role_id
        LEFT JOIN user_profiles p ON u.user_id = p.user_id
        WHERE u.user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $userRow = $result->fetch_assoc();

        $fullName = trim(($userRow['first_name'] ?? '') . ' ' . ($userRow['last_name'] ?? ''));
        if ($fullName === "") {
            $fullName = $userRow['username']; // fallback if no first/last name
        }

        $roleName = $userRow['role_name'] ?? 'Employee';

        if (!empty($userRow['reference_image'])) {
            $profileImage = "/" . $userRow['reference_image']; // stored path
        }
    }
}
?>

<!-- Right: User Info -->
<div class="relative flex items-center gap-4">
  <!-- Clock -->
  <span id="clock" class="text-sm text-gray-600 font-mono"></span>

  <button id="userDropdownToggle" class="flex items-center gap-2 focus:outline-none">
    <img src="/public_html/<?php echo htmlspecialchars($profileImage); ?>" 
         alt="profile picture" 
         class="w-8 h-8 rounded-full border object-cover" />
    <div class="flex flex-col items-start">
        <span class="text-sm text-gray-800 font-medium">
            <?php echo htmlspecialchars($fullName); ?>
        </span>
        <span class="text-xs text-gray-500">
            <?php echo htmlspecialchars($roleName); ?>
        </span>
    </div>
    <i data-lucide="chevron-down" class="w-4 h-4 text-gray-600"></i>
  </button>

  <!-- Dropdown -->
  <div id="userDropdown" class="absolute right-0 mt-2 w-40 bg-white rounded shadow-lg hidden z-20">
      <a href="/public_html/user/createUser.php" 
         class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 <?php echo ($currentPage == '/user/createUser.php') ? 'bg-gray-700 text-white' : ''; ?>">
          Profile
      </a>
      <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
      <a href="/public_html/logout.php"
         class="flex items-center gap-3 px-3 py-2 rounded hover:bg-red-600 hover:text-white transition-colors <?php echo ($currentPage == '/logout.php') ? 'bg-red-500 text-white' : 'text-red-500'; ?>">
          <i data-lucide="log-out" class="w-5 h-5"></i>
          <span class="sidebar-text">Logout</span>
      </a>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const userDropdownToggle = document.getElementById("userDropdownToggle");
    const userDropdown = document.getElementById("userDropdown");

    if(userDropdownToggle && userDropdown) {
        userDropdownToggle.addEventListener("click", function (event) {
            event.stopPropagation();
            userDropdown.classList.toggle("hidden");
        });

        // Close dropdown when clicking outside
        document.addEventListener("click", function (event) {
            if (!userDropdown.contains(event.target) && !userDropdownToggle.contains(event.target)) {
                userDropdown.classList.add("hidden");
            }
        });
    }
});
</script>

<script>
function updateClock() {
  const now = new Date();
  let hours = now.getHours();
  let minutes = now.getMinutes();
  let seconds = now.getSeconds();
  
  hours = hours.toString().padStart(2, '0');
  minutes = minutes.toString().padStart(2, '0');
  seconds = seconds.toString().padStart(2, '0');

  document.getElementById('clock').textContent = `${hours}:${minutes}:${seconds}`;
}
setInterval(updateClock, 1000);
updateClock();
</script>
