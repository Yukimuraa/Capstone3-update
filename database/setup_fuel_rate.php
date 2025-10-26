<?php
// Initialize fuel rate setting for the bus management system
require_once '../config/database.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Setup Fuel Rate</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        h1 { color: #333; }
        .message { 
            padding: 10px; 
            margin: 10px 0; 
            border-radius: 4px;
            border-left: 4px solid;
        }
        .message.success { background: #d4edda; border-color: #28a745; }
        .message.error { background: #f8d7da; border-color: #dc3545; }
        .message.info { background: #d1ecf1; border-color: #17a2b8; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🚌 Setup Fuel Rate Settings</h1>";

try {
    // Create bus_settings table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS bus_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(50) UNIQUE NOT NULL,
        setting_value VARCHAR(255) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table_sql)) {
        echo "<div class='message success'>✓ Bus settings table created/verified successfully</div>";
    } else {
        throw new Exception("Error creating table: " . $conn->error);
    }
    
    // Check if fuel_rate already exists
    $check_query = "SELECT * FROM bus_settings WHERE setting_key = 'fuel_rate'";
    $result = $conn->query($check_query);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "<div class='message info'>ℹ Fuel rate already exists: ₱" . number_format($row['setting_value'], 2) . " per liter</div>";
        echo "<div class='message info'>Last updated: " . $row['updated_at'] . "</div>";
    } else {
        // Insert default fuel rate
        $default_fuel_rate = 70.00;
        $insert_sql = "INSERT INTO bus_settings (setting_key, setting_value) VALUES ('fuel_rate', ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("d", $default_fuel_rate);
        
        if ($stmt->execute()) {
            echo "<div class='message success'>✓ Default fuel rate set to ₱" . number_format($default_fuel_rate, 2) . " per liter</div>";
        } else {
            throw new Exception("Error setting fuel rate: " . $conn->error);
        }
    }
    
    echo "<div class='message success'><strong>✓ Setup Complete!</strong></div>";
    echo "<p>You can now manage the fuel rate from the Admin Bus Management page.</p>";
    echo "<p><a href='../admin/bus.php?tab=fuel'>Go to Fuel Rate Settings</a></p>";
    
} catch (Exception $e) {
    echo "<div class='message error'>✗ Error: " . $e->getMessage() . "</div>";
}

echo "    </div>
</body>
</html>";

$conn->close();
?>

