<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_admin();

$page_title = "Inventory Report - CHMSU BAO";
$base_url = "..";

$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : '';

// Item movement = orders joined with inventory
$orders_sql = "SELECT i.id as item_id, i.name as item_name, SUM(o.quantity) as qty_issued, SUM(o.total_price) as revenue
               FROM orders o JOIN inventory i ON o.inventory_id = i.id
               WHERE o.status IN ('approved','completed')";
$params = []; $types = "";
if (!empty($start_date)) { $orders_sql .= " AND DATE(o.created_at) >= ?"; $params[] = $start_date; $types .= "s"; }
if (!empty($end_date))   { $orders_sql .= " AND DATE(o.created_at) <= ?"; $params[] = $end_date;   $types .= "s"; }
$orders_sql .= " GROUP BY i.id, i.name ORDER BY revenue DESC";

$stmt = $conn->prepare($orders_sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$movement = $stmt->get_result();

// Remaining stock: try stock_quantity, fallback to quantity if column doesn't exist
try {
    $stock_sql = "SELECT id, name, stock_quantity AS remaining FROM inventory ORDER BY name ASC";
    $stock = $conn->query($stock_sql);
} catch (mysqli_sql_exception $e) {
    // Fallback schema without stock_quantity
    $stock_sql = "SELECT id, name, quantity AS remaining FROM inventory ORDER BY name ASC";
    $stock = $conn->query($stock_sql);
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
						<button onclick="window.print()" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700"><i class="fas fa-print mr-1"></i> Print</button>
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

<?php include '../includes/footer.php'; ?>


