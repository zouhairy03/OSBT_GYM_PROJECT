<?php
$servername = "localhost"; // Change this to your database server
$username = "root";        // Change this to your database username
$password = "root";            // Change this to your database password
$dbname = "gym_management"; // Change this to your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
