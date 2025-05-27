
<?php
// Database configuration
$host = 'localhost';
$username = 'root';  // Your database username
$password = '';      // Your database password
$database = 'bauapp_db';  // Your database name

// Create connection
$connection = new mysqli($host, $username, $password, $database);

// Check connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// Set charset to utf8
$connection->set_charset("utf8");
?>