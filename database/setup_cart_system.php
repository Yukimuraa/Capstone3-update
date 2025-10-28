<?php
// Setup script for the shopping cart system
require_once '../config/database.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Setup Shopping Cart System</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; margin: 10px 0; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>Shopping Cart System Setup</h1>
    <p>This script will create the necessary database tables for the multiple-item shopping cart system.</p>
";

$errors = [];
$success = [];

// Create cart table
$cart_table_sql = "
CREATE TABLE IF NOT EXISTS cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    inventory_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    size VARCHAR(10) NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, inventory_id, size)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($cart_table_sql)) {
    $success[] = "✓ Cart table created successfully";
} else {
    $errors[] = "✗ Error creating cart table: " . $conn->error;
}

// Check if batch_id column exists in orders table
$check_batch_id = $conn->query("SHOW COLUMNS FROM orders LIKE 'batch_id'");
if ($check_batch_id->num_rows == 0) {
    // Add batch_id column
    $alter_batch_sql = "ALTER TABLE orders ADD COLUMN batch_id VARCHAR(50) NULL AFTER order_id";
    if ($conn->query($alter_batch_sql)) {
        $success[] = "✓ Added batch_id column to orders table";
    } else {
        $errors[] = "✗ Error adding batch_id column: " . $conn->error;
    }
} else {
    $success[] = "✓ batch_id column already exists in orders table";
}

// Check if size column exists in orders table
$check_size = $conn->query("SHOW COLUMNS FROM orders LIKE 'size'");
if ($check_size->num_rows == 0) {
    // Add size column
    $alter_size_sql = "ALTER TABLE orders ADD COLUMN size VARCHAR(10) NULL AFTER quantity";
    if ($conn->query($alter_size_sql)) {
        $success[] = "✓ Added size column to orders table";
    } else {
        $errors[] = "✗ Error adding size column: " . $conn->error;
    }
} else {
    $success[] = "✓ size column already exists in orders table";
}

// Create index for batch_id
$create_index_sql = "CREATE INDEX IF NOT EXISTS idx_orders_batch ON orders(batch_id)";
if ($conn->query($create_index_sql)) {
    $success[] = "✓ Created index for batch_id";
} else {
    $errors[] = "✗ Error creating index: " . $conn->error;
}

// Display results
if (count($errors) > 0) {
    echo "<div class='error'><h3>Errors:</h3><ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul></div>";
}

if (count($success) > 0) {
    echo "<div class='success'><h3>Success:</h3><ul>";
    foreach ($success as $msg) {
        echo "<li>$msg</li>";
    }
    echo "</ul></div>";
}

if (count($errors) == 0) {
    echo "<div class='info'>
        <h3>Setup Complete!</h3>
        <p>The shopping cart system has been successfully set up. Users can now:</p>
        <ul>
            <li>Add multiple items to their cart</li>
            <li>Review and modify cart items before checkout</li>
            <li>Place batch orders with multiple items</li>
            <li>View batch receipts for all items in an order</li>
        </ul>
        <p><strong>Note:</strong> The cart feature is available for both students and staff members.</p>
        <p><a href='../student/inventory.php'>Go to Student Inventory</a> | <a href='../staff/inventory.php'>Go to Staff Inventory</a></p>
    </div>";
}

echo "</body></html>";

$conn->close();
?>


