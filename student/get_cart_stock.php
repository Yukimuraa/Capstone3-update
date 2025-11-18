<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is student
require_student();

header('Content-Type: application/json');

$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;

if ($item_id <= 0) {
    echo json_encode([]);
    exit();
}

// Get cart items for this specific item and current user
$cart_query = $conn->prepare("SELECT size, SUM(quantity) as total_quantity FROM cart WHERE user_id = ? AND inventory_id = ? GROUP BY size");
$cart_query->bind_param("ii", $_SESSION['user_id'], $item_id);
$cart_query->execute();
$cart_result = $cart_query->get_result();

$reserved_stock = [];
while ($row = $cart_result->fetch_assoc()) {
    // Use the actual size value (can be NULL for items without sizes)
    $size = $row['size'] ?? null;
    if ($size !== null) {
        if (!isset($reserved_stock[$size])) {
            $reserved_stock[$size] = 0;
        }
        $reserved_stock[$size] += intval($row['total_quantity']);
    }
}

// Get PENDING orders for this specific item from ALL users
// Note: Completed orders have already reduced the database stock, so we only need to count pending orders
// Stock is shared, so we need to check all pending orders, not just current user's
$orders_query = $conn->prepare("SELECT size, SUM(quantity) as total_quantity FROM orders WHERE inventory_id = ? AND status = 'pending' GROUP BY size");
$orders_query->bind_param("i", $item_id);
$orders_query->execute();
$orders_result = $orders_query->get_result();

while ($row = $orders_result->fetch_assoc()) {
    // Use the actual size value (can be NULL for items without sizes)
    $size = $row['size'] ?? null;
    if ($size !== null) {
        if (!isset($reserved_stock[$size])) {
            $reserved_stock[$size] = 0;
        }
        $reserved_stock[$size] += intval($row['total_quantity']);
    }
}

echo json_encode($reserved_stock);
?>

