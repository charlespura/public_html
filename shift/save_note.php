<?php
error_reporting(E_ALL);
ini_set('display_errors',1);
include __DIR__ . '/../dbconnection/dbShift.php';
$shiftConn = $conn;

$employee_id = $_POST['employee_id'] ?? null;
$work_date   = $_POST['work_date'] ?? null;
$notes       = $_POST['notes'] ?? null;

if(!$employee_id || !$work_date){
    echo json_encode(["status"=>"error","message"=>"Invalid data."]);
    exit;
}

// Insert or update notes
$stmt = $shiftConn->prepare("
    INSERT INTO employee_schedules (employee_id, work_date, notes)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE notes=VALUES(notes)
");
$stmt->bind_param("sss", $employee_id, $work_date, $notes);

if($stmt->execute()){
    echo json_encode(["status"=>"success","message"=>"Note saved"]);
}else{
    echo json_encode(["status"=>"error","message"=>"DB error: ".$stmt->error]);
}
$stmt->close();
exit;
