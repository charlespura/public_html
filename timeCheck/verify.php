<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// -------------------------
// Configuration
// -------------------------
$api_key = "nlDPUaWDEsAAD81T-zdC3lNhdKOL2zHH";
$api_secret = "nfZ00JGDKhX3izjF0U6H6VwxCWNaoeCn";

$baseUploadDir = __DIR__ . '/../uploads/attenance/';
$clockInDir = $baseUploadDir . 'clock_in/';
$clockOutDir = $baseUploadDir . 'clock_out/';

foreach ([$baseUploadDir, $clockInDir, $clockOutDir] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0777, true);
}

// -------------------------
// Database Connections
// -------------------------
include __DIR__ . '/../dbconnection/dbEmployee.php';
$empConn = $conn;

include __DIR__ . '/../dbconnection/mainDB.php';
$mainConn = $conn;

// -------------------------
// Helper function
// -------------------------
function flash_and_redirect($message) {
    $_SESSION['flash_message'] = $message;
    header("Location: clockin.php");
    exit;
}

// -------------------------
// Check if image is received
// -------------------------
if (!isset($_POST['current']) || empty($_POST['current'])) {
    flash_and_redirect("❌ No image data received from webcam.");
}

// Save temporary capture
$data = str_replace(['data:image/jpeg;base64,', ' '], ['', '+'], $_POST['current']);
$tempImage = __DIR__ . '/../uploads/reference_image/temp_' . time() . '.jpg';
file_put_contents($tempImage, base64_decode($data));

// Fetch users with reference images
$users = $mainConn->query("SELECT user_id, username, reference_image FROM users WHERE reference_image IS NOT NULL");
if ($users->num_rows == 0) flash_and_redirect("❌ No registered users with reference image found.");

// Compare faces
$matched = false;

while ($user = $users->fetch_assoc()) {
    $referenceImagePath = __DIR__ . '/../' . $user['reference_image'];
    if (!file_exists($referenceImagePath)) continue;

    // Face++ API call
    $url = "https://api-us.faceplusplus.com/facepp/v3/compare";
    $postData = [
        "api_key" => $api_key,
        "api_secret" => $api_secret,
        "image_file1" => new CURLFile($referenceImagePath),
        "image_file2" => new CURLFile($tempImage)
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);

    if (isset($result['confidence']) && $result['confidence'] > 85) {
        $user_id = $user['user_id'];

        // Map user to employee
        $empRes = $empConn->query("SELECT employee_id FROM employees WHERE user_id='$user_id' LIMIT 1");
        if ($empRes->num_rows == 0) {
            flash_and_redirect("❌ No employee record found for {$user['username']}.");
        }
        $employee_id = $empRes->fetch_assoc()['employee_id'];

        // Get today's schedule
        $today = date('Y-m-d');
        $schedRes = $mainConn->query("SELECT schedule_id FROM employee_schedules WHERE employee_id='$employee_id' AND work_date='$today' AND status='scheduled' LIMIT 1");
        if ($schedRes->num_rows == 0) {
            flash_and_redirect("ℹ️ {$user['username']} has no scheduled shift today. Cannot clock in.");
        }

        $schedule_id = $schedRes->fetch_assoc()['schedule_id'];

        // Check attendance
        $attRes = $mainConn->query("SELECT * FROM attendance WHERE user_id='$user_id' AND schedule_id='$schedule_id' LIMIT 1");

        if ($attRes->num_rows == 0) {
            // Clock In
            $clockInImage = $clockInDir . $user_id . '_' . time() . '.jpg';
            if (copy($tempImage, $clockInImage)) {
                $stmt = $mainConn->prepare("INSERT INTO attendance (attendance_id, schedule_id, user_id, clock_in, clock_in_image) VALUES (UUID(), ?, ?, NOW(), ?)");
                $stmt->bind_param("sss", $schedule_id, $user_id, $clockInImage);
                if ($stmt->execute()) {
                    flash_and_redirect("✅ {$user['username']} Clocked In!");
                } else {
                    flash_and_redirect("❌ DB Error (Clock In): " . $stmt->error);
                }
            } else {
                flash_and_redirect("❌ Failed to save Clock-In image.");
            }
        } else {
            // Clock Out
            $att = $attRes->fetch_assoc();
            if ($att['clock_out'] != null) {
                flash_and_redirect("ℹ️ {$user['username']} already clocked out today.");
            } else {
                $clockOutImage = $clockOutDir . $user_id . '_' . time() . '.jpg';
                if (copy($tempImage, $clockOutImage)) {
                    $clockInTime = new DateTime($att['clock_in']);
                    $clockOutTime = new DateTime();
                    $worked = $clockOutTime->diff($clockInTime)->h + ($clockOutTime->diff($clockInTime)->i / 60);

                    $stmt = $mainConn->prepare("UPDATE attendance SET clock_out=NOW(), clock_out_image=?, hours_worked=? WHERE attendance_id=?");
                    $stmt->bind_param("sds", $clockOutImage, $worked, $att['attendance_id']);
                    if ($stmt->execute()) {
                        flash_and_redirect("✅ {$user['username']} Clocked Out! Hours worked: " . round($worked, 2));
                    } else {
                        flash_and_redirect("❌ DB Error (Clock Out): " . $stmt->error);
                    }
                } else {
                    flash_and_redirect("❌ Failed to save Clock-Out image.");
                }
            }
        }

        $matched = true;
        break;
    }
}

// No match
if (!$matched) {
    flash_and_redirect("❌ Face not recognized!");
}

// Cleanup
if (file_exists($tempImage)) unlink($tempImage);
?>
