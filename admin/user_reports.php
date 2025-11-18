<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_admin();

$page_title = "User Accounts Report - CHMSU BAO";
$base_url = "..";

$role = isset($_GET['role']) ? sanitize_input($_GET['role']) : '';
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : '';

$sql = "SELECT id, name, email, user_type, status, created_at FROM user_accounts WHERE 1=1";
$params = []; $types = "";
if (!empty($role)) { $sql .= " AND user_type = ?"; $params[] = $role; $types .= "s"; }
if (!empty($status)) { $sql .= " AND status = ?"; $params[] = $status; $types .= "s"; }
if (!empty($start_date)) { $sql .= " AND DATE(created_at) >= ?"; $params[] = $start_date; $types .= "s"; }
if (!empty($end_date))   { $sql .= " AND DATE(created_at) <= ?"; $params[] = $end_date;   $types .= "s"; }
$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$users = $stmt->get_result();

// Aggregates
$counts = [ 'admin'=>0, 'staff'=>0, 'student'=>0, 'external'=>0 ]; $active=0; $inactive=0; $suspended=0;
foreach (['admin','staff','student','external'] as $r) {
	$qr = $conn->query("SELECT COUNT(*) as c FROM user_accounts WHERE user_type='".$conn->real_escape_string($r)."'");
	$counts[$r] = (int)$qr->fetch_assoc()['c'];
}
$active = (int)$conn->query("SELECT COUNT(*) as c FROM user_accounts WHERE status='active'")->fetch_assoc()['c'];
$inactive = (int)$conn->query("SELECT COUNT(*) as c FROM user_accounts WHERE status='inactive'")->fetch_assoc()['c'];
$suspended = (int)$conn->query("SELECT COUNT(*) as c FROM user_accounts WHERE status='suspended'")->fetch_assoc()['c'];
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
	<?php include '../includes/admin_sidebar.php'; ?>
	<div class="flex-1 flex flex-col overflow-hidden">
		<header class="bg-white shadow-sm z-10">
			<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
				<h1 class="text-2xl font-semibold text-gray-900">User Accounts Report</h1>
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
					<form method="GET" action="user_reports.php" class="grid grid-cols-1 md:grid-cols-6 gap-4">
						<div>
							<label class="block text-sm text-gray-700 mb-1">Role</label>
							<select name="role" class="w-full rounded-md border-gray-300">
								<option value="">All</option>
								<?php foreach (['admin','staff','student','external'] as $r): ?>
									<option value="<?php echo $r; ?>" <?php echo $role===$r?'selected':''; ?>><?php echo ucfirst($r); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div>
							<label class="block text-sm text-gray-700 mb-1">Status</label>
							<select name="status" class="w-full rounded-md border-gray-300">
								<option value="">All</option>
								<?php foreach (['active','inactive','suspended'] as $s): ?>
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
				<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
					<div class="bg-white rounded-lg shadow p-4"><p class="text-gray-500 text-sm">Admins</p><h3 class="text-2xl font-bold"><?php echo number_format($counts['admin']); ?></h3></div>
					<div class="bg-white rounded-lg shadow p-4"><p class="text-gray-500 text-sm">Staff</p><h3 class="text-2xl font-bold"><?php echo number_format($counts['staff']); ?></h3></div>
					<div class="bg-white rounded-lg shadow p-4"><p class="text-gray-500 text-sm">Students</p><h3 class="text-2xl font-bold"><?php echo number_format($counts['student']); ?></h3></div>
					<div class="bg-white rounded-lg shadow p-4"><p class="text-gray-500 text-sm">External</p><h3 class="text-2xl font-bold"><?php echo number_format($counts['external']); ?></h3></div>
				</div>

				<!-- Table -->
				<div class="bg-white rounded-lg shadow">
					<div class="px-4 py-5 border-b border-gray-200 sm:px-6 flex justify-between items-center">
						<h3 class="text-lg font-medium text-gray-900">Registered Users</h3>
						<button onclick="window.print()" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700"><i class="fas fa-print mr-1"></i> Print</button>
					</div>
					<div class="overflow-x-auto">
						<table class="min-w-full divide-y divide-gray-200">
							<thead class="bg-gray-50">
								<tr>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User ID</th>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Registered</th>
								</tr>
							</thead>
							<tbody class="bg-white divide-y divide-gray-200">
								<?php if ($users->num_rows > 0): while ($u = $users->fetch_assoc()): ?>
								<tr>
									<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $u['id']; ?></td>
									<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($u['name']); ?></td>
									<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo ucfirst($u['user_type']); ?></td>
									<td class="px-6 py-4 whitespace-nowrap">
										<?php $cls='bg-gray-100 text-gray-800'; if ($u['status']==='active') $cls='bg-green-100 text-green-800'; elseif ($u['status']==='inactive') $cls='bg-yellow-100 text-yellow-800'; elseif ($u['status']==='suspended') $cls='bg-red-100 text-red-800'; ?>
										<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $cls; ?>"><?php echo ucfirst($u['status']); ?></span>
									</td>
									<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo format_date($u['created_at']); ?></td>
								</tr>
								<?php endwhile; else: ?>
								<tr><td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No users found</td></tr>
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
















