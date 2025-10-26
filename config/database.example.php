<?php
/**
 * Database Configuration Template
 * 
 * INSTRUCTIONS:
 * 1. Copy this file and rename it to: database.php
 * 2. Update the values below with your actual database credentials
 * 3. Never commit database.php to Git (it's in .gitignore)
 */

// Database connection settings
$servername = "localhost";           // Database server (usually "localhost")
$username = "root";                  // Database username
$password = "";                      // Database password
$dbname = "capstone3";              // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Optional: Set timezone
date_default_timezone_set('Asia/Manila');
?>

