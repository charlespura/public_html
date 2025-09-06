
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php
include __DIR__ . '/../dbconnection/mainDB.php';

// Get today's date
$today = date("Y-m-d");

// Count employees with shifts today (exclude "off" status)
$stmt = $conn->prepare("SELECT COUNT(DISTINCT employee_id) AS total 
                        FROM employee_schedules 
                        WHERE work_date = ? 
                        AND status = 'scheduled'");
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$todayEmployees = $result['total'] ?? 0;
?>
<?php
include __DIR__ . '/../dbconnection/mainDB.php';

$today = date("Y-m-d");

// Count employees who clocked in today
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT user_id) AS total 
    FROM attendance 
    WHERE DATE(clock_in) = ?
");
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$todayAttendance = $result['total'] ?? 0;
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="icon" type="image/png" href="/public_html/picture/logo2.png" />

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
          <h2 class="text-xl font-semibold text-gray-800" id="main-content-title">HR3 Dashboard</h2>
<?php include '../profile.php'; ?>

        </div>

     
      </main>
<!-- ================== Dashboard Cards Section ================== -->
<div class="px-6 mt-6">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    



<?php
// Assume $roles contains the role of the currently logged-in user
// e.g., $roles = $_SESSION['user_role'];

if (in_array($roles, ['Admin', 'Manager'])): 
?>

<!-- Card: Employees with Shifts Today -->
<div class="bg-white shadow-lg rounded-2xl p-8 h-64 hover:shadow-2xl transition flex flex-col">
  <!-- Header -->
  <div class="flex items-center space-x-3">
    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" 
         viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
    </svg>
    <h2 class="text-lg font-semibold text-gray-700">Employees on Shift Today</h2>
  </div>
  <hr class="my-4">

  <!-- Body -->
  <div class="flex-1 flex items-center justify-center text-gray-600 text-4xl font-bold">
    <?= $todayEmployees ?> Employee<?= $todayEmployees == 1 ? '' : 's' ?>
  </div>
</div>
 
<?php
include __DIR__ . '/../dbconnection/mainDB.php'; // shift/leave DB
$today = date("Y-m-d");

// query employees who are on leave today (and leave is approved)
$sql = "
    SELECT e.first_name, e.last_name, lr.start_date, lr.end_date, lt.leave_name
    FROM leave_requests lr
    JOIN hr3_system.employees e ON lr.employee_id = e.employee_id
    LEFT JOIN leave_types lt ON lr.leave_type_id = lt.leave_type_id
    WHERE lr.status = 'Approved'
      AND '$today' BETWEEN lr.start_date AND lr.end_date
    ORDER BY e.first_name, e.last_name
";

$result = $conn->query($sql);
?>

<!-- Card 4 -->
<div class="bg-white shadow-lg rounded-2xl p-8 h-64 hover:shadow-2xl transition flex flex-col">
  <div class="flex items-center space-x-3">
    <!-- Calendar/Leave Icon -->
    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" stroke-width="2" 
         viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
      <line x1="16" y1="2" x2="16" y2="6"></line>
      <line x1="8" y1="2" x2="8" y2="6"></line>
      <line x1="3" y1="10" x2="21" y2="10"></line>
      <!-- minus sign inside calendar -->
      <line x1="9" y1="16" x2="15" y2="16"></line>
    </svg>

    <h2 class="text-lg font-semibold text-gray-700">Employees on Leave Today</h2>
  </div>
  <hr class="my-4">

  <div class="flex-1 overflow-y-auto">
    <?php if ($result && $result->num_rows > 0): ?>
      <ul class="space-y-2 text-gray-700">
        <?php while ($row = $result->fetch_assoc()): ?>
          <li class="p-2 bg-gray-50 rounded hover:bg-gray-100">
            <span class="font-medium"><?= htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></span>
            <span class="text-sm text-gray-500">
              (<?= htmlspecialchars($row['leave_name'] ?? 'Leave'); ?>: 
              <?= htmlspecialchars($row['start_date']); ?> → <?= htmlspecialchars($row['end_date']); ?>)
            </span>
          </li>
        <?php endwhile; ?>
      </ul>
    <?php else: ?>
      <p class="text-gray-400 text-center">No employees on leave today</p>
    <?php endif; ?>
  </div>
</div>



<!-- Card 5: Attendance Today -->
<div class="bg-white shadow-lg rounded-2xl p-8 h-64 hover:shadow-2xl transition flex flex-col">
  <div class="flex items-center space-x-3">
    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" 
         viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      <path stroke-linecap="round" stroke-linejoin="round" 
            d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4
               4-1.79 4-4-1.79-4-4-4z"></path>
    </svg>
    <h2 class="text-lg font-semibold text-gray-700">Attendance Today</h2>
  </div>
  <hr class="my-4">

  <div class="flex-1 flex items-center justify-center text-gray-600 text-4xl font-bold">
    <?= $todayAttendance ?> Present
  </div>
</div>

        <?php 
else: 
  
endif; 
?>

<!-- Card 3 -->
<div class="bg-white shadow-lg rounded-2xl p-8 h-70 hover:shadow-2xl transition flex flex-col">
  <div class="flex items-center space-x-3">
    <!-- Flash / Quick Icon -->
    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
    </svg>
    <h2 class="text-lg font-semibold text-gray-700">Quick Launch</h2>
  </div>

  <hr class="my-4">

  <!-- Grid Layout for Quick Launch Items -->
  <div class="flex-1 grid grid-cols-3 gap-6 justify-items-center items-center text-gray-400">

  <?php
  // Assume $roles contains the role of the currently logged-in user
  if (in_array($roles, ['Admin', 'Manager'])): 
  ?>

    <!-- Create User -->
    <a href="/public_html/user/createUser.php" class="flex flex-col items-center hover:text-blue-500 transition">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14c-4 0-8 2-8 6h16c0-4-4-6-8-6z" />
      </svg>
      <span class="text-sm font-medium">Create User</span>
    </a>

    <!-- Assign Shift -->
    <a href="/public_html/shift/assignShift.php" class="flex flex-col items-center hover:text-green-500 transition">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M4 15h16M4 19h16" />
      </svg>
      <span class="text-sm font-medium">Assign Shift</span>
    </a>

    <!-- Employees -->
    <a href="/public_html/employee/employee.php" class="flex flex-col items-center hover:text-purple-500 transition">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87M16 7a4 4 0 11-8 0 4 4 0 018 0z" />
      </svg>
      <span class="text-sm font-medium">Employees</span>
    </a>

    <!-- Attendance Logs -->
    <a href="/public_html/timeAndattendance/time.php" class="flex flex-col items-center hover:text-purple-500 transition">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" 
              d="M12 11c0-1.105-.895-2-2-2s-2 .895-2 2 .895 2 2 2 2-.895 2-2zm6 4h-2v-1a6 6 0 10-12 0v1H2v2h20v-2z"/>
      </svg>
      <span class="text-sm font-medium">Attendance Logs</span>
    </a>

  <?php endif; ?>


<?php
// Assume $roles contains the role of the currently logged-in user
// e.g., $roles = $_SESSION['user_role'];

if (in_array($roles, ['Employee'])): 
?>

   <!-- Create User -->
<a href="/public_html/user/createUser.php" class="flex flex-col items-center hover:text-blue-500 transition">
  <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14c-4 0-8 2-8 6h16c0-4-4-6-8-6z" />
  </svg>
  <span class="text-sm font-medium">Profile</span>
</a>

<!-- Assign Shift -->
<a href="/public_html/shift/assignShift.php" class="flex flex-col items-center hover:text-green-500 transition">
  <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M4 15h16M4 19h16" />
  </svg>
  <span class="text-sm font-medium">My Shift</span>
</a>

<!-- Assign Leave -->
<a href="/public_html/leave/assignLeave.php" class="flex flex-col items-center hover:text-red-500 transition">
  <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12A9 9 0 113 12a9 9 0 0118 0z" />
  </svg>
<?php
// Assume $roles contains the role of the currently logged-in user
// e.g., $roles = $_SESSION['user_role']

if (in_array($roles, ['Admin', 'Manager'])): 
?>
    <span class="text-sm font-medium">Assign Shift</span>
<?php 
elseif ($roles === 'Employee'): 
?>
    <span class="text-sm font-medium">My Shift</span>
<?php 
endif; 
?>

</a>

<!-- Claims -->
<a href="/public_html/claims/claims.php" class="flex flex-col items-center hover:text-purple-500 transition">
  <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.105 0-2 .672-2 1.5S10.895 11 12 11s2 .672 2 1.5S13.105 14 12 14m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
  </svg>
  <span class="text-sm font-medium">Claims</span>
</a>


 <?php 
else: 
  
endif; 
?>
  </div>
</div>
  
    <!-- Card 4 -->
    <div class="bg-white shadow-lg rounded-2xl p-8 h-64 hover:shadow-2xl transition flex flex-col">
      <div class="flex items-center space-x-3">
        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" stroke-width="2" 
             viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
        <h2 class="text-lg font-semibold text-gray-700">Pending Requests</h2>
      </div>
      <hr class="my-4">
      <!-- 👉 Add content for Card 4 here -->
      <div class="flex-1 flex items-center justify-center text-gray-400">
        Content area
      </div>
    </div>

   



  </div>
</div>
<!-- ================== End Dashboard Cards Section ================== -->

    
    </div>
  </div>
  
</body>
</html>
