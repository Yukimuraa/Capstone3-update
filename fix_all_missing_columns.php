<?php
/**
 * Fix All Missing Columns
 * Checks all tables and adds missing columns
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Fix All Missing Columns</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' rel='stylesheet'>";
echo "</head><body class='bg-gradient-to-br from-gray-900 to-blue-900 text-white p-8'>";

echo "<div class='max-w-5xl mx-auto bg-gray-800 rounded-lg shadow-2xl p-8'>";
echo "<h1 class='text-4xl font-bold text-green-400 mb-6'>🔧 Fix All Missing Columns</h1>";

$total_added = 0;
$total_errors = 0;

// Define table structures with required columns
$table_structures = [
    'user_accounts' => [
        'organization' => ["VARCHAR(255) NULL", "AFTER user_type"],
        'profile_pic' => ["VARCHAR(255) NULL", "AFTER organization"],
    ],
    'bus_schedules' => [
        'user_id' => ["INT NULL", "AFTER no_of_vehicles"],
        'user_type' => ["ENUM('student', 'admin', 'staff', 'external') DEFAULT 'student'", "AFTER user_id"],
        'status' => ["ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending'", "AFTER user_type"],
        'updated_at' => ["TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", "AFTER created_at"],
    ],
    'buses' => [
        'status' => ["ENUM('available', 'booked', 'maintenance', 'out_of_service') DEFAULT 'available'", "AFTER capacity"],
    ],
    'facilities' => [
        'type' => ["ENUM('gym', 'other') DEFAULT 'other'", "AFTER capacity"],
        'status' => ["ENUM('active', 'inactive', 'maintenance') DEFAULT 'active'", "AFTER type"],
    ],
    'bookings' => [
        'is_buffer' => ["BOOLEAN DEFAULT FALSE", "AFTER status"],
    ],
];

// Check each table
foreach ($table_structures as $table_name => $columns) {
    echo "<div class='mb-8 bg-gray-700 rounded-lg p-6'>";
    echo "<h2 class='text-2xl font-bold text-blue-400 mb-4'>Table: $table_name</h2>";
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE '$table_name'");
    
    if (!$table_check || $table_check->num_rows == 0) {
        echo "<p class='text-yellow-300'>⚠️ Table does not exist, skipping...</p>";
        echo "</div>";
        continue;
    }
    
    // Get existing columns
    $columns_result = $conn->query("DESCRIBE $table_name");
    $existing_columns = [];
    
    while ($col = $columns_result->fetch_assoc()) {
        $existing_columns[] = $col['Field'];
    }
    
    echo "<p class='text-gray-300 mb-3'>Existing columns: " . implode(', ', $existing_columns) . "</p>";
    
    // Check and add missing columns
    $table_added = 0;
    foreach ($columns as $column_name => $column_def) {
        list($definition, $position) = $column_def;
        
        if (!in_array($column_name, $existing_columns)) {
            echo "<p class='text-yellow-300'>Adding column: <strong>$column_name</strong>...</p>";
            
            $sql = "ALTER TABLE $table_name ADD COLUMN $column_name $definition $position";
            
            if ($conn->query($sql)) {
                echo "<p class='text-green-400'>✓ Column <strong>$column_name</strong> added!</p>";
                $table_added++;
                $total_added++;
            } else {
                echo "<p class='text-red-400'>✗ Error: " . $conn->error . "</p>";
                $total_errors++;
            }
        } else {
            echo "<p class='text-gray-400'>• Column <strong>$column_name</strong> already exists</p>";
        }
    }
    
    if ($table_added > 0) {
        echo "<p class='text-green-300 font-bold mt-3'>Added $table_added column(s) to $table_name</p>";
    } else {
        echo "<p class='text-gray-400 mt-3'>No changes needed for $table_name</p>";
    }
    
    echo "</div>";
}

// Summary
echo "<hr class='my-6 border-gray-600'>";
echo "<div class='p-8 bg-gradient-to-r from-green-900 to-blue-900 rounded-lg'>";
echo "<h2 class='text-3xl font-bold text-green-300 mb-4'>📊 Summary</h2>";
echo "<div class='grid md:grid-cols-2 gap-4'>";
echo "<div class='bg-gray-800 rounded p-4'>";
echo "<p class='text-4xl font-bold text-green-400'>$total_added</p>";
echo "<p class='text-gray-300'>Columns Added</p>";
echo "</div>";
echo "<div class='bg-gray-800 rounded p-4'>";
echo "<p class='text-4xl font-bold text-red-400'>$total_errors</p>";
echo "<p class='text-gray-300'>Errors</p>";
echo "</div>";
echo "</div>";

if ($total_added > 0 && $total_errors == 0) {
    echo "<div class='mt-6 p-4 bg-green-800 rounded'>";
    echo "<p class='text-green-200 font-bold text-xl'>✅ All missing columns have been added successfully!</p>";
    echo "<p class='text-green-300 mt-2'>Your database is now properly configured.</p>";
    echo "</div>";
} elseif ($total_errors > 0) {
    echo "<div class='mt-6 p-4 bg-red-800 rounded'>";
    echo "<p class='text-red-200 font-bold text-xl'>⚠️ Some errors occurred</p>";
    echo "<p class='text-red-300 mt-2'>Please check the error messages above.</p>";
    echo "</div>";
} else {
    echo "<div class='mt-6 p-4 bg-blue-800 rounded'>";
    echo "<p class='text-blue-200 font-bold text-xl'>ℹ️ No changes needed</p>";
    echo "<p class='text-blue-300 mt-2'>All columns already exist in your tables.</p>";
    echo "</div>";
}

echo "</div>";

// Action buttons
echo "<div class='mt-8 flex flex-wrap gap-4'>";
echo "<a href='check_database.php' class='bg-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-700'>📊 Check Database</a>";
echo "<a href='admin/bus.php' class='bg-green-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-green-700'>🚌 Test Bus Page</a>";
echo "<a href='test_database.php' class='bg-purple-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-purple-700'>🔍 Run Tests</a>";
echo "<a href='login.php' class='bg-indigo-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-indigo-700'>🔐 Go to Login</a>";
echo "</div>";

$conn->close();

echo "</div>";
echo "</body></html>";
?>








































