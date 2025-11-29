<?php
/**
 * Script to update existing "Order Approved" notifications
 * to use receipt.php instead of cart.php
 * 
 * Run this once: http://localhost/Capstone-3/update_notification_links.php
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Update Notification Links</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;background:#f5f5f5;}";
echo ".container{background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
echo ".success{color:green;font-weight:bold;}.error{color:red;font-weight:bold;}";
echo "table{border-collapse:collapse;width:100%;margin:20px 0;}";
echo "th,td{padding:10px;text-align:left;border:1px solid #ddd;}";
echo "th{background:#1E40AF;color:white;}</style>";
echo "</head><body><div class='container'>";

echo "<h1>Updating Notification Links</h1>";

// Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
if (!$table_check || $table_check->num_rows == 0) {
    echo "<p class='error'>Notifications table does not exist!</p>";
    echo "</div></body></html>";
    exit();
}

// Get all "Order Approved" notifications with the old cart.php link
$query = "SELECT id, message, link FROM notifications WHERE title = 'Order Approved' AND link = 'student/cart.php'";
$result = $conn->query($query);

if (!$result) {
    echo "<p class='error'>Error querying notifications: " . $conn->error . "</p>";
    echo "</div></body></html>";
    exit();
}

$updated_count = 0;
$errors = [];

echo "<table>";
echo "<tr><th>ID</th><th>Old Link</th><th>New Link</th><th>Status</th></tr>";

while ($row = $result->fetch_assoc()) {
    $notification_id = $row['id'];
    $message = $row['message'];
    
    // Extract order ID from message
    // Message format: "Your order (Order ID: ORD-20251128-055958-5995-001) has been approved..."
    if (preg_match('/Order ID:\s*([A-Z0-9-]+)/i', $message, $matches)) {
        $order_id = $matches[1];
        $new_link = "student/receipt.php?order_id=" . urlencode($order_id);
        
        // Update the notification
        $update_stmt = $conn->prepare("UPDATE notifications SET link = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_link, $notification_id);
        
        if ($update_stmt->execute()) {
            $updated_count++;
            echo "<tr><td>{$notification_id}</td><td>student/cart.php</td><td>{$new_link}</td><td class='success'>✓ Updated</td></tr>";
        } else {
            $errors[] = "Failed to update notification ID {$notification_id}: " . $conn->error;
            echo "<tr><td>{$notification_id}</td><td>student/cart.php</td><td>{$new_link}</td><td class='error'>✗ Error</td></tr>";
        }
        $update_stmt->close();
    } else {
        $errors[] = "Could not extract order ID from notification ID {$notification_id}";
        echo "<tr><td>{$notification_id}</td><td>student/cart.php</td><td>N/A</td><td class='error'>✗ No Order ID found</td></tr>";
    }
}

echo "</table>";

echo "<h2>Summary</h2>";
echo "<p><strong>Total notifications found:</strong> " . $result->num_rows . "</p>";
echo "<p class='success'><strong>Successfully updated:</strong> {$updated_count}</p>";

if (!empty($errors)) {
    echo "<p class='error'><strong>Errors:</strong> " . count($errors) . "</p>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li class='error'>{$error}</li>";
    }
    echo "</ul>";
}

if ($updated_count > 0) {
    echo "<p class='success'>✓ All notification links have been updated successfully!</p>";
    echo "<p>You can now click on 'Order Approved' notifications and they will take you to the receipt page.</p>";
} else {
    echo "<p>No notifications needed updating, or all notifications already have the correct link.</p>";
}

echo "</div></body></html>";
?>


