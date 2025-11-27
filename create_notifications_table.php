<?php
/**
 * Quick script to create notifications table
 * Run this once: http://localhost/Capstone-3/create_notifications_table.php
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Create Notifications Table</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:green;font-weight:bold;}.error{color:red;font-weight:bold;}</style>";
echo "</head><body>";
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
            echo "<table border='1' cellpadding='5' style='border-collapse:collapse;width:100%;'>";
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
    } else {
        echo "<p class='error'>✗ Table was not found after creation</p>";
    }
} else {
    echo "<p class='error'>✗ Error creating notifications table: " . htmlspecialchars($conn->error) . "</p>";
}

echo "<hr>";
echo "<p><a href='admin/dashboard.php'>← Go to Admin Dashboard</a></p>";
echo "<p><a href='database/setup_notifications.php'>Or run full setup script</a></p>";
echo "</body></html>";

$conn->close();
?>

