<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "college_booking_sys";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
