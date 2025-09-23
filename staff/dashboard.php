<?php
// Use staff-specific session
session_name('bao_staff_session');
session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a staff
require_staff();

// Get user data for the staff
$user_id = $_SESSION['user_sessions']['staff']['user_id'];
$user_name = $_SESSION['user_sessions']['staff']['user_name'];

$page_title = "Staff Dashboard - CHMSU BAO";
$base_url = "..";

// Get today's bookings count
$today_bookings_query = "SELECT COUNT(*) as count FROM bookings WHERE DATE(booking_date) = CURDATE()";
$today_bookings = $conn->query($today_bookings_query)->fetch_assoc()['count'];

// Get pending inventory updates
$pending_inventory_query = "SELECT COUNT(*) as count FROM inventory WHERE last_updated < DATE_SUB(NOW(), INTERVAL 7 DAY)";
$pending_inventory = $conn->query($pending_inventory_query)->fetch_assoc()['count'];

// Get low stock items
$low_stock_query = "SELECT COUNT(*) as count FROM inventory WHERE quantity <= 5";
$low_stock = $conn->query($low_stock_query)->fetch_assoc()['count'];

// Get today's bookings
$today_bookings_list_query = "SELECT b.*, u.name as user_name, f.name as facility_name 
                             FROM bookings b 
                             JOIN users u ON b.user_id = u.id 
                             JOIN facilities f ON b.facility_id = f.id 
                             WHERE DATE(b.booking_date) = CURDATE() 
                             ORDER BY b.start_time ASC";
$today_bookings_list = $conn->query($today_bookings_list_query);

// Get low stock items list
$low_stock_list_query = "SELECT * FROM inventory WHERE quantity <= 5 ORDER BY quantity ASC LIMIT 5";
$low_stock_list = $conn->query($low_stock_list_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - CHMSU BAO</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <?php include '../includes/staff_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <?php include '../includes/header.php'; ?>
            
            <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <h1 class="text-2xl font-semibold text-gray-800 mb-6">Staff Dashboard</h1>
                
                <!-- Dashboard Content -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                    <!-- Card 1 -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-700">Bus Bookings</h2>
                            <i class="fas fa-bus text-blue-500"></i>
                        </div>
                        <p class="text-3xl font-bold text-gray-800">4</p>
                        <p class="text-sm text-gray-500">Upcoming this week</p>
                    </div>
                    
                    <!-- Card 2 -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-700">Pending Requests</h2>
                            <i class="fas fa-clipboard-list text-green-500"></i>
                        </div>
                        <p class="text-3xl font-bold text-gray-800">7</p>
                        <p class="text-sm text-gray-500">Need your attention</p>
                    </div>
                    
                    <!-- Card 3 -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-700">Completed Tasks</h2>
                            <i class="fas fa-check-circle text-purple-500"></i>
                        </div>
                        <p class="text-3xl font-bold text-gray-800">23</p>
                        <p class="text-sm text-gray-500">This month</p>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Today's Bookings -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                                <i class="fas fa-calendar-day text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h2 class="text-gray-600 text-sm">Today's Bookings</h2>
                                <p class="text-2xl font-semibold text-gray-800"><?php echo $today_bookings; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pending Inventory Updates -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-500">
                                <i class="fas fa-clock text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h2 class="text-gray-600 text-sm">Pending Updates</h2>
                                <p class="text-2xl font-semibold text-gray-800"><?php echo $pending_inventory; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Low Stock Items -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-100 text-red-500">
                                <i class="fas fa-exclamation-triangle text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h2 class="text-gray-600 text-sm">Low Stock Items</h2>
                                <p class="text-2xl font-semibold text-gray-800"><?php echo $low_stock; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Today's Bookings -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                        <h3 class="text-lg font-medium text-gray-900">Today's Bookings</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Facility</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($today_bookings_list->num_rows > 0): ?>
                                    <?php while ($booking = $today_bookings_list->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('h:i A', strtotime($booking['start_time'])); ?> - 
                                                <?php echo date('h:i A', strtotime($booking['end_time'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $booking['facility_name']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $booking['user_name']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Confirmed
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No bookings for today
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Low Stock Items -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                        <h3 class="text-lg font-medium text-gray-900">Low Stock Items</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($low_stock_list->num_rows > 0): ?>
                                    <?php while ($item = $low_stock_list->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $item['name']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $item['quantity']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo format_date($item['last_updated']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Low Stock
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No low stock items
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('menu-button').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        });
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
