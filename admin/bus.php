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
        if ($_POST['action'] === 'approve_schedule') {
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

include '../includes/header.php';
?>
<div class="flex min-h-screen">
    <?php include '../includes/admin_sidebar.php'; ?>
    <main class="flex-1 p-8 bg-gray-100">
        <h2 class="text-3xl font-bold mb-6 text-blue-900 flex items-center"><i class="fas fa-bus mr-3"></i>Bus Management System <span class="ml-2 text-lg font-normal text-gray-500">(<?php echo date('F Y'); ?>)</span></h2>
        
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
        
        // Count schedules by status
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
        
        // Get all schedules for this month and year with billing info
        $list_sql = "SELECT bs.*, bst.total_amount, bst.payment_status 
                     FROM bus_schedules bs 
                     LEFT JOIN billing_statements bst ON bs.id = bst.schedule_id 
                     WHERE MONTH(bs.date_covered) = ? AND YEAR(bs.date_covered) = ? 
                     ORDER BY bs.created_at DESC";
        $list_stmt = $conn->prepare($list_sql);
        $list_stmt->bind_param("ii", $current_month, $current_year);
        $list_stmt->execute();
        $list_result = $list_stmt->get_result();
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
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="reject_schedule">
                                                    <input type="hidden" name="schedule_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" class="text-red-600 hover:text-red-900"
                                                            onclick="return confirm('Are you sure you want to reject this schedule?')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
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
                            <tr><td colspan="11" class="border px-4 py-2 text-center text-gray-500">No schedules found for this month.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?> 