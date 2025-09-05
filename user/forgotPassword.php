<?php
session_start();
include __DIR__ . '/../dbconnection/mainDB.php';
$FIREBASE_API_KEY = "AIzaSyCQg9yf_oWKyDAE_WApgRnG3q-BEDL6bSc";

$message = '';
$messageType = '';
$showResetForm = false;

// Step 1: Send password reset email
if (isset($_POST['send_reset'])) {
    $email = trim($_POST['email'] ?? '');

    if (!$email) {
        $message = "❌ Please enter your email.";
        $messageType = "error";
    } else {
        // Check if user exists locally
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email=? AND is_active=1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $message = "❌ No active user found with that email.";
            $messageType = "error";
        } else {
            // Firebase REST API: send password reset email
            $payload = json_encode([
                "requestType" => "PASSWORD_RESET",
                "email" => $email
            ]);

            $ch = curl_init("https://identitytoolkit.googleapis.com/v1/accounts:sendOobCode?key=$FIREBASE_API_KEY");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

            $response = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if (isset($response['error'])) {
                $message = "⚠️ Firebase error: " . $response['error']['message'];
                $messageType = "error";
            } else {
                $message = "✅ Reset link sent! Check your email and follow the link to reset your password.";
                $messageType = "success";
            }
        }
    }
}

// Step 2: Handle password reset via Firebase OOB code
if (isset($_POST['reset_password'])) {
    $oobCode = $_POST['oobCode'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!$oobCode || !$newPassword || !$confirmPassword) {
        $message = "❌ All fields are required.";
        $messageType = "error";
    } elseif ($newPassword !== $confirmPassword) {
        $message = "❌ Passwords do not match.";
        $messageType = "error";
    } elseif (strlen($newPassword) < 8) {
        $message = "❌ Password must be at least 8 characters.";
        $messageType = "error";
    } else {
        // 🔹 Update password in Firebase
        $payload = json_encode([
            "oobCode" => $oobCode,
            "newPassword" => $newPassword
        ]);

        $ch = curl_init("https://identitytoolkit.googleapis.com/v1/accounts:resetPassword?key=$FIREBASE_API_KEY");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (isset($response['error'])) {
            $message = "⚠️ Firebase error: " . $response['error']['message'];
            $messageType = "error";
        } else {
            $email = $response['email'] ?? null;

            // 🔹 Update local password hash
            if ($email) {
                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET password_hash=? WHERE email=?");
                $stmt->bind_param("ss", $newHash, $email);
                $stmt->execute();
                $stmt->close();

                $message = "✅ Password updated successfully! You can now login.";
                $messageType = "success";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
<div class="bg-white p-8 rounded shadow-md w-96">
  <h2 class="text-xl font-bold mb-4">Forgot Password</h2>
  <?php if ($message): ?>
    <div class="<?= $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> p-3 rounded mb-4">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['oobCode'])): ?>
    <!-- Password reset form -->
    <form method="POST">
      <input type="hidden" name="oobCode" value="<?= htmlspecialchars($_GET['oobCode']) ?>">
      <label class="block mb-2">New Password:</label>
      <input type="password" name="new_password" class="w-full border p-2 rounded mb-4" required>
      <label class="block mb-2">Confirm Password:</label>
      <input type="password" name="confirm_password" class="w-full border p-2 rounded mb-4" required>
      <button type="submit" name="reset_password" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">
        Reset Password
      </button>
    </form>
  <?php else: ?>
    <!-- Send reset link form -->
    <form method="POST">
      <label class="block mb-2">Enter your email:</label>
      <input type="email" name="email" class="w-full border p-2 rounded mb-4" placeholder="you@example.com" required>
      <button type="submit" name="send_reset" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">
        Send Reset Link
      </button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
