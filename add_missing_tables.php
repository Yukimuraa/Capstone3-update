<?php
/**
 * Add Missing Tables Script
 * This script will add any tables that are missing from your database
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Add Missing Tables</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' rel='stylesheet'>";
echo "</head><body class='bg-gray-900 text-white p-8'>";

echo "<div class='max-w-4xl mx-auto bg-gray-800 rounded-lg shadow-2xl p-8'>";
echo "<h1 class='text-3xl font-bold text-green-400 mb-6'>🔧 Add Missing Tables</h1>";

// First, check what tables exist
$existing_tables = [];
$result = $conn->query("SHOW TABLES");
if ($result) {
    while ($row = $result->fetch_array()) {
        $existing_tables[] = $row[0];
    }
}

echo "<div class='mb-6 p-4 bg-gray-700 rounded'>";
echo "<p class='text-gray-300'>Current tables in database: <strong class='text-green-400'>" . count($existing_tables) . "</strong></p>";
echo "</div>";

$tables_added = 0;
$tables_skipped = 0;
$errors = [];

// Check and add user_accounts table
if (!in_array('user_accounts', $existing_tables)) {
    echo "<p class='text-yellow-400'>Adding user_accounts table...</p>";
    $sql = "CREATE TABLE user_accounts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        user_type ENUM('admin', 'staff', 'student', 'external') NOT NULL,
        organization VARCHAR(255) NULL,
        profile_pic VARCHAR(255) NULL,
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql)) {
        echo "<p class='text-green-400'>✓ user_accounts table created!</p>";
        $tables_added++;
        
        // Add sample users
        $insert = "INSERT INTO user_accounts (name, email, password, user_type, organization, status) VALUES
        ('System Administrator', 'admin@chmsu.edu.ph', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, 'active'),
        ('BAO Staff', 'staff@chmsu.edu.ph', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, 'active'),
        ('Test Student', 'student@chmsu.edu.ph', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', NULL, 'active'),
        ('External User', 'external@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'external', 'Sample Organization', 'active')
        ON DUPLICATE KEY UPDATE name=name";
        
        if ($conn->query($insert)) {
            echo "<p class='text-green-400'>✓ Sample users added!</p>";
        }
    } else {
        echo "<p class='text-red-400'>✗ Error: " . $conn->error . "</p>";
        $errors[] = "user_accounts: " . $conn->error;
    }
} else {
    echo "<p class='text-gray-400'>• user_accounts table already exists</p>";
    $tables_skipped++;
}

// Check and add password_resets table
if (!in_array('password_resets', $existing_tables)) {
    echo "<p class='text-yellow-400'>Adding password_resets table...</p>";
    $sql = "CREATE TABLE password_resets (
        id INT PRIMARY KEY AUTO_INCREMENT,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_email (email)
    )";
    
    if ($conn->query($sql)) {
        echo "<p class='text-green-400'>✓ password_resets table created!</p>";
        $tables_added++;
    } else {
        echo "<p class='text-red-400'>✗ Error: " . $conn->error . "</p>";
        $errors[] = "password_resets: " . $conn->error;
    }
} else {
    echo "<p class='text-gray-400'>• password_resets table already exists</p>";
    $tables_skipped++;
}

// Check user_accounts for missing columns
if (in_array('user_accounts', $existing_tables)) {
    echo "<div class='mt-6'>";
    echo "<h3 class='text-xl font-bold text-blue-400 mb-3'>Checking user_accounts columns...</h3>";
    
    $columns_result = $conn->query("DESCRIBE user_accounts");
    $existing_columns = [];
    while ($col = $columns_result->fetch_assoc()) {
        $existing_columns[] = $col['Field'];
    }
    
    // Add organization column if missing
    if (!in_array('organization', $existing_columns)) {
        echo "<p class='text-yellow-400'>Adding organization column...</p>";
        if ($conn->query("ALTER TABLE user_accounts ADD COLUMN organization VARCHAR(255) NULL AFTER user_type")) {
            echo "<p class='text-green-400'>✓ organization column added!</p>";
        } else {
            echo "<p class='text-red-400'>✗ Error: " . $conn->error . "</p>";
        }
    } else {
        echo "<p class='text-gray-400'>• organization column exists</p>";
    }
    
    // Add profile_pic column if missing
    if (!in_array('profile_pic', $existing_columns)) {
        echo "<p class='text-yellow-400'>Adding profile_pic column...</p>";
        if ($conn->query("ALTER TABLE user_accounts ADD COLUMN profile_pic VARCHAR(255) NULL AFTER organization")) {
            echo "<p class='text-green-400'>✓ profile_pic column added!</p>";
        } else {
            echo "<p class='text-red-400'>✗ Error: " . $conn->error . "</p>";
        }
    } else {
        echo "<p class='text-gray-400'>• profile_pic column exists</p>";
    }
    
    echo "</div>";
}

// Check if request_comments exists but requests doesn't
if (in_array('request_comments', $existing_tables) && !in_array('requests', $existing_tables)) {
    echo "<p class='text-yellow-400'>Adding requests table...</p>";
    $sql = "CREATE TABLE requests (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        user_type ENUM('student', 'admin', 'staff', 'external') NOT NULL,
        request_type VARCHAR(100) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
        admin_notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES user_accounts(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql)) {
        echo "<p class='text-green-400'>✓ requests table created!</p>";
        $tables_added++;
    } else {
        echo "<p class='text-red-400'>✗ Error: " . $conn->error . "</p>";
        $errors[] = "requests: " . $conn->error;
    }
}

// Summary
echo "<hr class='my-6 border-gray-600'>";
echo "<div class='p-6 bg-gray-700 rounded-lg'>";
echo "<h2 class='text-2xl font-bold text-green-400 mb-4'>Summary</h2>";
echo "<p class='text-lg'><strong>Tables Added:</strong> <span class='text-green-400'>$tables_added</span></p>";
echo "<p class='text-lg'><strong>Tables Skipped:</strong> <span class='text-gray-400'>$tables_skipped</span></p>";
echo "<p class='text-lg'><strong>Errors:</strong> <span class='text-red-400'>" . count($errors) . "</span></p>";

if (count($errors) > 0) {
    echo "<div class='mt-4 p-4 bg-red-900 rounded'>";
    echo "<p class='font-bold text-red-300'>Errors encountered:</p>";
    echo "<ul class='list-disc list-inside mt-2'>";
    foreach ($errors as $error) {
        echo "<li class='text-red-300'>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
    echo "</div>";
}

if ($tables_added > 0 && count($errors) == 0) {
    echo "<div class='mt-4 p-4 bg-green-900 rounded'>";
    echo "<p class='text-green-300 font-bold text-lg'>✓ All missing tables have been added successfully!</p>";
    echo "</div>";
}

echo "</div>";

// Action buttons
echo "<div class='mt-8 flex flex-wrap gap-4'>";
echo "<a href='check_database.php' class='bg-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-700'>📊 Check Database Status</a>";
echo "<a href='test_database.php' class='bg-purple-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-purple-700'>🔍 Run Tests</a>";
echo "<a href='login.php' class='bg-green-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-green-700'>🔐 Go to Login</a>";
echo "</div>";

// Sample credentials
if ($tables_added > 0) {
    echo "<div class='mt-8 p-6 bg-blue-900 rounded-lg'>";
    echo "<h3 class='text-xl font-bold text-blue-300 mb-3'>🔐 Sample Login Credentials</h3>";
    echo "<p class='text-gray-300 mb-2'>Password for all accounts: <code class='bg-gray-700 px-2 py-1 rounded'>admin123</code></p>";
    echo "<ul class='space-y-1'>";
    echo "<li class='text-gray-300'>• <strong>Admin:</strong> admin@chmsu.edu.ph</li>";
    echo "<li class='text-gray-300'>• <strong>Staff:</strong> staff@chmsu.edu.ph</li>";
    echo "<li class='text-gray-300'>• <strong>Student:</strong> student@chmsu.edu.ph</li>";
    echo "<li class='text-gray-300'>• <strong>External:</strong> external@example.com</li>";
    echo "</ul>";
    echo "</div>";
}

$conn->close();

echo "</div>";
echo "</body></html>";
?>











































