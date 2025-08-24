


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
include 'shiftnavbar.php'; ?>
<div class="bg-white shadow-md rounded-2xl p-10 w-full mx-auto mt-10 mb-10">
    <h2 class="text-2xl font-bold mb-6">Assign Shift Schedule</h2>

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
    <form method="GET" action="pdfreport.php" target="_blank">
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
  <label class="block text-sm font-medium text-gray-700 mb-1">Role:</label>
  <select name="role" onchange="this.form.submit()"
    class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
    <option value="">--Select Role--</option>
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
    class="px-4 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 transition">
    Show Week
  </button>
</form>

<!-- Schedule Table -->
<?php if($selected_role && $employees->num_rows>0): ?>
<div class="overflow-x-auto bg-white shadow rounded-lg">
  <table class="min-w-full border-collapse">
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
      <?php while($emp=$employees->fetch_assoc()): ?>
        <tr>
          <td class="px-4 py-2 font-medium whitespace-nowrap"><?= htmlspecialchars($emp['fullname']) ?></td>
          <?php foreach($days as $day): 
            $shift_id = $schedules[$emp['employee_id']][$day] ?? '';
            $note_text = $notes[$emp['employee_id']][$day] ?? '';
          ?>
          <td class="px-2 py-2 text-center align-middle">
            <div class="flex flex-col items-center gap-1">
              <select data-employee="<?= $emp['employee_id'] ?>" data-date="<?= $day ?>" onchange="saveShift(this)"
                class="w-full sm:w-auto text-xs sm:text-sm p-1 border rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">Off</option>
                <?php foreach($shiftsArray as $s): ?>
                  <option value="<?= $s['shift_id'] ?>" <?= $shift_id==$s['shift_id']?'selected':'' ?>>
                    <?= $s['shift_code'] ?> (<?= $s['start_time'] ?>-<?= $s['end_time'] ?>)
                  </option>
                <?php endforeach; ?>
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

    fetch('save_note.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:new URLSearchParams({
            employee_id: currentEmployee,
            work_date: currentDate,
            notes: note
        })
    })
    .then(res => res.text())   // ✅ get raw HTML
    .then(html => {
        const modal = document.getElementById('noteModal');
        const content = modal.querySelector('.modal-content');

        // Insert success/error message
        const messageDiv = document.createElement('div');
        messageDiv.innerHTML = html;
        content.appendChild(messageDiv);

        // ⏳ Remove message after 3 seconds, then close modal
        setTimeout(() => {
            messageDiv.remove();
            closeNoteModal();
        }, 3000);
    })
    .catch(err=>{
        alert("Error saving note");
        closeNoteModal();
    });
}


</script>



</body>
</html>


