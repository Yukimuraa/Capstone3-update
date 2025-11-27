<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_admin();

$page_title = "Bus Reservation Report - CHMSU BAO";
$base_url = "..";

// Filters
$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : '';
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$department = isset($_GET['department']) ? sanitize_input($_GET['department']) : '';

// Pagination
$rows_per_page = 20;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $rows_per_page;

// Build count query
$count_query = "SELECT COUNT(*) as total
          FROM bus_schedules s
          LEFT JOIN billing_statements bs ON bs.schedule_id = s.id
          WHERE 1=1";

$count_params = [];
$count_types = "";

if (!empty($start_date)) { $count_query .= " AND s.date_covered >= ?"; $count_params[] = $start_date; $count_types .= "s"; }
if (!empty($end_date))   { $count_query .= " AND s.date_covered <= ?"; $count_params[] = $end_date;   $count_types .= "s"; }
if (!empty($status))     { $count_query .= " AND s.status = ?";         $count_params[] = $status;     $count_types .= "s"; }
if (!empty($department)) { $count_query .= " AND s.client LIKE ?";      $count_params[] = "%$department%"; $count_types .= "s"; }

$count_stmt = $conn->prepare($count_query);
if (!empty($count_params)) { $count_stmt->bind_param($count_types, ...$count_params); }
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $rows_per_page);

// Build schedule query
$query = "SELECT s.id, s.client, s.destination, s.purpose, s.date_covered, s.vehicle, s.bus_no, s.no_of_days, s.no_of_vehicles, s.status,
                 bs.total_amount, bs.payment_status, bs.payment_date
          FROM bus_schedules s
          LEFT JOIN billing_statements bs ON bs.schedule_id = s.id
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($start_date)) { $query .= " AND s.date_covered >= ?"; $params[] = $start_date; $types .= "s"; }
if (!empty($end_date))   { $query .= " AND s.date_covered <= ?"; $params[] = $end_date;   $types .= "s"; }
if (!empty($status))     { $query .= " AND s.status = ?";         $params[] = $status;     $types .= "s"; }
if (!empty($department)) { $query .= " AND s.client LIKE ?";      $params[] = "%$department%"; $types .= "s"; }

$query .= " ORDER BY s.date_covered DESC LIMIT ? OFFSET ?";
$params[] = $rows_per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

// Totals
$total_requests = 0;
$total_amount = 0.00;
$pending = 0; $approved = 0; $rejected = 0; $completed = 0;

$rows = [];
while ($row = $result->fetch_assoc()) {
	$rows[] = $row;
	$total_requests++;
	if (isset($row['total_amount'])) {
		$total_amount += (float)$row['total_amount'];
	}
	switch ($row['status']) {
		case 'pending': $pending++; break;
		case 'approved': $approved++; break;
		case 'rejected': $rejected++; break;
		case 'completed': $completed++; break;
	}
}
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
	<?php include '../includes/admin_sidebar.php'; ?>

	<div class="flex-1 flex flex-col overflow-hidden">
		<header class="bg-white shadow-sm z-10">
			<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
				<h1 class="text-2xl font-semibold text-gray-900">Bus Reservation Report</h1>
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
					<form method="GET" action="bus_reports.php" class="grid grid-cols-1 md:grid-cols-5 gap-4">
						<div>
							<label class="block text-sm text-gray-700 mb-1">Start Date</label>
							<input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" class="w-full rounded-md border-gray-300">
						</div>
						<div>
							<label class="block text-sm text-gray-700 mb-1">End Date</label>
							<input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" class="w-full rounded-md border-gray-300">
						</div>
						<div>
							<label class="block text-sm text-gray-700 mb-1">Status</label>
							<select name="status" class="w-full rounded-md border-gray-300">
								<option value="">All</option>
								<?php foreach (['pending','approved','rejected','completed'] as $s): ?>
									<option value="<?php echo $s; ?>" <?php echo $status===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div>
							<label class="block text-sm text-gray-700 mb-1">Requester/Department</label>
							<input type="text" name="department" value="<?php echo htmlspecialchars($department); ?>" placeholder="e.g., CICS" class="w-full rounded-md border-gray-300">
						</div>
						<div class="flex items-end">
							<button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"><i class="fas fa-filter mr-2"></i>Apply</button>
						</div>
					</form>
				</div>

				<!-- KPIs -->
				<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
					<div class="bg-white rounded-lg shadow p-4"><p class="text-gray-500 text-sm">Total Requests</p><h3 class="text-2xl font-bold"><?php echo number_format($total_requests); ?></h3></div>
					<div class="bg-white rounded-lg shadow p-4"><p class="text-gray-500 text-sm">Approved</p><h3 class="text-2xl font-bold text-green-600"><?php echo number_format($approved); ?></h3></div>
					<div class="bg-white rounded-lg shadow p-4"><p class="text-gray-500 text-sm">Pending</p><h3 class="text-2xl font-bold text-yellow-600"><?php echo number_format($pending); ?></h3></div>
					<div class="bg-white rounded-lg shadow p-4"><p class="text-gray-500 text-sm">Total Amount</p><h3 class="text-2xl font-bold text-emerald-600">₱<?php echo number_format($total_amount,2); ?></h3></div>
				</div>

				<!-- Table -->
				<div class="bg-white rounded-lg shadow">
					<div class="px-4 py-5 border-b border-gray-200 sm:px-6 flex justify-between items-center">
						<h3 class="text-lg font-medium text-gray-900">Bus Reservations</h3>
						<div class="flex gap-2">
							<a href="download_report.php?type=bus&format=pdf&<?php echo http_build_query($_GET); ?>" class="bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700">
								<i class="fas fa-file-pdf mr-1"></i> PDF
							</a>
							<a href="download_report.php?type=bus&format=excel&<?php echo http_build_query($_GET); ?>" class="bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700">
								<i class="fas fa-file-excel mr-1"></i> Excel
							</a>
							<button onclick="window.print()" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700"><i class="fas fa-print mr-1"></i> Print</button>
						</div>
					</div>
					<div class="overflow-x-auto">
						<table class="min-w-full divide-y divide-gray-200">
							<thead class="bg-gray-50">
								<tr>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reservation ID</th>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requester/Dept</th>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicles</th>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
									<th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
								</tr>
							</thead>
							<tbody class="bg-white divide-y divide-gray-200">
								<?php if (count($rows) > 0): foreach ($rows as $r): ?>
									<tr>
										<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $r['id']; ?></td>
										<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($r['client']); ?></td>
										<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($r['destination']); ?></td>
										<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo format_date($r['date_covered']); ?></td>
										<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $r['no_of_vehicles']; ?></td>
										<td class="px-6 py-4 whitespace-nowrap">
											<?php 
											$cls = 'bg-gray-100 text-gray-800';
											if ($r['status']==='approved') $cls='bg-green-100 text-green-800';
											elseif ($r['status']==='pending') $cls='bg-yellow-100 text-yellow-800';
											elseif ($r['status']==='rejected') $cls='bg-red-100 text-red-800';
											?>
											<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $cls; ?>"><?php echo ucfirst($r['status']); ?></span>
										</td>
										<td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-right text-emerald-600">₱<?php echo number_format((float)($r['total_amount'] ?? 0), 2); ?></td>
										<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo isset($r['payment_status']) ? ucfirst($r['payment_status']) : '—'; ?></td>
									</tr>
								<?php endforeach; else: ?>
									<tr><td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">No reservations found</td></tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
					<?php if ($total_pages > 1): ?>
					<div class="px-4 py-3 border-t border-gray-200 sm:px-6">
						<nav class="flex items-center justify-between">
							<div class="flex-1 flex justify-between sm:hidden">
								<?php if ($current_page > 1): ?>
									<a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
								<?php endif; ?>
								<?php if ($current_page < $total_pages): ?>
									<a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
								<?php endif; ?>
							</div>
							<div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
								<div>
									<p class="text-sm text-gray-700">
										Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $rows_per_page, $total_rows); ?></span> of <span class="font-medium"><?php echo number_format($total_rows); ?></span> results
									</p>
								</div>
								<div>
									<nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
										<?php if ($current_page > 1): ?>
											<a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
												<span class="sr-only">Previous</span>
												<i class="fas fa-chevron-left"></i>
											</a>
										<?php endif; ?>
										<?php
										$start_page = max(1, $current_page - 2);
										$end_page = min($total_pages, $current_page + 2);
										if ($start_page > 1): ?>
											<a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>
											<?php if ($start_page > 2): ?>
												<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
											<?php endif; ?>
										<?php endif; ?>
										<?php for ($i = $start_page; $i <= $end_page; $i++): ?>
											<a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="<?php echo $i == $current_page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
												<?php echo $i; ?>
											</a>
										<?php endfor; ?>
										<?php if ($end_page < $total_pages): ?>
											<?php if ($end_page < $total_pages - 1): ?>
												<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
											<?php endif; ?>
											<a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"><?php echo $total_pages; ?></a>
										<?php endif; ?>
										<?php if ($current_page < $total_pages): ?>
											<a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
												<span class="sr-only">Next</span>
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

	// Hide buttons on print
	window.onbeforeprint = function() {
		document.querySelectorAll('button').forEach(function(el) { el.style.display = 'none'; });
	};
	window.onafterprint = function() {
		document.querySelectorAll('button').forEach(function(el) { el.style.display = ''; });
	};
</script>

    <script src="<?php echo $base_url ?? ''; ?>/assets/js/main.js"></script>
</body>
</html>


















