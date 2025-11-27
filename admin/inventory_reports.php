<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_admin();

$page_title = "Inventory Report - CHMSU BAO";
$base_url = "..";

$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : '';

// Pagination
$rows_per_page = 20;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $rows_per_page;

// Count query for movement
$count_sql = "SELECT COUNT(DISTINCT i.id) as total
               FROM orders o JOIN inventory i ON o.inventory_id = i.id
               WHERE o.status IN ('approved','completed')";
$count_params = []; $count_types = "";
if (!empty($start_date)) { $count_sql .= " AND DATE(o.created_at) >= ?"; $count_params[] = $start_date; $count_types .= "s"; }
if (!empty($end_date))   { $count_sql .= " AND DATE(o.created_at) <= ?"; $count_params[] = $end_date;   $count_types .= "s"; }
$count_stmt = $conn->prepare($count_sql);
if (!empty($count_params)) { $count_stmt->bind_param($count_types, ...$count_params); }
$count_stmt->execute();
$total_movement = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages_movement = ceil($total_movement / $rows_per_page);

// Item movement = orders joined with inventory
$orders_sql = "SELECT i.id as item_id, i.name as item_name, SUM(o.quantity) as qty_issued, SUM(o.total_price) as revenue
               FROM orders o JOIN inventory i ON o.inventory_id = i.id
               WHERE o.status IN ('approved','completed')";
$params = []; $types = "";
if (!empty($start_date)) { $orders_sql .= " AND DATE(o.created_at) >= ?"; $params[] = $start_date; $types .= "s"; }
if (!empty($end_date))   { $orders_sql .= " AND DATE(o.created_at) <= ?"; $params[] = $end_date;   $types .= "s"; }
$orders_sql .= " GROUP BY i.id, i.name ORDER BY revenue DESC LIMIT ? OFFSET ?";
$params[] = $rows_per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($orders_sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$movement = $stmt->get_result();

// Remaining stock: try stock_quantity, fallback to quantity if column doesn't exist
// Pagination for stock
$stock_page = isset($_GET['stock_page']) ? max(1, intval($_GET['stock_page'])) : 1;
$stock_offset = ($stock_page - 1) * $rows_per_page;

try {
    $stock_count_sql = "SELECT COUNT(*) as total FROM inventory";
    $total_stock = $conn->query($stock_count_sql)->fetch_assoc()['total'];
    $total_pages_stock = ceil($total_stock / $rows_per_page);
    
    $stock_sql = "SELECT id, name, stock_quantity AS remaining FROM inventory ORDER BY name ASC LIMIT ? OFFSET ?";
    $stock_stmt = $conn->prepare($stock_sql);
    $stock_stmt->bind_param("ii", $rows_per_page, $stock_offset);
    $stock_stmt->execute();
    $stock = $stock_stmt->get_result();
} catch (mysqli_sql_exception $e) {
    // Fallback schema without stock_quantity
    $stock_count_sql = "SELECT COUNT(*) as total FROM inventory";
    $total_stock = $conn->query($stock_count_sql)->fetch_assoc()['total'];
    $total_pages_stock = ceil($total_stock / $rows_per_page);
    
    $stock_sql = "SELECT id, name, quantity AS remaining FROM inventory ORDER BY name ASC LIMIT ? OFFSET ?";
    $stock_stmt = $conn->prepare($stock_sql);
    $stock_stmt->bind_param("ii", $rows_per_page, $stock_offset);
    $stock_stmt->execute();
    $stock = $stock_stmt->get_result();
}
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
	<?php include '../includes/admin_sidebar.php'; ?>
	<div class="flex-1 flex flex-col overflow-hidden">
		<header class="bg-white shadow-sm z-10">
			<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
				<h1 class="text-2xl font-semibold text-gray-900">Item Request / Inventory Report</h1>
				<div class="flex items-center">
					<span class="text-gray-700 mr-2"><?php echo $_SESSION['user_name']; ?></span>
					<button class="md:hidden rounded-md p-2 inline-flex items-center justify-center text-gray-500 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" id="menu-button">
						<span class="sr-only">Open menu</span>
						<i class="fas fa-bars"></i>
					</button>
				</div>
			</div>
		</header>

		<main class="flex-1 overflow-y-auto p-4">
			<div class="max-w-7xl mx-auto">
				<!-- Filters -->
				<div class="bg-white rounded-lg shadow p-6 mb-6">
					<h2 class="text-lg font-semibold text-gray-900 mb-4">Filter</h2>
					<form method="GET" action="inventory_reports.php" class="grid grid-cols-1 md:grid-cols-4 gap-4">
						<div>
							<label class="block text-sm text-gray-700 mb-1">Start Date</label>
							<input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" class="w-full rounded-md border-gray-300">
						</div>
						<div>
							<label class="block text-sm text-gray-700 mb-1">End Date</label>
							<input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" class="w-full rounded-md border-gray-300">
						</div>
						<div class="md:col-span-2 flex items-end">
							<button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"><i class="fas fa-filter mr-2"></i>Apply</button>
						</div>
					</form>
				</div>

				<!-- Movement -->
				<div class="bg-white rounded-lg shadow mb-6">
					<div class="px-4 py-5 border-b border-gray-200 sm:px-6 flex justify-between items-center">
						<h3 class="text-lg font-medium text-gray-900">Inventory Movement (Issued)</h3>
						<div class="flex gap-2">
							<a href="download_report.php?type=inventory&format=pdf&<?php echo http_build_query($_GET); ?>" class="bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700">
								<i class="fas fa-file-pdf mr-1"></i> PDF
							</a>
							<a href="download_report.php?type=inventory&format=excel&<?php echo http_build_query($_GET); ?>" class="bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700">
								<i class="fas fa-file-excel mr-1"></i> Excel
							</a>
							<button onclick="window.print()" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700"><i class="fas fa-print mr-1"></i> Print</button>
						</div>
					</div>
					<div class="overflow-x-auto">
						<table class="min-w-full divide-y divide-gray-200">
							<thead class="bg-gray-50">
								<tr>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
									<th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty Issued</th>
									<th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
								</tr>
							</thead>
							<tbody class="bg-white divide-y divide-gray-200">
								<?php if ($movement->num_rows > 0): while ($m = $movement->fetch_assoc()): ?>
								<tr>
									<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($m['item_name']); ?></td>
									<td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500"><?php echo number_format($m['qty_issued']); ?></td>
									<td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-emerald-600">₱<?php echo number_format($m['revenue'],2); ?></td>
								</tr>
								<?php endwhile; else: ?>
								<tr><td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No movement found</td></tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
					<?php if ($total_pages_movement > 1): ?>
					<div class="px-4 py-3 border-t border-gray-200 sm:px-6">
						<nav class="flex items-center justify-between">
							<div class="flex-1 flex justify-between sm:hidden">
								<?php if ($current_page > 1): ?>
									<a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
								<?php endif; ?>
								<?php if ($current_page < $total_pages_movement): ?>
									<a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
								<?php endif; ?>
							</div>
							<div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
								<div>
									<p class="text-sm text-gray-700">
										Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $rows_per_page, $total_movement); ?></span> of <span class="font-medium"><?php echo number_format($total_movement); ?></span> results
									</p>
								</div>
								<div>
									<nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
										<?php if ($current_page > 1): ?>
											<a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
												<i class="fas fa-chevron-left"></i>
											</a>
										<?php endif; ?>
										<?php
										$start_page = max(1, $current_page - 2);
										$end_page = min($total_pages_movement, $current_page + 2);
										if ($start_page > 1): ?>
											<a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>
											<?php if ($start_page > 2): ?><span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span><?php endif; ?>
										<?php endif; ?>
										<?php for ($i = $start_page; $i <= $end_page; $i++): ?>
											<a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="<?php echo $i == $current_page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium"><?php echo $i; ?></a>
										<?php endfor; ?>
										<?php if ($end_page < $total_pages_movement): ?>
											<?php if ($end_page < $total_pages_movement - 1): ?><span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span><?php endif; ?>
											<a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages_movement])); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"><?php echo $total_pages_movement; ?></a>
										<?php endif; ?>
										<?php if ($current_page < $total_pages_movement): ?>
											<a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
												<i class="fas fa-chevron-right"></i>
											</a>
										<?php endif; ?>
									</nav>
								</div>
							</div>
						</nav>
					</div>
					<?php endif; ?>
				</div>

				<!-- Stock -->
				<div class="bg-white rounded-lg shadow">
					<div class="px-4 py-5 border-b border-gray-200 sm:px-6">
						<h3 class="text-lg font-medium text-gray-900">Remaining Stock</h3>
					</div>
					<div class="overflow-x-auto">
						<table class="min-w-full divide-y divide-gray-200">
							<thead class="bg-gray-50">
								<tr>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Remaining</th>
								</tr>
							</thead>
							<tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($stock->num_rows > 0): while ($s = $stock->fetch_assoc()): ?>
								<tr>
									<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($s['name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500"><?php echo number_format($s['remaining']); ?></td>
								</tr>
								<?php endwhile; else: ?>
								<tr><td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No items found</td></tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
					<?php if ($total_pages_stock > 1): ?>
					<div class="px-4 py-3 border-t border-gray-200 sm:px-6">
						<nav class="flex items-center justify-between">
							<div class="flex-1 flex justify-between sm:hidden">
								<?php if ($stock_page > 1): ?>
									<a href="?<?php echo http_build_query(array_merge($_GET, ['stock_page' => $stock_page - 1])); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
								<?php endif; ?>
								<?php if ($stock_page < $total_pages_stock): ?>
									<a href="?<?php echo http_build_query(array_merge($_GET, ['stock_page' => $stock_page + 1])); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
								<?php endif; ?>
							</div>
							<div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
								<div>
									<p class="text-sm text-gray-700">
										Showing <span class="font-medium"><?php echo $stock_offset + 1; ?></span> to <span class="font-medium"><?php echo min($stock_offset + $rows_per_page, $total_stock); ?></span> of <span class="font-medium"><?php echo number_format($total_stock); ?></span> results
									</p>
								</div>
								<div>
									<nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
										<?php if ($stock_page > 1): ?>
											<a href="?<?php echo http_build_query(array_merge($_GET, ['stock_page' => $stock_page - 1])); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
												<i class="fas fa-chevron-left"></i>
											</a>
										<?php endif; ?>
										<?php
										$start_page = max(1, $stock_page - 2);
										$end_page = min($total_pages_stock, $stock_page + 2);
										if ($start_page > 1): ?>
											<a href="?<?php echo http_build_query(array_merge($_GET, ['stock_page' => 1])); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>
											<?php if ($start_page > 2): ?><span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span><?php endif; ?>
										<?php endif; ?>
										<?php for ($i = $start_page; $i <= $end_page; $i++): ?>
											<a href="?<?php echo http_build_query(array_merge($_GET, ['stock_page' => $i])); ?>" class="<?php echo $i == $stock_page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium"><?php echo $i; ?></a>
										<?php endfor; ?>
										<?php if ($end_page < $total_pages_stock): ?>
											<?php if ($end_page < $total_pages_stock - 1): ?><span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span><?php endif; ?>
											<a href="?<?php echo http_build_query(array_merge($_GET, ['stock_page' => $total_pages_stock])); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"><?php echo $total_pages_stock; ?></a>
										<?php endif; ?>
										<?php if ($stock_page < $total_pages_stock): ?>
											<a href="?<?php echo http_build_query(array_merge($_GET, ['stock_page' => $stock_page + 1])); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
												<i class="fas fa-chevron-right"></i>
											</a>
										<?php endif; ?>
									</nav>
								</div>
							</div>
						</nav>
					</div>
					<?php endif; ?>
				</div>
			</div>
		</main>
	</div>
</div>

<script>
	document.getElementById('menu-button').addEventListener('click', function() {
		document.getElementById('sidebar').classList.toggle('-translate-x-full');
	});
	window.onbeforeprint = function() { document.querySelectorAll('button').forEach(function(el){ el.style.display='none'; }); };
	window.onafterprint = function() { document.querySelectorAll('button').forEach(function(el){ el.style.display=''; }); };
</script>

    <script src="<?php echo $base_url ?? ''; ?>/assets/js/main.js"></script>
</body>
</html>


