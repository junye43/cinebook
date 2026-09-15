<?php
// ============================================================
// Database connection settings
// Update these to match your phpMyAdmin / MySQL server setup
// ============================================================
$db_host = "localhost";
$db_user = "root";
$db_pass = "";          // set your MySQL/phpMyAdmin password here
$db_name = "cinebook_db";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
