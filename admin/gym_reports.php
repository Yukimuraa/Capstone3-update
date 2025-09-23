<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
require_admin();

// Get report parameters
$report_type = isset($_GET['report_type']) ? sanitize_input($_GET['report_type']) : '';

// Set page title based on report type
$page_title = "Gym Reports - CHMSU BAO";
switch ($report_type) {
    case 'usage':
        $page_title = "Gym Usage Report - CHMSU BAO";
        break;
    case 'utilization':
        $page_title = "Facility Utilization Report - CHMSU BAO";
        break;
    case 'status':
        $page_title = "Booking Status Report - CHMSU BAO";
        break;
}

$base_url = "..";

// Generate report data based on type
$report_data = [];
$chart_data = [];

if ($report_type === 'usage') {
    $start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
    $end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : date('Y-m-d');
    
    // Get usage data
    $query = "SELECT b.booking_date, f.name as facility_name, COUNT(*) as booking_count 
              FROM gym_bookings b 
              JOIN gym_facilities f ON b.facility_id = f.id 
              WHERE b.booking_date BETWEEN ? AND ? 
              GROUP BY b.booking_date, f.name 
              ORDER BY b.booking_date ASC, f.name ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $report_data[] = $row;
    }
    
    // Prepare chart data
    $chart_query = "SELECT f.name as facility_name, COUNT(*) as booking_count 
                   FROM gym_bookings b 
                   JOIN gym_facilities f ON b.facility_id = f.id 
                   WHERE b.booking_date BETWEEN ? AND ? 
                   GROUP BY f.name 
                   ORDER BY booking_count DESC";
    
    $chart_stmt = $conn->prepare($chart_query);
    $chart_stmt->bind_param("ss", $start_date, $end_date);
    $chart_stmt->execute();
    $chart_result = $chart_stmt->get_result();
    
    $labels = [];
    $data = [];
    
    while ($row = $chart_result->fetch_assoc()) {
        $labels[] = $row['facility_name'];
        $data[] = $row['booking_count'];
    }
    
    $chart_data = [
        'labels' => $labels,
        'data' => $data
    ];
} elseif ($report_type === 'utilization') {
    $month = isset($_GET['month']) ? sanitize_input($_GET['month']) : date('Y-m');
    $facility_id = isset($_GET['facility_id']) ? (int)$_GET['facility_id'] : 0;
    
    // Get start and end date of the month
    $start_date = $month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    
    // Build query based on facility filter
    $query = "SELECT f.id, f.name as facility_name, f.capacity,
              COUNT(b.id) as booking_count,
              COUNT(CASE WHEN b.status = 'approved' THEN 1 ELSE NULL END) as approved_count,
              COUNT(CASE WHEN b.status = 'rejected' THEN 1 ELSE NULL END) as rejected_count,
              COUNT(CASE WHEN b.status = 'cancelled' THEN 1 ELSE NULL END) as cancelled_count
              FROM gym_facilities f
              LEFT JOIN gym_bookings b ON f.id = b.facility_id AND b.booking_date BETWEEN ? AND ?";
    
    $params = [$start_date, $end_date];
    $types = "ss";
    
    if ($facility_id > 0) {
        $query .= " WHERE f.id = ?";
        $params[] = $facility_id;
        $types .= "i";
    }
    
    $query .= " GROUP BY f.id, f.name, f.capacity ORDER BY f.name ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Calculate utilization percentage
        $days_in_month = date('t', strtotime($start_date));
        $max_possible_bookings = $days_in_month; // Assuming 1 booking per day
        $utilization_rate = ($row['approved_count'] / $max_possible_bookings) * 100;
        
        $row['utilization_rate'] = round($utilization_rate, 2);
        $report_data[] = $row;
    }
    
    // Prepare chart data
    $labels = [];
    $data = [];
    
    foreach ($report_data as $row) {
        $labels[] = $row['facility_name'];
        $data[] = $row['utilization_rate'];
    }
    
    $chart_data = [
        'labels' => $labels,
        'data' => $data
    ];
} elseif ($report_type === 'status') {
    $status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
    $period = isset($_GET['period']) ? sanitize_input($_GET['period']) : 'month';
    
    // Determine date range based on period
    $end_date = date('Y-m-d');
    switch ($period) {
        case 'week':
            $start_date = date('Y-m-d', strtotime('-1 week'));
            break;
        case 'month':
            $start_date = date('Y-m-d', strtotime('-1 month'));
            break;
        case 'quarter':
            $start_date = date('Y-m-d', strtotime('-3 months'));
            break;
        case 'year':
            $start_date = date('Y-m-d', strtotime('-1 year'));
            break;
        default:
            $start_date = date('Y-m-d', strtotime('-1 month'));
    }
    
    // Build query based on status filter
    $query = "SELECT b.status, f.name as facility_name, COUNT(*) as booking_count 
              FROM gym_bookings b 
              JOIN gym_facilities f ON b.facility_id = f.id 
              WHERE b.booking_date BETWEEN ? AND ?";
    
    $params = [$start_date, $end_date];
    $types = "ss";
    
    if (!empty($status)) {
        $query .= " AND b.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $query .= " GROUP BY b.status, f.name ORDER BY b.status, f.name";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $report_data[] = $row;
    }
    
    // Prepare chart data
    $status_query = "SELECT b.status, COUNT(*) as count 
                    FROM gym_bookings b 
                    WHERE b.booking_date BETWEEN ? AND ?";
    
    $status_params = [$start_date, $end_date];
    $status_types = "ss";
    
    if (!empty($status)) {
        $status_query .= " AND b.status = ?";
        $status_params[] = $status;
        $status_types .= "s";
    }
    
    $status_query .= " GROUP BY b.status";
    
    $status_stmt = $conn->prepare($status_query);
    $status_stmt->bind_param($status_types, ...$status_params);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();
    
    $labels = [];
    $data = [];
    
    while ($row = $status_result->fetch_assoc()) {
        $labels[] = ucfirst($row['status']);
        $data[] = $row['count'];
    }
    
    $chart_data = [
        'labels' => $labels,
        'data' => $data
    ];
}
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900"><?php echo $page_title; ?></h1>
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
                <!-- Report Header -->
                <div class="mb-6 bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-900">
                            <?php if ($report_type === 'usage'): ?>
                                Gym Usage Report
                            <?php elseif ($report_type === 'utilization'): ?>
                                Facility Utilization Report
                            <?php elseif ($report_type === 'status'): ?>
                                Booking Status Report
                            <?php else: ?>
                                Gym Report
                            <?php endif; ?>
                        </h2>
                        <div>
                            <button onclick="window.print()" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <i class="fas fa-print mr-1"></i> Print Report
                            </button>
                            <a href="gym_management.php" class="ml-2 bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                <i class="fas fa-arrow-left mr-1"></i> Back
                            </a>
                        </div>
                    </div>
                    
                    <div class="text-sm text-gray-500">
                        <?php if ($report_type === 'usage'): ?>
                            <p>Report Period: <?php echo date('F j, Y', strtotime($start_date)); ?> to <?php echo date('F j, Y', strtotime($end_date)); ?></p>
                        <?php elseif ($report_type === 'utilization'): ?>
                            <p>Report Month: <?php echo date('F Y', strtotime($month)); ?></p>
                            <?php if ($facility_id > 0): ?>
                                <?php 
                                $facility_query = "SELECT name FROM gym_facilities WHERE id = ?";
                                $facility_stmt = $conn->prepare($facility_query);
                                $facility_stmt->bind_param("i", $facility_id);
                                $facility_stmt->execute();
                                $facility_result = $facility_stmt->get_result();
                                $facility = $facility_result->fetch_assoc();
                                ?>
                                <p>Facility: <?php echo $facility['name']; ?></p>
                            <?php else: ?>
                                <p>Facility: All Facilities</p>
                            <?php endif; ?>
                        <?php elseif ($report_type === 'status'): ?>
                            <p>Report Period: <?php echo date('F j, Y', strtotime($start_date)); ?> to <?php echo date('F j, Y', strtotime($end_date)); ?></p>
                            <?php if (!empty($status)): ?>
                                <p>Status: <?php echo ucfirst($status); ?></p>
                            <?php else: ?>
                                <p>Status: All Statuses</p>
                            <?php endif; ?>
                        <?php endif; ?>
                        <p>Generated on: <?php echo date('F j, Y, g:i a'); ?></p>
                    </div>
                </div>
                
                <!-- Chart -->
                <div class="mb-6 bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Report Visualization</h3>
                    <div class="h-64">
                        <canvas id="reportChart"></canvas>
                    </div>
                </div>
                
                <!-- Report Data -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                        <h3 class="text-lg font-medium text-gray-900">Report Data</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <?php if ($report_type === 'usage'): ?>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Facility</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking Count</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (count($report_data) > 0): ?>
                                        <?php foreach ($report_data as $row): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('F j, Y', strtotime($row['booking_date'])); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['facility_name']; ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['booking_count']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No data found for the selected period</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        <?php elseif ($report_type === 'utilization'): ?>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Facility</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacity</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Bookings</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rejected</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cancelled</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilization Rate</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (count($report_data) > 0): ?>
                                        <?php foreach ($report_data as $row): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['facility_name']; ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['capacity']; ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['booking_count']; ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['approved_count']; ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['rejected_count']; ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['cancelled_count']; ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <div class="flex items-center">
                                                        <span class="mr-2"><?php echo $row['utilization_rate']; ?>%</span>
                                                        <div class="w-24 bg-gray-200 rounded-full h-2.5">
                                                            <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo min($row['utilization_rate'], 100); ?>%"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No data found for the selected period</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        <?php elseif ($report_type === 'status'): ?>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Facility</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking Count</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (count($report_data) > 0): ?>
                                        <?php foreach ($report_data as $row): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <?php 
                                                    $status_class = '';
                                                    switch ($row['status']) {
                                                        case 'pending':
                                                            $status_class = 'bg-yellow-100 text-yellow-800';
                                                            break;
                                                        case 'approved':
                                                            $status_class = 'bg-green-100 text-green-800';
                                                            break;
                                                        case 'rejected':
                                                            $status_class = 'bg-red-100 text-red-800';
                                                            break;
                                                        case 'cancelled':
                                                            $status_class = 'bg-gray-100 text-gray-800';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                        <?php echo ucfirst($row['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['facility_name']; ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['booking_count']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No data found for the selected period</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
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
    
    // Initialize chart
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('reportChart').getContext('2d');
        
        <?php if (!empty($chart_data)): ?>
            const chartData = {
                labels: <?php echo json_encode($chart_data['labels']); ?>,
                datasets: [{
                    label: '<?php 
                        if ($report_type === 'usage') echo 'Booking Count';
                        elseif ($report_type === 'utilization') echo 'Utilization Rate (%)';
                        elseif ($report_type === 'status') echo 'Booking Count by Status';
                    ?>',
                    data: <?php echo json_encode($chart_data['data']); ?>,
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            };
            
            const chartConfig = {
                type: '<?php echo ($report_type === 'status') ? 'pie' : 'bar'; ?>',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    <?php if ($report_type !== 'status'): ?>
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                    <?php endif; ?>
                }
            };
            
            new Chart(ctx, chartConfig);
        <?php endif; ?>
    });
    
    // Print styles
    window.onbeforeprint = function() {
        document.querySelectorAll('button, a').forEach(function(el) {
            el.style.display = 'none';
        });
    };
    
    window.onafterprint = function() {
        document.querySelectorAll('button, a').forEach(function(el) {
            el.style.display = '';
        });
    };
</script>

<?php include '../includes/footer.php'; ?>
