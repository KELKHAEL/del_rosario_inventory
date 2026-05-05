<?php
// db.php - Database connection setup for XAMPP
$servername = "localhost";
$username = "root";       // Default XAMPP username
$password = "";           // Default XAMPP password is blank
$database = "del_rosario_inventory"; 

// Create the connection
$conn = new mysqli($servername, $username, $password, $database);

// Check the connection
if ($conn->connect_error) {
    die("Connection to MySQL failed: " . $conn->connect_error);
}
// If it connects successfully, this file will quietly run in the background.
?>