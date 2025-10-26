<?php
/**
 * Migration Script: users table -> user_accounts table
 * This script helps migrate data if you have an existing "users" table
 */

require_once dirname(__DIR__) . '/config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Table Migration</title>";
echo "<style>body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }";
echo ".success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; }";
echo "ul { line-height: 2; } .button { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #1E40AF; color: white; text-decoration: none; border-radius: 5px; }";
echo "</style></head><body>";

echo "<h1>Users Table Migration</h1>";
echo "<p class='info'>This script will help you migrate from 'users' table to 'user_accounts' table.</p>";

// Check if users table exists
$users_table_check = $conn->query("SHOW TABLES LIKE 'users'");
$user_accounts_check = $conn->query("SHOW TABLES LIKE 'user_accounts'");

echo "<h2>Step 1: Check Tables</h2>";

if ($users_table_check && $users_table_check->num_rows > 0) {
    echo "<p class='warning'>✓ 'users' table exists</p>";
    $has_users = true;
    
    // Count records
    $count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
    echo "<p>Found $count record(s) in 'users' table</p>";
} else {
    echo "<p class='info'>ℹ 'users' table does not exist</p>";
    $has_users = false;
}

if ($user_accounts_check && $user_accounts_check->num_rows > 0) {
    echo "<p class='success'>✓ 'user_accounts' table exists</p>";
    $has_user_accounts = true;
    
    // Count records
    $count = $conn->query("SELECT COUNT(*) as count FROM user_accounts")->fetch_assoc()['count'];
    echo "<p>Found $count record(s) in 'user_accounts' table</p>";
} else {
    echo "<p class='error'>✗ 'user_accounts' table does not exist</p>";
    echo "<p>Please run the database setup first: <a href='setup_complete.php'>Run Setup</a></p>";
    $has_user_accounts = false;
}

if ($has_users && $has_user_accounts) {
    echo "<h2>Step 2: Migration</h2>";
    echo "<p class='warning'>⚠️ Warning: This will copy data from 'users' to 'user_accounts'</p>";
    
    if (isset($_GET['migrate']) && $_GET['migrate'] == 'yes') {
        echo "<h3>Migrating data...</h3>";
        
        // Get all users
        $users = $conn->query("SELECT * FROM users");
        
        $migrated = 0;
        $skipped = 0;
        $errors = 0;
        
        while ($user = $users->fetch_assoc()) {
            // Check if user already exists in user_accounts
            $check = $conn->prepare("SELECT id FROM user_accounts WHERE email = ?");
            $check->bind_param("s", $user['email']);
            $check->execute();
            $exists = $check->get_result();
            
            if ($exists->num_rows > 0) {
                echo "<p class='warning'>Skipped: {$user['email']} (already exists)</p>";
                $skipped++;
            } else {
                // Insert into user_accounts
                $stmt = $conn->prepare("INSERT INTO user_accounts (name, email, password, user_type, organization, profile_pic, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $name = $user['name'] ?? 'Unknown';
                $email = $user['email'];
                $password = $user['password'];
                $user_type = $user['user_type'] ?? 'student';
                $organization = $user['organization'] ?? NULL;
                $profile_pic = $user['profile_pic'] ?? NULL;
                $status = $user['status'] ?? 'active';
                $created_at = $user['created_at'] ?? date('Y-m-d H:i:s');
                $updated_at = $user['updated_at'] ?? date('Y-m-d H:i:s');
                
                $stmt->bind_param("sssssssss", $name, $email, $password, $user_type, $organization, $profile_pic, $status, $created_at, $updated_at);
                
                if ($stmt->execute()) {
                    echo "<p class='success'>✓ Migrated: {$email}</p>";
                    $migrated++;
                } else {
                    echo "<p class='error'>✗ Error migrating {$email}: " . $conn->error . "</p>";
                    $errors++;
                }
            }
        }
        
        echo "<hr>";
        echo "<h3>Migration Summary</h3>";
        echo "<p><strong>Migrated:</strong> $migrated</p>";
        echo "<p><strong>Skipped:</strong> $skipped</p>";
        echo "<p><strong>Errors:</strong> $errors</p>";
        
        if ($errors == 0 && $migrated > 0) {
            echo "<p class='success' style='font-size: 18px; font-weight: bold;'>✓ Migration completed successfully!</p>";
            echo "<p class='warning'>Note: The old 'users' table has NOT been deleted. You can rename or drop it manually if needed.</p>";
        }
        
        echo "<p><a href='../login.php' class='button'>Go to Login</a></p>";
        
    } else {
        echo "<p>Click the button below to start the migration:</p>";
        echo "<p><a href='?migrate=yes' class='button' style='background: #F59E0B;'>Start Migration</a></p>";
        echo "<p class='info'>Note: Existing records in user_accounts will not be overwritten.</p>";
    }
}

if (!$has_users && $has_user_accounts) {
    echo "<h2>Result</h2>";
    echo "<p class='success'>✓ You're already using the 'user_accounts' table. No migration needed!</p>";
    echo "<p><a href='../login.php' class='button'>Go to Login</a></p>";
}

$conn->close();

echo "</body></html>";
?>





