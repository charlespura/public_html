<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__ . '/../dbconnection/dbShift.php';
$shiftConn = $conn;

$employee_id = $_POST['employee_id'] ?? null;
$work_date   = $_POST['work_date'] ?? null;
$shift_id    = $_POST['shift_id'] ?? null;

if (!$employee_id || !$work_date) {
    echo json_encode(["status"=>"error","message"=>"Invalid data."]);
    exit;
}

// Validate shift_id if not empty
if ($shift_id) {
    $chk = $shiftConn->prepare("SELECT shift_id FROM shifts WHERE shift_id = ?");
    $chk->bind_param("s", $shift_id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows === 0) {
        echo json_encode(["status"=>"error","message"=>"Invalid shift selected."]);
        exit;
    }
    $chk->close();

    $stmt = $shiftConn->prepare("
        INSERT INTO employee_schedules (employee_id, work_date, shift_id)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE shift_id = VALUES(shift_id)
    ");
    $stmt->bind_param("sss", $employee_id, $work_date, $shift_id);
    if ($stmt->execute()) {
        echo json_encode(["status"=>"success","message"=>"Schedule updated successfully."]);
    } else {
        echo json_encode(["status"=>"error","message"=>"DB error: ".$stmt->error]);
    }
    $stmt->close();
} else {
    // Delete schedule
    $stmt = $shiftConn->prepare("
        DELETE FROM employee_schedules
        WHERE employee_id=? AND work_date=?
    ");
    $stmt->bind_param("ss", $employee_id, $work_date);
    if ($stmt->execute()) {
        echo json_encode(["status"=>"success","message"=>"Schedule cleared."]);
    } else {
        echo json_encode(["status"=>"error","message"=>"DB error: ".$stmt->error]);
    }
    $stmt->close();
}
exit;
