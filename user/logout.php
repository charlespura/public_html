



<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>



<?php
// Start the session
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Optional: clear the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Define base URL
$baseURL = '/public_html';

// Redirect to login page using base URL
header("Location: {$baseURL}/index.php");
exit;
