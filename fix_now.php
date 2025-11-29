<?php
/**
 * EMERGENCY FIX - Add All Missing Columns to bus_schedules
 * This fixes both admin/bus.php and student/bus.php errors
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Emergency Fix - Bus Schedules</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' rel='stylesheet'>";
echo "<style>
body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
</style>";
echo "</head><body class='min-h-screen p-8'>";

echo "<div class='max-w-4xl mx-auto'>";
echo "<div class='bg-white rounded-lg shadow-2xl p-8'>";
echo "<h1 class='text-4xl font-bold text-purple-600 mb-2'>⚡ Emergency Database Fix</h1>";
echo "<p class='text-gray-600 mb-6'>Fixing bus_schedules table...</p>";

// Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'bus_schedules'");

if (!$table_check || $table_check->num_rows == 0) {
    echo "<div class='bg-red-100 border-l-4 border-red-500 p-4 mb-6'>";
    echo "<p class='text-red-700 font-bold'>❌ ERROR: bus_schedules table doesn't exist!</p>";
    echo "<p class='text-red-600 mt-2'>Creating it now...</p>";
    echo "</div>";
    
    // Create the complete table
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
        user_id INT NULL,
        user_type ENUM('student', 'admin', 'staff', 'external') DEFAULT 'student',
        status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status),
        INDEX idx_date (date_covered)
    )";
    
    if ($conn->query($create_sql)) {
        echo "<div class='bg-green-100 border-l-4 border-green-500 p-4'>";
        echo "<p class='text-green-700 font-bold'>✓ Table created successfully!</p>";
        echo "</div>";
    } else {
        echo "<div class='bg-red-100 border-l-4 border-red-500 p-4'>";
        echo "<p class='text-red-700'>Error: " . $conn->error . "</p>";
        echo "</div>";
        echo "</div></div></body></html>";
        exit;
    }
} else {
    echo "<div class='bg-blue-100 border-l-4 border-blue-500 p-4 mb-6'>";
    echo "<p class='text-blue-700'>✓ Table exists, checking columns...</p>";
    echo "</div>";
}

// Get existing columns
$columns = [];
$result = $conn->query("DESCRIBE bus_schedules");
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

echo "<div class='mb-6'>";
echo "<h2 class='text-2xl font-bold text-gray-800 mb-3'>Current Columns</h2>";
echo "<div class='flex flex-wrap gap-2'>";
foreach ($columns as $col) {
    echo "<span class='bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-sm'>$col</span>";
}
echo "</div>";
echo "</div>";

// Required columns with their definitions
$required_columns = [
    'user_id' => [
        'definition' => 'INT NULL',
        'position' => 'AFTER no_of_vehicles',
        'description' => 'Links booking to user who created it'
    ],
    'user_type' => [
        'definition' => "ENUM('student', 'admin', 'staff', 'external') DEFAULT 'student'",
        'position' => 'AFTER user_id',
        'description' => 'Type of user who created booking'
    ],
    'status' => [
        'definition' => "ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending'",
        'position' => 'AFTER user_type',
        'description' => 'Status of the booking request'
    ],
    'created_at' => [
        'definition' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'position' => '',
        'description' => 'When the booking was created'
    ],
    'updated_at' => [
        'definition' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        'position' => 'AFTER created_at',
        'description' => 'When the booking was last updated'
    ]
];

echo "<div class='mb-6'>";
echo "<h2 class='text-2xl font-bold text-gray-800 mb-3'>🔧 Adding Missing Columns</h2>";

$added = 0;
$errors = [];

foreach ($required_columns as $col_name => $col_info) {
    if (!in_array($col_name, $columns)) {
        echo "<div class='bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-3'>";
        echo "<p class='text-yellow-800 font-semibold'>⚙️ Adding: <code class='bg-yellow-100 px-2 py-1 rounded'>$col_name</code></p>";
        echo "<p class='text-yellow-700 text-sm'>{$col_info['description']}</p>";
        
        $sql = "ALTER TABLE bus_schedules ADD COLUMN $col_name {$col_info['definition']} {$col_info['position']}";
        
        if ($conn->query($sql)) {
            echo "<p class='text-green-600 font-bold mt-2'>✓ Added successfully!</p>";
            $added++;
        } else {
            echo "<p class='text-red-600 font-bold mt-2'>✗ Error: " . $conn->error . "</p>";
            $errors[] = "$col_name: " . $conn->error;
        }
        echo "</div>";
    } else {
        echo "<div class='bg-gray-50 border-l-4 border-gray-400 p-3 mb-2'>";
        echo "<p class='text-gray-700'>✓ <code class='bg-gray-200 px-2 py-1 rounded'>$col_name</code> already exists</p>";
        echo "</div>";
    }
}

echo "</div>";

// Create indexes if missing
echo "<div class='mb-6'>";
echo "<h2 class='text-2xl font-bold text-gray-800 mb-3'>📊 Adding Indexes</h2>";

$indexes = [
    'idx_bus_schedules_user_id' => 'CREATE INDEX idx_bus_schedules_user_id ON bus_schedules(user_id)',
    'idx_bus_schedules_status' => 'CREATE INDEX idx_bus_schedules_status ON bus_schedules(status)',
    'idx_bus_schedules_date' => 'CREATE INDEX idx_bus_schedules_date ON bus_schedules(date_covered)'
];

foreach ($indexes as $idx_name => $idx_sql) {
    // Check if index exists
    $check = $conn->query("SHOW INDEX FROM bus_schedules WHERE Key_name = '$idx_name'");
    if ($check->num_rows == 0) {
        if ($conn->query($idx_sql)) {
            echo "<p class='text-green-600'>✓ Index <code class='bg-green-100 px-2 py-1 rounded'>$idx_name</code> created</p>";
        } else {
            echo "<p class='text-yellow-600'>⚠ Index $idx_name: " . $conn->error . "</p>";
        }
    } else {
        echo "<p class='text-gray-600'>• Index <code class='bg-gray-200 px-2 py-1 rounded'>$idx_name</code> exists</p>";
    }
}

echo "</div>";

// Final verification
echo "<div class='mb-6'>";
echo "<h2 class='text-2xl font-bold text-gray-800 mb-3'>✅ Final Structure</h2>";

$final_columns = [];
$result = $conn->query("DESCRIBE bus_schedules");

echo "<div class='overflow-x-auto'>";
echo "<table class='w-full border-collapse'>";
echo "<thead><tr class='bg-purple-600 text-white'>";
echo "<th class='border p-2 text-left'>Column</th>";
echo "<th class='border p-2 text-left'>Type</th>";
echo "<th class='border p-2 text-left'>Null</th>";
echo "<th class='border p-2 text-left'>Default</th>";
echo "<th class='border p-2'>Status</th>";
echo "</tr></thead><tbody>";

while ($row = $result->fetch_assoc()) {
    $final_columns[] = $row['Field'];
    $is_required = array_key_exists($row['Field'], $required_columns);
    $row_class = $is_required ? 'bg-green-50' : 'bg-white';
    $status = $is_required ? '<span class="text-green-600 font-bold">✓ Required</span>' : '<span class="text-gray-500">Standard</span>';
    
    echo "<tr class='$row_class'>";
    echo "<td class='border p-2 font-semibold'>{$row['Field']}</td>";
    echo "<td class='border p-2 text-sm text-gray-600'>{$row['Type']}</td>";
    echo "<td class='border p-2 text-sm text-gray-600'>{$row['Null']}</td>";
    echo "<td class='border p-2 text-sm text-gray-600'>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "<td class='border p-2 text-center'>$status</td>";
    echo "</tr>";
}

echo "</tbody></table>";
echo "</div>";
echo "</div>";

// Summary
$all_required_exist = true;
foreach ($required_columns as $col_name => $col_info) {
    if (!in_array($col_name, $final_columns)) {
        $all_required_exist = false;
        break;
    }
}

echo "<div class='p-6 rounded-lg " . ($all_required_exist ? 'bg-green-100 border-2 border-green-500' : 'bg-red-100 border-2 border-red-500') . "'>";

if ($all_required_exist) {
    echo "<h2 class='text-3xl font-bold text-green-700 mb-3'>🎉 SUCCESS!</h2>";
    echo "<p class='text-green-600 text-lg mb-4'>All required columns are now in place!</p>";
    echo "<div class='bg-white rounded p-4 mb-4'>";
    echo "<p class='font-semibold mb-2'>Fixed Issues:</p>";
    echo "<ul class='list-disc list-inside text-green-700 space-y-1'>";
    echo "<li>✓ <strong>admin/bus.php line 134</strong> - status column added</li>";
    echo "<li>✓ <strong>student/bus.php line 563</strong> - user_id column added</li>";
    echo "<li>✓ All other required columns verified</li>";
    echo "</ul>";
    echo "</div>";
    echo "<p class='text-green-700 font-semibold'>Your bus booking system should work now!</p>";
} else {
    echo "<h2 class='text-3xl font-bold text-red-700 mb-3'>⚠️ Issues Found</h2>";
    echo "<p class='text-red-600 text-lg mb-2'>Some columns are still missing.</p>";
    if (count($errors) > 0) {
        echo "<div class='bg-white rounded p-4 mt-4'>";
        echo "<p class='font-semibold mb-2'>Errors:</p>";
        echo "<ul class='list-disc list-inside text-red-600'>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
}

echo "</div>";

// Action buttons
echo "<div class='mt-8 flex flex-wrap gap-4'>";
echo "<a href='student/bus.php' class='bg-purple-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-purple-700 shadow-lg'>🚌 Test Student Bus Page</a>";
echo "<a href='admin/bus.php' class='bg-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-700 shadow-lg'>👨‍💼 Test Admin Bus Page</a>";
echo "<a href='check_database.php' class='bg-green-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-green-700 shadow-lg'>📊 Check Database</a>";
echo "</div>";

$conn->close();

echo "</div>";
echo "</div>";
echo "</body></html>";
?>





















































