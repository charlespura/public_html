<?php
// dbShift.php

$host = "localhost";      // Database host
$user = "hr3_databasemain";           // Your MySQL username hratier2
$pass = "opIU3pBa9U9#crCd";               // Your MySQL password
$db   = "hr3_databasemain"; // Database name

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Optional: set charset to avoid encoding issues
$conn->set_charset("utf8mb4");
?>
