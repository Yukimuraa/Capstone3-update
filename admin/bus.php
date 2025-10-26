<?php
// admin/bus.php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is admin
require_admin();

$title = "Bus Management System";
$page_title = $title;

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_bus') {
            $bus_number = trim($_POST['bus_number']);
            $vehicle_type = trim($_POST['vehicle_type']);
            $capacity = intval($_POST['capacity']);
            $status = $_POST['status'];
            
            // Validate inputs
            if (empty($bus_number) || empty($vehicle_type) || $capacity <= 0) {
                $error = 'Please fill in all fields correctly.';
            } else {
                // Check if bus number already exists
                $check_stmt = $conn->prepare("SELECT id FROM buses WHERE bus_number = ?");
                $check_stmt->bind_param("s", $bus_number);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error = 'Bus number already exists. Please use a different number.';
                } else {
                    // Insert new bus
                    $insert_stmt = $conn->prepare("INSERT INTO buses (bus_number, vehicle_type, capacity, status) VALUES (?, ?, ?, ?)");
                    $insert_stmt->bind_param("ssis", $bus_number, $vehicle_type, $capacity, $status);
                    
                    if ($insert_stmt->execute()) {
                        $success = 'Bus added successfully!';
                    } else {
                        $error = 'Error adding bus: ' . $conn->error;
                    }
                }
            }
        } elseif ($_POST['action'] === 'update_bus_status') {
            $bus_id = intval($_POST['bus_id']);
            $new_status = $_POST['new_status'];
            
            $update_stmt = $conn->prepare("UPDATE buses SET status = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_status, $bus_id);
            
            if ($update_stmt->execute()) {
                $success = 'Bus status updated successfully!';
            } else {
                $error = 'Error updating bus status: ' . $conn->error;
            }
        } elseif ($_POST['action'] === 'delete_bus') {
            $bus_id = intval($_POST['bus_id']);
            
            // Check if bus has active bookings
            $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM bus_bookings WHERE bus_id = ? AND status = 'active'");
            $check_stmt->bind_param("i", $bus_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result()->fetch_assoc();
            
            if ($result['count'] > 0) {
                $error = 'Cannot delete bus with active bookings. Please complete or cancel the bookings first.';
            } else {
                $delete_stmt = $conn->prepare("DELETE FROM buses WHERE id = ?");
                $delete_stmt->bind_param("i", $bus_id);
                
                if ($delete_stmt->execute()) {
                    $success = 'Bus deleted successfully!';
                } else {
                    $error = 'Error deleting bus: ' . $conn->error;
                }
            }
        } elseif ($_POST['action'] === 'update_fuel_rate') {
            $new_fuel_rate = floatval($_POST['fuel_rate']);
            
            if ($new_fuel_rate <= 0) {
                $error = 'Please enter a valid fuel rate.';
            } else {
                // Create settings table if not exists
                $conn->query("CREATE TABLE IF NOT EXISTS bus_settings (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    setting_key VARCHAR(50) UNIQUE NOT NULL,
                    setting_value VARCHAR(255) NOT NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )");
                
                // Update or insert fuel rate
                $stmt = $conn->prepare("INSERT INTO bus_settings (setting_key, setting_value) VALUES ('fuel_rate', ?) 
                                       ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param("ss", $new_fuel_rate, $new_fuel_rate);
                
                if ($stmt->execute()) {
                    $success = 'Fuel rate updated successfully to ₱' . number_format($new_fuel_rate, 2) . ' per liter!';
                } else {
                    $error = 'Error updating fuel rate: ' . $conn->error;
                }
            }
        } elseif ($_POST['action'] === 'approve_schedule') {
            $schedule_id = intval($_POST['schedule_id']);
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Update schedule status
                $update_stmt = $conn->prepare("UPDATE bus_schedules SET status = 'approved' WHERE id = ?");
                $update_stmt->bind_param("i", $schedule_id);
                $update_stmt->execute();
                
                // Get schedule details
                $schedule_query = "SELECT * FROM bus_schedules WHERE id = ?";
                $schedule_stmt = $conn->prepare($schedule_query);
                $schedule_stmt->bind_param("i", $schedule_id);
                $schedule_stmt->execute();
                $schedule = $schedule_stmt->get_result()->fetch_assoc();
                
                // Create bus bookings for each vehicle
                $available_buses = $conn->query("SELECT id FROM buses WHERE status = 'available' LIMIT " . $schedule['no_of_vehicles']);
                $bus_ids = [];
                while ($bus = $available_buses->fetch_assoc()) {
                    $bus_ids[] = $bus['id'];
                }
                
                if (count($bus_ids) >= $schedule['no_of_vehicles']) {
                    foreach ($bus_ids as $bus_id) {
                        $booking_stmt = $conn->prepare("INSERT INTO bus_bookings (schedule_id, bus_id, booking_date, status) VALUES (?, ?, ?, 'active')");
                        $booking_stmt->bind_param("iis", $schedule_id, $bus_id, $schedule['date_covered']);
                        $booking_stmt->execute();
                    }
                    
                    // Update bus status to booked
                    $bus_update_stmt = $conn->prepare("UPDATE buses SET status = 'booked' WHERE id IN (" . implode(',', array_fill(0, count($bus_ids), '?')) . ")");
                    $bus_update_stmt->bind_param(str_repeat('i', count($bus_ids)), ...$bus_ids);
                    $bus_update_stmt->execute();
                    
                    $conn->commit();
                    $success = 'Schedule approved and buses allocated successfully!';
                } else {
                    throw new Exception('Not enough buses available for this booking.');
                }
            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        } elseif ($_POST['action'] === 'reject_schedule') {
            $schedule_id = intval($_POST['schedule_id']);
            $update_stmt = $conn->prepare("UPDATE bus_schedules SET status = 'rejected' WHERE id = ?");
            $update_stmt->bind_param("i", $schedule_id);
            
            if ($update_stmt->execute()) {
                $success = 'Schedule rejected successfully!';
            } else {
                $error = 'Error rejecting schedule: ' . $conn->error;
            }
        }
    }
}

// Check if filter is set (default to "all" to show all requests)
$view_filter = isset($_GET['view']) ? $_GET['view'] : 'all';

// Get active tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'schedules';

// Get current fuel rate
$fuel_rate_query = $conn->query("SELECT setting_value FROM bus_settings WHERE setting_key = 'fuel_rate'");
$current_fuel_rate = 70.00; // Default
if ($fuel_rate_query && $fuel_rate_query->num_rows > 0) {
    $current_fuel_rate = floatval($fuel_rate_query->fetch_assoc()['setting_value']);
}

// Get all buses for management
$all_buses = $conn->query("SELECT * FROM buses ORDER BY bus_number ASC");

include '../includes/header.php';
?>
<div class="flex min-h-screen">
    <?php include '../includes/admin_sidebar.php'; ?>
    <main class="flex-1 p-8 bg-gray-100">
        <div class="mb-6">
            <h2 class="text-3xl font-bold text-blue-900 flex items-center mb-4">
                <i class="fas fa-bus mr-3"></i>Bus Management System
            </h2>
            
            <!-- Tab Navigation -->
            <div class="flex space-x-1 bg-white rounded-lg p-1 shadow">
                <a href="?tab=schedules<?php echo $view_filter !== 'all' ? '&view=' . $view_filter : ''; ?>" 
                   class="flex-1 px-4 py-2 rounded-md text-center transition <?php echo $active_tab === 'schedules' ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="fas fa-calendar-alt mr-2"></i>Bus Schedules
                </a>
                <a href="?tab=buses" 
                   class="flex-1 px-4 py-2 rounded-md text-center transition <?php echo $active_tab === 'buses' ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="fas fa-bus mr-2"></i>Manage Buses
                </a>
                <a href="?tab=fuel" 
                   class="flex-1 px-4 py-2 rounded-md text-center transition <?php echo $active_tab === 'fuel' ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="fas fa-gas-pump mr-2"></i>Fuel Rate
                </a>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium"><?php echo $success; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium"><?php echo $error; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- SCHEDULES TAB -->
        <?php if ($active_tab === 'schedules'): ?>
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-semibold text-gray-800 flex items-center">
                <span class="ml-2 text-lg font-normal text-gray-500">
                    <?php echo $view_filter === 'current_month' ? '(' . date('F Y') . ')' : '(All Requests)'; ?>
                </span>
            </h3>
            <div class="flex gap-2">
                <a href="?tab=schedules&view=all" class="px-4 py-2 rounded-lg <?php echo $view_filter === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'; ?> shadow">
                    <i class="fas fa-list mr-2"></i>All Requests
                </a>
                <a href="?tab=schedules&view=current_month" class="px-4 py-2 rounded-lg <?php echo $view_filter === 'current_month' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'; ?> shadow">
                    <i class="fas fa-calendar mr-2"></i>Current Month
                </a>
            </div>
        </div>
        <?php
        // Get bus availability statistics
        $bus_stats_query = "SELECT 
            COUNT(*) as total_buses,
            SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_buses,
            SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) as booked_buses,
            SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_buses
            FROM buses";
        $bus_stats_result = $conn->query($bus_stats_query);
        $bus_stats = $bus_stats_result->fetch_assoc();
        
        // Get current month and year
        $current_month = date('m');
        $current_year = date('Y');
        
        // Count schedules by status (ALL TIME or FILTERED)
        if ($view_filter === 'current_month') {
            $count_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM bus_schedules WHERE MONTH(date_covered) = ? AND YEAR(date_covered) = ?";
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->bind_param("ii", $current_month, $current_year);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $schedule_stats = $count_result->fetch_assoc();
            
            // Get schedules for this month with billing info
            $list_sql = "SELECT bs.*, bst.total_amount, bst.payment_status 
                         FROM bus_schedules bs 
                         LEFT JOIN billing_statements bst ON bs.id = bst.schedule_id 
                         WHERE MONTH(bs.date_covered) = ? AND YEAR(bs.date_covered) = ? 
                         ORDER BY bs.created_at DESC";
            $list_stmt = $conn->prepare($list_sql);
            $list_stmt->bind_param("ii", $current_month, $current_year);
            $list_stmt->execute();
            $list_result = $list_stmt->get_result();
        } else {
            // Show ALL requests (default)
            $count_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM bus_schedules";
            $count_result = $conn->query($count_sql);
            $schedule_stats = $count_result->fetch_assoc();
            
            // Get all schedules with billing info
            $list_sql = "SELECT bs.*, bst.total_amount, bst.payment_status 
                         FROM bus_schedules bs 
                         LEFT JOIN billing_statements bst ON bs.id = bst.schedule_id 
                         ORDER BY bs.created_at DESC";
            $list_result = $conn->query($list_sql);
        }
        ?>
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Bus Availability -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-bus text-blue-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Available Buses</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $bus_stats['available_buses']; ?>/<?php echo $bus_stats['total_buses']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Total Requests -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-list text-green-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Requests</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $schedule_stats['total']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pending Requests -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $schedule_stats['pending']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Approved Requests -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Approved</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $schedule_stats['approved']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="font-bold text-lg mb-4 flex items-center"><i class="fas fa-list mr-2"></i>Schedules List</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm border rounded-lg overflow-hidden">
                    <thead>
                        <tr class="bg-blue-100 text-blue-900">
                            <th class="border px-4 py-2">Client</th>
                            <th class="border px-4 py-2">Destination</th>
                            <th class="border px-4 py-2">Purpose</th>
                            <th class="border px-4 py-2">Date Covered</th>
                            <th class="border px-4 py-2">Vehicle</th>
                            <th class="border px-4 py-2">Bus No.</th>
                            <th class="border px-4 py-2">No. of Days</th>
                            <th class="border px-4 py-2">No. of Vehicles</th>
                            <th class="border px-4 py-2">Amount</th>
                            <th class="border px-4 py-2">Status</th>
                            <th class="border px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($list_result->num_rows > 0): ?>
                            <?php while ($row = $list_result->fetch_assoc()): ?>
                                <tr class="hover:bg-blue-50 transition">
                                    <td class="border px-4 py-2"><?php echo htmlspecialchars($row['client']); ?></td>
                                    <td class="border px-4 py-2"><?php echo htmlspecialchars($row['destination']); ?></td>
                                    <td class="border px-4 py-2"><?php echo htmlspecialchars($row['purpose']); ?></td>
                                    <td class="border px-4 py-2"><?php echo date('M d, Y', strtotime($row['date_covered'])); ?></td>
                                    <td class="border px-4 py-2"><?php echo htmlspecialchars($row['vehicle']); ?></td>
                                    <td class="border px-4 py-2"><?php echo htmlspecialchars($row['bus_no']); ?></td>
                                    <td class="border px-4 py-2 text-center"><?php echo htmlspecialchars($row['no_of_days']); ?></td>
                                    <td class="border px-4 py-2 text-center"><?php echo htmlspecialchars($row['no_of_vehicles']); ?></td>
                                    <td class="border px-4 py-2 text-center">
                                        <?php if ($row['total_amount']): ?>
                                            <span class="font-semibold text-green-600">₱<?php echo number_format($row['total_amount'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-500">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="border px-4 py-2 text-center">
                                        <?php
                                        $status_class = '';
                                        switch($row['status']) {
                                            case 'pending':
                                                $status_class = 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'approved':
                                                $status_class = 'bg-green-100 text-green-800';
                                                break;
                                            case 'rejected':
                                                $status_class = 'bg-red-100 text-red-800';
                                                break;
                                            default:
                                                $status_class = 'bg-gray-100 text-gray-800';
                                        }
                                        ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="border px-4 py-2 text-center">
                                        <div class="flex space-x-2 justify-center">
                                            <?php if ($row['status'] === 'pending'): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="approve_schedule">
                                                    <input type="hidden" name="schedule_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" class="text-green-600 hover:text-green-900" 
                                                            onclick="return confirm('Are you sure you want to approve this schedule?')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <button type="button" class="text-red-600 hover:text-red-900"
                                                        onclick="openRejectModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['client'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['destination'], ENT_QUOTES); ?>')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($row['total_amount']): ?>
                                                <a href="../student/print_bus_receipt.php?id=<?php echo $row['id']; ?>" 
                                                   target="_blank" class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="11" class="border px-4 py-2 text-center text-gray-500">
                                <?php if ($view_filter === 'current_month'): ?>
                                    No schedules found for this month. <a href="?view=all" class="text-blue-600 hover:underline">View all requests</a>
                                <?php else: ?>
                                    No bus requests found in the system.
                                <?php endif; ?>
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; // End schedules tab ?>
        
        <!-- MANAGE BUSES TAB -->
        <?php if ($active_tab === 'buses'): ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Add New Bus Form -->
            <div class="lg:col-span-1 bg-white shadow rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 flex items-center text-blue-900">
                    <i class="fas fa-plus-circle mr-2"></i>Add New Bus
                </h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_bus">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bus Number <span class="text-red-500">*</span></label>
                        <input type="text" name="bus_number" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="e.g., 4, 5, Bus-001">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Type <span class="text-red-500">*</span></label>
                        <select name="vehicle_type" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="Bus">Bus</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Capacity (Seats) <span class="text-red-500">*</span></label>
                        <input type="number" name="capacity" required min="1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="e.g., 50">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                        <select name="status" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="available">Available</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="out_of_service">Out of Service</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition">
                        <i class="fas fa-plus mr-2"></i>Add Bus
                    </button>
                </form>
            </div>
            
            <!-- Buses List -->
            <div class="lg:col-span-2 bg-white shadow rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 flex items-center text-blue-900">
                    <i class="fas fa-list mr-2"></i>All Buses (<?php echo $all_buses->num_rows; ?>)
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm border rounded-lg overflow-hidden">
                        <thead>
                            <tr class="bg-blue-100 text-blue-900">
                                <th class="border px-4 py-2">Bus #</th>
                                <th class="border px-4 py-2">Type</th>
                                <th class="border px-4 py-2">Capacity</th>
                                <th class="border px-4 py-2">Status</th>
                                <th class="border px-4 py-2">Added</th>
                                <th class="border px-4 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($all_buses->num_rows > 0): ?>
                                <?php while ($bus = $all_buses->fetch_assoc()): ?>
                                    <tr class="hover:bg-blue-50 transition">
                                        <td class="border px-4 py-2 font-semibold"><?php echo htmlspecialchars($bus['bus_number']); ?></td>
                                        <td class="border px-4 py-2"><?php echo htmlspecialchars($bus['vehicle_type']); ?></td>
                                        <td class="border px-4 py-2 text-center"><?php echo $bus['capacity']; ?> seats</td>
                                        <td class="border px-4 py-2 text-center">
                                            <?php
                                            $status_class = '';
                                            switch($bus['status']) {
                                                case 'available':
                                                    $status_class = 'bg-green-100 text-green-800';
                                                    break;
                                                case 'booked':
                                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                                    break;
                                                case 'maintenance':
                                                    $status_class = 'bg-orange-100 text-orange-800';
                                                    break;
                                                case 'out_of_service':
                                                    $status_class = 'bg-red-100 text-red-800';
                                                    break;
                                                default:
                                                    $status_class = 'bg-gray-100 text-gray-800';
                                            }
                                            ?>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $bus['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="border px-4 py-2 text-sm text-gray-600">
                                            <?php echo date('M d, Y', strtotime($bus['created_at'])); ?>
                                        </td>
                                        <td class="border px-4 py-2 text-center">
                                            <div class="flex space-x-2 justify-center">
                                                <button onclick="openStatusModal(<?php echo $bus['id']; ?>, '<?php echo htmlspecialchars($bus['bus_number']); ?>', '<?php echo $bus['status']; ?>')" 
                                                        class="text-blue-600 hover:text-blue-900" title="Change Status">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="deleteBus(<?php echo $bus['id']; ?>, '<?php echo htmlspecialchars($bus['bus_number']); ?>')" 
                                                        class="text-red-600 hover:text-red-900" title="Delete Bus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="border px-4 py-8 text-center text-gray-500">
                                        <i class="fas fa-bus text-4xl mb-2"></i>
                                        <p>No buses found. Add your first bus above!</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; // End buses tab ?>
        
        <!-- FUEL RATE TAB -->
        <?php if ($active_tab === 'fuel'): ?>
        <div class="max-w-2xl mx-auto">
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-gas-pump text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-blue-900">Current Fuel Rate</h3>
                        <p class="text-3xl font-bold text-green-600">₱<?php echo number_format($current_fuel_rate, 2); ?> <span class="text-sm text-gray-600">per liter</span></p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>This rate is used for calculating bus rental costs. Update it when fuel prices change.
                </p>
            </div>
            
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 flex items-center text-blue-900">
                    <i class="fas fa-edit mr-2"></i>Update Fuel Rate
                </h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_fuel_rate">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Fuel Rate (₱ per liter) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">₱</span>
                            <input type="number" name="fuel_rate" required min="0.01" step="0.01" 
                                   value="<?php echo $current_fuel_rate; ?>"
                                   class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="e.g., 75.50">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Enter the current market price for diesel/gasoline per liter</p>
                    </div>
                    
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Important:</strong> This will affect all new bus rental calculations. Existing bookings will not be affected.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition">
                        <i class="fas fa-save mr-2"></i>Update Fuel Rate
                    </button>
                </form>
            </div>
            
            <!-- Fuel Rate History (Optional Enhancement) -->
            <div class="bg-white shadow rounded-lg p-6 mt-6">
                <h3 class="font-bold text-lg mb-4 flex items-center text-blue-900">
                    <i class="fas fa-chart-line mr-2"></i>Fuel Rate Tips
                </h3>
                <ul class="space-y-2 text-sm text-gray-700">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mr-2 mt-1"></i>
                        <span>Check local fuel prices regularly and update the rate accordingly</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mr-2 mt-1"></i>
                        <span>Keep records of when you update rates for accounting purposes</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mr-2 mt-1"></i>
                        <span>Consider adding a buffer to account for price fluctuations</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mr-2 mt-1"></i>
                        <span>This rate is used in combination with distance and vehicle consumption to calculate total fuel costs</span>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; // End fuel tab ?>
        
    </main>
</div>

<!-- Reject Schedule Confirmation Modal -->
<div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <div class="flex items-center mb-4">
            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-medium text-gray-900">Reject Bus Schedule</h3>
            </div>
        </div>
        
        <div class="mb-6">
            <p class="text-sm text-gray-600 mb-4">Are you sure you want to reject this bus schedule?</p>
            <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                <div>
                    <p class="text-xs font-medium text-gray-500">Client:</p>
                    <p class="text-sm text-gray-900" id="reject-client"></p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">Destination:</p>
                    <p class="text-sm text-gray-900" id="reject-destination"></p>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-3">
                <i class="fas fa-info-circle mr-1"></i>
                The student will be notified that their request has been rejected.
            </p>
        </div>
        
        <form method="POST" id="rejectForm">
            <input type="hidden" name="action" value="reject_schedule">
            <input type="hidden" name="schedule_id" id="reject-schedule-id">
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <i class="fas fa-times mr-2"></i>
                    Yes, Reject Schedule
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Change Bus Status Modal -->
<div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <div class="flex items-center mb-4">
            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                <i class="fas fa-edit text-blue-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-medium text-gray-900">Change Bus Status</h3>
            </div>
        </div>
        
        <div class="mb-6">
            <p class="text-sm text-gray-600 mb-4">Update the status of Bus <strong id="status-bus-number"></strong></p>
            <form method="POST" id="statusForm">
                <input type="hidden" name="action" value="update_bus_status">
                <input type="hidden" name="bus_id" id="status-bus-id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Status</label>
                    <select name="new_status" id="status-new-status" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="available">Available</option>
                        <option value="booked">Booked</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="out_of_service">Out of Service</option>
                    </select>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeStatusModal()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-save mr-2"></i>Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Bus Form (Hidden) -->
<form method="POST" id="deleteBusForm" style="display: none;">
    <input type="hidden" name="action" value="delete_bus">
    <input type="hidden" name="bus_id" id="delete-bus-id">
</form>

<script>
// Schedule Rejection Functions
function openRejectModal(scheduleId, client, destination) {
    document.getElementById('reject-schedule-id').value = scheduleId;
    document.getElementById('reject-client').textContent = client;
    document.getElementById('reject-destination').textContent = destination;
    document.getElementById('rejectModal').classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}

// Bus Status Management Functions
function openStatusModal(busId, busNumber, currentStatus) {
    document.getElementById('status-bus-id').value = busId;
    document.getElementById('status-bus-number').textContent = busNumber;
    document.getElementById('status-new-status').value = currentStatus;
    document.getElementById('statusModal').classList.remove('hidden');
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
}

// Bus Deletion Function
function deleteBus(busId, busNumber) {
    if (confirm('Are you sure you want to delete Bus ' + busNumber + '? This action cannot be undone.\n\nNote: Buses with active bookings cannot be deleted.')) {
        document.getElementById('delete-bus-id').value = busId;
        document.getElementById('deleteBusForm').submit();
    }
}

// Close modals when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.id === 'rejectModal') {
        closeRejectModal();
    }
    if (event.target.id === 'statusModal') {
        closeStatusModal();
    }
});

// Close modals with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeRejectModal();
        closeStatusModal();
    }
});
</script>

<?php include '../includes/footer.php'; ?> 