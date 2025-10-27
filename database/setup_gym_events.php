<?php
// Setup script for gym event blocking system
require_once '../config/database.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Setup Gym Event Blocking System</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Setup Gym Event Blocking System</h1>";

// Read SQL file
$sql_file = __DIR__ . '/gym_event_blocking.sql';
if (!file_exists($sql_file)) {
    echo "<div class='error'>SQL file not found: $sql_file</div>";
    exit;
}

$sql = file_get_contents($sql_file);

// Split into individual statements
$statements = array_filter(
    array_map('trim', 
    preg_split('/;(\s*--.*)?(\n|$)/m', $sql)),
    function($stmt) {
        return !empty($stmt) && 
               !preg_match('/^--/', $stmt) && 
               strlen(trim($stmt)) > 5;
    }
);

$success_count = 0;
$error_count = 0;

echo "<div class='info'>Found " . count($statements) . " SQL statements to execute</div>";

foreach ($statements as $index => $statement) {
    $statement = trim($statement);
    if (empty($statement)) continue;
    
    // Show what we're executing
    $preview = substr($statement, 0, 100) . (strlen($statement) > 100 ? '...' : '');
    echo "<p><strong>Statement " . ($index + 1) . ":</strong> " . htmlspecialchars($preview) . "</p>";
    
    try {
        if ($conn->query($statement)) {
            echo "<div class='success'>✓ Success: " . $conn->affected_rows . " rows affected</div>";
            $success_count++;
        } else {
            echo "<div class='error'>✗ Error: " . $conn->error . "</div>";
            $error_count++;
        }
    } catch (Exception $e) {
        echo "<div class='error'>✗ Exception: " . $e->getMessage() . "</div>";
        $error_count++;
    }
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<div class='success'>Successful: $success_count</div>";
if ($error_count > 0) {
    echo "<div class='error'>Errors: $error_count</div>";
} else {
    echo "<div class='success'>✓ All tables created successfully!</div>";
    echo "<div class='info'>
        <h3>Next Steps:</h3>
        <ul>
            <li>Go to <a href='../admin/gym_bookings.php'>Gym Bookings</a> to manage blocked dates</li>
            <li>Sample events have been added: Pinning Ceremony (Nov 7) and Intramurals (Nov 14-20)</li>
            <li>External users can only book from March to July</li>
        </ul>
    </div>";
}

echo "</body></html>";

$conn->close();
?>

