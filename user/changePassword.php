<?php
// changePassword.php

// Enable MySQLi exceptions instead of fatal errors
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Include DB connections
include __DIR__ . '/../dbconnection/mainDB.php'; // hr3_maindb
$mainConn = $conn;

$message = '';
$messageType = ''; // success / error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id         = $_POST['user_id'] ?? null; // or get from session if logged-in
    $currentPassword = $_POST['current_password'] ?? null;
    $newPassword     = $_POST['new_password'] ?? null;
    $confirmPassword = $_POST['confirm_password'] ?? null;

    if (!$user_id || !$currentPassword || !$newPassword || !$confirmPassword) {
        $message = "❌ All fields are required.";
        $messageType = "error";
    } elseif ($newPassword !== $confirmPassword) {
        $message = "❌ New password and confirmation do not match.";
        $messageType = "error";
    } else {
        try {
            // Fetch current password hash
            $stmt = $mainConn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $message = "❌ User not found.";
                $messageType = "error";
            } else {
                $row = $result->fetch_assoc();
                $storedHash = $row['password_hash'];

                // Verify old password
                if (!password_verify($currentPassword, $storedHash)) {
                    $message = "❌ Current password is incorrect.";
                    $messageType = "error";
                } else {
                    // Update with new password hash
                    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);

                    $stmt = $mainConn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                    $stmt->bind_param("ss", $newHash, $user_id);
                    $stmt->execute();

                    $message = "✅ Password updated successfully.";
                    $messageType = "success";
                }
            }
        } catch (mysqli_sql_exception $e) {
            $message = "❌ Error: " . $e->getMessage();
            $messageType = "error";
        }
    }
}
?>


<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Change Password  </title>
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
          <h2 class="text-xl font-semibold text-gray-800" id="main-content-title">Change Password  </h2>

  <?php include '../profile.php'; ?>


        </div>

        
<?php 
include 'userNavbar.php'; ?>


        <!-- Page Body -->
        <p class="text-gray-600"></p>
      </main>
     

      
<div class="bg-white shadow-md rounded-2xl p-10 w-full mx-auto mt-10 mb-10">



    <?php if ($message): ?>
        <p style="color: <?= $messageType === 'success' ? 'green' : 'red' ?>;">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="user_id" value="<?= htmlspecialchars($_POST['user_id'] ?? '') ?>">
        <label>Current Password:</label><br>
        <input type="password" name="current_password" required><br><br>

        <label>New Password:</label><br>
        <input type="password" name="new_password" required><br><br>

        <label>Confirm New Password:</label><br>
        <input type="password" name="confirm_password" required><br><br>

        <button type="submit">Change Password</button>
    </form>





</body>
</html>

