<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Please <a href='login.php'>login</a> first");
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle add to cart
if (isset($_POST['add_item'])) {
    $item_id = intval($_POST['item_id']);
    $quantity = 1;
    
    // Check if item exists in cart
    $check = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND inventory_id = ? AND size IS NULL");
    $check->bind_param("ii", $user_id, $item_id);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        // Update quantity
        $row = $result->fetch_assoc();
        $new_qty = $row['quantity'] + 1;
        $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $update->bind_param("ii", $new_qty, $row['id']);
        $update->execute();
        $message = "Updated quantity to $new_qty";
    } else {
        // Insert new
        $insert = $conn->prepare("INSERT INTO cart (user_id, inventory_id, quantity) VALUES (?, ?, ?)");
        $insert->bind_param("iii", $user_id, $item_id, $quantity);
        if ($insert->execute()) {
            $message = "Item added to cart!";
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}

// Get available inventory
$inventory = $conn->query("SELECT * FROM inventory WHERE in_stock = 1 LIMIT 5");

// Get cart count
$cart_query = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
$cart_query->bind_param("i", $user_id);
$cart_query->execute();
$cart_count = $cart_query->get_result()->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Add Test</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .item { border: 1px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .btn { background: #10b981; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 5px; }
        .btn:hover { background: #059669; }
        .success { background: #d4edda; padding: 10px; margin: 10px 0; border-left: 4px solid #28a745; }
        .cart-badge { background: #fbbf24; color: #000; padding: 5px 10px; border-radius: 50%; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Quick Add to Cart Test</h1>
    
    <div style="background: #e3f2fd; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
        <strong>Logged in as:</strong> <?php echo $_SESSION['user_name']; ?> (ID: <?php echo $user_id; ?>)<br>
        <strong>Items in cart:</strong> <span class="cart-badge"><?php echo $cart_count; ?></span>
    </div>
    
    <?php if ($message): ?>
        <div class="success">✓ <?php echo $message; ?></div>
    <?php endif; ?>
    
    <h2>Available Items (Click to Add)</h2>
    
    <?php while ($item = $inventory->fetch_assoc()): ?>
        <div class="item">
            <strong><?php echo $item['name']; ?></strong><br>
            <small><?php echo $item['description']; ?></small><br>
            Price: ₱<?php echo number_format($item['price'], 2); ?> | 
            Available: <?php echo $item['quantity']; ?><br><br>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                <button type="submit" name="add_item" class="btn">
                    ➕ Add to Cart
                </button>
            </form>
        </div>
    <?php endwhile; ?>
    
    <hr>
    
    <h3>View Your Cart:</h3>
    <?php
    $cart_items = $conn->prepare("SELECT c.*, i.name, i.price FROM cart c JOIN inventory i ON c.inventory_id = i.id WHERE c.user_id = ?");
    $cart_items->bind_param("i", $user_id);
    $cart_items->execute();
    $items = $cart_items->get_result();
    
    if ($items->num_rows > 0):
    ?>
        <table border="1" cellpadding="10" style="border-collapse: collapse; width: 100%;">
            <tr style="background: #f3f4f6;">
                <th>Item</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
            <?php 
            $grand_total = 0;
            while ($item = $items->fetch_assoc()): 
                $total = $item['price'] * $item['quantity'];
                $grand_total += $total;
            ?>
                <tr>
                    <td><?php echo $item['name']; ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>₱<?php echo number_format($item['price'], 2); ?></td>
                    <td>₱<?php echo number_format($total, 2); ?></td>
                </tr>
            <?php endwhile; ?>
            <tr style="background: #f3f4f6; font-weight: bold;">
                <td colspan="3" align="right">TOTAL:</td>
                <td>₱<?php echo number_format($grand_total, 2); ?></td>
            </tr>
        </table>
        
        <br>
        <a href="student/cart.php" class="btn">Go to Shopping Cart Page</a>
    <?php else: ?>
        <p><em>Cart is empty. Add some items above!</em></p>
    <?php endif; ?>
    
    <hr>
    <p>
        <a href="student/inventory.php">Go to Student Inventory</a> | 
        <a href="student/cart.php">Go to Cart Page</a> | 
        <a href="test_cart.php">Run Debug Test</a>
    </p>
</body>
</html>


