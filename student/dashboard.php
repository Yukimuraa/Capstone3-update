<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is student
require_student();

// Get user data for the student
$user_id = $_SESSION['user_sessions']['student']['user_id'];
$user_name = $_SESSION['user_sessions']['student']['user_name'];
$user_role = $_SESSION['user_sessions']['student']['role'] ?? null;

// If role is not in session, fetch it from database
if (empty($user_role)) {
    $role_stmt = $conn->prepare("SELECT role FROM user_accounts WHERE id = ?");
    $role_stmt->bind_param("i", $user_id);
    $role_stmt->execute();
    $role_result = $role_stmt->get_result();
    if ($role_result->num_rows > 0) {
        $user_data = $role_result->fetch_assoc();
        $user_role = $user_data['role'] ?? 'student';
        // Update session with role
        $_SESSION['user_sessions']['student']['role'] = $user_role;
    } else {
        $user_role = 'student'; // Default fallback
    }
}

// Determine dashboard title based on role
$role_labels = [
    'student' => 'Student',
    'faculty' => 'Faculty',
    'staff' => 'Staff'
];
$role_label = $role_labels[$user_role] ?? 'Student';
$dashboard_title = $role_label . " Dashboard";

$page_title = $dashboard_title . " - CHMSU BAO";
$base_url = "..";

// Get pending orders count
$pending_orders_query = "SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'pending'";
$stmt = $conn->prepare($pending_orders_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_result = $stmt->get_result();
$pending_count = $pending_result->fetch_assoc()['count'];

// Get completed orders count
$completed_orders_query = "SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'completed'";
$stmt = $conn->prepare($completed_orders_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$completed_result = $stmt->get_result();
$completed_count = $completed_result->fetch_assoc()['count'];

// Get recent activity count (last 30 days)
$recent_activity_query = "SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$stmt = $conn->prepare($recent_activity_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$activity_result = $stmt->get_result();
$activity_count = $activity_result->fetch_assoc()['count'];

// Get recent orders with item details
$recent_orders_query = "SELECT o.*, i.name as item_name, i.description as item_description, i.price as item_price 
                        FROM orders o 
                        JOIN inventory i ON o.inventory_id = i.id 
                        WHERE o.user_id = ? 
                        ORDER BY o.created_at DESC 
                        LIMIT 6";
$stmt = $conn->prepare($recent_orders_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_orders = $stmt->get_result();

// Get available inventory items
$inventory_query = "SELECT * FROM inventory WHERE in_stock = 1 LIMIT 6";
$inventory_result = $conn->query($inventory_query);

// Get gym booking statistics
$gym_stats_query = "SELECT 
                COUNT(*) as total_bookings,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as approved_bookings,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_bookings,
                SUM(CASE WHEN status = 'cancelled' OR status = 'cancel' THEN 1 ELSE 0 END) as cancelled_bookings
                FROM bookings
                WHERE user_id = ?";
$stmt = $conn->prepare($gym_stats_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$gym_stats = $stmt->get_result()->fetch_assoc();

// Get recent gym bookings
$recent_gym_bookings_query = "SELECT * FROM bookings 
                            WHERE user_id = ? 
                            ORDER BY created_at DESC 
                            LIMIT 5";
$stmt = $conn->prepare($recent_gym_bookings_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_gym_bookings = $stmt->get_result();

// Get upcoming gym bookings
$upcoming_gym_bookings_query = "SELECT * FROM bookings 
                              WHERE user_id = ? AND date >= CURDATE() AND (status = 'confirmed' OR status = 'pending')
                              ORDER BY date ASC
                              LIMIT 3";
$stmt = $conn->prepare($upcoming_gym_bookings_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcoming_gym_bookings = $stmt->get_result();

// Get user profile picture
$user_stmt = $conn->prepare("SELECT profile_pic FROM user_accounts WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$profile_pic = $user_data['profile_pic'] ?? '';
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/student_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900"><?php echo $dashboard_title; ?></h1>
                <div class="flex items-center gap-3">
                    <?php require_once '../includes/notification_bell.php'; ?>
                    <a href="profile.php" class="flex items-center">
                        <?php if (!empty($profile_pic) && file_exists('../' . $profile_pic)): ?>
                            <img src="../<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover border-2 border-gray-300 hover:border-blue-500 transition-colors cursor-pointer">
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center border-2 border-gray-300 hover:border-blue-500 transition-colors cursor-pointer">
                                <i class="fas fa-user text-gray-600"></i>
                            </div>
                        <?php endif; ?>
                    </a>
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
                <!-- Tabs for Recent Orders and Available Items -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px">
                            <button id="tab-orders" class="tab-button active py-4 px-6 border-b-2 border-emerald-500 font-medium text-sm text-emerald-600">
                                Recent Orders
                            </button>
                            <!-- <button id="tab-inventory" class="tab-button py-4 px-6 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                Available Items
                            </button> -->
                        </nav>
                    </div>
                    
                    <!-- Recent Orders Tab Content -->
                    <div id="content-orders" class="tab-content p-4">
                        <div class="grid gap-4 grid-cols-1 md:grid-cols-3 grid-rows-2">
                            <?php if ($recent_orders->num_rows > 0): ?>
                                <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                    <div class="bg-white border rounded-lg shadow-sm p-4">
                                        <h3 class="font-medium text-lg"><?php echo htmlspecialchars($order['item_name']); ?></h3>
                                        <p class="text-sm text-gray-500 mb-2">Order #<?php echo htmlspecialchars($order['order_id']); ?></p>
                                        <p class="text-sm text-gray-500 mb-2">Ordered on <?php echo format_date($order['created_at']); ?></p>
                                        <div class="flex items-center gap-2 mb-2">
                                            <?php if ($order['status'] == 'pending'): ?>
                                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-800">
                                                    Pending
                                                </span>
                                            <?php elseif ($order['status'] == 'completed'): ?>
                                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800">
                                                    Completed
                                                </span>
                                            <?php elseif ($order['status'] == 'cancelled'): ?>
                                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-800">
                                                    Cancelled
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-sm text-gray-600">
                                            Quantity: <?php echo $order['quantity']; ?>
                                            <?php if (!empty($order['size'])): ?>
                                                | Size: <?php echo htmlspecialchars($order['size']); ?>
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-sm font-medium text-gray-900 mt-2">₱<?php echo number_format($order['total_price'], 2); ?></p>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="col-span-3 text-center py-4 text-gray-500">
                                    No recent orders found. <a href="inventory.php" class="text-emerald-600 hover:underline">Browse items</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="orders.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                View All Orders
                            </a>
                        </div>
                    </div>
                    
                    <!-- Available Items Tab Content -->
                    <div id="content-inventory" class="tab-content p-4 hidden">
                        <div class="grid gap-4 grid-cols-1 md:grid-cols-3 grid-rows-2">
                            <?php if ($inventory_result->num_rows > 0): ?>
                                <?php while ($item = $inventory_result->fetch_assoc()): ?>
                                    <div class="bg-white border rounded-lg shadow-sm p-4">
                                        <h3 class="font-medium text-lg"><?php echo $item['name']; ?></h3>
                                        <p class="text-sm text-gray-500 mb-2"><?php echo $item['description']; ?></p>
                                        <p class="font-medium mb-4">₱<?php echo number_format($item['price'], 2); ?></p>
                                        <a href="order_item.php?id=<?php echo $item['id']; ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700">
                                            Order Now
                                        </a>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="col-span-3 text-center py-4 text-gray-500">
                                    No items available at the moment.
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="inventory.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                View All Items
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Gym Reservation Section -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="px-4 py-5 border-b border-gray-200 sm:px-6 flex justify-between items-center">
                        <h2 class="text-xl font-bold text-gray-900">Gym Reservation</h2>
                        <a href="gym.php" class="text-emerald-600 hover:text-emerald-800 text-sm font-medium">Make Reservation</a>
                    </div>
                    
                    <!-- Gym Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-6 border-b border-gray-200">
                        <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4">
                                    <i class="fas fa-calendar-check text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-sm">Total Bookings</p>
                                    <h3 class="text-2xl font-semibold text-gray-800"><?php echo $gym_stats['total_bookings'] ?? 0; ?></h3>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-yellow-100 text-yellow-500 mr-4">
                                    <i class="fas fa-clock text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-sm">Pending</p>
                                    <h3 class="text-2xl font-semibold text-gray-800"><?php echo $gym_stats['pending_bookings'] ?? 0; ?></h3>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100 text-green-500 mr-4">
                                    <i class="fas fa-check-circle text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-sm">Approved</p>
                                    <h3 class="text-2xl font-semibold text-gray-800"><?php echo $gym_stats['approved_bookings'] ?? 0; ?></h3>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-red-100 text-red-500 mr-4">
                                    <i class="fas fa-times-circle text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-sm">Rejected/Cancelled</p>
                                    <h3 class="text-2xl font-semibold text-gray-800"><?php echo ($gym_stats['rejected_bookings'] ?? 0) + ($gym_stats['cancelled_bookings'] ?? 0); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Main Content Sections -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 p-6">
                        <!-- Recent Gym Bookings -->
                        <div class="lg:col-span-2">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-xl font-bold text-gray-900">My Recent Reservations</h2>
                                <a href="gym.php" class="text-emerald-600 hover:text-emerald-800 text-sm">View All</a>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Facility</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php if ($recent_gym_bookings->num_rows > 0): ?>
                                            <?php while ($booking = $recent_gym_bookings->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?php echo htmlspecialchars($booking['facility_name'] ?? 'Gym Facility'); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?php echo date('M d, Y', strtotime($booking['date'])); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?php 
                                                        if (!empty($booking['start_time']) && !empty($booking['end_time'])) {
                                                            echo date('h:i A', strtotime($booking['start_time'])) . ' - ' . date('h:i A', strtotime($booking['end_time']));
                                                        } elseif (!empty($booking['time_slot'])) {
                                                            echo $booking['time_slot'];
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?php echo htmlspecialchars($booking['purpose'] ?? 'N/A'); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <?php
                                                        $status = strtolower($booking['status'] ?? 'pending');
                                                        if ($status == 'pending'): ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                                        <?php elseif ($status == 'approved' || $status == 'confirmed'): ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800"><?php echo ucfirst($status == 'approved' ? 'Approved' : 'Confirmed'); ?></span>
                                                        <?php elseif ($status == 'rejected'): ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Rejected</span>
                                                        <?php else: ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800"><?php echo ucfirst($status); ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                                    No recent reservations found. <a href="gym.php" class="text-emerald-600 hover:underline">Make a reservation</a>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Upcoming Gym Bookings -->
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Upcoming Reservations</h2>
                            <div class="space-y-4">
                                <?php if ($upcoming_gym_bookings->num_rows > 0): ?>
                                    <?php while ($booking = $upcoming_gym_bookings->fetch_assoc()): ?>
                                        <div class="border rounded-lg p-4">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($booking['booking_id'] ?? 'Gym Reservation'); ?></h3>
                                                    <p class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($booking['date'])); ?></p>
                                                    <p class="text-sm text-gray-500">
                                                        <?php 
                                                        if (!empty($booking['start_time']) && !empty($booking['end_time'])) {
                                                            echo date('h:i A', strtotime($booking['start_time'])) . ' - ' . date('h:i A', strtotime($booking['end_time']));
                                                        } elseif (!empty($booking['time_slot'])) {
                                                            echo $booking['time_slot'];
                                                        } else {
                                                            echo 'Time TBD';
                                                        }
                                                        ?>
                                                    </p>
                                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['purpose'] ?? 'N/A'); ?></p>
                                                </div>
                                                <div class="text-right">
                                                    <?php
                                                    $status = strtolower($booking['status'] ?? 'pending');
                                                    if ($status == 'pending'): ?>
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                                    <?php elseif ($status == 'approved' || $status == 'confirmed'): ?>
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800"><?php echo ucfirst($status == 'approved' ? 'Approved' : 'Confirmed'); ?></span>
                                                    <?php else: ?>
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800"><?php echo ucfirst($status); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p class="text-center text-gray-500">No upcoming reservations</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
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
    
    // Tab functionality
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.classList.remove('border-emerald-500');
                btn.classList.remove('text-emerald-600');
                btn.classList.add('border-transparent');
                btn.classList.add('text-gray-500');
            });
            
            // Add active class to clicked button
            button.classList.add('active');
            button.classList.add('border-emerald-500');
            button.classList.add('text-emerald-600');
            button.classList.remove('border-transparent');
            button.classList.remove('text-gray-500');
            
            // Hide all tab contents
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });
            
            // Show the corresponding tab content
            const contentId = 'content-' + button.id.split('-')[1];
            document.getElementById(contentId).classList.remove('hidden');
        });
    });
</script>

    <script src="<?php echo $base_url ?? ''; ?>/assets/js/main.js"></script>
</body>
</html>
