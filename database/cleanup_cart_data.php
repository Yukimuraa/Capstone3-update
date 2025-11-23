<?php
/**
 * Cleanup script to normalize cart data
 * Converts empty string size values to NULL to prevent duplicate entry errors
 */
require_once '../config/database.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Cart Data Cleanup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; margin: 10px 0; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>Cart Data Cleanup</h1>
    <p>This script normalizes empty string size values to NULL in the cart table to prevent duplicate entry errors.</p>
";

$errors = [];
$success = [];

// Check for empty string sizes in cart
$check_query = "SELECT COUNT(*) as count FROM cart WHERE size = ''";
$result = $conn->query($check_query);
$row = $result->fetch_assoc();
$empty_string_count = $row['count'];

if ($empty_string_count > 0) {
    echo "<div class='info'>Found $empty_string_count cart items with empty string size values.</div>";
    
    // Update empty strings to NULL
    $update_query = "UPDATE cart SET size = NULL WHERE size = ''";
    if ($conn->query($update_query)) {
        $success[] = "✓ Successfully normalized $empty_string_count cart items (empty string → NULL)";
    } else {
        $errors[] = "✗ Error updating cart data: " . $conn->error;
    }
} else {
    $success[] = "✓ No empty string size values found in cart table";
}

// Check for empty string sizes in orders (if they exist)
$check_orders_query = "SHOW COLUMNS FROM orders LIKE 'size'";
$orders_has_size = $conn->query($check_orders_query);

if ($orders_has_size->num_rows > 0) {
    $check_orders_empty = "SELECT COUNT(*) as count FROM orders WHERE size = ''";
    $result = $conn->query($check_orders_empty);
    $row = $result->fetch_assoc();
    $orders_empty_count = $row['count'];
    
    if ($orders_empty_count > 0) {
        echo "<div class='info'>Found $orders_empty_count orders with empty string size values.</div>";
        
        // Update empty strings to NULL in orders
        $update_orders = "UPDATE orders SET size = NULL WHERE size = ''";
        if ($conn->query($update_orders)) {
            $success[] = "✓ Successfully normalized $orders_empty_count orders (empty string → NULL)";
        } else {
            $errors[] = "✗ Error updating orders data: " . $conn->error;
        }
    } else {
        $success[] = "✓ No empty string size values found in orders table";
    }
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
        <h3>Cleanup Complete!</h3>
        <p>All cart data has been normalized. The system will now:</p>
        <ul>
            <li>Store NULL instead of empty strings for items without sizes</li>
            <li>Prevent duplicate entry errors when adding items to cart</li>
            <li>Handle both NULL and empty strings when checking for existing cart items</li>
        </ul>
        <p><strong>Note:</strong> You can now safely add items to cart without encountering duplicate entry errors.</p>
        <p><a href='../student/inventory.php'>Go to Student Inventory</a> | <a href='../staff/inventory.php'>Go to Staff Inventory</a></p>
    </div>";
}

echo "</body></html>";

$conn->close();
?>

