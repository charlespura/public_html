
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
<?php include '../profile.php'; ?>

        </div>
<!-- Second Header: Submodules -->


<?php 
include 'shiftNavbar.php'; ?>



<div class="bg-white shadow-md rounded-2xl p-10 w-full mx-auto mt-10 mb-10">
    <h2 class="text-2xl font-bold mb-6">Assign Shift to Employee</h2>
<?php
// Include employee DB connection
include __DIR__ . '/../../dbconnection/dbEmployee.php';
$empConn = $conn; // rename the included $conn to $empConn

// Include shift DB connection
include __DIR__ . '/../../dbconnection/dbShift.php';
$shiftConn = $conn; // rename the included $conn to $shiftConn
?>

<?php
include __DIR__ . '/../../dbconnection/dbEmployee.php'; // $empConn
include __DIR__ . '/../../dbconnection/dbShift.php';    // $shiftConn

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'] ?? '';
    $shift_id    = $_POST['shift_id'] ?? '';
    $work_date   = $_POST['work_date'] ?? '';
    $notes       = $_POST['notes'] ?? '';

    if ($employee_id && $shift_id && $work_date) {
        $schedule_id = bin2hex(random_bytes(16)); // unique ID

        $stmt = $shiftConn->prepare("INSERT INTO employee_schedules 
            (schedule_id, employee_id, shift_id, work_date, notes) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                shift_id = VALUES(shift_id),
                notes = VALUES(notes),
                updated_at = NOW()");
        $stmt->bind_param("sssss", $schedule_id, $employee_id, $shift_id, $work_date, $notes);

        if ($stmt->execute()) {
            echo '<div class="bg-green-100 text-green-700 p-3 mb-4 rounded">✅ Shift assigned successfully!</div>';
        } else {
            echo '<div class="bg-red-100 text-red-700 p-3 mb-4 rounded">❌ Error: ' . htmlspecialchars($stmt->error) . '</div>';
        }
    } else {
        echo '<div class="bg-red-100 text-red-700 p-3 mb-4 rounded">⚠️ Please fill in all required fields.</div>';
    }
}

// Fetch employees from Hr3_system
$employees = $empConn->query("SELECT employee_id, CONCAT(first_name, ' ', last_name) AS fullname 
                              FROM employees 
                              ORDER BY first_name, last_name");

// Fetch shifts from Hr3_shiftschedule
$shifts = $shiftConn->query("SELECT shift_id, shift_code, name, start_time, end_time 
                             FROM shifts 
                             ORDER BY start_time");
?>

<form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-6">

   <!-- Add TomSelect CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>

<!-- Employee -->
<div>
    <label class="block mb-1 font-medium">Employee <span class="text-red-500">*</span></label>
    <select id="employee_id" name="employee_id" required class="border rounded w-full p-2">
        <option value="">--  Type  Employee Name --</option>
        <?php while ($e = $employees->fetch_assoc()): ?>
            <option value="<?php echo $e['employee_id']; ?>">
                <?php echo htmlspecialchars($e['fullname']); ?>
            </option>
        <?php endwhile; ?>
    </select>
</div>

<script>
new TomSelect("#employee_id", {
    create: false,
    sortField: {
        field: "text",
        direction: "asc"
    },
    placeholder: "Search employee..."
});
</script>


    <!-- Shift -->
    <div>
        <label class="block mb-1 font-medium">Shift <span class="text-red-500">*</span></label>
        <select name="shift_id" required class="border rounded w-full p-2">
            <option value="">-- Select Shift --</option>
            <?php while ($s = $shifts->fetch_assoc()): ?>
                <option value="<?php echo $s['shift_id']; ?>">
                    <?php echo htmlspecialchars($s['shift_code'] . " - " . $s['name']); ?>
                    (<?php echo substr($s['start_time'],0,5) . " - " . substr($s['end_time'],0,5); ?>)
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- Work Date -->
    <div>
        <label class="block mb-1 font-medium">Work Date <span class="text-red-500">*</span></label>
        <input type="date" name="work_date" required class="border rounded w-full p-2">
    </div>

    <!-- Notes -->
    <div>
        <label class="block mb-1 font-medium">Notes</label>
        <textarea name="notes" rows="2" class="border rounded w-full p-2" placeholder="Optional..."></textarea>
    </div>

    <!-- Submit -->
    <div class="md:col-span-2 flex justify-end">
        <button type="submit" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
            Assign Shift
        </button>
    </div>
</form>
</div>



<!-- Ito yung body ng page kung saan mo ilalagay yung mga content na gusto mong ipakita sa page mo -->
    
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
