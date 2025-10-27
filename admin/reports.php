<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
require_admin();

$page_title = "Revenue Reports - CHMSU BAO";
$base_url = "..";

// Get filter parameters
$period = isset($_GET['period']) ? sanitize_input($_GET['period']) : 'all';
$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : '';

// Calculate date range based on period
switch ($period) {
    case 'today':
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
        break;
    case 'week':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $end_date = date('Y-m-d');
        break;
    case 'month':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        break;
    case 'year':
        $start_date = date('Y-01-01');
        $end_date = date('Y-12-31');
        break;
    case 'custom':
        // Use the provided start_date and end_date
        if (empty($start_date)) $start_date = date('Y-m-d', strtotime('-30 days'));
        if (empty($end_date)) $end_date = date('Y-m-d');
        break;
    case 'all':
    default:
        $start_date = '';
        $end_date = '';
        break;
}

// Get overall statistics
$overall_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(quantity) as total_items_sold,
    SUM(total_price) as total_revenue
FROM orders 
WHERE status = 'completed'";

if (!empty($start_date) && !empty($end_date)) {
    $overall_query .= " AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
}

$overall_stats = $conn->query($overall_query)->fetch_assoc();

// Get orders with details
$orders_query = "SELECT 
    o.id,
    o.order_id,
    o.quantity,
    o.total_price,
    o.created_at,
    u.name as customer_name,
    u.email as customer_email,
    i.name as item_name
FROM orders o
JOIN users u ON o.user_id = u.id
JOIN inventory i ON o.inventory_id = i.id
WHERE o.status = 'completed'";

if (!empty($start_date) && !empty($end_date)) {
    $orders_query .= " AND DATE(o.created_at) BETWEEN '$start_date' AND '$end_date'";
}

$orders_query .= " ORDER BY o.created_at DESC";
$orders_result = $conn->query($orders_query);

// Get top selling items
$top_items_query = "SELECT 
    i.name as item_name,
    COUNT(*) as order_count,
    SUM(o.quantity) as total_quantity,
    SUM(o.total_price) as total_revenue
FROM orders o
JOIN inventory i ON o.inventory_id = i.id
WHERE o.status = 'completed'";

if (!empty($start_date) && !empty($end_date)) {
    $top_items_query .= " AND DATE(o.created_at) BETWEEN '$start_date' AND '$end_date'";
}

$top_items_query .= " GROUP BY o.inventory_id, i.name ORDER BY total_revenue DESC LIMIT 10";
$top_items_result = $conn->query($top_items_query);

// Get daily revenue for chart
$daily_revenue_query = "SELECT 
    DATE(created_at) as date,
    COUNT(*) as orders,
    SUM(total_price) as revenue
FROM orders
WHERE status = 'completed'";

if (!empty($start_date) && !empty($end_date)) {
    $daily_revenue_query .= " AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
}

$daily_revenue_query .= " GROUP BY DATE(created_at) ORDER BY date ASC";
$daily_revenue_result = $conn->query($daily_revenue_query);

$chart_dates = [];
$chart_revenues = [];
while ($row = $daily_revenue_result->fetch_assoc()) {
    $chart_dates[] = date('M d', strtotime($row['date']));
    $chart_revenues[] = $row['revenue'];
}
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">Revenue Reports</h1>
                <div class="flex items-center">
                    <span class="text-gray-700 mr-2"><?php echo $_SESSION['user_name']; ?></span>
                    <button class="md:hidden rounded-md p-2 inline-flex items-center justify-center text-gray-500 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" id="menu-button">
                        <span class="sr-only">Open menu</span>
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </header>
        
        <!-- Main content -->
        <main class="flex-1 overflow-y-auto p-4">
            <div class="max-w-7xl mx-auto">
                <!-- Filter Section -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Filter Reports</h2>
                    <form method="GET" action="reports.php" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="period" class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                            <select name="period" id="period" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                <option value="all" <?php echo $period === 'all' ? 'selected' : ''; ?>>All Time</option>
                                <option value="today" <?php echo $period === 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>This Month</option>
                                <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>This Year</option>
                                <option value="custom" <?php echo $period === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                            </select>
                        </div>
                        <div id="custom-dates" class="col-span-2 <?php echo $period !== 'custom' ? 'hidden' : ''; ?>">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                    <input type="date" name="start_date" id="start_date" value="<?php echo $start_date; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                </div>
                                <div>
                                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                    <input type="date" name="end_date" id="end_date" value="<?php echo $end_date; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                </div>
                            </div>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <i class="fas fa-filter mr-2"></i>Apply Filter
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Total Revenue -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 mb-1">Total Revenue</p>
                                <p class="text-3xl font-bold text-green-600">₱<?php echo number_format($overall_stats['total_revenue'] ?? 0, 2); ?></p>
                            </div>
                            <div class="bg-green-100 rounded-full p-3">
                                <i class="fas fa-dollar-sign text-green-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Orders -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 mb-1">Total Orders</p>
                                <p class="text-3xl font-bold text-blue-600"><?php echo number_format($overall_stats['total_orders'] ?? 0); ?></p>
                            </div>
                            <div class="bg-blue-100 rounded-full p-3">
                                <i class="fas fa-shopping-cart text-blue-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Items Sold -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 mb-1">Items Sold</p>
                                <p class="text-3xl font-bold text-purple-600"><?php echo number_format($overall_stats['total_items_sold'] ?? 0); ?></p>
                            </div>
                            <div class="bg-purple-100 rounded-full p-3">
                                <i class="fas fa-box text-purple-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Chart -->
                <?php if (!empty($chart_dates)): ?>
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Revenue Trend</h3>
                    <div class="h-64">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Top Selling Items -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                        <h3 class="text-lg font-medium text-gray-900">Top Selling Items</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity Sold</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($top_items_result->num_rows > 0): ?>
                                    <?php while ($item = $top_items_result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['item_name']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right"><?php echo number_format($item['order_count']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right"><?php echo number_format($item['total_quantity']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600 text-right">₱<?php echo number_format($item['total_revenue'], 2); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-4 py-5 border-b border-gray-200 sm:px-6 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Recent Orders</h3>
                        <button onclick="window.print()" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <i class="fas fa-print mr-1"></i> Print Report
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Price</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($orders_result->num_rows > 0): ?>
                                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($order['order_id']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($order['item_name']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right"><?php echo number_format($order['quantity']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600 text-right">₱<?php echo number_format($order['total_price'], 2); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No orders found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Mobile menu toggle
    document.getElementById('menu-button').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
    });
    
    // Show/hide custom date range
    document.getElementById('period').addEventListener('change', function() {
        const customDates = document.getElementById('custom-dates');
        if (this.value === 'custom') {
            customDates.classList.remove('hidden');
        } else {
            customDates.classList.add('hidden');
        }
    });
    
    // Revenue Chart
    <?php if (!empty($chart_dates)): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('revenueChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_dates); ?>,
                datasets: [{
                    label: 'Revenue (₱)',
                    data: <?php echo json_encode($chart_revenues); ?>,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: ₱' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    });
    <?php endif; ?>
    
    // Print styles
    window.onbeforeprint = function() {
        document.querySelectorAll('button').forEach(function(el) {
            el.style.display = 'none';
        });
    };
    
    window.onafterprint = function() {
        document.querySelectorAll('button').forEach(function(el) {
            el.style.display = '';
        });
    };
</script>

<?php include '../includes/footer.php'; ?>

