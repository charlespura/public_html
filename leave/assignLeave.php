

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>


<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Now we can safely get the logged-in employee
$approver = $_SESSION['employee_id'] ?? null;

error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB Connections
include __DIR__ . '/../dbconnection/dbEmployee.php';
$empConn = $conn;
include __DIR__ . '/../dbconnection/mainDB.php';
$shiftConn = $conn;

// ==========================
// MESSAGE FEEDBACK
// ==========================
$message = '';
$messageType = '';

// ==========================
// ADD LEAVE REQUEST
// ==========================

// ==========================
// ADD LEAVE REQUEST
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_leave_request'])) {

    // Make sure all expected POST values exist
    $employee_id = $_POST['employee_id'] ?? '';
    $leave_type_id = isset($_POST['leave_type_id']) ? intval($_POST['leave_type_id']) : 0;
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

    // Validate required fields
    if (empty($employee_id) || $leave_type_id === 0 || empty($start_date) || empty($end_date)) {
        $message = "Please fill in all required fields.";
        $messageType = "error";
    } else {
        // Convert to timestamps
        $start_ts = strtotime($start_date);
        $end_ts = strtotime($end_date);
        $today_ts = strtotime(date('Y-m-d'));
        $max_ts = strtotime('+1 year', $today_ts);

        // Check if dates are within 1 year
        if ($start_ts === false || $end_ts === false) {
            $message = "Invalid start or end date.";
            $messageType = "error";
        } elseif ($start_ts > $max_ts || $end_ts > $max_ts) {
            $message = "Start or end date cannot be more than 1 year from today.";
            $messageType = "error";
        } elseif ($end_ts < $start_ts) {
            $message = "End date cannot be before start date.";
            $messageType = "error";
        } else {
            $days = ($end_ts - $start_ts) / (60*60*24) + 1;

            // Check max days per leave type
            $stmt = $shiftConn->prepare("SELECT max_days_per_year FROM leave_types WHERE leave_type_id=?");
            $stmt->bind_param("i", $leave_type_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $max_days = $result['max_days_per_year'];
            $stmt->close();

            if ($days > $max_days) {
                $message = "Requested leave days ($days) exceed the maximum allowed ($max_days) for this leave type.";
                $messageType = "error";
            } else {
                // Insert leave request
                $stmt = $shiftConn->prepare("INSERT INTO leave_requests 
                    (employee_id, leave_type_id, start_date, end_date, total_days, reason) 
                    VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sissis", $employee_id, $leave_type_id, $start_date, $end_date, $days, $reason);

                if ($stmt->execute()) {
                    $message = "Leave request submitted successfully!";
                    $messageType = "success";
                } else {
                    $message = "Error: " . $shiftConn->error;
                    $messageType = "error";
                }
                $stmt->close();
            }
        }
    }
}

// ==========================
// UPDATE STATUS (Approve/Reject/Cancel)
// ==========================
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if (in_array($action, ['Approved','Rejected','Cancelled'])) {

        if ($approver) { // Now guaranteed to exist
            $stmt = $shiftConn->prepare("UPDATE leave_requests 
                SET status=?, approved_by=?, approved_date=NOW() 
                WHERE leave_request_id=?");
            $stmt->bind_param("ssi", $action, $approver, $id);
            if ($stmt->execute()) {
                $message = "Leave request $action successfully!";
                $messageType = "success";
            } else {
                $message = "Error: " . $shiftConn->error;
                $messageType = "error";
            }
            $stmt->close();
        } else {
            $message = "No approver logged in!";
            $messageType = "error";
        }
    }
}

// ==========================
// DELETE REQUEST
// ==========================
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $stmt = $shiftConn->prepare("DELETE FROM leave_requests WHERE leave_request_id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Leave request deleted!";
        $messageType = "success";
    } else {
        $message = "Error deleting request: " . $shiftConn->error;
        $messageType = "error";
    }
    $stmt->close();
}

// ==========================
// FETCH EMPLOYEES & LEAVE TYPES
// ==========================
$employees = $shiftConn->query("SELECT employee_id, first_name, last_name FROM hr3_system.employees ORDER BY first_name")->fetch_all(MYSQLI_ASSOC);
$leaveTypes = $shiftConn->query("
    SELECT leave_type_id, leave_name, description, max_days_per_year 
    FROM leave_types 
    ORDER BY leave_name
")->fetch_all(MYSQLI_ASSOC);

// ==========================
// FETCH LEAVE REQUESTS WITH APPROVER NAMES
// ==========================
$requests = $shiftConn->query("
    SELECT lr.*, lt.leave_name, 
           e.first_name AS emp_first, e.last_name AS emp_last,
           a.first_name AS approver_first, a.last_name AS approver_last
    FROM leave_requests lr
    JOIN leave_types lt ON lr.leave_type_id=lt.leave_type_id
    JOIN hr3_system.employees e ON lr.employee_id=e.employee_id
    LEFT JOIN hr3_system.employees a ON lr.approved_by=a.employee_id
    ORDER BY lr.created_at DESC
")->fetch_all(MYSQLI_ASSOC);


?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title> Leave</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="icon" type="image/png" href="../picture/logo2.png" />
  <script>
    document.addEventListener("DOMContentLoaded", () => lucide.createIcons());
    function openDeleteModal(id) {
      document.getElementById("deleteModal").classList.remove("hidden");
      document.getElementById("confirmDeleteBtn").href = "?delete_id=" + id;
    }
    function closeDeleteModal() {
      document.getElementById("deleteModal").classList.add("hidden");
    }
  </script>
</head>

<body class="h-screen overflow-hidden">

<div class="flex h-full">
  <!-- Sidebar -->
  <?php include '../sidebar.php'; ?>

  <!-- Main -->
  <div class="flex-1 flex flex-col overflow-y-auto">
    <main class="p-6 space-y-4">
      <div class="flex items-center justify-between border-b py-6">
        <h2 class="text-xl font-semibold text-gray-800">Leave Management</h2>
        <?php include '../profile.php'; ?>
      </div>

      <?php include 'leavenavbar.php'; ?>

      <div class="bg-white shadow-md rounded-2xl p-8 w-full mx-auto mt-8">


        <!-- Feedback Message -->
        <?php if ($message): ?>
        <div class="mb-4 px-4 py-3 rounded-lg text-white <?= $messageType === 'success' ? 'bg-green-500' : 'bg-red-500' ?>">
          <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>


        
<?php
// Assume $roles contains the role of the currently logged-in user
// e.g., $roles = $_SESSION['user_role'];

if (in_array($roles, ['Admin', 'Manager'])): 
?>


<!-- Leave Assign -->
<div class="bg-white shadow rounded-2xl p-6">
  <h3 class="text-lg font-semibold mb-4">Assign Leave</h3>
  <form method="POST" class="grid grid-cols-2 gap-4">
  <div>
  <label class="block">Employee</label>
  <input type="text" id="employeeInput" class="w-full border p-2 rounded" placeholder="Type employee name..." autocomplete="off" required>
  <div id="suggestions" class="border rounded mt-1 max-h-40 overflow-auto hidden"></div>
</div>

<script>
  const employees = <?php echo json_encode($employees); ?>; // pass PHP array to JS
  const input = document.getElementById('employeeInput');
  const suggestions = document.getElementById('suggestions');

  input.addEventListener('input', () => {
    const value = input.value.toLowerCase();
    suggestions.innerHTML = '';

    if (!value) {
      suggestions.classList.add('hidden');
      return;
    }

    const matches = employees.filter(emp => 
      (emp.first_name + ' ' + emp.last_name).toLowerCase().includes(value)
    );

    if (matches.length === 0) {
      suggestions.classList.add('hidden');
      return;
    }

    matches.forEach(emp => {
      const div = document.createElement('div');
      div.classList.add('p-2', 'cursor-pointer', 'hover:bg-gray-200');
      div.textContent = emp.first_name + ' ' + emp.last_name;
      div.addEventListener('click', () => {
        input.value = emp.first_name + ' ' + emp.last_name;
        suggestions.classList.add('hidden');
        // Optional: store the employee_id in a hidden input
        let hiddenInput = document.getElementById('employee_id');
        if(!hiddenInput){
          hiddenInput = document.createElement('input');
          hiddenInput.type = 'hidden';
          hiddenInput.name = 'employee_id';
          hiddenInput.id = 'employee_id';
          input.parentNode.appendChild(hiddenInput);
        }
        hiddenInput.value = emp.employee_id;
      });
      suggestions.appendChild(div);
    });

    suggestions.classList.remove('hidden');
  });

  // Hide suggestions when clicking outside
  document.addEventListener('click', (e) => {
    if (!input.contains(e.target) && !suggestions.contains(e.target)) {
      suggestions.classList.add('hidden');
    }
  });
</script>

    <div>
      <label class="block">Leave Type</label>
      <select id="leaveTypeSelect" name="leave_type_id" class="w-full border p-2 rounded" required>
        <option value="">Select Type</option>
     <?php foreach ($leaveTypes as $lt): ?>
<option 
    value="<?= htmlspecialchars($lt['leave_type_id'] ?? '') ?>" 
    data-description="<?= htmlspecialchars($lt['description'] ?? '') ?>"
    data-maxdays="<?= htmlspecialchars($lt['max_days_per_year'] ?? '') ?>"
>
    <?= htmlspecialchars($lt['leave_name'] ?? '') ?>
</option>
<?php endforeach; ?>

      </select>
    </div>

    <div class="col-span-2 mt-2">
      <div id="leaveDetails" class="p-3 border rounded bg-gray-50 hidden">
        <p><strong>Description:</strong> <span id="leaveDescription"></span></p>
        <p><strong>Max Days per Year:</strong> <span id="leaveMaxDays"></span></p>
      </div>
    </div>

    <div>
      <label class="block">Start Date</label>
      <input type="date" name="start_date" class="w-full border p-2 rounded" required>
    </div>
    <div>
      <label class="block">End Date</label>
      <input type="date" name="end_date" class="w-full border p-2 rounded" required>
    </div>
    <div class="col-span-2">
      <label class="block">Reason</label>
      <textarea name="reason" class="w-full border p-2 rounded"></textarea>
    </div>
    <div class="col-span-2 flex justify-end">
      <button type="submit" name="add_leave_request" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Assign Leave</button>
    </div>
  </form>
</div>

<script>
  const leaveSelect = document.getElementById('leaveTypeSelect');
  const leaveDetails = document.getElementById('leaveDetails');
  const leaveDescription = document.getElementById('leaveDescription');
  const leaveMaxDays = document.getElementById('leaveMaxDays');

  leaveSelect.addEventListener('change', () => {
    const selectedOption = leaveSelect.selectedOptions[0];
    if (selectedOption.value) {
      leaveDescription.textContent = selectedOption.dataset.description;
      leaveMaxDays.textContent = selectedOption.dataset.maxdays;
      leaveDetails.classList.remove('hidden');
    } else {
      leaveDetails.classList.add('hidden');
    }
  });
</script>



<!-- Leave Requests Table -->
<div class="bg-white shadow rounded-2xl p-6">
  <h3 class="text-lg font-semibold mb-4">Leave Requests</h3>
  <table class="w-full border-collapse border">
    <thead class="bg-gray-200">
      <tr>
        <th class="border px-3 py-2">#</th>
        <th class="border px-3 py-2">Employee</th>
        <th class="border px-3 py-2">Leave Type</th>
        <th class="border px-3 py-2">Dates</th>
        <th class="border px-3 py-2">Days</th>
        <th class="border px-3 py-2">Status</th>
        <th class="border px-3 py-2">Approved By</th>
        <th class="border px-3 py-2">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($requests as $r): ?>
      <tr class="hover:bg-gray-50">
        <td class="border px-3 py-2"><?= $r['leave_request_id'] ?></td>
        <td class="border px-3 py-2"><?= htmlspecialchars($r['emp_first'].' '.$r['emp_last']) ?></td>
        <td class="border px-3 py-2"><?= htmlspecialchars($r['leave_name']) ?></td>
        <td class="border px-3 py-2"><?= $r['start_date'] ?> → <?= $r['end_date'] ?></td>
        <td class="border px-3 py-2 text-center"><?= $r['total_days'] ?></td>
        <td class="border px-3 py-2"><?= $r['status'] ?></td>
        <td class="border px-3 py-2">
          <?= $r['approver_first'] ? htmlspecialchars($r['approver_first'].' '.$r['approver_last']) : '-' ?>
        </td>
        <td class="border px-3 py-2 flex space-x-2">
          <?php if ($r['status']=='Pending'): ?>
          <a href="?action=Approved&id=<?= $r['leave_request_id'] ?>" class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600">Approve</a>
          <a href="?action=Rejected&id=<?= $r['leave_request_id'] ?>" class="px-3 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600">Reject</a>
          <?php endif; ?>
          <button onclick="openDeleteModal('<?= $r['leave_request_id'] ?>')" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600">Delete</button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

  <!-- Delete Modal -->
  <div id="deleteModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-lg w-96 p-6">
      <h2 class="text-lg font-bold mb-4">Are you sure?</h2>
      <p class="mb-6 text-gray-600">The selected leave request will be permanently deleted. Continue?</p>
      <div class="flex justify-end space-x-3">
        <button onclick="closeDeleteModal()" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
        <a id="confirmDeleteBtn" href="#" class="px-4 py-2 rounded bg-red-600 hover:bg-red-700 text-white">Delete</a>
      </div>
    </div>
  </div>


  
        <?php 
else: 
  
endif; 
?>




<?php
// Assume $roles contains the role of the currently logged-in user
// e.g., $roles = $_SESSION['roles'];

// Admin & Manager → Assign Leave
if (in_array($roles, [ 'Employee'])): 
?> 

<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Employee info
$roles = $_SESSION['roles'] ?? 'Employee';
$employeeId = $_SESSION['employee_id'] ?? null;

// DB Connections
include __DIR__ . '/../dbconnection/mainDB.php';
$shiftConn = $conn;

// Message feedback
$message = '';
$messageType = '';

// ==========================
// ADD LEAVE REQUEST (for Employee)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_leave'])) {

    if ($roles !== 'Employee') {
        $message = "Only employees can request leave!";
        $messageType = "error";
    } else {

        $leave_type_id = intval($_POST['leave_type_id']);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $reason = trim($_POST['reason']);

        $days = (strtotime($end_date) - strtotime($start_date)) / (60*60*24) + 1;

        $stmt = $shiftConn->prepare("INSERT INTO leave_requests 
            (employee_id, leave_type_id, start_date, end_date, total_days, reason, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("sissis", $employeeId, $leave_type_id, $start_date, $end_date, $days, $reason);

        if ($stmt->execute()) {
            $message = "Leave request submitted successfully!";
            $messageType = "success";
        } else {
            $message = "Error: " . $shiftConn->error;
            $messageType = "error";
        }
        $stmt->close();
    }
}

// Fetch leave types for dropdown
$leaveTypes = $shiftConn->query("
    SELECT leave_type_id, leave_name, description, max_days_per_year 
    FROM leave_types 
    ORDER BY leave_name
")->fetch_all(MYSQLI_ASSOC);

// ==========================
// FETCH EMPLOYEE LEAVE REQUESTS
$requests = $shiftConn->query("
    SELECT lr.*, lt.leave_name, 
           a.first_name AS approver_first, a.last_name AS approver_last
    FROM leave_requests lr
    JOIN leave_types lt ON lr.leave_type_id = lt.leave_type_id
    LEFT JOIN hr3_system.employees a ON lr.approved_by = a.employee_id
    WHERE lr.employee_id = '{$employeeId}'
    ORDER BY lr.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

?>

<div class="max-w-xl mx-auto bg-white p-6 rounded shadow mb-6">
    <h2 class="text-xl font-bold mb-4">Request Leave</h2>

    <!-- Feedback -->
    <?php if ($message): ?>
        <div class="mb-4 p-3 rounded <?= $messageType === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <div>
            <label class="block mb-1">Leave Type</label>
            <select id="leaveTypeSelect" name="leave_type_id" class="w-full border p-2 rounded" required>
                <option value="">Select Type</option>
                <?php foreach ($leaveTypes as $lt): ?>
                    <option 
                        value="<?= $lt['leave_type_id'] ?>" 
                        data-maxdays="<?= $lt['max_days_per_year'] ?? 0 ?>"
                    >
                        <?= htmlspecialchars($lt['leave_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <!-- Max Days Display -->
            <p id="maxDaysDisplay" class="mt-1 text-sm text-gray-600 hidden">
                Max Days per Year: <span id="maxDaysValue"></span>
            </p>
        </div>

        <div>
            <label class="block mb-1">Start Date</label>
            <input type="date" name="start_date" class="w-full border p-2 rounded" required>
        </div>
        <div>
            <label class="block mb-1">End Date</label>
            <input type="date" name="end_date" class="w-full border p-2 rounded" required>
        </div>
        <div>
            <label class="block mb-1">Reason</label>
            <textarea name="reason" class="w-full border p-2 rounded"></textarea>
        </div>
        <div class="text-right">
            <button type="submit" name="request_leave" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Submit Request</button>
        </div>
    </form>
</div>

<script>
const leaveSelect = document.getElementById('leaveTypeSelect');
const maxDaysDisplay = document.getElementById('maxDaysDisplay');
const maxDaysValue = document.getElementById('maxDaysValue');

leaveSelect.addEventListener('change', () => {
    const selectedOption = leaveSelect.selectedOptions[0];
    if (selectedOption.value) {
        const maxDays = selectedOption.dataset.maxdays || 0;
        maxDaysValue.textContent = maxDays;
        maxDaysDisplay.classList.remove('hidden');
    } else {
        maxDaysDisplay.classList.add('hidden');
        maxDaysValue.textContent = '';
    }
});
</script>


<!-- ========================== -->
<!-- Leave Requests Table -->
<div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-4">My Leave Requests</h2>
    <table class="w-full border-collapse border">
        <thead class="bg-gray-200">
            <tr>
                <th class="border px-3 py-2">#</th>
                <th class="border px-3 py-2">Leave Type</th>
                <th class="border px-3 py-2">Dates</th>
                <th class="border px-3 py-2">Days</th>
                <th class="border px-3 py-2">Status</th>
                <th class="border px-3 py-2">Approved By</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $r): ?>
                <tr class="hover:bg-gray-50">
                    <td class="border px-3 py-2"><?= $r['leave_request_id'] ?></td>
                    <td class="border px-3 py-2"><?= htmlspecialchars($r['leave_name']) ?></td>
                    <td class="border px-3 py-2"><?= $r['start_date'] ?> → <?= $r['end_date'] ?></td>
                    <td class="border px-3 py-2 text-center"><?= $r['total_days'] ?></td>
                    <td class="border px-3 py-2"><?= $r['status'] ?></td>
                    <td class="border px-3 py-2">
                        <?= $r['approver_first'] ? htmlspecialchars($r['approver_first'].' '.$r['approver_last']) : '-' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>




<?php 
else: 
  
endif; 
?>

</body>
</html>
