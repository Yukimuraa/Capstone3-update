<?php
/**
 * Database Setup Index Page
 * Provides options for setting up the database
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - CHMSU BAO</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-success { background-color: #10B981; }
        .status-error { background-color: #EF4444; }
        .status-warning { background-color: #F59E0B; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-900 to-blue-700 min-h-screen">
    <div class="container mx-auto px-4 py-12">
        <!-- Header -->
        <div class="text-center mb-12">
            <img src="../image/CHMSUWebLOGO.png" alt="CHMSU Logo" class="mx-auto mb-4" width="100">
            <h1 class="text-4xl font-bold text-white mb-2">Database Setup Center</h1>
            <p class="text-blue-200">CHMSU Business Affairs Office System</p>
        </div>

        <!-- Status Check -->
        <div class="bg-white rounded-lg shadow-xl p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">📊 System Status</h2>
            <?php
            require_once dirname(__DIR__) . '/config/database.php';
            
            $status_items = [
                'Database Connection' => false,
                'user_accounts table' => false,
                'inventory table' => false,
                'orders table' => false,
                'buses table' => false,
                'facilities table' => false,
                'bookings table' => false,
            ];
            
            // Check database connection
            if ($conn && !$conn->connect_error) {
                $status_items['Database Connection'] = true;
                
                // Check each table
                $tables_to_check = ['user_accounts', 'inventory', 'orders', 'buses', 'facilities', 'bookings'];
                foreach ($tables_to_check as $table) {
                    $result = $conn->query("SHOW TABLES LIKE '$table'");
                    if ($result && $result->num_rows > 0) {
                        $status_items["$table table"] = true;
                    }
                }
            }
            
            $all_good = true;
            foreach ($status_items as $item => $status) {
                $status_class = $status ? 'status-success' : 'status-error';
                $status_text = $status ? 'OK' : 'Not Found';
                $text_class = $status ? 'text-green-700' : 'text-red-700';
                
                echo "<div class='flex items-center justify-between py-2 border-b'>";
                echo "<span class='text-gray-700'><span class='$status_class status-indicator'></span>$item</span>";
                echo "<span class='font-semibold $text_class'>$status_text</span>";
                echo "</div>";
                
                if (!$status) $all_good = false;
            }
            
            if ($conn) $conn->close();
            ?>
        </div>

        <!-- Setup Options -->
        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <!-- Complete Setup -->
            <div class="card bg-white rounded-lg shadow-xl p-6">
                <div class="text-4xl mb-4">🚀</div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Complete Setup</h3>
                <p class="text-gray-600 mb-4">
                    Run the complete database setup. This will create all necessary tables and insert sample data.
                    <strong>Recommended for first-time setup.</strong>
                </p>
                <a href="setup_complete.php" class="block w-full bg-blue-600 text-white text-center font-bold py-3 px-4 rounded hover:bg-blue-700 transition">
                    Run Complete Setup
                </a>
            </div>

            <!-- User Accounts Only -->
            <div class="card bg-white rounded-lg shadow-xl p-6">
                <div class="text-4xl mb-4">👥</div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">User Accounts Only</h3>
                <p class="text-gray-600 mb-4">
                    Create only the user_accounts table. Use this if you only need to fix the login/register functionality.
                </p>
                <a href="setup_user_accounts.php" class="block w-full bg-green-600 text-white text-center font-bold py-3 px-4 rounded hover:bg-green-700 transition">
                    Setup User Accounts
                </a>
            </div>

            <!-- Bus System -->
            <div class="card bg-white rounded-lg shadow-xl p-6">
                <div class="text-4xl mb-4">🚌</div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Bus Management System</h3>
                <p class="text-gray-600 mb-4">
                    Create tables for bus scheduling, bookings, and billing statements.
                </p>
                <a href="setup_bus.php" class="block w-full bg-yellow-600 text-white text-center font-bold py-3 px-4 rounded hover:bg-yellow-700 transition">
                    Setup Bus System
                </a>
            </div>

            <!-- Facility Bookings -->
            <div class="card bg-white rounded-lg shadow-xl p-6">
                <div class="text-4xl mb-4">🏛️</div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Facility Bookings</h3>
                <p class="text-gray-600 mb-4">
                    Create tables for facility management and booking system (gym, courts, etc.).
                </p>
                <a href="setup_calendar.php" class="block w-full bg-purple-600 text-white text-center font-bold py-3 px-4 rounded hover:bg-purple-700 transition">
                    Setup Facilities
                </a>
            </div>
        </div>

        <!-- Quick Links -->
        <?php if ($all_good): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded mb-8">
            <p class="font-bold mb-2">✅ All Systems Ready!</p>
            <p class="mb-4">Your database is properly configured and ready to use.</p>
            <div class="flex flex-wrap gap-4">
                <a href="../login.php" class="bg-green-600 text-white font-bold py-2 px-4 rounded hover:bg-green-700 transition">
                    Go to Login
                </a>
                <a href="../register.php" class="bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 transition">
                    Register New Account
                </a>
                <a href="../index.php" class="bg-gray-600 text-white font-bold py-2 px-4 rounded hover:bg-gray-700 transition">
                    Home Page
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded mb-8">
            <p class="font-bold mb-2">⚠️ Setup Required</p>
            <p>Some database tables are missing. Please run the <strong>Complete Setup</strong> to initialize your database.</p>
        </div>
        <?php endif; ?>

        <!-- Documentation -->
        <div class="bg-white rounded-lg shadow-xl p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">📚 Documentation</h2>
            <div class="prose max-w-none">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">What does the setup do?</h3>
                <p class="text-gray-600 mb-4">
                    The setup script creates all necessary database tables for the CHMSU BAO system, including:
                </p>
                <ul class="list-disc list-inside text-gray-600 mb-4 space-y-1">
                    <li>User accounts and authentication system</li>
                    <li>Inventory and order management</li>
                    <li>Bus scheduling and booking system</li>
                    <li>Facility booking calendar</li>
                    <li>Request management system</li>
                </ul>
                
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Default Login Credentials</h3>
                <p class="text-gray-600 mb-2">All sample accounts use password: <code class="bg-gray-200 px-2 py-1 rounded">admin123</code></p>
                <ul class="list-disc list-inside text-gray-600 mb-4 space-y-1">
                    <li><strong>Admin:</strong> admin@chmsu.edu.ph</li>
                    <li><strong>Staff:</strong> staff@chmsu.edu.ph</li>
                    <li><strong>Student:</strong> student@chmsu.edu.ph</li>
                    <li><strong>External:</strong> external@example.com</li>
                </ul>

                <h3 class="text-lg font-semibold text-gray-700 mb-2">Need Help?</h3>
                <p class="text-gray-600">
                    If you encounter any issues during setup, make sure:
                </p>
                <ul class="list-disc list-inside text-gray-600 space-y-1">
                    <li>XAMPP Apache and MySQL services are running</li>
                    <li>The database name in <code class="bg-gray-200 px-2 py-1 rounded">config/database.php</code> is correct</li>
                    <li>You have proper MySQL user permissions</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>



















































