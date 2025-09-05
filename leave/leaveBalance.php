<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$employeeId = $_SESSION['employee_id'] ?? null;
$roles = $_SESSION['roles'] ?? 'Employee'; // Role of logged-in user

$message = '';
$messageType = '';

// ==========================
// DB Connections
// ==========================
include __DIR__ . '/../dbconnection/dbEmployee.php';
$empConn = $conn;
include __DIR__ . '/../dbconnection/mainDB.php';
$shiftConn = $conn;

// ==========================
// Handle Admin Assign Leave Balance
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_balance']) && in_array($roles, ['Admin', 'Manager'])) {
    $emp_id = $_POST['employee_id'];
    $leave_type_id = $_POST['leave_type_id'];
    $total_days = intval($_POST['total_days']);

    // Check if balance already exists
    $stmt = $shiftConn->prepare("SELECT * FROM employee_leave_balance WHERE employee_id=? AND leave_type_id=?");
    $stmt->bind_param("ss", $emp_id, $leave_type_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing balance
        $stmtUpdate = $shiftConn->prepare("UPDATE employee_leave_balance SET total_days=? WHERE employee_id=? AND leave_type_id=?");
        $stmtUpdate->bind_param("iss", $total_days, $emp_id, $leave_type_id);
        $stmtUpdate->execute();
        $stmtUpdate->close();
        $message = "Leave balance updated successfully!";
        $messageType = "success";
    } else {
        // Insert new balance
        $stmtInsert = $shiftConn->prepare("INSERT INTO employee_leave_balance (employee_id, leave_type_id, total_days, used_days) VALUES (?, ?, ?, 0)");
        $stmtInsert->bind_param("ssi", $emp_id, $leave_type_id, $total_days);
        $stmtInsert->execute();
        $stmtInsert->close();
        $message = "Leave balance assigned successfully!";
        $messageType = "success";
    }
    $stmt->close();
}

// ==========================
// Fetch Employees and Leave Types
// ==========================
$employees = $empConn->query("SELECT employee_id, first_name, last_name FROM employees ORDER BY first_name")->fetch_all(MYSQLI_ASSOC);
$leaveTypes = $shiftConn->query("SELECT leave_type_id, leave_name FROM leave_types ORDER BY leave_name")->fetch_all(MYSQLI_ASSOC);

// ==========================
// Fetch Employee Leave Balance (for logged-in user)
// ==========================
$balances = $shiftConn->query("
    SELECT lt.leave_name, elb.total_days, elb.used_days, (elb.total_days - elb.used_days) AS remaining_days
    FROM employee_leave_balance elb
    JOIN leave_types lt ON elb.leave_type_id = lt.leave_type_id
    WHERE elb.employee_id = '{$employeeId}'
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Leave Balance</title>
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
<div class="flex h-full">
    <?php include '../sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-y-auto">
        <main class="p-6 space-y-6">
            <div class="flex items-center justify-between border-b py-6">
                <h2 class="text-xl font-semibold text-gray-800">Leave Balance</h2>
                <?php include '../profile.php'; ?>
            </div>

            <?php if ($message): ?>
            <div class="mb-4 px-4 py-3 rounded-lg text-white <?= $messageType === 'success' ? 'bg-green-500' : 'bg-red-500' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <!-- ========================== -->
            <!-- Admin Assign Leave Balance -->
            <!-- ========================== -->
            <?php if (in_array($roles, ['Admin', 'Manager'])): ?>
            <div class="bg-white shadow-md rounded-2xl p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Assign Leave Balance</h3>
                <form method="POST" class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block">Employee</label>
                        <select name="employee_id" class="w-full border p-2 rounded" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['employee_id'] ?>"><?= htmlspecialchars($emp['first_name'].' '.$emp['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block">Leave Type</label>
                        <select name="leave_type_id" class="w-full border p-2 rounded" required>
                            <option value="">Select Leave Type</option>
                            <?php foreach ($leaveTypes as $lt): ?>
                                <option value="<?= $lt['leave_type_id'] ?>"><?= htmlspecialchars($lt['leave_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block">Total Days</label>
                        <input type="number" name="total_days" class="w-full border p-2 rounded" required>
                    </div>
                    <div class="col-span-3 flex justify-end mt-4">
                        <button type="submit" name="assign_balance" class="bg-gray-800 hover:bg-gray-900 text-white hover:text-yellow-500 px-4 py-2 rounded w-full sm:w-auto">Assign / Update Balance</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- ========================== -->
            <!-- Employee Leave Balance Table -->
            <!-- ========================== -->
            <div class="bg-white shadow-md rounded-2xl p-6">
                <h3 class="text-lg font-semibold mb-4">Your Leave Balance</h3>
                <table class="w-full border-collapse border">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="border px-3 py-2">Leave Type</th>
                            <th class="border px-3 py-2">Total Days</th>
                            <th class="border px-3 py-2">Used Days</th>
                            <th class="border px-3 py-2">Remaining Days</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($balances as $b): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="border px-3 py-2"><?= htmlspecialchars($b['leave_name']) ?></td>
                            <td class="border px-3 py-2 text-center"><?= $b['total_days'] ?></td>
                            <td class="border px-3 py-2 text-center"><?= $b['used_days'] ?></td>
                            <td class="border px-3 py-2 text-center"><?= $b['remaining_days'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>
</div>
</body>
</html>
