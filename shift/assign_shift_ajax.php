







<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php
header('Content-Type: application/json');
include __DIR__ . '/../dbconnection/dbShift.php';
$shiftConn = $conn;

$employee_id = $_POST['employee_id'] ?? '';
$shift_id    = $_POST['shift_id'] ?? null; // '' for Off, 'REMOVE', or normal ID
$work_date   = $_POST['work_date'] ?? '';

if (!$employee_id || !$work_date) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

// Normalize shift_id
if ($shift_id === '') {
    $shift_id = null; // treat empty string as NULL
}

try {
    if ($shift_id === 'REMOVE') {
        // Remove shift completely
        $stmt = $shiftConn->prepare("DELETE FROM employee_schedules WHERE employee_id=? AND work_date=?");
        $stmt->bind_param("ss", $employee_id, $work_date);
        $stmt->execute();
    } else if ($shift_id === null) {
        // Off / No Shift → keep row with status='off' and shift_id=NULL
        $status = 'off';
        $stmt = $shiftConn->prepare("
            INSERT INTO employee_schedules (employee_id, shift_id, work_date, status)
            VALUES (?, NULL, ?, ?)
            ON DUPLICATE KEY UPDATE shift_id=NULL, status='off', updated_at=NOW()
        ");
        $stmt->bind_param("sss", $employee_id, $work_date, $status);
        $stmt->execute();
    } else {
        // Normal shift → status 'scheduled'
        $status = 'scheduled';
        $stmt = $shiftConn->prepare("
            INSERT INTO employee_schedules (employee_id, shift_id, work_date, status)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE shift_id=VALUES(shift_id), status='scheduled', updated_at=NOW()
        ");
        $stmt->bind_param("ssss", $employee_id, $shift_id, $work_date, $status);
        $stmt->execute();
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
