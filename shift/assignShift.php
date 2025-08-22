
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
include 'shiftnavbar.php'; 

// Include DB connections
include __DIR__ . '/../dbconnection/dbEmployee.php';
$empConn = $conn;
include __DIR__ . '/../dbconnection/dbShift.php';
$shiftConn = $conn;

// Fetch employees
$employees = $empConn->query("SELECT employee_id, CONCAT(first_name,' ',last_name) AS fullname 
                              FROM employees ORDER BY first_name");

// Fetch shifts
$shifts = $shiftConn->query("SELECT shift_id, shift_code, name, start_time, end_time 
                             FROM shifts ORDER BY start_time");

// Fetch existing schedules for the week
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end   = date('Y-m-d', strtotime('sunday this week'));

$schedules = [];
$res = $shiftConn->query("SELECT employee_id, shift_id, work_date 
                          FROM employee_schedules 
                          WHERE work_date BETWEEN '$week_start' AND '$week_end'");
while($row = $res->fetch_assoc()) {
    $schedules[$row['employee_id']][$row['work_date']] = $row['shift_id'];
}
?>
<div class="bg-white shadow-md rounded-2xl p-4 sm:p-6 w-full mx-auto mt-6 mb-10 overflow-x-auto">
    <h2 class="text-2xl font-bold mb-4 sm:mb-6">Weekly Shift Scheduler</h2>

    <!-- Responsive scrollable grid -->
    <div class="min-w-max">
        <div class="grid grid-cols-1 sm:grid-cols-8 gap-2 items-center">
            <!-- Header: Employee + Days -->
            <div class="font-bold p-2 bg-gray-100 border">Employee</div>
            <?php
            $days = [];
            for($i=0;$i<7;$i++){
                $day = date('D M d', strtotime("{$week_start} +{$i} days"));
                $days[] = date('Y-m-d', strtotime("{$week_start} +{$i} days"));
                echo "<div class='font-bold text-center p-2 bg-gray-100 border'>$day</div>";
            }
            ?>

            <!-- Employee rows -->
            <?php while($emp = $employees->fetch_assoc()): ?>
                <div class="font-medium p-2 border bg-gray-50"><?php echo htmlspecialchars($emp['fullname']); ?></div>
                <?php foreach($days as $day): ?>
                    <div class="border rounded p-1 min-h-[50px] bg-gray-50 flex flex-col items-center justify-center"
                         data-employee="<?php echo $emp['employee_id']; ?>"
                         data-date="<?php echo $day; ?>"
                         ondragover="allowDrop(event)" ondrop="dropShift(event)">
                        <?php 
                        $shiftId = $schedules[$emp['employee_id']][$day] ?? null;

                        if ($shiftId) {
                            $shift = $shiftConn->query("SELECT name, shift_code FROM shifts WHERE shift_id='$shiftId'")->fetch_assoc();
                            echo "<div class='bg-blue-400 text-white px-2 py-1 rounded mb-1 cursor-move' draggable='true' ondragstart='dragShift(event)' data-shiftid='$shiftId'>{$shift['shift_code']} - {$shift['name']}</div>";
                        } else {
                            echo "<div class='bg-gray-400 text-white px-2 py-1 rounded mb-1 cursor-default'>Off</div>";
                        }
                        ?>
                    </div>
                <?php endforeach; ?>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Available Shifts -->
    <h3 class="mt-6 font-bold">Available Shifts (Drag to Assign)</h3>
    <div class="flex gap-2 mt-2 overflow-x-auto py-2">
        <?php
        $shifts->data_seek(0);
        while($s = $shifts->fetch_assoc()): ?>
            <div class="bg-green-500 text-white px-3 py-1 rounded cursor-move whitespace-nowrap" 
                 draggable="true" 
                 ondragstart="dragShift(event)"
                 data-shiftid="<?php echo $s['shift_id']; ?>">
                <?php echo $s['shift_code'] . " - " . $s['name']; ?>
            </div>
        <?php endwhile; ?>

        <!-- Remove Shift -->
        <div class="bg-red-500 text-white px-3 py-1 rounded cursor-move whitespace-nowrap"
             draggable="true"
             ondragstart="dragShift(event)"
             data-shiftid="REMOVE">
            Remove Shift
        </div>
    </div>
</div>

<script>
let draggedShiftId = null;
let draggedShiftName = '';

function dragShift(ev) {
    draggedShiftId = ev.target.dataset.shiftid;
    draggedShiftName = ev.target.innerText;
}

function allowDrop(ev) {
    ev.preventDefault();
}

function dropShift(ev) {
    ev.preventDefault();
    const employeeId = ev.currentTarget.dataset.employee;
    const workDate = ev.currentTarget.dataset.date;
    if (!employeeId || !workDate) return;

    let shiftIdToSend = draggedShiftId;
    let displayText = draggedShiftName;

    const cell = ev.currentTarget;
    const currentShiftDiv = cell.querySelector('div');

    if (!currentShiftDiv) return; // nothing to remove

    if (draggedShiftId === 'REMOVE') {
        // Only remove if not already 'Off'
        if (currentShiftDiv.dataset.shiftid) {
            shiftIdToSend = 'REMOVE';
            
            // Clear cell and add Off placeholder
            cell.innerHTML = '';
            const offDiv = document.createElement('div');
            offDiv.className = 'bg-gray-400 text-white px-2 py-1 rounded mb-1 cursor-default';
            offDiv.innerText = 'Off';
            cell.appendChild(offDiv);

        } else {
            // If cell is already Off, do nothing
            return;
        }
    } else {
        // Normal shift → replace or add
        cell.innerHTML = '';
        const div = document.createElement('div');
        div.className = 'bg-blue-400 text-white px-2 py-1 rounded mb-1 cursor-move';
        div.draggable = true;
        div.dataset.shiftid = draggedShiftId;
        div.innerText = displayText;
        div.ondragstart = dragShift;
        cell.appendChild(div);
    }

    // AJAX
    const formData = new FormData();
    formData.append('employee_id', employeeId);
    formData.append('shift_id', shiftIdToSend === null ? '' : shiftIdToSend);
    formData.append('work_date', workDate);

    fetch('assign_shift_ajax.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert('Error updating shift: ' + data.error);
            }
        });
}

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
