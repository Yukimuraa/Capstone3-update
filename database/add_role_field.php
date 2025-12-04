<?php
// Add role field to user_accounts table to distinguish Student/Faculty/Staff
require_once dirname(__DIR__) . '/config/database.php';

echo "<h2>Adding Role Field to User Accounts</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { color: green; padding: 10px; background: #e8f5e9; border-left: 4px solid green; margin: 10px 0; }
    .error { color: red; padding: 10px; background: #ffebee; border-left: 4px solid red; margin: 10px 0; }
    .info { color: blue; padding: 10px; background: #e3f2fd; border-left: 4px solid blue; margin: 10px 0; }
</style>";

// Check if role column already exists
$check_column = "SHOW COLUMNS FROM user_accounts LIKE 'role'";
$result = $conn->query($check_column);

if ($result->num_rows > 0) {
    echo "<div class='info'>✓ Role column already exists in user_accounts table</div>";
} else {
    // Add role column
    $add_role = "ALTER TABLE user_accounts 
                 ADD COLUMN role ENUM('student', 'faculty', 'staff') NULL AFTER user_type";
    
    if ($conn->query($add_role)) {
        echo "<div class='success'>✓ Role column added successfully!</div>";
        
        // Set default role for existing users based on user_type
        // Users with user_type='student' get role='student' by default
        $update_existing = "UPDATE user_accounts 
                           SET role = 'student' 
                           WHERE user_type = 'student' AND role IS NULL";
        
        if ($conn->query($update_existing)) {
            $affected = $conn->affected_rows;
            echo "<div class='success'>✓ Updated $affected existing student users with default role='student'</div>";
        }
        
        // Users with user_type='staff' get role='staff'
        $update_staff = "UPDATE user_accounts 
                        SET role = 'staff' 
                        WHERE user_type = 'staff' AND role IS NULL";
        
        if ($conn->query($update_staff)) {
            $affected = $conn->affected_rows;
            if ($affected > 0) {
                echo "<div class='success'>✓ Updated $affected existing staff users with role='staff'</div>";
            }
        }
        
    } else {
        echo "<div class='error'>✗ Error adding role column: " . $conn->error . "</div>";
    }
}

// Show current structure
echo "<h3>Current user_accounts structure:</h3>";
$structure = $conn->query("DESCRIBE user_accounts");
echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; background: white;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = $structure->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><a href='../admin/users.php'>Go to User Management</a>";


