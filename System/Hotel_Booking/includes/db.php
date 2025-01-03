<?php
// Database Configuration
$servername = "localhost"; // Server name or IP address
$username = "root";        // Database username
$password = "";            // Database password (default is empty for XAMPP)
$dbname = "TSC_Hotel_Booking"; // Name of your database

// Create Connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check Connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Success Message (Optional, for debugging)
// echo "Connected successfully";
?>
