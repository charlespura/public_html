<?php
include __DIR__ . '/../dbconnection/mainDB.php';
header('Content-Type: application/json');

$employee_id = $_POST['employee_id'] ?? '';
$work_date   = $_POST['work_date'] ?? '';
$shift_id    = $_POST['shift_id'] ?? null;

if (!$employee_id || !$work_date) {
    echo json_encode(["status"=>"error","message"=>"Missing data"]);
    exit;
}

// Normalize shift_id
if ($shift_id === "" || strtolower((string)$shift_id) === "null") {
    $shift_id = null;
}

if ($shift_id === null) {
    // Delete schedule if Off
    $sql = "DELETE FROM employee_schedules WHERE employee_id=? AND work_date=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $employee_id, $work_date);
    if ($stmt->execute()) {
        echo json_encode(["status"=>"success","message"=>"Schedule removed"]);
    } else {
        echo json_encode(["status"=>"error","message"=>$stmt->error]);
    }
    $stmt->close();
} else {
    // Insert or update schedule
    $status = 'scheduled';
    $sql = "
        INSERT INTO employee_schedules 
            (employee_id, work_date, shift_id, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE 
            shift_id   = VALUES(shift_id),
            status     = VALUES(status),
            updated_at = NOW()
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $employee_id, $work_date, $shift_id, $status);
    if ($stmt->execute()) {
        echo json_encode(["status"=>"success","message"=>"Schedule saved"]);
    } else {
        echo json_encode(["status"=>"error","message"=>$stmt->error]);
    }
    $stmt->close();
}
?>
