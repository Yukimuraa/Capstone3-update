<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is student
require_student();

// Get user data for the student
$user_id = $_SESSION['user_sessions']['student']['user_id'];
$user_name = $_SESSION['user_sessions']['student']['user_name'];

$page_title = "Student Dashboard - CHMSU BAO";
$base_url = "..";

// Get pending requests count
$pending_requests_query = "SELECT COUNT(*) as count FROM requests WHERE user_id = ? AND status = 'pending'";
$stmt = $conn->prepare($pending_requests_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_result = $stmt->get_result();
$pending_count = $pending_result->fetch_assoc()['count'];

// Get orders ready for pickup
$ready_orders_query = "SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'approved'";
$stmt = $conn->prepare($ready_orders_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$ready_result = $stmt->get_result();
$ready_count = $ready_result->fetch_assoc()['count'];

// Get recent activity count (last 30 days)
$recent_activity_query = "SELECT COUNT(*) as count FROM 
                         (SELECT id FROM requests WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                         UNION ALL
                         SELECT id FROM orders WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as activity";
$stmt = $conn->prepare($recent_activity_query);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$activity_result = $stmt->get_result();
$activity_count = $activity_result->fetch_assoc()['count'];

// Get recent requests
$recent_requests_query = "SELECT * FROM requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 6";
$stmt = $conn->prepare($recent_requests_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_requests = $stmt->get_result();

// Get available inventory items
$inventory_query = "SELECT * FROM inventory WHERE in_stock = 1 LIMIT 6";
$inventory_result = $conn->query($inventory_query);
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/student_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">Student Dashboard</h1>
                <div class="flex items-center">
                    <span class="text-gray-700 mr-2"><?php echo $user_name; ?></span>
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
                <!-- Tabs for Recent Requests and Available Items -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px">
                            <button id="tab-requests" class="tab-button active py-4 px-6 border-b-2 border-emerald-500 font-medium text-sm text-emerald-600">
                                Recent Requests
                            </button>
                            <!-- <button id="tab-inventory" class="tab-button py-4 px-6 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                Available Items
                            </button> -->
                        </nav>
                    </div>
                    
                    <!-- Recent Requests Tab Content -->
                    <div id="content-requests" class="tab-content p-4">
                        <div class="grid gap-4 grid-cols-1 md:grid-cols-3 grid-rows-2">
                            <?php if ($recent_requests->num_rows > 0): ?>
                                <?php while ($request = $recent_requests->fetch_assoc()): ?>
                                    <div class="bg-white border rounded-lg shadow-sm p-4">
                                        <h3 class="font-medium text-lg"><?php echo $request['type']; ?></h3>
                                        <p class="text-sm text-gray-500 mb-2">Submitted on <?php echo format_date($request['created_at']); ?></p>
                                        <div class="flex items-center gap-2 mb-2">
                                            <?php if ($request['status'] == 'pending'): ?>
                                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-800">
                                                    Pending
                                                </span>
                                            <?php elseif ($request['status'] == 'approved'): ?>
                                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">
                                                    Approved
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800">
                                                    Rejected
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-sm text-gray-600"><?php echo $request['details']; ?></p>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="col-span-3 text-center py-4 text-gray-500">
                                    No recent requests found. <a href="requests.php" class="text-emerald-600 hover:underline">Submit a request</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="requests.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                View All Requests
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

<?php include '../includes/footer.php'; ?>
