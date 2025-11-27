<?php
// Fix missing phone and address columns in user_accounts table

require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Fix Profile Columns</title>";
echo "<style>body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }</style>";
echo "</head><body>";

echo "<h1>🔧 Fix Profile Columns</h1>";
echo "<p>Adding missing <code>phone</code> and <code>address</code> columns to user_accounts table...</p>";

// Check current columns
$columns_result = $conn->query("DESCRIBE user_accounts");
$existing_columns = [];
while ($col = $columns_result->fetch_assoc()) {
    $existing_columns[] = $col['Field'];
}

$changes_made = 0;

// Add phone column if missing
if (!in_array('phone', $existing_columns)) {
    echo "<p style='color: orange;'>⚠️ <strong>phone</strong> column is missing. Adding it now...</p>";
    
    $sql = "ALTER TABLE user_accounts ADD COLUMN phone VARCHAR(20) NULL AFTER organization";
    
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✅ <strong>phone</strong> column added successfully!</p>";
        $changes_made++;
    } else {
        echo "<p style='color: red;'>❌ Error adding phone column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: gray;'>✓ <strong>phone</strong> column already exists.</p>";
}

// Add address column if missing
if (!in_array('address', $existing_columns)) {
    echo "<p style='color: orange;'>⚠️ <strong>address</strong> column is missing. Adding it now...</p>";
    
    $sql = "ALTER TABLE user_accounts ADD COLUMN address TEXT NULL AFTER phone";
    
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✅ <strong>address</strong> column added successfully!</p>";
        $changes_made++;
    } else {
        echo "<p style='color: red;'>❌ Error adding address column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: gray;'>✓ <strong>address</strong> column already exists.</p>";
}

// Show final table structure
echo "<hr>";
echo "<h2>Current user_accounts Table Structure:</h2>";
echo "<table border='1' cellpadding='8' cellspacing='0' style='background: white; border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #4CAF50; color: white;'>";
echo "<th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";

$columns_result = $conn->query("DESCRIBE user_accounts");
while ($col = $columns_result->fetch_assoc()) {
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
    echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

if ($changes_made > 0) {
    echo "<hr>";
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>✅ Success!</h3>";
    echo "<p style='color: #155724;'>$changes_made column(s) were added to the user_accounts table.</p>";
    echo "<p style='color: #155724;'><strong>Your profile page should now work correctly!</strong></p>";
    echo "<p><a href='student/profile.php' style='color: #007bff;'>Go to Profile Page →</a></p>";
    echo "</div>";
} else {
    echo "<hr>";
    echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3 style='color: #0c5460; margin-top: 0;'>ℹ️ No Changes Needed</h3>";
    echo "<p style='color: #0c5460;'>All required columns already exist in the database.</p>";
    echo "</div>";
}

$conn->close();

echo "</body></html>";
?>

















































