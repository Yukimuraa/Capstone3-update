<?php
// Add Buses to Database - Fix "Available Buses /0" Error

require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Add Buses to System</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }";
echo "h1 { color: #333; }";
echo ".success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; color: #155724; }";
echo ".error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; color: #721c24; }";
echo ".info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 20px 0; color: #0c5460; }";
echo ".warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; color: #856404; }";
echo "table { width: 100%; border-collapse: collapse; background: white; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }";
echo "th { background: #4CAF50; color: white; padding: 12px; text-align: left; }";
echo "td { padding: 10px; border-bottom: 1px solid #ddd; }";
echo "tr:nth-child(even) { background: #f9f9f9; }";
echo ".badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }";
echo ".available { background: #d4edda; color: #155724; }";
echo ".booked { background: #fff3cd; color: #856404; }";
echo "</style>";
echo "</head><body>";

echo "<h1>🚌 Add Buses to Database</h1>";

// Check if buses table exists
$tables_check = $conn->query("SHOW TABLES LIKE 'buses'");
if ($tables_check->num_rows == 0) {
    echo "<div class='error'>";
    echo "<p><strong>❌ ERROR:</strong> The <code>buses</code> table does not exist!</p>";
    echo "<p>Please run the database setup first: <code>database/bus_tables.sql</code></p>";
    echo "</div>";
    exit;
}

// Check current buses
$current_buses = $conn->query("SELECT * FROM buses");
$current_count = $current_buses->num_rows;

echo "<div class='info'>";
echo "<p><strong>Current Buses in System:</strong> <span style='font-size: 24px; color: #0c5460;'>$current_count</span></p>";
echo "</div>";

if ($current_count > 0) {
    echo "<h2>Existing Buses:</h2>";
    echo "<table>";
    echo "<tr><th>Bus Number</th><th>Vehicle Type</th><th>Capacity</th><th>Status</th></tr>";
    
    $buses_list = $conn->query("SELECT * FROM buses ORDER BY bus_number");
    while ($bus = $buses_list->fetch_assoc()) {
        $status_class = $bus['status'] == 'available' ? 'available' : 'booked';
        echo "<tr>";
        echo "<td><strong>Bus " . htmlspecialchars($bus['bus_number']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($bus['vehicle_type']) . "</td>";
        echo "<td>" . $bus['capacity'] . " seats</td>";
        echo "<td><span class='badge $status_class'>" . ucfirst($bus['status']) . "</span></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='success'>";
    echo "<h3>✅ Buses Already Exist!</h3>";
    echo "<p>You have <strong>$current_count buses</strong> in the system. No need to add more.</p>";
    echo "<p><a href='admin/bus.php' style='color: #155724; font-weight: bold;'>Go to Bus Management →</a></p>";
    echo "</div>";
    
} else {
    echo "<div class='warning'>";
    echo "<h3>⚠️ No Buses Found!</h3>";
    echo "<p>Adding default buses to the system...</p>";
    echo "</div>";
    
    // Define buses to add
    $buses_to_add = [
        ['number' => '1', 'type' => 'Bus', 'capacity' => 50],
        ['number' => '2', 'type' => 'Bus', 'capacity' => 50],
        ['number' => '3', 'type' => 'Bus', 'capacity' => 50]
    ];
    
    $added_count = 0;
    $errors = [];
    
    foreach ($buses_to_add as $bus) {
        $stmt = $conn->prepare("INSERT INTO buses (bus_number, vehicle_type, capacity, status) VALUES (?, ?, ?, 'available')");
        $stmt->bind_param("ssi", $bus['number'], $bus['type'], $bus['capacity']);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Added <strong>Bus " . $bus['number'] . "</strong> (" . $bus['type'] . ", " . $bus['capacity'] . " seats)</p>";
            $added_count++;
        } else {
            $error_msg = "Error adding Bus " . $bus['number'] . ": " . $conn->error;
            echo "<p style='color: red;'>❌ " . $error_msg . "</p>";
            $errors[] = $error_msg;
        }
    }
    
    if ($added_count > 0) {
        echo "<div class='success'>";
        echo "<h3>✅ Success!</h3>";
        echo "<p><strong>$added_count buses</strong> have been added to the system!</p>";
        
        // Show the added buses
        echo "<h3>Added Buses:</h3>";
        echo "<table>";
        echo "<tr><th>Bus Number</th><th>Vehicle Type</th><th>Capacity</th><th>Status</th></tr>";
        
        $new_buses = $conn->query("SELECT * FROM buses ORDER BY bus_number");
        while ($bus = $new_buses->fetch_assoc()) {
            echo "<tr>";
            echo "<td><strong>Bus " . htmlspecialchars($bus['bus_number']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($bus['vehicle_type']) . "</td>";
            echo "<td>" . $bus['capacity'] . " seats</td>";
            echo "<td><span class='badge available'>" . ucfirst($bus['status']) . "</span></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<hr>";
        echo "<h3>🎉 All Done!</h3>";
        echo "<p><strong>Your bus system is now ready to use!</strong></p>";
        echo "<p>Students can now request buses and you'll see:</p>";
        echo "<ul>";
        echo "<li>✅ Admin page will show: <strong>Available Buses: 3/3</strong></li>";
        echo "<li>✅ Students can submit bus requests without errors</li>";
        echo "<li>✅ No more 'Invalid bus number' error</li>";
        echo "</ul>";
        
        echo "<p style='margin-top: 20px;'>";
        echo "<a href='admin/bus.php' style='display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Go to Bus Management →</a>";
        echo "</p>";
        echo "</div>";
    }
    
    if (!empty($errors)) {
        echo "<div class='error'>";
        echo "<h3>⚠️ Some Errors Occurred:</h3>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
}

$conn->close();

echo "</body></html>";
?>
































