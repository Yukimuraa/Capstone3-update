<?php
// This is a one-time script to add the 'sizes' column to the inventory table

// Database connection
require_once 'config/database.php';

// Check if the sizes column already exists
$check_column = "SHOW COLUMNS FROM inventory LIKE 'sizes'";
$column_exists = $conn->query($check_column);

if ($column_exists->num_rows == 0) {
    // Add the sizes column to the inventory table
    $alter_query = "ALTER TABLE inventory ADD COLUMN sizes TEXT NULL AFTER image_path";
    
    if ($conn->query($alter_query)) {
        echo "SUCCESS: 'sizes' column has been added to the inventory table.";
    } else {
        echo "ERROR: Failed to add 'sizes' column: " . $conn->error;
    }
} else {
    echo "INFO: The 'sizes' column already exists in the inventory table.";
}

$conn->close();
?> 