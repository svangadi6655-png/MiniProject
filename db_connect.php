<?php
// Define database connection parameters
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Default XAMPP username
define('DB_PASSWORD', '');     // Default XAMPP password
define('DB_NAME', 'wms');

// Attempt to connect to MySQL database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// echo "Connected successfully"; // Uncomment for testing connection
?>