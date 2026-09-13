<?php
$host = "sql202.infinityfree.com";
$username = "if0_42711587";
$password = "Your_password"";
$database = "if0_42711587_jobtracker";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
