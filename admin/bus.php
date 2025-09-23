<?php
// admin/bus.php
require_once '../config/database.php';
$title = "Bus Schedule Report";
$page_title = $title;
include '../includes/header.php';
?>
<div class="flex min-h-screen">
    <?php include '../includes/admin_sidebar.php'; ?>
    <main class="flex-1 p-8 bg-gray-100">
        <h2 class="text-3xl font-bold mb-6 text-blue-900 flex items-center"><i class="fas fa-bus mr-3"></i>Bus Schedule Report <span class="ml-2 text-lg font-normal text-gray-500">(<?php echo date('F Y'); ?>)</span></h2>
        <?php
        // Get current month and year
        $current_month = date('m');
        $current_year = date('Y');
        // Count schedules for this month and year
        $count_sql = "SELECT COUNT(*) as total FROM bus_schedules WHERE MONTH(date_covered) = ? AND YEAR(date_covered) = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("ii", $current_month, $current_year);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_this_month = $count_result->fetch_assoc()['total'];
        // Get all schedules for this month and year
        $list_sql = "SELECT * FROM bus_schedules WHERE MONTH(date_covered) = ? AND YEAR(date_covered) = ? ORDER BY date_covered DESC";
        $list_stmt = $conn->prepare($list_sql);
        $list_stmt->bind_param("ii", $current_month, $current_year);
        $list_stmt->execute();
        $list_result = $list_stmt->get_result();
        ?>
        <div class="bg-white shadow rounded-lg p-6 mb-8 flex items-center justify-between">
            <div class="text-xl font-semibold text-blue-800 flex items-center">
                <i class="fas fa-calendar-alt mr-2"></i>
                Total Bus Schedules This Month:
                <span class="ml-2 text-2xl font-bold text-blue-600"><?php echo $total_this_month; ?></span>
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
                            <th class="border px-4 py-2">Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($list_result->num_rows > 0): ?>
                            <?php while ($row = $list_result->fetch_assoc()): ?>
                                <tr class="hover:bg-blue-50 transition">
                                    <td class="border px-4 py-2"><?php echo htmlspecialchars($row['client']); ?></td>
                                    <td class="border px-4 py-2"><?php echo htmlspecialchars($row['destination']); ?></td>
                                    <td class="border px-4 py-2"><?php echo htmlspecialchars($row['purpose']); ?></td>
                                    <td class="border px-4 py-2"><?php echo htmlspecialchars($row['date_covered']); ?></td>
                                    <td class="border px-4 py-2"><?php echo htmlspecialchars($row['vehicle']); ?></td>
                                    <td class="border px-4 py-2"><?php echo htmlspecialchars($row['bus_no']); ?></td>
                                    <td class="border px-4 py-2 text-center"><?php echo htmlspecialchars($row['no_of_days']); ?></td>
                                    <td class="border px-4 py-2 text-center"><?php echo htmlspecialchars($row['no_of_vehicles']); ?></td>
                                    <td class="border px-4 py-2 text-xs text-gray-500"><?php echo htmlspecialchars($row['created_at']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="border px-4 py-2 text-center text-gray-500">No schedules found for this month.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?> 