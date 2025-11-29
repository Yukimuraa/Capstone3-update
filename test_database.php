<?php
/**
 * Database Connection Test
 * Quick script to test if database connection is working
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen py-12 px-4">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">🔌 Database Connection Test</h1>
            
            <?php
            require_once 'config/database.php';
            
            echo "<div class='space-y-4'>";
            
            // Test 1: Connection
            echo "<div class='border-l-4 border-blue-500 bg-blue-50 p-4'>";
            echo "<h2 class='font-bold text-lg text-blue-800 mb-2'>Test 1: Database Connection</h2>";
            if ($conn && !$conn->connect_error) {
                echo "<p class='text-green-600'>✅ <strong>SUCCESS:</strong> Connected to database successfully!</p>";
                echo "<p class='text-sm text-gray-600 mt-1'>Host: localhost | Database: chmsu_bao</p>";
            } else {
                echo "<p class='text-red-600'>❌ <strong>FAILED:</strong> Could not connect to database</p>";
                echo "<p class='text-sm text-red-600 mt-1'>Error: " . ($conn ? $conn->connect_error : "Connection object not created") . "</p>";
                echo "<div class='mt-3 p-3 bg-yellow-100 rounded'>";
                echo "<p class='text-sm'><strong>Fix:</strong> Make sure MySQL is running in XAMPP</p>";
                echo "</div>";
                echo "</div></div></div></body></html>";
                exit;
            }
            echo "</div>";
            
            // Test 2: Show Tables
            echo "<div class='border-l-4 border-purple-500 bg-purple-50 p-4'>";
            echo "<h2 class='font-bold text-lg text-purple-800 mb-2'>Test 2: Database Tables</h2>";
            $result = $conn->query("SHOW TABLES");
            if ($result) {
                $tables = [];
                while ($row = $result->fetch_array()) {
                    $tables[] = $row[0];
                }
                
                if (count($tables) > 0) {
                    echo "<p class='text-green-600 mb-2'>✅ <strong>SUCCESS:</strong> Found " . count($tables) . " table(s)</p>";
                    echo "<div class='bg-white p-3 rounded border'>";
                    echo "<ul class='list-disc list-inside text-sm text-gray-700 space-y-1'>";
                    foreach ($tables as $table) {
                        echo "<li>$table</li>";
                    }
                    echo "</ul>";
                    echo "</div>";
                } else {
                    echo "<p class='text-yellow-600'>⚠️ <strong>WARNING:</strong> No tables found in database</p>";
                    echo "<div class='mt-3 p-3 bg-yellow-100 rounded'>";
                    echo "<p class='text-sm'><strong>Fix:</strong> Run the database setup script</p>";
                    echo "<a href='database/setup_complete.php' class='text-blue-600 underline'>Go to Setup</a>";
                    echo "</div>";
                }
            } else {
                echo "<p class='text-red-600'>❌ <strong>FAILED:</strong> Could not retrieve tables</p>";
            }
            echo "</div>";
            
            // Test 3: Check Critical Tables
            echo "<div class='border-l-4 border-green-500 bg-green-50 p-4'>";
            echo "<h2 class='font-bold text-lg text-green-800 mb-2'>Test 3: Critical Tables Check</h2>";
            $critical_tables = [
                'user_accounts' => 'User authentication system',
                'inventory' => 'Inventory management',
                'orders' => 'Order management',
                'buses' => 'Bus fleet management',
                'bus_schedules' => 'Bus scheduling',
                'facilities' => 'Facility information',
                'bookings' => 'Facility bookings'
            ];
            
            $missing_tables = [];
            $found_tables = [];
            
            echo "<div class='space-y-2'>";
            foreach ($critical_tables as $table => $description) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if ($result && $result->num_rows > 0) {
                    // Get row count
                    $count_result = $conn->query("SELECT COUNT(*) as count FROM `$table`");
                    $count = $count_result ? $count_result->fetch_assoc()['count'] : 0;
                    
                    echo "<div class='flex items-center justify-between p-2 bg-white rounded border border-green-200'>";
                    echo "<div>";
                    echo "<span class='text-green-600'>✅</span> ";
                    echo "<span class='font-semibold'>$table</span>";
                    echo "<span class='text-gray-500 text-sm ml-2'>($description)</span>";
                    echo "</div>";
                    echo "<span class='text-gray-600 text-sm'>$count records</span>";
                    echo "</div>";
                    $found_tables[] = $table;
                } else {
                    echo "<div class='flex items-center p-2 bg-red-50 rounded border border-red-200'>";
                    echo "<span class='text-red-600'>❌</span> ";
                    echo "<span class='font-semibold text-red-700'>$table</span>";
                    echo "<span class='text-gray-500 text-sm ml-2'>($description)</span>";
                    echo "</div>";
                    $missing_tables[] = $table;
                }
            }
            echo "</div>";
            
            if (count($missing_tables) > 0) {
                echo "<div class='mt-4 p-4 bg-red-100 rounded'>";
                echo "<p class='text-red-800 font-semibold'>⚠️ Missing Tables: " . count($missing_tables) . "</p>";
                echo "<p class='text-sm text-red-700 mt-2'>Please run the database setup to create missing tables.</p>";
                echo "<a href='database/setup_complete.php' class='inline-block mt-3 bg-red-600 text-white font-bold py-2 px-4 rounded hover:bg-red-700'>Run Database Setup</a>";
                echo "</div>";
            } else {
                echo "<div class='mt-4 p-4 bg-green-100 rounded'>";
                echo "<p class='text-green-800 font-semibold'>🎉 All critical tables are present!</p>";
                echo "</div>";
            }
            echo "</div>";
            
            // Test 4: Test Query
            if (in_array('user_accounts', $found_tables)) {
                echo "<div class='border-l-4 border-indigo-500 bg-indigo-50 p-4'>";
                echo "<h2 class='font-bold text-lg text-indigo-800 mb-2'>Test 4: Query Test</h2>";
                $result = $conn->query("SELECT COUNT(*) as count FROM user_accounts");
                if ($result) {
                    $row = $result->fetch_assoc();
                    echo "<p class='text-green-600'>✅ <strong>SUCCESS:</strong> Found {$row['count']} user(s) in database</p>";
                    
                    if ($row['count'] > 0) {
                        // Show sample users
                        $users = $conn->query("SELECT name, email, user_type FROM user_accounts LIMIT 5");
                        if ($users && $users->num_rows > 0) {
                            echo "<div class='mt-3 bg-white p-3 rounded border'>";
                            echo "<p class='text-sm font-semibold text-gray-700 mb-2'>Sample Users:</p>";
                            echo "<table class='w-full text-sm'>";
                            echo "<tr class='border-b'><th class='text-left py-1'>Name</th><th class='text-left py-1'>Email</th><th class='text-left py-1'>Type</th></tr>";
                            while ($user = $users->fetch_assoc()) {
                                echo "<tr class='border-b'>";
                                echo "<td class='py-1'>" . htmlspecialchars($user['name']) . "</td>";
                                echo "<td class='py-1'>" . htmlspecialchars($user['email']) . "</td>";
                                echo "<td class='py-1'><span class='bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs'>" . htmlspecialchars($user['user_type']) . "</span></td>";
                                echo "</tr>";
                            }
                            echo "</table>";
                            echo "</div>";
                        }
                    } else {
                        echo "<p class='text-yellow-600 mt-2'>⚠️ No users found. Run setup to create sample users.</p>";
                    }
                } else {
                    echo "<p class='text-red-600'>❌ <strong>FAILED:</strong> Could not query user_accounts table</p>";
                }
                echo "</div>";
            }
            
            echo "</div>";
            
            $conn->close();
            ?>
            
            <!-- Action Buttons -->
            <div class="mt-8 pt-6 border-t flex flex-wrap gap-4">
                <a href="database/index.php" class="bg-blue-600 text-white font-bold py-3 px-6 rounded hover:bg-blue-700 transition">
                    🔧 Database Setup Center
                </a>
                <a href="login.php" class="bg-green-600 text-white font-bold py-3 px-6 rounded hover:bg-green-700 transition">
                    🔐 Go to Login
                </a>
                <a href="register.php" class="bg-purple-600 text-white font-bold py-3 px-6 rounded hover:bg-purple-700 transition">
                    📝 Register Account
                </a>
                <a href="index.php" class="bg-gray-600 text-white font-bold py-3 px-6 rounded hover:bg-gray-700 transition">
                    🏠 Home Page
                </a>
            </div>
            
            <!-- Help Section -->
            <div class="mt-8 p-4 bg-gray-50 rounded">
                <h3 class="font-bold text-gray-800 mb-2">Need Help?</h3>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>• If connection fails: Make sure MySQL is running in XAMPP Control Panel</li>
                    <li>• If tables are missing: Run <code class="bg-gray-200 px-1">database/setup_complete.php</code></li>
                    <li>• Default password for all sample accounts: <code class="bg-gray-200 px-1">admin123</code></li>
                    <li>• Database config file: <code class="bg-gray-200 px-1">config/database.php</code></li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>





















































