<?php
require_once '../config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Create Notifications Table</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;background:#f5f5f5;}";
echo ".container{background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
echo ".success{color:green;font-weight:bold;}.error{color:red;font-weight:bold;}";
echo "table{border-collapse:collapse;width:100%;margin:20px 0;}";
echo "th,td{padding:10px;text-align:left;border:1px solid #ddd;}";
echo "th{background:#1E40AF;color:white;}</style>";
echo "</head><body><div class='container'>";

echo "<h1>Creating Notifications Table</h1>";

$create_notifications = "
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error', 'booking', 'order', 'request', 'system') DEFAULT 'info',
    link VARCHAR(255) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_accounts(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($create_notifications)) {
    echo "<p class='success'>✓ Notifications table created successfully!</p>";
    
    // Verify the table was created
    $result = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($result && $result->num_rows > 0) {
        echo "<p class='success'>✓ Table verified in database</p>";
        
        // Show table structure
        $structure = $conn->query("DESCRIBE notifications");
        if ($structure) {
            echo "<h2>Table Structure:</h2>";
            echo "<table>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            while ($row = $structure->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Count existing notifications
        $count_result = $conn->query("SELECT COUNT(*) as count FROM notifications");
        if ($count_result) {
            $count = $count_result->fetch_assoc()['count'];
            echo "<p><strong>Current notifications in table:</strong> $count</p>";
        }
    } else {
        echo "<p class='error'>✗ Table was not found after creation</p>";
    }
} else {
    echo "<p class='error'>✗ Error creating notifications table: " . htmlspecialchars($conn->error) . "</p>";
    
    // Check if table already exists
    $check = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($check && $check->num_rows > 0) {
        echo "<p class='success'>Note: Table already exists. You can proceed to use the notification system.</p>";
    }
}

echo "<hr>";
echo "<p><a href='../admin/dashboard.php' style='display:inline-block;padding:10px 20px;background:#1E40AF;color:white;text-decoration:none;border-radius:5px;'>← Go to Admin Dashboard</a></p>";
echo "</div></body></html>";

$conn->close();
?>

