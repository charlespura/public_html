
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
  <link rel="icon" type="image/png" href="/web/picture/logo2.png" />

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

<!-- Second Header: Submodules -->

<div class="bg-white shadow-md rounded-2xl p-10 w-full mx-auto mt-10 mb-10">
    <h2 class="text-2xl font-bold mb-6">Add New Shift</h2>

<?php
// Include shift DB connection
include __DIR__ . '/../../dbconnection/dbShift.php';
$shiftConn = $conn; // Shift DB connection

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shift_id      = bin2hex(random_bytes(16)); // Unique ID
    $shift_code    = $_POST['shift_code'] ?? '';
    $name          = $_POST['name'] ?? '';
    $start_time    = $_POST['start_time'] ?? '';
    $end_time      = $_POST['end_time'] ?? '';
    $break_minutes = $_POST['break_minutes'] ?? 0;
    $is_overnight  = isset($_POST['is_overnight']) ? 1 : 0;

    if ($shift_code && $name && $start_time && $end_time) {
        $stmt = $shiftConn->prepare("INSERT INTO shifts 
            (shift_id, shift_code, name, start_time, end_time, break_minutes, is_overnight) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssi", 
            $shift_id, $shift_code, $name, $start_time, $end_time, $break_minutes, $is_overnight
        );

        if ($stmt->execute()) {
            echo '<div class="bg-green-100 text-green-700 p-3 mb-4 rounded">✅ Shift added successfully!</div>';
        } else {
            echo '<div class="bg-red-100 text-red-700 p-3 mb-4 rounded">❌ Error: ' . htmlspecialchars($stmt->error) . '</div>';
        }
    } else {
        echo '<div class="bg-red-100 text-red-700 p-3 mb-4 rounded">⚠️ Please fill in all required fields.</div>';
    }
}
?>

<form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <!-- Shift Code -->
    <div>
        <label class="block mb-1 font-medium">Shift Code <span class="text-red-500">*</span></label>
        <input type="text" name="shift_code" required class="border rounded w-full p-2">
    </div>

    <!-- Name -->
    <div>
        <label class="block mb-1 font-medium">Shift Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" required class="border rounded w-full p-2">
    </div>

    <!-- Start Time -->
    <div>
        <label class="block mb-1 font-medium">Start Time <span class="text-red-500">*</span></label>
        <input type="time" name="start_time" required class="border rounded w-full p-2">
    </div>

    <!-- End Time -->
    <div>
        <label class="block mb-1 font-medium">End Time <span class="text-red-500">*</span></label>
        <input type="time" name="end_time" required class="border rounded w-full p-2">
    </div>

    <!-- Break Minutes -->
    <div>
        <label class="block mb-1 font-medium">Break Minutes</label>
        <input type="number" name="break_minutes" value="0" min="0" class="border rounded w-full p-2">
    </div>

    <!-- Overnight -->
    <div class="flex items-center">
        <input type="checkbox" name="is_overnight" value="1" class="mr-2">
        <label class="font-medium">Overnight Shift</label>
    </div>

    <!-- Submit -->
    <div class="md:col-span-2 flex justify-end">
        <button type="submit" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
            ➕ Add Shift
        </button>
    </div>
</form>
</div>



    </div>


    
  </div>
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
