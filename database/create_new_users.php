<?php
// Create new users table with different approach
require_once dirname(__DIR__) . '/config/database.php';

echo "<h2>Creating New Users Table</h2>";

// Try to create the table with a different name first
$create_users = "
CREATE TABLE IF NOT EXISTS user_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'staff', 'student', 'external') NOT NULL,
    organization VARCHAR(255) NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($create_users)) {
    echo "<p style='color: green;'>✓ User accounts table created successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating user accounts table: " . $conn->error . "</p>";
    exit;
}

// Insert sample users
$insert_users = "INSERT INTO user_accounts (name, email, password, user_type, organization, status) VALUES
('System Administrator', 'admin@chmsu.edu.ph', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, 'active'),
('BAO Staff', 'staff@chmsu.edu.ph', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, 'active'),
('Test Student', 'student@chmsu.edu.ph', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', NULL, 'active'),
('External User', 'external@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'external', 'Sample Organization', 'active')
ON DUPLICATE KEY UPDATE name=name";

if ($conn->query($insert_users)) {
    echo "<p style='color: green;'>✓ Sample users inserted successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error inserting users: " . $conn->error . "</p>";
}

// Verify the table works
$result = $conn->query("SELECT COUNT(*) as count FROM user_accounts");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p style='color: green;'>✓ User accounts table is working. Found " . $row['count'] . " users.</p>";
} else {
    echo "<p style='color: red;'>✗ Error querying user accounts table: " . $conn->error . "</p>";
}

echo "<hr>";
echo "<h3>Setup Complete!</h3>";
echo "<p style='color: green; font-weight: bold;'>User accounts table is now ready!</p>";

echo "<h4>Sample Login Credentials:</h4>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin@chmsu.edu.ph (password: admin123)</li>";
echo "<li><strong>Staff:</strong> staff@chmsu.edu.ph (password: admin123)</li>";
echo "<li><strong>Student:</strong> student@chmsu.edu.ph (password: admin123)</li>";
echo "<li><strong>External:</strong> external@example.com (password: admin123)</li>";
echo "</ul>";

echo "<p><a href='../login.php' style='background: #1E40AF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";

$conn->close();
?>

