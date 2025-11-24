<?php
/**
 * Check Current Database Status
 * This script compares your current database with what the system needs
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Status Check</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' rel='stylesheet'>";
echo "</head><body class='bg-gray-100 p-8'>";

echo "<div class='max-w-6xl mx-auto bg-white rounded-lg shadow-lg p-8'>";
echo "<h1 class='text-3xl font-bold text-blue-600 mb-6'>📊 Database Status Check</h1>";

// Required tables for the system
$required_tables = [
    'user_accounts' => 'User authentication and profiles',
    'password_resets' => 'Password reset tokens',
    'inventory' => 'Product inventory management',
    'orders' => 'Order tracking',
    'buses' => 'Bus fleet information',
    'bus_schedules' => 'Bus booking schedules',
    'bus_bookings' => 'Active bus bookings',
    'billing_statements' => 'Billing and receipts',
    'facilities' => 'Facility information',
    'bookings' => 'Facility bookings',
    'requests' => 'General user requests',
];

// Get all existing tables
$existing_tables = [];
$result = $conn->query("SHOW TABLES");
if ($result) {
    while ($row = $result->fetch_array()) {
        $existing_tables[] = $row[0];
    }
}

echo "<div class='mb-8'>";
echo "<h2 class='text-2xl font-bold text-gray-800 mb-4'>Current Database: <span class='text-blue-600'>chmsu_bao</span></h2>";
echo "<p class='text-gray-600 mb-4'>Total tables found: <strong>" . count($existing_tables) . "</strong></p>";
echo "</div>";

// Check required tables
$missing_tables = [];
$existing_required = [];

echo "<h2 class='text-2xl font-bold text-gray-800 mb-4'>Required Tables Status</h2>";
echo "<div class='overflow-x-auto'>";
echo "<table class='w-full border-collapse'>";
echo "<thead><tr class='bg-blue-600 text-white'>";
echo "<th class='border p-3 text-left'>Table Name</th>";
echo "<th class='border p-3 text-left'>Description</th>";
echo "<th class='border p-3 text-left'>Status</th>";
echo "<th class='border p-3 text-left'>Records</th>";
echo "</tr></thead><tbody>";

foreach ($required_tables as $table => $description) {
    $exists = in_array($table, $existing_tables);
    
    echo "<tr class='" . ($exists ? 'bg-green-50' : 'bg-red-50') . "'>";
    echo "<td class='border p-3 font-semibold'>" . $table . "</td>";
    echo "<td class='border p-3 text-gray-600'>" . $description . "</td>";
    
    if ($exists) {
        $count_result = $conn->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $count_result ? $count_result->fetch_assoc()['count'] : 0;
        
        echo "<td class='border p-3 text-green-600 font-bold'>✓ EXISTS</td>";
        echo "<td class='border p-3 text-gray-700'>" . $count . "</td>";
        $existing_required[] = $table;
    } else {
        echo "<td class='border p-3 text-red-600 font-bold'>✗ MISSING</td>";
        echo "<td class='border p-3 text-gray-400'>-</td>";
        $missing_tables[] = $table;
    }
    
    echo "</tr>";
}

echo "</tbody></table>";
echo "</div>";

// Extra tables in your database
$extra_tables = array_diff($existing_tables, array_keys($required_tables));

if (!empty($extra_tables)) {
    echo "<div class='mt-8'>";
    echo "<h2 class='text-2xl font-bold text-gray-800 mb-4'>Additional Tables (Not Required)</h2>";
    echo "<div class='bg-blue-50 border border-blue-200 rounded p-4'>";
    echo "<ul class='list-disc list-inside space-y-1'>";
    foreach ($extra_tables as $table) {
        $count_result = $conn->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $count_result ? $count_result->fetch_assoc()['count'] : 0;
        echo "<li class='text-gray-700'><strong>" . $table . "</strong> - " . $count . " records</li>";
    }
    echo "</ul>";
    echo "<p class='text-sm text-gray-600 mt-3'>These tables are in your database but not in the core requirements. They might be for extended functionality.</p>";
    echo "</div>";
    echo "</div>";
}

// Summary
echo "<div class='mt-8 p-6 rounded-lg " . (empty($missing_tables) ? 'bg-green-100 border-2 border-green-500' : 'bg-yellow-100 border-2 border-yellow-500') . "'>";
echo "<h2 class='text-2xl font-bold mb-4'>" . (empty($missing_tables) ? '🎉 Status: Complete!' : '⚠️ Status: Missing Tables') . "</h2>";

if (empty($missing_tables)) {
    echo "<p class='text-green-800 text-lg'>All required tables are present in your database!</p>";
    echo "<p class='text-green-700 mt-2'>Your system should work properly now.</p>";
    
    // Check if user_accounts has data
    $user_count = $conn->query("SELECT COUNT(*) as count FROM user_accounts")->fetch_assoc()['count'];
    if ($user_count == 0) {
        echo "<div class='mt-4 p-4 bg-yellow-50 border border-yellow-300 rounded'>";
        echo "<p class='text-yellow-800 font-semibold'>⚠️ Note: user_accounts table is empty!</p>";
        echo "<p class='text-yellow-700 mt-2'>You need to add users to login. Run the setup to add sample users.</p>";
        echo "<a href='database/setup_complete.php' class='inline-block mt-3 bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700'>Add Sample Users</a>";
        echo "</div>";
    }
} else {
    echo "<p class='text-yellow-800 text-lg'>Missing " . count($missing_tables) . " required table(s):</p>";
    echo "<ul class='list-disc list-inside mt-2 text-yellow-700'>";
    foreach ($missing_tables as $table) {
        echo "<li><strong>" . $table . "</strong> - " . $required_tables[$table] . "</li>";
    }
    echo "</ul>";
}

echo "</div>";

// Action buttons
echo "<div class='mt-8 flex flex-wrap gap-4'>";

if (!empty($missing_tables)) {
    echo "<a href='database/setup_complete.php' class='bg-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-700'>🔧 Run Complete Setup</a>";
    echo "<a href='database/index.php' class='bg-green-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-green-700'>📋 Setup Center</a>";
}

echo "<a href='test_database.php' class='bg-purple-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-purple-700'>🔍 Detailed Test</a>";
echo "<a href='login.php' class='bg-gray-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-gray-700'>🔐 Go to Login</a>";

echo "</div>";

// Check for specific columns in critical tables
echo "<div class='mt-8'>";
echo "<h2 class='text-2xl font-bold text-gray-800 mb-4'>Table Structure Verification</h2>";

if (in_array('user_accounts', $existing_tables)) {
    echo "<div class='bg-white border rounded p-4 mb-4'>";
    echo "<h3 class='font-bold text-lg mb-2'>user_accounts table columns:</h3>";
    
    $columns = $conn->query("DESCRIBE user_accounts");
    $required_columns = ['id', 'name', 'email', 'password', 'user_type', 'organization', 'profile_pic'];
    $found_columns = [];
    
    echo "<div class='grid grid-cols-2 gap-2'>";
    while ($col = $columns->fetch_assoc()) {
        $found_columns[] = $col['Field'];
        $is_required = in_array($col['Field'], $required_columns);
        echo "<div class='flex items-center'>";
        echo "<span class='" . ($is_required ? 'text-green-600' : 'text-gray-600') . "'>" . ($is_required ? '✓' : '•') . " " . $col['Field'] . "</span>";
        echo "</div>";
    }
    echo "</div>";
    
    // Check for missing columns
    $missing_cols = array_diff($required_columns, $found_columns);
    if (!empty($missing_cols)) {
        echo "<div class='mt-4 p-3 bg-yellow-50 border border-yellow-300 rounded'>";
        echo "<p class='text-yellow-800 font-semibold'>⚠️ Missing columns: " . implode(', ', $missing_cols) . "</p>";
        echo "<p class='text-sm text-yellow-700 mt-1'>These columns are needed for full functionality.</p>";
        echo "</div>";
    }
    
    echo "</div>";
}

echo "</div>";

$conn->close();

echo "</div>";
echo "</body></html>";
?>












































