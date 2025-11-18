<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is student
require_student();

header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get POST data
$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
$size = isset($_POST['size']) ? sanitize_input($_POST['size']) : null;

// Validate item ID
if ($item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit();
}

// Get item details
$stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ? AND in_stock = 1");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Item not found or out of stock']);
    exit();
}

$item = $result->fetch_assoc();

// Check size-specific stock if size is provided
$available_quantity = $item['quantity'];
if ($size && !empty($item['size_quantities'])) {
    $size_quantities = json_decode($item['size_quantities'], true);
    if (is_array($size_quantities) && isset($size_quantities[$size])) {
        $available_quantity = $size_quantities[$size];
        
        // Subtract items already in cart for this size (current user only)
        $cart_check = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as in_cart FROM cart WHERE user_id = ? AND inventory_id = ? AND size = ?");
        $cart_check->bind_param("iis", $_SESSION['user_id'], $item_id, $size);
        $cart_check->execute();
        $cart_result = $cart_check->get_result();
        $in_cart = $cart_result->fetch_assoc()['in_cart'];
        $available_quantity -= $in_cart;
        
        // Subtract items already in PENDING orders for this size from ALL users
        // Note: Completed orders have already reduced the database stock, so we only need to count pending orders
        // Stock is shared, so we need to check all pending orders, not just current user's
        $orders_check = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as in_orders FROM orders WHERE inventory_id = ? AND size = ? AND status = 'pending'");
        $orders_check->bind_param("is", $item_id, $size);
        $orders_check->execute();
        $orders_result = $orders_check->get_result();
        $in_orders = $orders_result->fetch_assoc()['in_orders'];
        $available_quantity -= $in_orders;
    }
}

// Check if requested quantity is available
if ($quantity <= 0 || $quantity > $available_quantity) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity. Available: ' . $available_quantity]);
    exit();
}

try {
    // Check if item already exists in cart (with same size if applicable)
    if ($size) {
        $check_stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND inventory_id = ? AND size = ?");
        $check_stmt->bind_param("iis", $_SESSION['user_id'], $item_id, $size);
    } else {
        $check_stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND inventory_id = ? AND size IS NULL");
        $check_stmt->bind_param("ii", $_SESSION['user_id'], $item_id);
    }
    
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing cart item
        $cart_item = $check_result->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + $quantity;
        
        // Check if new quantity exceeds available stock (consider size-specific stock and orders)
        $max_allowed = $item['quantity'];
        if ($size && !empty($item['size_quantities'])) {
            $size_quantities = json_decode($item['size_quantities'], true);
            if (is_array($size_quantities) && isset($size_quantities[$size])) {
                $max_allowed = $size_quantities[$size];
                
                // Subtract items already in PENDING orders for this size from ALL users
                // Note: Completed orders have already reduced the database stock
                $orders_check = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as in_orders FROM orders WHERE inventory_id = ? AND size = ? AND status = 'pending'");
                $orders_check->bind_param("is", $item_id, $size);
                $orders_check->execute();
                $orders_result = $orders_check->get_result();
                $in_orders = $orders_result->fetch_assoc()['in_orders'];
                $max_allowed -= $in_orders;
                
                // Subtract items already in cart for this size (current user only)
                // BUT exclude the current cart item being updated (we'll add it back with new quantity)
                $cart_check = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as in_cart FROM cart WHERE user_id = ? AND inventory_id = ? AND size = ? AND id != ?");
                $cart_check->bind_param("iisi", $_SESSION['user_id'], $item_id, $size, $cart_item['id']);
                $cart_check->execute();
                $cart_result = $cart_check->get_result();
                $in_cart = $cart_result->fetch_assoc()['in_cart'];
                $max_allowed -= $in_cart;
            }
        }
        
        if ($new_quantity > $max_allowed) {
            echo json_encode(['success' => false, 'message' => 'Total quantity would exceed available stock. Available: ' . $max_allowed]);
            exit();
        }
        
        $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $update_stmt->bind_param("ii", $new_quantity, $cart_item['id']);
        $update_stmt->execute();
        
        $message = 'Cart updated successfully';
    } else {
        // Insert new cart item
        $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, inventory_id, quantity, size) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("iiis", $_SESSION['user_id'], $item_id, $quantity, $size);
        $insert_stmt->execute();
        
        $message = 'Item added to cart successfully';
    }
    
    // Get updated cart count
    $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $count_stmt->bind_param("i", $_SESSION['user_id']);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $cart_count = $count_result->fetch_assoc()['count'];
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'cart_count' => $cart_count
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error adding to cart: ' . $e->getMessage()]);
}
?>



