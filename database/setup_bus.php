<?php
// Setup script for bus management system
require_once dirname(__DIR__) . '/config/database.php';

// Read and execute the SQL file
$sql = file_get_contents(__DIR__ . '/bus_tables.sql');

// Split the SQL into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success_count = 0;
$error_count = 0;

foreach ($statements as $statement) {
    if (!empty($statement)) {
        if ($conn->query($statement)) {
            $success_count++;
        } else {
            $error_count++;
            echo "Error executing statement: " . $conn->error . "\n";
            echo "Statement: " . $statement . "\n\n";
        }
    }
}

echo "Database setup completed!\n";
echo "Successful statements: $success_count\n";
echo "Failed statements: $error_count\n";

$conn->close();
?>
