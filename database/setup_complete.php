<?php
/**
 * Complete Database Setup Script
 * This script creates all necessary tables for the CHMSU BAO System
 */

require_once dirname(__DIR__) . '/config/database.php';

// Start HTML output
echo "<!DOCTYPE html>";
echo "<html lang='en'><head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Complete Database Setup - CHMSU BAO</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; background: #f5f5f5; }";
echo ".container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo "h1 { color: #1E40AF; border-bottom: 3px solid #1E40AF; padding-bottom: 10px; }";
echo "h2 { color: #059669; margin-top: 30px; }";
echo "h3 { color: #374151; }";
echo ".success { color: #059669; font-weight: bold; }";
echo ".error { color: #DC2626; font-weight: bold; }";
echo ".info { color: #3B82F6; }";
echo ".warning { color: #F59E0B; }";
echo "table { width: 100%; border-collapse: collapse; margin: 20px 0; }";
echo "table th, table td { padding: 10px; text-align: left; border: 1px solid #ddd; }";
echo "table th { background: #1E40AF; color: white; }";
echo "table tr:nth-child(even) { background: #f9f9f9; }";
echo "ul { line-height: 2; }";
echo ".button-group { margin-top: 30px; }";
echo ".button { display: inline-block; margin: 10px 10px 10px 0; padding: 12px 24px; background: #1E40AF; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }";
echo ".button:hover { background: #1E3A8A; }";
echo ".button.success { background: #059669; }";
echo ".button.success:hover { background: #047857; }";
echo ".progress { background: #E5E7EB; border-radius: 5px; height: 30px; margin: 20px 0; }";
echo ".progress-bar { background: #059669; height: 100%; border-radius: 5px; text-align: center; line-height: 30px; color: white; font-weight: bold; transition: width 0.3s; }";
echo ".card { background: #F3F4F6; padding: 15px; border-radius: 5px; margin: 15px 0; }";
echo ".status-icon { font-size: 20px; margin-right: 10px; }";
echo "</style>";
echo "</head><body>";
echo "<div class='container'>";

echo "<h1>🚀 Complete Database Setup</h1>";
echo "<p class='info'>Setting up all tables for CHMSU Business Affairs Office System...</p>";

// Read the SQL file
$sql_file = __DIR__ . '/complete_database_setup.sql';
if (!file_exists($sql_file)) {
    echo "<p class='error'>✗ Error: SQL file not found at $sql_file</p>";
    echo "</div></body></html>";
    exit;
}

$sql = file_get_contents($sql_file);

// Split the SQL into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success_count = 0;
$error_count = 0;
$errors = [];
$created_tables = [];
$total_statements = count($statements);

echo "<div class='progress'>";
echo "<div class='progress-bar' id='progressBar'>0%</div>";
echo "</div>";

echo "<h2>📋 Execution Log</h2>";
echo "<div id='log'>";

$counter = 0;
foreach ($statements as $statement) {
    $counter++;
    $statement = trim($statement);
    
    // Skip empty statements and comments
    if (empty($statement) || preg_match('/^--/', $statement)) {
        continue;
    }
    
    // Execute the statement
    if ($conn->query($statement)) {
        $success_count++;
        
        // Check if this is a CREATE TABLE statement
        if (preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/i', $statement, $matches)) {
            $table_name = $matches[1];
            $created_tables[] = $table_name;
            echo "<p class='success'><span class='status-icon'>✓</span>Created table: <strong>$table_name</strong></p>";
        } else if (preg_match('/CREATE INDEX/i', $statement)) {
            echo "<p class='success'><span class='status-icon'>✓</span>Created index</p>";
        } else if (preg_match('/INSERT INTO (\w+)/i', $statement, $matches)) {
            $table_name = $matches[1];
            echo "<p class='success'><span class='status-icon'>✓</span>Inserted data into: <strong>$table_name</strong></p>";
        }
    } else {
        $error_count++;
        $error_msg = $conn->error;
        $errors[] = $error_msg;
        
        // Don't treat duplicate key errors as critical
        if (strpos($error_msg, 'Duplicate entry') !== false) {
            echo "<p class='warning'><span class='status-icon'>⚠</span>Warning: " . htmlspecialchars($error_msg) . "</p>";
        } else {
            echo "<p class='error'><span class='status-icon'>✗</span>Error: " . htmlspecialchars($error_msg) . "</p>";
        }
    }
    
    // Update progress
    $progress = round(($counter / $total_statements) * 100);
    echo "<script>document.getElementById('progressBar').style.width = '{$progress}%'; document.getElementById('progressBar').textContent = '{$progress}%';</script>";
    flush();
}

echo "</div>";

// Summary
echo "<hr>";
echo "<h2>📊 Setup Summary</h2>";
echo "<div class='card'>";
echo "<p><strong>Total Statements Processed:</strong> $total_statements</p>";
echo "<p class='success'><strong>Successful:</strong> $success_count</p>";
echo "<p class='error'><strong>Failed:</strong> $error_count</p>";
echo "</div>";

// Verify tables
echo "<h2>🗂️ Database Tables</h2>";
$result = $conn->query("SHOW TABLES");
if ($result) {
    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    
    echo "<div class='card'>";
    echo "<p><strong>Total tables in database:</strong> " . count($tables) . "</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        // Count records
        $count_result = $conn->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $count_result ? $count_result->fetch_assoc()['count'] : 0;
        echo "<li><strong>$table</strong> ($count records)</li>";
    }
    echo "</ul>";
    echo "</div>";
} else {
    echo "<p class='error'>✗ Could not retrieve table list</p>";
}

// Check critical tables
echo "<h2>✅ Critical Tables Check</h2>";
$critical_tables = ['user_accounts', 'inventory', 'orders', 'buses', 'bus_schedules', 'facilities', 'bookings'];
$all_critical_exist = true;

echo "<table>";
echo "<tr><th>Table Name</th><th>Status</th><th>Records</th></tr>";
foreach ($critical_tables as $table) {
    $exists = $conn->query("SHOW TABLES LIKE '$table'");
    if ($exists && $exists->num_rows > 0) {
        $count_result = $conn->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $count_result ? $count_result->fetch_assoc()['count'] : 0;
        echo "<tr><td>$table</td><td class='success'>✓ Exists</td><td>$count</td></tr>";
    } else {
        echo "<tr><td>$table</td><td class='error'>✗ Missing</td><td>-</td></tr>";
        $all_critical_exist = false;
    }
}
echo "</table>";

// Sample credentials
if ($all_critical_exist) {
    echo "<h2>🔐 Sample Login Credentials</h2>";
    echo "<div class='card'>";
    echo "<p class='warning'>⚠️ All sample accounts use password: <strong>admin123</strong></p>";
    echo "<table>";
    echo "<tr><th>User Type</th><th>Email</th><th>Description</th></tr>";
    echo "<tr><td>Admin</td><td>admin@chmsu.edu.ph</td><td>System Administrator</td></tr>";
    echo "<tr><td>Staff</td><td>staff@chmsu.edu.ph</td><td>BAO Staff Member</td></tr>";
    echo "<tr><td>Student</td><td>student@chmsu.edu.ph</td><td>Test Student Account</td></tr>";
    echo "<tr><td>External</td><td>external@example.com</td><td>External Organization User</td></tr>";
    echo "</table>";
    echo "</div>";
    
    echo "<h2>🎉 Setup Complete!</h2>";
    echo "<p class='success' style='font-size: 18px;'>✓ All tables have been created successfully! Your system is ready to use.</p>";
    
    echo "<div class='button-group'>";
    echo "<a href='../login.php' class='button'>Go to Login Page</a>";
    echo "<a href='../register.php' class='button success'>Register New Account</a>";
    echo "</div>";
} else {
    echo "<h2>⚠️ Setup Incomplete</h2>";
    echo "<p class='error'>Some critical tables are missing. Please check the errors above and try again.</p>";
    echo "<div class='button-group'>";
    echo "<a href='setup_complete.php' class='button'>Retry Setup</a>";
    echo "</div>";
}

// Show errors if any
if (!empty($errors) && count($errors) > 0) {
    echo "<h2>❌ Errors Encountered</h2>";
    echo "<div class='card'>";
    echo "<ul>";
    foreach ($errors as $error) {
        // Skip duplicate entry errors
        if (strpos($error, 'Duplicate entry') === false) {
            echo "<li class='error'>" . htmlspecialchars($error) . "</li>";
        }
    }
    echo "</ul>";
    echo "</div>";
}

$conn->close();

echo "</div>";
echo "</body></html>";
?>











































