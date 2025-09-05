


<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Shift and Schedule</title>
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
include 'shiftnavbar.php'; ?>

<div class="bg-white shadow-md rounded-2xl p-10 w-full mx-auto mt-10 mb-10">
    <!-- <h2 class="text-2xl font-bold mb-6">Assign Shift Schedule</h2> -->



<?php
// Assume $roles contains the role of the currently logged-in user
// e.g., $roles = $_SESSION['user_role'];

if (in_array($roles, ['Admin', 'Manager'])): 
?>



<?php 

error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB Connections
include __DIR__ . '/../dbconnection/dbEmployee.php';
$empConn = $conn;
include __DIR__ . '/../dbconnection/mainDB.php';
$shiftConn = $conn;

// Step 1: Departments
$departments = $empConn->query("SELECT department_id, name FROM departments ORDER BY name");

// Filters
$selected_department = $_GET['department'] ?? '';
$selected_role = $_GET['role'] ?? '';
$view = $_GET['view'] ?? 'week';
$week_start_input = $_GET['week_start'] ?? '';

// Step 2: Roles by Department
$roles = [];
if ($selected_department) {
    $roles = $empConn->query("
        SELECT DISTINCT p.position_id, p.title
        FROM positions p
        INNER JOIN employees e ON e.position_id = p.position_id
        WHERE e.department_id = '{$selected_department}'
        ORDER BY p.title
    ");
}

// Step 3: Employees by Role
$employees = [];
if ($selected_role) {
    $employees = $empConn->query("
        SELECT employee_id, CONCAT(first_name,' ',last_name) AS fullname 
        FROM employees 
        WHERE position_id = '{$selected_role}'
        ORDER BY first_name
    ");
}

// Step 4: Shifts
$shiftsArray = [];
$shiftsResult = $shiftConn->query("SELECT shift_id, shift_code, start_time, end_time FROM shifts ORDER BY start_time");
while($row = $shiftsResult->fetch_assoc()){
    $shiftsArray[$row['shift_id']] = $row;
}

// Step 5: Determine days to show
if ($view === 'month') {
    $year  = $_GET['year'] ?? date('Y');
    $month = $_GET['month'] ?? date('m');
    $month_start = date('Y-m-01', strtotime("$year-$month-01"));
    $month_end   = date('Y-m-t', strtotime($month_start));
    $days = [];
    $period = new DatePeriod(new DateTime($month_start), new DateInterval('P1D'), (new DateTime($month_end))->modify('+1 day'));
    foreach ($period as $dt) { $days[] = $dt->format('Y-m-d'); }
} else {
    // Weekly view
    if ($week_start_input) {
        $week_start = date('Y-m-d', strtotime($week_start_input));
    } else {
        $week_start = date('Y-m-d', strtotime('monday this week'));
    }
    $week_end = date('Y-m-d', strtotime("$week_start +6 days"));

    $days = [];
    for($i=0;$i<7;$i++){ 
        $days[] = date('Y-m-d', strtotime("$week_start +$i days")); 
    }
}

// Step 6: Existing schedules and notes
$schedules = [];
$notes = [];
if ($selected_role && !empty($days)) {
    $dayStart = $days[0];
    $dayEnd   = end($days);
    $res = $shiftConn->query("
        SELECT employee_id, shift_id, work_date, notes 
        FROM employee_schedules 
        WHERE work_date BETWEEN '$dayStart' AND '$dayEnd'
    ");
    while($row = $res->fetch_assoc()){
        $schedules[$row['employee_id']][$row['work_date']] = $row['shift_id'];
        $notes[$row['employee_id']][$row['work_date']] = $row['notes'];
    }
}

?>

<style>
table { border-collapse: collapse; width: 100%; }
th, td { border:1px solid #ddd; padding:8px; text-align:center; }
th { background:#f4f4f4; }
select { padding:5px; width:100%; }
.note-icon { cursor:pointer; color: #007bff; font-weight:bold; margin-left:5px; }
.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; transition: all 0.3s ease; }
.modal-content { background:#fff; padding:20px; border-radius:5px; width:300px; transform: translateY(-50px); transition: all 0.3s ease; }
.modal.show { display:flex; }
.modal.show .modal-content { transform: translateY(0); }
</style>

<h2 class="text-2xl font-bold mb-6">Role-Based Shift Scheduling</h2>
<?php if($selected_role && $employees->num_rows > 0): ?>
    <form method="GET" action="pdfreport.php" target="">
        <input type="hidden" name="department" value="<?= $selected_department ?>">
        <input type="hidden" name="role" value="<?= $selected_role ?>">
        <input type="hidden" name="week_start" value="<?= $week_start_input ?: date('Y-m-d', strtotime('monday this week')) ?>">
        <button type="submit"
            class="mt-4 px-4 py-2 bg-green-600 text-white rounded-lg shadow hover:bg-green-700 transition">
            View PDF Report
        </button>
    </form>
<?php endif; ?>

<!-- Department -->
<form method="GET" class="mb-4">
  <label class="block text-sm font-medium text-gray-700 mb-1">Department:</label>
  <select name="department" onchange="this.form.submit()"
    class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
    <option value="">--Select Department--</option>
    <?php while($d=$departments->fetch_assoc()): ?>
      <option value="<?= $d['department_id'] ?>" <?= $selected_department==$d['department_id']?'selected':'' ?>>
        <?= htmlspecialchars($d['name']) ?>
      </option>
    <?php endwhile; ?>
  </select>
</form>

<!-- Role -->
<?php if ($selected_department): ?>
<form method="GET" class="mb-4">
  <input type="hidden" name="department" value="<?= $selected_department ?>">
  <label class="block text-sm font-medium text-gray-700 mb-1">Position:</label>
  <select name="role" onchange="this.form.submit()"
    class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
    <option value="">--Select Position--</option>
    <?php while($r=$roles->fetch_assoc()): ?>
      <option value="<?= $r['position_id'] ?>" <?= $selected_role==$r['position_id']?'selected':'' ?>>
        <?= htmlspecialchars($r['title']) ?>
      </option>
    <?php endwhile; ?>
  </select>
</form>
<?php endif; ?>

<!-- Weekly view picker -->
<form method="GET" class="mb-6 flex flex-col sm:flex-row gap-3 items-start sm:items-end">
  <input type="hidden" name="department" value="<?= $selected_department ?>">
  <input type="hidden" name="role" value="<?= $selected_role ?>">
  <div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Week Start:</label>
    <input type="date" name="week_start"
      value="<?= $week_start_input ?: date('Y-m-d', strtotime('monday this week')) ?>"
      class="p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
  </div>
  <input type="hidden" name="view" value="week">
  <button type="submit"
    class="bg-gray-800 hover:bg-gray-900 text-white hover:text-yellow-500 px-4 py-2 rounded w-full sm:w-auto">
    Show Week
  </button>
</form>
<?php
// Step: Fetch approved leaves for selected days (from $shiftConn)
$leaves = [];
if (!empty($days)) {
    $dayStart = $days[0];
    $dayEnd   = end($days);
    $resLeaves = $shiftConn->query("
        SELECT employee_id, start_date, end_date, status 
        FROM leave_requests 
        WHERE status = 'Approved'
          AND (
              (start_date BETWEEN '$dayStart' AND '$dayEnd') 
              OR (end_date BETWEEN '$dayStart' AND '$dayEnd') 
              OR ('$dayStart' BETWEEN start_date AND end_date)
          )
    ");
    while ($row = $resLeaves->fetch_assoc()) {
        $empId = $row['employee_id'];
        $start = strtotime($row['start_date']);
        $end   = strtotime($row['end_date']);
        while ($start <= $end) {
            $leaves[$empId][date('Y-m-d', $start)] = true;
            $start = strtotime('+1 day', $start);
        }
    }
}
?>

<!-- Zoom Button -->
<?php if($selected_role && $employees->num_rows>0): ?>
<div class="flex justify-between items-center mb-2">
  <h2 class="font-semibold text-lg">Schedule</h2>

  <!-- Button with Zoomable SVG + Tooltip -->
  <button onclick="openZoom()" class="bg-blue-500 hover:bg-blue-600 text-white p-2 rounded flex items-center justify-center">
    
    <!-- Zoomable SVG with tooltip -->
    <div class="relative group">
      <svg id="zoom" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6 cursor-pointer">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
      </svg>

      <!-- Tooltip -->
      <span class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 w-max bg-gray-700 text-white text-xs rounded px-2 py-1 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
        Toggle Fullscreen
      </span>
    </div>

  </button>
</div>



<!-- Schedule Table -->
<div id="scheduleTable" class="bg-white shadow rounded-lg overflow-hidden">
  <table class="table-fixed w-full border-collapse text-sm">
    <thead>
      <tr class="bg-gray-100">
        <th class="px-2 py-2 text-left font-semibold w-32">Employee</th>
        <?php foreach($days as $day): ?>
          <th class="px-2 py-2 text-center font-semibold w-[12%]">
            <?= date('D', strtotime($day)) ?><br>
            <span class="text-xs text-gray-500"><?= date('m/d', strtotime($day)) ?></span>
          </th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody class="divide-y">
      <?php while($emp=$employees->fetch_assoc()): ?>
        <tr>
          <td class="px-2 py-2 font-medium whitespace-nowrap"><?= htmlspecialchars($emp['fullname']) ?></td>
          <?php foreach($days as $day): 
            $shift_id = $schedules[$emp['employee_id']][$day] ?? '';
            $note_text = $notes[$emp['employee_id']][$day] ?? '';
            $isLeave = isset($leaves[$emp['employee_id']][$day]);
          ?>
          <td class="px-1 py-1 text-center align-middle">
            <div class="flex flex-col items-center gap-1">
              <select data-employee="<?= $emp['employee_id'] ?>" data-date="<?= $day ?>" onchange="saveShift(this)"
                class="w-full text-xs p-1 border rounded focus:ring-1 focus:ring-blue-500"
                <?= $isLeave ? 'disabled' : '' ?>>
                <?php if ($isLeave): ?>
                  <option value="" selected>On Leave</option>
                <?php else: ?>
                  <option value="" <?= $shift_id==''?'selected':'' ?>>Off</option>
                  <?php foreach($shiftsArray as $s): ?>
                    <option value="<?= $s['shift_id'] ?>" <?= $shift_id==$s['shift_id']?'selected':'' ?>>
                      <?= $s['shift_code'] ?> (<?= $s['start_time'] ?>-<?= $s['end_time'] ?>)
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
              <button type="button" class="text-blue-500 hover:text-blue-700 text-lg"
                onclick="openNoteModal('<?= $emp['employee_id'] ?>','<?= $day ?>','<?= htmlspecialchars($note_text,ENT_QUOTES) ?>')">
                📝
              </button>
            </div>
          </td>
          <?php endforeach; ?>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
<?php elseif($selected_role): ?>
<p class="text-gray-600 italic">No employees found for this role.</p>
<?php endif; ?>

<!-- Zoom Modal -->
<div id="zoomModal" class="fixed inset-0 bg-white z-50 hidden overflow-auto p-4">
  <div class="flex justify-between items-center mb-4">
    <h2 class="font-semibold text-xl">Zoomed Schedule</h2>
    <button onclick="closeZoom()" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Close</button>
  </div>
</div>

<script>
let scheduleTable = document.getElementById('scheduleTable');
let zoomModal = document.getElementById('zoomModal');
let originalParent = scheduleTable.parentNode; // keep original container

function openZoom() {
  zoomModal.appendChild(scheduleTable); // move table into modal
  zoomModal.classList.remove('hidden');
}

function closeZoom() {
  originalParent.appendChild(scheduleTable); // move table back to original place
  zoomModal.classList.add('hidden');
}
</script>



<!-- Note Modal -->
<div id="noteModal" class="modal">
<div class="modal-content">
<h4>Notes</h4>
<textarea id="noteText" style="width:100%;height:100px"></textarea>
<br><br>
<button onclick="saveNote()" class="bg-blue-500 text-white px-4 py-2 rounded">Save</button>
<button onclick="closeNoteModal()" class="bg-gray-300 px-4 py-2 rounded">Cancel</button>
</div>
</div>

<script>
let currentEmployee = null;
let currentDate = null;

function saveShift(select){
    const employeeId = select.dataset.employee;
    const workDate = select.dataset.date;
    const shiftId = select.value;

    fetch('save_schedule.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:new URLSearchParams({employee_id:employeeId, work_date:workDate, shift_id:shiftId})
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.status==="success") select.style.backgroundColor="#d4edda";
        else select.style.backgroundColor="#f8d7da";
        setTimeout(()=>select.style.backgroundColor='',1000);
    })
    .catch(err=>{
        select.style.backgroundColor="#f8d7da";
        setTimeout(()=>select.style.backgroundColor='',1000);
        alert("Error saving schedule");
    });
}

// Note modal
function openNoteModal(employeeId, workDate, text){
    currentEmployee = employeeId;
    currentDate = workDate;
    document.getElementById('noteText').value = text;
    const modal = document.getElementById('noteModal');
    modal.classList.add('show');
}
function closeNoteModal(){
    const modal = document.getElementById('noteModal');
    modal.classList.remove('show');
}

function saveNote(){
    const note = document.getElementById('noteText').value;

    fetch('save_note.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            employee_id: currentEmployee,
            work_date: currentDate,
            notes: note
        })
    })
    .then(res => res.json())   // ✅ parse JSON instead of text
    .then(data => {
        const modal = document.getElementById('noteModal');
        const content = modal.querySelector('.modal-content');

        // Create feedback message
        const messageDiv = document.createElement('div');
        messageDiv.className = (data.status === "success") 
            ? "bg-green-100 text-green-700 p-2 rounded mt-2 text-sm"
            : "bg-red-100 text-red-700 p-2 rounded mt-2 text-sm";
        messageDiv.textContent = data.message;

        content.appendChild(messageDiv);

        // ⏳ Remove message after 2s, then close modal
        setTimeout(() => {
            messageDiv.remove();
            if (data.status === "success") {
                closeNoteModal();
            }
        }, 2000);
    })
    .catch(err => {
        alert("❌ Error saving note: " + err);
        closeNoteModal();
    });
}

</script>

        <?php 
else: 
  
endif; 
?>

<?php
// Assume $roles contains the role of the currently logged-in user
// e.g., $roles = $_SESSION['user_role'];

if (in_array($roles, [ 'Employee'])): 
?>


<?php 

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Make sure user is logged in
if (!isset($_SESSION['employee_id'])) {
    header("Location: ../index.php");
    exit;
}

// Logged-in employee info
$loggedInUserId = $_SESSION['employee_id'];
$loggedInUserName = $_SESSION['user_name'] ?? 'Guest';
$view = $_GET['view'] ?? 'week';
$week_start_input = $_GET['week_start'] ?? '';

// DB Connections
include __DIR__ . '/../dbconnection/dbEmployee.php';
$empConn = $conn;
include __DIR__ . '/../dbconnection/mainDB.php';
$shiftConn = $conn;

// Step 1: Shifts
$shiftsArray = [];
$shiftsResult = $shiftConn->query("SELECT shift_id, shift_code, start_time, end_time FROM shifts ORDER BY start_time");
while($row = $shiftsResult->fetch_assoc()){
    $shiftsArray[$row['shift_id']] = $row;
}

// Step 2: Determine days to show
if ($view === 'month') {
    $year  = $_GET['year'] ?? date('Y');
    $month = $_GET['month'] ?? date('m');
    $month_start = date('Y-m-01', strtotime("$year-$month-01"));
    $month_end   = date('Y-m-t', strtotime($month_start));
    $days = [];
    $period = new DatePeriod(new DateTime($month_start), new DateInterval('P1D'), (new DateTime($month_end))->modify('+1 day'));
    foreach ($period as $dt) { $days[] = $dt->format('Y-m-d'); }
} else {
    $week_start = $week_start_input ? date('Y-m-d', strtotime($week_start_input)) : date('Y-m-d', strtotime('monday this week'));
    $week_end = date('Y-m-d', strtotime("$week_start +6 days"));

    $days = [];
    for($i=0;$i<7;$i++){ 
        $days[] = date('Y-m-d', strtotime("$week_start +$i days")); 
    }
}

// Step 3: Employee info (only logged-in employee)
$employeeRes = $empConn->query("
    SELECT employee_id, CONCAT(first_name,' ',last_name) AS fullname 
    FROM employees 
    WHERE employee_id = '{$loggedInUserId}'
");
$employee = $employeeRes->fetch_assoc();

// Step 4: Existing schedules and notes
$schedules = [];
$notes = [];
if (!empty($days)) {
    $dayStart = $days[0];
    $dayEnd   = end($days);
    $res = $shiftConn->query("
        SELECT employee_id, shift_id, work_date, notes 
        FROM employee_schedules 
        WHERE employee_id = '{$loggedInUserId}'
          AND work_date BETWEEN '$dayStart' AND '$dayEnd'
    ");
    while($row = $res->fetch_assoc()){
        $schedules[$row['employee_id']][$row['work_date']] = $row['shift_id'];
        $notes[$row['employee_id']][$row['work_date']] = $row['notes'];
    }
}

// Step 5: Approved leaves
$leaves = [];
$resLeaves = $shiftConn->query("
    SELECT employee_id, start_date, end_date, status
    FROM leave_requests
    WHERE employee_id = '{$loggedInUserId}'
      AND status = 'Approved'
      AND (
          (start_date BETWEEN '$dayStart' AND '$dayEnd') 
          OR (end_date BETWEEN '$dayStart' AND '$dayEnd') 
          OR ('$dayStart' BETWEEN start_date AND end_date)
      )
");
while($row = $resLeaves->fetch_assoc()){
    $start = strtotime($row['start_date']);
    $end = strtotime($row['end_date']);
    while($start <= $end){
        $leaves[$row['employee_id']][date('Y-m-d',$start)] = true;
        $start = strtotime('+1 day',$start);
    }
}
?>



<?php if($employee): ?>
<div class="bg-white shadow rounded-lg">
  <!-- Desktop view (hidden on small screens) -->
  <div class="hidden md:block overflow-x-auto">
    <table class="min-w-full border-collapse table-auto">
      <thead>
        <tr class="bg-gray-100 text-sm">
          <th class="px-4 py-2 text-left font-semibold">Employee</th>
          <?php foreach($days as $day): ?>
            <th class="px-4 py-2 text-center font-semibold">
              <?= date('D', strtotime($day)) ?><br>
              <span class="text-xs text-gray-500"><?= date('m/d', strtotime($day)) ?></span>
            </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody class="text-sm divide-y">
        <tr>
          <td class="px-4 py-2 font-medium whitespace-nowrap"><?= htmlspecialchars($employee['fullname']) ?></td>
          <?php foreach($days as $day): 
              $shift_id = $schedules[$employee['employee_id']][$day] ?? '';
              $note_text = $notes[$employee['employee_id']][$day] ?? '';
              $isLeave = isset($leaves[$employee['employee_id']][$day]);
              $shiftText = $isLeave ? 'On Leave' : ($shift_id ? $shiftsArray[$shift_id]['shift_code'].' ('.$shiftsArray[$shift_id]['start_time'].'-'.$shiftsArray[$shift_id]['end_time'].')' : 'Off');
          ?>
          <td class="px-2 py-2 text-center align-middle">
            <div class="flex flex-col items-center gap-1">
              <span class="text-xs sm:text-sm p-1 border rounded-lg bg-gray-50 w-full">
                <?= $shiftText ?>
              </span>
              <?php if($note_text): ?>
              <button type="button" class="text-blue-500 hover:text-blue-700 text-lg"
                  onclick="openNoteModal('<?= $employee['employee_id'] ?>','<?= $day ?>','<?= htmlspecialchars($note_text,ENT_QUOTES) ?>')">
                  📝
              </button>
              <?php endif; ?>
            </div>
          </td>
          <?php endforeach; ?>
        </tr>
      </tbody>
    </table>
  </div>
</div>



  <!-- Mobile stacked view (hidden on desktop) -->
  <div class="block md:hidden p-4 space-y-4">
    <h3 class="text-base font-semibold mb-2"><?= htmlspecialchars($employee['fullname']) ?></h3>
    <?php foreach($days as $day): 
        $shift_id = $schedules[$employee['employee_id']][$day] ?? '';
        $note_text = $notes[$employee['employee_id']][$day] ?? '';
        $isLeave = isset($leaves[$employee['employee_id']][$day]);
    ?>
    <div class="border rounded-lg p-3 shadow-sm">
      <div class="flex justify-between items-center mb-2">
        <span class="font-medium"><?= date('D', strtotime($day)) ?></span>
        <span class="text-xs text-gray-500"><?= date('m/d', strtotime($day)) ?></span>
      </div>
      <div>
        <select class="w-full text-sm p-2 border rounded-lg focus:ring-2 focus:ring-blue-500" disabled>
            <?php if($isLeave): ?>
                <option selected>On Leave</option>
            <?php else: ?>
                <option <?= $shift_id==''?'selected':'' ?>>Off</option>
                <?php foreach($shiftsArray as $s): ?>
                    <option value="<?= $s['shift_id'] ?>" <?= $shift_id==$s['shift_id']?'selected':'' ?>>
                        <?= $s['shift_code'] ?> (<?= $s['start_time'] ?>-<?= $s['end_time'] ?>)
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
      </div>
      <button type="button" class="mt-2 text-blue-500 hover:text-blue-700 text-lg"
          onclick="openNoteModal('<?= $employee['employee_id'] ?>','<?= $day ?>','<?= htmlspecialchars($note_text,ENT_QUOTES) ?>')">
          📝 View Note
      </button>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Modal -->
<div id="noteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-lg p-4 w-11/12 max-w-md">
    <h2 class="text-lg font-semibold mb-2">Shift Note</h2>
    <p id="noteContent" class="text-gray-700 whitespace-pre-wrap"></p>
    <button onclick="closeNoteModal()" class="mt-4 px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">Close</button>
  </div>
</div>

<script>
function openNoteModal(employeeId, date, note) {
    const modal = document.getElementById('noteModal');
    const content = document.getElementById('noteContent');
    content.textContent = note || 'No notes for this shift.';
    modal.classList.remove('hidden');
    modal.classList.add('flex'); // center modal
}
function closeNoteModal() {
    const modal = document.getElementById('noteModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>

<?php else: ?>
<p class="text-gray-600 italic">No schedule found for you.</p>
<?php endif; ?>


        <?php 
else: 
  
endif; 
?>


</body>
</html>


