<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is admin or secretary
require_admin();

// Get user data based on active user type (admin or secretary)
$active_type = $_SESSION['active_user_type'];
$user_id = $_SESSION['user_sessions'][$active_type]['user_id'];
$user_name = $_SESSION['user_sessions'][$active_type]['user_name'];

$page_title = "Admin Dashboard - CHMSU BAO";
$base_url = "..";

// Get counts for dashboard
$pending_orders_query = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
$pending_result = $conn->query($pending_orders_query);
$pending_count = $pending_result->fetch_assoc()['count'];

$inventory_query = "SELECT SUM(quantity) as count FROM inventory";
$inventory_result = $conn->query($inventory_query);
$inventory_count = $inventory_result->fetch_assoc()['count'];

$bookings_query = "SELECT COUNT(*) as count FROM bookings WHERE date >= CURDATE()";
$bookings_result = $conn->query($bookings_query);
$upcoming_bookings = $bookings_result->fetch_assoc()['count'];

$users_query = "SELECT COUNT(*) as count FROM user_accounts";
$users_result = $conn->query($users_query);
$users_count = $users_result->fetch_assoc()['count'];

// Get recent orders
$recent_orders_query = "SELECT o.*, u.name as user_name, u.user_type, u.role, i.name as item_name 
                         FROM orders o 
                         JOIN user_accounts u ON o.user_id = u.id 
                         JOIN inventory i ON o.inventory_id = i.id 
                         ORDER BY o.created_at DESC LIMIT 5";
$recent_orders = $conn->query($recent_orders_query);

// Get upcoming bookings
$upcoming_bookings_query = "SELECT b.*, u.name as user_name, u.organization 
                           FROM bookings b 
                           JOIN user_accounts u ON b.user_id = u.id 
                           WHERE b.date >= CURDATE() 
                           ORDER BY b.date ASC LIMIT 5";
$upcoming_bookings_result = $conn->query($upcoming_bookings_query);
?>


<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
                <div class="flex items-center gap-3">
                    <?php require_once '../includes/notification_bell.php'; ?>
                    <span class="text-gray-700 hidden sm:inline"><?php echo $user_name; ?></span>
                    <button class="md:hidden rounded-md p-2 inline-flex items-center justify-center text-gray-500 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-emerald-500" id="menu-button">
                        <span class="sr-only">Open menu</span>
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </header>
        
        <!-- Main content -->
        <main class="flex-1 overflow-y-auto p-4">
            <div class="max-w-7xl mx-auto">
                <!-- Stats cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Pending Orders</p>
                                <h3 class="text-2xl font-bold"><?php echo $pending_count; ?></h3>
                            </div>
                            <div class="bg-emerald-100 p-3 rounded-full">
                                <i class="fas fa-shopping-cart text-emerald-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Inventory Items</p>
                                <h3 class="text-2xl font-bold"><?php echo $inventory_count; ?></h3>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-box text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Upcoming reservation</p>
                                <h3 class="text-2xl font-bold"><?php echo $upcoming_bookings; ?></h3>
                            </div>
                            <div class="bg-purple-100 p-3 rounded-full">
                                <i class="fas fa-calendar-alt text-purple-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Active Users</p>
                                <h3 class="text-2xl font-bold"><?php echo $users_count; ?></h3>
                            </div>
                            <div class="bg-yellow-100 p-3 rounded-full">
                                <i class="fas fa-users text-yellow-600"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent orders -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                        <h3 class="text-lg font-medium text-gray-900">Recent Orders</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($recent_orders->num_rows > 0): ?>
                                    <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($order['order_id']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($order['item_name']); ?>
                                                <?php if (!empty($order['size'])): ?>
                                                    <span class="text-xs text-gray-400">(<?php echo htmlspecialchars($order['size']); ?>)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($order['user_name']); ?>
                                                <?php 
                                                // Display role for student type users, otherwise display user_type
                                                if ($order['user_type'] === 'student' && !empty($order['role'])) {
                                                    $roleLabels = ['student' => 'Student', 'faculty' => 'Faculty', 'staff' => 'Staff'];
                                                    $displayRole = $roleLabels[$order['role']] ?? 'Student';
                                                    echo '<span class="text-xs text-gray-400">(' . $displayRole . ')</span>';
                                                } else {
                                                    echo '<span class="text-xs text-gray-400">(' . ucfirst($order['user_type']) . ')</span>';
                                                }
                                                ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $order['quantity']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱<?php echo number_format($order['total_price'], 2); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($order['status'] == 'pending'): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                                <?php elseif ($order['status'] == 'completed'): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Completed</span>
                                                <?php else: ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Cancelled</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No recent orders found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                        <a href="orders.php" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">View all orders</a>
                    </div>
                </div>
                
                <!-- Upcoming bookings -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                        <h3 class="text-lg font-medium text-gray-900">Upcoming reservation</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reservation ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Facility</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requester</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($upcoming_bookings_result->num_rows > 0): ?>
                                    <?php while ($booking = $upcoming_bookings_result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $booking['booking_id']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $booking['facility_type']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $booking['user_name']; ?>
                                                <?php if (!empty($booking['organization'])): ?>
                                                    <span class="text-xs text-gray-400">(<?php echo $booking['organization']; ?>)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo format_date($booking['date']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($booking['status'] == 'pending'): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                                <?php elseif ($booking['status'] == 'confirmed'): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Confirmed</span>
                                                <?php else: ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Rejected</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No upcoming reservation found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                        <a href="reservation.php" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">View all reservation</a>
                    </div> -->
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

    <script src="<?php echo $base_url ?? ''; ?>/assets/js/main.js"></script>
</body>
</html>
