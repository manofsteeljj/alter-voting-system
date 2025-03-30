<?php
$servername = "localhost";
$username = "root";  // Change if needed
$password = "mary";  // Change if needed
$dbname = "voting_system1";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
