
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
<style>
    .selected-shift {
        outline: 2px solid black;
        outline-offset: 2px;
    }
</style>

<div class="bg-white shadow-md rounded-xl p-4 w-full mx-auto mt-6 mb-8">
    <h2 class="text-xl font-bold mb-4">Weekly Shift Scheduler</h2>

    <div class="grid grid-cols-8 gap-1 text-sm">
        <div class="font-bold p-1">Employee</div>
        <?php
        $days = [];
        for($i=0;$i<7;$i++){
            $day = date('D M d', strtotime("{$week_start} +{$i} days"));
            $days[] = date('Y-m-d', strtotime("{$week_start} +{$i} days"));
            echo "<div class='font-bold text-center p-1'>$day</div>";
        }
        ?>

        <?php while($emp = $employees->fetch_assoc()): ?>
            <div class="font-medium py-1"><?php echo htmlspecialchars($emp['fullname']); ?></div>
            <?php foreach($days as $day): ?>
                <div class="border rounded p-1 min-h-[40px] bg-gray-50 flex flex-col items-center justify-center schedule-cell"
                     data-employee="<?php echo $emp['employee_id']; ?>"
                     data-date="<?php echo $day; ?>"
                     ondragover="allowDrop(event)" 
                     ondrop="dropShift(event)"
                     onclick="clickAssign(this)">
                    <?php 
                    $shiftId = $schedules[$emp['employee_id']][$day] ?? null;

                    if ($shiftId) {
                        $shift = $shiftConn->query("SELECT name, shift_code FROM shifts WHERE shift_id='$shiftId'")->fetch_assoc();
                        echo "<div class='bg-blue-400 text-white px-1.5 py-0.5 rounded cursor-move text-xs' draggable='true' ondragstart='dragShift(event)' data-shiftid='$shiftId'>{$shift['shift_code']} - {$shift['name']}</div>";
                    } else {
                        echo "<div class='bg-gray-400 text-white px-1.5 py-0.5 rounded cursor-default text-xs'>Off</div>";
                    }
                    ?>
                </div>
            <?php endforeach; ?>
        <?php endwhile; ?>
    </div>

    <h3 class="mt-4 font-bold text-sm">Available Shifts (Click or Drag to Assign)</h3>
    <div class="flex flex-wrap gap-2 mt-2">
        <?php
        $shifts->data_seek(0);
        while($s = $shifts->fetch_assoc()): ?>
            <div class="bg-green-500 text-white px-2 py-0.5 rounded cursor-pointer text-xs available-shift" 
                 draggable="true" 
                 ondragstart="dragShift(event)"
                 onclick="selectShift(this)"
                 data-shiftid="<?php echo $s['shift_id']; ?>">
                <?php echo $s['shift_code'] . " - " . $s['name']; ?>
            </div>
        <?php endwhile; ?>

        <!-- Remove Shift -->
        <div class="bg-red-500 text-white px-2 py-0.5 rounded cursor-pointer text-xs available-shift"
             draggable="true"
             ondragstart="dragShift(event)"
             onclick="selectShift(this)"
             data-shiftid="REMOVE">
            Remove Shift
        </div>
    </div>
</div>

<script>
let draggedShiftId = null;
let draggedShiftName = '';
let selectedShiftId = null;
let selectedShiftName = null;
let lastSelectedEl = null;

function dragShift(ev) {
    draggedShiftId = ev.target.dataset.shiftid;
    draggedShiftName = ev.target.innerText;
}

function allowDrop(ev) {
    ev.preventDefault();
}

function dropShift(ev) {
    ev.preventDefault();
    handleAssign(ev.currentTarget, draggedShiftId, draggedShiftName);
}

/* ------- CLICK TO SELECT SHIFT ------- */
function selectShift(el) {
    // Reset previous selection
    if (lastSelectedEl) lastSelectedEl.classList.remove("selected-shift");

    selectedShiftId = el.dataset.shiftid;
    selectedShiftName = el.innerText;
    lastSelectedEl = el;

    // Highlight selected
    el.classList.add("selected-shift");
}

/* ------- CLICK TO ASSIGN TO CELL ------- */
function clickAssign(cell) {
    if (!selectedShiftId) return; // nothing selected

    handleAssign(cell, selectedShiftId, selectedShiftName);

    // Optional: clear selection after assignment
    if (lastSelectedEl) lastSelectedEl.classList.remove("selected-shift");
    selectedShiftId = null;
    selectedShiftName = null;
    lastSelectedEl = null;
}

/* ------- COMMON ASSIGN HANDLER ------- */
function handleAssign(cell, shiftId, shiftName) {
    const employeeId = cell.dataset.employee;
    const workDate = cell.dataset.date;
    if (!employeeId || !workDate) return;

    let shiftIdToSend = shiftId;

    if (shiftId === 'REMOVE') {
        cell.innerHTML = '';
        const offDiv = document.createElement('div');
        offDiv.className = 'bg-gray-400 text-white px-1.5 py-0.5 rounded text-xs cursor-default';
        offDiv.innerText = 'Off';
        cell.appendChild(offDiv);
    } else {
        cell.innerHTML = '';
        const div = document.createElement('div');
        div.className = 'bg-blue-400 text-white px-1.5 py-0.5 rounded cursor-move text-xs';
        div.draggable = true;
        div.dataset.shiftid = shiftId;
        div.innerText = shiftName;
        div.ondragstart = dragShift;
        cell.appendChild(div);
    }

    // AJAX update
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
