<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_admin();

$page_title = "Billing Summary - CHMSU BAO";
$base_url = "..";

$service = isset($_GET['service']) ? sanitize_input($_GET['service']) : '';
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : '';

// Collect rows from sources: Bus billing_statements, Inventory orders (as sales)
$rows = [];

// Bus billing
if ($service === '' || $service === 'bus') {
	$sql = "SELECT 'Bus' as service, bs.id as billing_id, s.client as requester, s.destination as details, bs.total_amount as amount,
				bs.payment_status as pay_status, bs.payment_date as paid_at, bs.created_at as created_at
		FROM billing_statements bs JOIN bus_schedules s ON s.id = bs.schedule_id WHERE 1=1";
	$params = []; $types = "";
	if (!empty($status)) { $sql .= " AND bs.payment_status = ?"; $params[] = $status; $types .= "s"; }
	if (!empty($start_date)) { $sql .= " AND DATE(bs.created_at) >= ?"; $params[] = $start_date; $types .= "s"; }
	if (!empty($end_date))   { $sql .= " AND DATE(bs.created_at) <= ?"; $params[] = $end_date;   $types .= "s"; }
	$stmt = $conn->prepare($sql);
	if (!empty($params)) { $stmt->bind_param($types, ...$params); }
	$stmt->execute();
	$res = $stmt->get_result();
	while ($r = $res->fetch_assoc()) { $rows[] = $r; }
}

// Inventory orders as Item Sales
if ($service === '' || $service === 'items') {
	$sql2 = "SELECT 'Items' as service, o.id as billing_id, u.name as requester, i.name as details, o.total_price as amount,
				CASE WHEN o.status IN ('completed') THEN 'paid' ELSE 'pending' END as pay_status, o.updated_at as paid_at, o.created_at as created_at
		FROM orders o JOIN user_accounts u ON u.id = o.user_id JOIN inventory i ON i.id = o.inventory_id WHERE 1=1";
	$params2 = []; $types2 = "";
	if (!empty($status)) { 
		if ($status === 'paid') { $sql2 .= " AND o.status = 'completed'"; }
		elseif ($status === 'pending') { $sql2 .= " AND o.status IN ('pending','approved')"; }
		elseif ($status === 'cancelled') { $sql2 .= " AND o.status = 'cancelled'"; }
	}
	if (!empty($start_date)) { $sql2 .= " AND DATE(o.created_at) >= ?"; $params2[] = $start_date; $types2 .= "s"; }
	if (!empty($end_date))   { $sql2 .= " AND DATE(o.created_at) <= ?"; $params2[] = $end_date;   $types2 .= "s"; }
	$stmt2 = $conn->prepare($sql2);
	if (!empty($params2)) { $stmt2->bind_param($types2, ...$params2); }
	$stmt2->execute();
	$res2 = $stmt2->get_result();
	while ($r = $res2->fetch_assoc()) { $rows[] = $r; }
}

// Aggregates
$total_amount = 0.0; $paid = 0.0; $pending_amt = 0.0;
foreach ($rows as $r) {
	$amt = (float)$r['amount'];
	$total_amount += $amt;
	if ($r['pay_status'] === 'paid') { $paid += $amt; }
	if ($r['pay_status'] === 'pending') { $pending_amt += $amt; }
}
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
	<?php include '../includes/admin_sidebar.php'; ?>
	<div class="flex-1 flex flex-col overflow-hidden">
		<header class="bg-white shadow-sm z-10">
			<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
				<h1 class="text-2xl font-semibold text-gray-900">Billing Summary</h1>
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
					<form method="GET" action="billing_reports.php" class="grid grid-cols-1 md:grid-cols-6 gap-4">
						<div>
							<label class="block text-sm text-gray-700 mb-1">Service</label>
							<select name="service" class="w-full rounded-md border-gray-300">
								<option value="">All</option>
								<option value="bus" <?php echo $service==='bus'?'selected':''; ?>>Bus</option>
								<option value="items" <?php echo $service==='items'?'selected':''; ?>>Items</option>
							</select>
						</div>
						<div>
							<label class="block text-sm text-gray-700 mb-1">Payment Status</label>
							<select name="status" class="w-full rounded-md border-gray-300">
								<option value="">All</option>
								<?php foreach (['paid','pending','cancelled'] as $s): ?>
									<option value="<?php echo $s; ?>" <?php echo $status===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
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

				<!-- KPIs -->
				<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
					<div class="bg-white rounded-lg shadow p-4"><p class="text-gray-500 text-sm">Total Amount</p><h3 class="text-2xl font-bold text-emerald-600">₱<?php echo number_format($total_amount,2); ?></h3></div>
					<div class="bg-white rounded-lg shadow p-4"><p class="text-gray-500 text-sm">Paid</p><h3 class="text-2xl font-bold text-green-600">₱<?php echo number_format($paid,2); ?></h3></div>
					<div class="bg-white rounded-lg shadow p-4"><p class="text-gray-500 text-sm">Pending</p><h3 class="text-2xl font-bold text-yellow-600">₱<?php echo number_format($pending_amt,2); ?></h3></div>
				</div>

				<!-- Table -->
				<div class="bg-white rounded-lg shadow">
					<div class="px-4 py-5 border-b border-gray-200 sm:px-6 flex justify-between items-center">
						<h3 class="text-lg font-medium text-gray-900">Billing Records</h3>
						<button onclick="window.print()" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700"><i class="fas fa-print mr-1"></i> Print</button>
					</div>
					<div class="overflow-x-auto">
						<table class="min-w-full divide-y divide-gray-200">
							<thead class="bg-gray-50">
								<tr>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Billing ID</th>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requester / Details</th>
									<th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Status</th>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
								</tr>
							</thead>
							<tbody class="bg-white divide-y divide-gray-200">
								<?php if (count($rows) > 0): foreach ($rows as $r): ?>
								<tr>
									<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $r['billing_id']; ?></td>
									<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $r['service']; ?></td>
									<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($r['requester']); ?> — <?php echo htmlspecialchars($r['details']); ?></td>
									<td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-emerald-600">₱<?php echo number_format((float)$r['amount'],2); ?></td>
									<td class="px-6 py-4 whitespace-nowrap text-sm">
										<?php $cls='bg-gray-100 text-gray-800'; if ($r['pay_status']==='paid') $cls='bg-green-100 text-green-800'; elseif ($r['pay_status']==='pending') $cls='bg-yellow-100 text-yellow-800'; elseif ($r['pay_status']==='cancelled') $cls='bg-red-100 text-red-800'; ?>
										<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $cls; ?>"><?php echo ucfirst($r['pay_status']); ?></span>
									</td>
									<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo format_date($r['created_at']); ?></td>
								</tr>
								<?php endforeach; else: ?>
								<tr><td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No billing records found</td></tr>
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
















