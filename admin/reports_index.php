<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin or Secretary access
require_admin();

$page_title = "Reports - CHMSU BAO";
$base_url = "..";
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
	<?php include '../includes/admin_sidebar.php'; ?>
	
	<div class="flex-1 flex flex-col overflow-hidden">
		<!-- Top header -->
		<header class="bg-white shadow-sm z-10">
			<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
				<h1 class="text-2xl font-semibold text-gray-900">Reports</h1>
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
				<!-- Report tiles -->
				<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
					<a href="gym_reports.php?report_type=usage" class="bg-white rounded-lg shadow p-6 hover:shadow-md transition">
						<div class="flex items-center justify-between mb-3">
							<h2 class="text-lg font-semibold text-gray-900">Gym Reservation Report</h2>
							<i class="fas fa-dumbbell text-blue-600"></i>
						</div>
						<p class="text-sm text-gray-600">Bookings by date, facility, status.</p>
					</a>
					<a href="bus_reports.php" class="bg-white rounded-lg shadow p-6 hover:shadow-md transition">
						<div class="flex items-center justify-between mb-3">
							<h2 class="text-lg font-semibold text-gray-900">Bus Reservation Report</h2>
							<i class="fas fa-bus text-emerald-600"></i>
						</div>
						<p class="text-sm text-gray-600">Requests, destinations, kilometers, costs, approvals.</p>
					</a>
					<a href="inventory_reports.php" class="bg-white rounded-lg shadow p-6 hover:shadow-md transition">
						<div class="flex items-center justify-between mb-3">
							<h2 class="text-lg font-semibold text-gray-900">Item Request / Inventory</h2>
							<i class="fas fa-box text-purple-600"></i>
						</div>
						<p class="text-sm text-gray-600">Issued vs stock, movement by category.</p>
					</a>
					<a href="billing_reports.php" class="bg-white rounded-lg shadow p-6 hover:shadow-md transition">
						<div class="flex items-center justify-between mb-3">
							<h2 class="text-lg font-semibold text-gray-900">Billing Summary</h2>
							<i class="fas fa-file-invoice-dollar text-green-600"></i>
						</div>
						<p class="text-sm text-gray-600">Paid vs pending by service type.</p>
					</a>
					<a href="user_reports.php" class="bg-white rounded-lg shadow p-6 hover:shadow-md transition">
						<div class="flex items-center justify-between mb-3">
							<h2 class="text-lg font-semibold text-gray-900">User Accounts</h2>
							<i class="fas fa-users text-yellow-600"></i>
						</div>
						<p class="text-sm text-gray-600">Users by role and status.</p>
					</a>
					<a href="summary_reports.php" class="bg-white rounded-lg shadow p-6 hover:shadow-md transition">
						<div class="flex items-center justify-between mb-3">
							<h2 class="text-lg font-semibold text-gray-900">Daily / Monthly Summary</h2>
							<i class="fas fa-chart-line text-red-600"></i>
						</div>
						<p class="text-sm text-gray-600">Requests, approvals, collections, top services.</p>
					</a>
				</div>
			</div>
		</main>
	</div>
</div>

<script>
	// Mobile menu toggle
	document.getElementById('menu-button').addEventListener('click', function() {
		document.getElementById('sidebar').classList.toggle('-translate-x-full');
	});
</script>

<?php include '../includes/footer.php'; ?>
















