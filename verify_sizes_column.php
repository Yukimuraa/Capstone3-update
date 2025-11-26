<?php
// This script verifies and adds the 'sizes' column to the inventory table if needed

require_once 'config/database.php';

echo "<h2>Inventory Table Column Verification</h2>";

// Check if the sizes column already exists
$check_column = "SHOW COLUMNS FROM inventory LIKE 'sizes'";
$column_exists = $conn->query($check_column);

if ($column_exists->num_rows == 0) {
    echo "<p style='color: orange;'>⚠️ WARNING: 'sizes' column does NOT exist in the inventory table.</p>";
    echo "<p>Adding the 'sizes' column now...</p>";
    
    // Add the sizes column to the inventory table
    $alter_query = "ALTER TABLE inventory ADD COLUMN sizes TEXT NULL AFTER image_path";
    
    if ($conn->query($alter_query)) {
        echo "<p style='color: green;'>✅ SUCCESS: 'sizes' column has been added to the inventory table.</p>";
    } else {
        echo "<p style='color: red;'>❌ ERROR: Failed to add 'sizes' column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✅ INFO: The 'sizes' column already exists in the inventory table.</p>";
}

// Show current inventory table structure
echo "<h3>Current Inventory Table Structure:</h3>";
$table_structure = "SHOW COLUMNS FROM inventory";
$result = $conn->query($table_structure);

if ($result) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p style='color: red;'>Error fetching table structure: " . $conn->error . "</p>";
}

$conn->close();
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 900px;
        margin: 50px auto;
        padding: 20px;
        background-color: #f5f5f5;
    }
    h2 {
        color: #333;
        border-bottom: 2px solid #4CAF50;
        padding-bottom: 10px;
    }
    h3 {
        color: #555;
        margin-top: 30px;
    }
    table {
        background-color: white;
        border-collapse: collapse;
        width: 100%;
        margin-top: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    th {
        background-color: #4CAF50;
        color: white;
        padding: 12px;
        text-align: left;
    }
    td {
        padding: 10px;
    }
    tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    p {
        padding: 10px;
        margin: 10px 0;
        border-radius: 4px;
        background-color: white;
    }
</style>













































