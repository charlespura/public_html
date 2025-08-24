

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
<?php include '../profile.php'; ?>

        </div>
<!-- Second Header: Submodules -->


<?php 
include 'userNavbar.php'; ?>





                                  <!-- ADMIN AREA  -->

                


<?php
// Assume $roles contains the role of the currently logged-in user
// e.g., $roles = $_SESSION['user_role'];

if (in_array($roles, ['Admin', 'Manager'])): 
?>

<div class="bg-white shadow-md rounded-2xl p-10 w-full mx-auto mt-10 mb-10">
    <h2 class="text-2xl font-bold mb-6">Create Employee Account</h2>
<?php
// Include DB connections
include __DIR__ . '/../dbconnection/dbEmployee.php'; // hr3_system
$empConn = $conn;

include __DIR__ . '/../dbconnection/mainDB.php'; // hr3_maindb
$mainConn = $conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Get selected employee info
    $employee_id = $_POST['employee_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password']; // plaintext, will hash
    $role_id = $_POST['role_id'];   // admin-selected role

    // 2. Generate new UUID for user
    $user_id = uniqid(); // for simplicity; use a proper UUID generator in production

    // 3. Hash the password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // 4. Insert into hr3_maindb.users
    $stmt = $mainConn->prepare("
        INSERT INTO users (user_id, username, email, password_hash, is_active)
        VALUES (?, ?, ?, ?, 1)
    ");
    $stmt->bind_param("ssss", $user_id, $username, $email, $password_hash);
    $stmt->execute();

    // 5. Insert into user_profiles
    $stmt = $mainConn->prepare("
        INSERT INTO user_profiles (user_id, first_name, last_name)
        SELECT ?, first_name, last_name FROM hr3_system.employees WHERE employee_id = ?
    ");
    $stmt->bind_param("ss", $user_id, $employee_id);
    $stmt->execute();

    // 6. Assign role
    $assigned_by = null; // optional: set to current admin's user_id
    $stmt = $mainConn->prepare("
        INSERT INTO user_roles (user_id, role_id, assigned_by)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("sss", $user_id, $role_id, $assigned_by);
    $stmt->execute();

    // 7. Update employee record with user_id
    $stmt = $empConn->prepare("
        UPDATE employees SET user_id = ? WHERE employee_id = ?
    ");
    $stmt->bind_param("ss", $user_id, $employee_id);
    $stmt->execute();

    echo "User account created successfully for employee!";
}
?>

<div class="bg-white shadow-lg rounded-2xl p-8 w-full max-w-lg">
    <h2 class="text-2xl font-bold mb-6 text-center">Create User Account for Employee</h2>

    <form action="" method="POST" class="space-y-4">

        <!-- Employee Selection -->
        <div>
            <label for="employee_id" class="block text-gray-700 font-semibold mb-1">Select Employee</label>
            <select id="employee_id" name="employee_id" required
                class="w-full border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-400">
                <?php
                // Fetch employees without user accounts
                $res = $empConn->query("SELECT employee_id, first_name, last_name, employee_code FROM employees WHERE user_id IS NULL");
                while($row = $res->fetch_assoc()) {
                    echo "<option value='{$row['employee_id']}'>{$row['employee_code']} - {$row['first_name']} {$row['last_name']}</option>";
                }
                ?>
            </select>
        </div>

        <!-- Username -->
        <div>
            <label for="username" class="block text-gray-700 font-semibold mb-1">Username</label>
            <input type="text" id="username" name="username" required
                class="w-full border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-400" placeholder="Enter username">
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="block text-gray-700 font-semibold mb-1">Email</label>
            <input type="email" id="email" name="email" required
                class="w-full border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-400" placeholder="Enter email">
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-gray-700 font-semibold mb-1">Password</label>
            <input type="password" id="password" name="password" required
                class="w-full border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-400" placeholder="Enter password">
        </div>

        <!-- Role Selection -->
        <div>
            <label for="role_id" class="block text-gray-700 font-semibold mb-1">Assign Role</label>
            <select id="role_id" name="role_id" required
                class="w-full border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-400">
                <?php
                // Fetch roles from hr3_maindb
                $rolesRes = $mainConn->query("SELECT role_id, name FROM roles");
                while($role = $rolesRes->fetch_assoc()) {
                    echo "<option value='{$role['role_id']}'>{$role['name']}</option>";
                }
                ?>
            </select>
        </div>

        <!-- Submit Button -->
        <div class="text-center mt-4">
            <button type="submit"
                class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition">
                Create Account
            </button>
        </div>

    </form>
</div>


<?php 
else: 
  
endif; 
?>







                                        <!-- EMPLOYEE AREA  -->






 <?php
// Assume session stores the logged-in user's ID and role
include __DIR__ . '/../dbconnection/mainDB.php'; // hr3_maindb
$mainConn = $conn;

$user_id = $_SESSION['user_id'];
$roles = $_SESSION['roles']; // 'Admin', 'Manager', or 'Employee'

// Connect to mainDB
include __DIR__ . '/../dbconnection/mainDB.php';

// Only allow employees to view this page
if ($roles !== 'Employee') {
  
    exit;
}

// Fetch employee profile using their user_id
$stmt = $mainConn->prepare("
    SELECT u.username, u.email, up.first_name, up.last_name
    FROM users u
    JOIN user_profiles up ON u.user_id = up.user_id
    WHERE u.user_id = ?
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();
?>

<div class="bg-white shadow-md rounded-2xl p-10 w-full mx-auto mt-10 mb-10 max-w-lg">
    <h2 class="text-2xl font-bold mb-6 text-center">My Account</h2>

    <?php if ($employee): ?>
    <div class="space-y-4">
        <div>
            <label class="block text-gray-700 font-semibold mb-1">Username</label>
            <p class="p-2 border rounded bg-gray-50"><?php echo htmlspecialchars($employee['username']); ?></p>
        </div>
        <div>
            <label class="block text-gray-700 font-semibold mb-1">Email</label>
            <p class="p-2 border rounded bg-gray-50"><?php echo htmlspecialchars($employee['email']); ?></p>
        </div>
        <div>
            <label class="block text-gray-700 font-semibold mb-1">First Name</label>
            <p class="p-2 border rounded bg-gray-50"><?php echo htmlspecialchars($employee['first_name']); ?></p>
        </div>
        <div>
            <label class="block text-gray-700 font-semibold mb-1">Last Name</label>
            <p class="p-2 border rounded bg-gray-50"><?php echo htmlspecialchars($employee['last_name']); ?></p>
        </div>
    </div>
    <?php else: ?>
        <p class="text-center text-red-500">Your account information could not be found.</p>
    <?php endif; ?>
</div>






</body>
</html>

