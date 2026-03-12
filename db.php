<?php
$servername = "localhost";
$username = "root"; // your MySQL username
$password = "";     // your MySQL password
$dbname = "evoting_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
