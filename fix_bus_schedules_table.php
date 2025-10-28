<?php
/**
 * Fix bus_schedules Table
 * Add missing status column and other required columns
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Fix bus_schedules Table</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' rel='stylesheet'>";
echo "</head><body class='bg-gray-900 text-white p-8'>";

echo "<div class='max-w-4xl mx-auto bg-gray-800 rounded-lg shadow-2xl p-8'>";
echo "<h1 class='text-3xl font-bold text-green-400 mb-6'>🔧 Fix bus_schedules Table</h1>";

// Check if bus_schedules table exists
$table_check = $conn->query("SHOW TABLES LIKE 'bus_schedules'");

if (!$table_check || $table_check->num_rows == 0) {
    echo "<div class='bg-red-900 border border-red-500 rounded p-4 mb-6'>";
    echo "<p class='text-red-300 font-bold'>❌ bus_schedules table does not exist!</p>";
    echo "<p class='text-red-200 mt-2'>Creating the table now...</p>";
    echo "</div>";
    
    $create_sql = "CREATE TABLE bus_schedules (
        id INT PRIMARY KEY AUTO_INCREMENT,
        client VARCHAR(255) NOT NULL,
        destination VARCHAR(255) NOT NULL,
        purpose VARCHAR(255) NOT NULL,
        date_covered DATE NOT NULL,
        vehicle VARCHAR(50) NOT NULL,
        bus_no VARCHAR(20) NOT NULL,
        no_of_days INT NOT NULL DEFAULT 1,
        no_of_vehicles INT NOT NULL DEFAULT 1,
        user_id INT,
        user_type ENUM('student', 'admin', 'staff', 'external') DEFAULT 'student',
        status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_sql)) {
        echo "<p class='text-green-400 font-bold'>✓ bus_schedules table created successfully!</p>";
    } else {
        echo "<p class='text-red-400'>✗ Error creating table: " . $conn->error . "</p>";
        echo "</div></body></html>";
        exit;
    }
} else {
    echo "<div class='bg-blue-900 border border-blue-500 rounded p-4 mb-6'>";
    echo "<p class='text-blue-300'>✓ bus_schedules table exists</p>";
    echo "</div>";
}

// Get current columns
$columns_result = $conn->query("DESCRIBE bus_schedules");
$existing_columns = [];

echo "<h2 class='text-2xl font-bold text-blue-400 mb-4'>Current Table Structure</h2>";
echo "<div class='bg-gray-700 rounded p-4 mb-6'>";
echo "<table class='w-full text-sm'>";
echo "<tr class='border-b border-gray-600'><th class='text-left py-2'>Column</th><th class='text-left py-2'>Type</th><th class='text-left py-2'>Null</th><th class='text-left py-2'>Default</th></tr>";

while ($col = $columns_result->fetch_assoc()) {
    $existing_columns[] = $col['Field'];
    echo "<tr class='border-b border-gray-600'>";
    echo "<td class='py-2'>" . htmlspecialchars($col['Field']) . "</td>";
    echo "<td class='py-2 text-gray-400'>" . htmlspecialchars($col['Type']) . "</td>";
    echo "<td class='py-2 text-gray-400'>" . htmlspecialchars($col['Null']) . "</td>";
    echo "<td class='py-2 text-gray-400'>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

// Required columns
$required_columns = [
    'status' => "ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending'",
    'user_id' => "INT NULL",
    'user_type' => "ENUM('student', 'admin', 'staff', 'external') DEFAULT 'student'",
    'updated_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
];

echo "<h2 class='text-2xl font-bold text-yellow-400 mb-4'>Adding Missing Columns</h2>";

$added = 0;
$skipped = 0;
$errors = [];

foreach ($required_columns as $column => $definition) {
    if (!in_array($column, $existing_columns)) {
        echo "<p class='text-yellow-300'>Adding column: <strong>$column</strong>...</p>";
        
        // Determine position
        $position = "";
        if ($column == 'user_id') {
            $position = "AFTER no_of_vehicles";
        } elseif ($column == 'user_type') {
            $position = "AFTER user_id";
        } elseif ($column == 'status') {
            $position = "AFTER user_type";
        } elseif ($column == 'updated_at') {
            $position = "AFTER created_at";
        }
        
        $sql = "ALTER TABLE bus_schedules ADD COLUMN $column $definition $position";
        
        if ($conn->query($sql)) {
            echo "<p class='text-green-400'>✓ Column <strong>$column</strong> added successfully!</p>";
            $added++;
        } else {
            echo "<p class='text-red-400'>✗ Error adding $column: " . $conn->error . "</p>";
            $errors[] = $column . ": " . $conn->error;
        }
    } else {
        echo "<p class='text-gray-400'>• Column <strong>$column</strong> already exists</p>";
        $skipped++;
    }
}

// Summary
echo "<hr class='my-6 border-gray-600'>";
echo "<div class='p-6 bg-gray-700 rounded-lg'>";
echo "<h2 class='text-2xl font-bold text-green-400 mb-4'>Summary</h2>";
echo "<p class='text-lg'><strong>Columns Added:</strong> <span class='text-green-400'>$added</span></p>";
echo "<p class='text-lg'><strong>Columns Skipped:</strong> <span class='text-gray-400'>$skipped</span></p>";
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

if ($added > 0 && count($errors) == 0) {
    echo "<div class='mt-4 p-4 bg-green-900 rounded'>";
    echo "<p class='text-green-300 font-bold text-lg'>✓ All missing columns have been added successfully!</p>";
    echo "<p class='text-green-200 mt-2'>The bus_schedules table is now properly configured.</p>";
    echo "</div>";
}

echo "</div>";

// Verify the fix
echo "<h2 class='text-2xl font-bold text-purple-400 mt-8 mb-4'>Verification</h2>";

$verify = $conn->query("DESCRIBE bus_schedules");
$final_columns = [];

echo "<div class='bg-gray-700 rounded p-4'>";
echo "<table class='w-full text-sm'>";
echo "<tr class='border-b border-gray-600'><th class='text-left py-2'>Column</th><th class='text-left py-2'>Type</th><th class='text-left py-2'>Status</th></tr>";

while ($col = $verify->fetch_assoc()) {
    $final_columns[] = $col['Field'];
    $is_required = array_key_exists($col['Field'], $required_columns);
    $status_class = $is_required ? 'text-green-400' : 'text-gray-400';
    $status_icon = $is_required ? '✓' : '•';
    
    echo "<tr class='border-b border-gray-600'>";
    echo "<td class='py-2'>" . htmlspecialchars($col['Field']) . "</td>";
    echo "<td class='py-2 text-gray-400'>" . htmlspecialchars($col['Type']) . "</td>";
    echo "<td class='py-2 $status_class'>$status_icon " . ($is_required ? 'Required' : 'Standard') . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

// Check if status column exists now
$has_status = in_array('status', $final_columns);

if ($has_status) {
    echo "<div class='mt-6 p-6 bg-green-900 border border-green-500 rounded'>";
    echo "<p class='text-green-300 font-bold text-xl'>🎉 Success!</p>";
    echo "<p class='text-green-200 mt-2'>The 'status' column has been added to bus_schedules table.</p>";
    echo "<p class='text-green-200'>Your admin/bus.php should work now!</p>";
    echo "</div>";
} else {
    echo "<div class='mt-6 p-6 bg-red-900 border border-red-500 rounded'>";
    echo "<p class='text-red-300 font-bold text-xl'>⚠️ Issue</p>";
    echo "<p class='text-red-200 mt-2'>The 'status' column is still missing. Please check the errors above.</p>";
    echo "</div>";
}

// Action buttons
echo "<div class='mt-8 flex flex-wrap gap-4'>";
echo "<a href='admin/bus.php' class='bg-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-700'>📊 Test Bus Page</a>";
echo "<a href='check_database.php' class='bg-purple-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-purple-700'>🔍 Check Database</a>";
echo "<a href='test_database.php' class='bg-green-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-green-700'>🧪 Run Tests</a>";
echo "</div>";

$conn->close();

echo "</div>";
echo "</body></html>";
?>















