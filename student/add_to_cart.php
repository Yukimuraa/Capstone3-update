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

// Check if requested quantity is available
if ($quantity <= 0 || $quantity > $item['quantity']) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity. Available: ' . $item['quantity']]);
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
        
        // Check if new quantity exceeds available stock
        if ($new_quantity > $item['quantity']) {
            echo json_encode(['success' => false, 'message' => 'Total quantity would exceed available stock']);
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



