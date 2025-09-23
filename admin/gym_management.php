<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is admin
require_admin();

// Get user data for the admin
$user_id = $_SESSION['user_sessions']['admin']['user_id'];
$user_name = $_SESSION['user_sessions']['admin']['user_name'];

$page_title = "Gym Management - CHMSU BAO";
$base_url = "..";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Approve booking
        if ($_POST['action'] === 'approve' && isset($_POST['booking_id'])) {
            $booking_id = sanitize_input($_POST['booking_id']);
            $remarks = sanitize_input($_POST['remarks'] ?? '');
            
            $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed', additional_info = JSON_SET(COALESCE(additional_info, '{}'), '$.admin_remarks', ?) WHERE booking_id = ? AND facility_type = 'gym'");
            $stmt->bind_param("ss", $remarks, $booking_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Reservation has been approved successfully.";
            } else {
                $_SESSION['error'] = "Error approving reservation: " . $conn->error;
            }
        }

        // Reject booking
        elseif ($_POST['action'] === 'reject' && isset($_POST['booking_id'])) {
            $booking_id = sanitize_input($_POST['booking_id']);
            $remarks = sanitize_input($_POST['remarks'] ?? '');
            
            if (empty($remarks)) {
                $_SESSION['error'] = "Rejection reason is required.";
            } else {
                $stmt = $conn->prepare("UPDATE bookings SET status = 'rejected', additional_info = JSON_SET(COALESCE(additional_info, '{}'), '$.admin_remarks', ?) WHERE booking_id = ? AND facility_type = 'gym'");
                $stmt->bind_param("ss", $remarks, $booking_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Reservation has been rejected.";
                } else {
                    $_SESSION['error'] = "Error rejecting reservation: " . $conn->error;
                }
            }
        }
        
        // Add new facility
        elseif ($_POST['action'] === 'add_facility') {
            $facility_name = sanitize_input($_POST['facility_name']);
            $capacity = (int)$_POST['capacity'];
            $description = sanitize_input($_POST['description']);
            
            if (empty($facility_name)) {
                $_SESSION['error'] = "Facility name is required.";
            } else {
                $stmt = $conn->prepare("INSERT INTO gym_facilities (name, capacity, description, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
                $stmt->bind_param("sis", $facility_name, $capacity, $description);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "New facility has been added successfully.";
                } else {
                    $_SESSION['error'] = "Error adding facility: " . $conn->error;
                }
            }
        }
        
        // Update facility
        elseif ($_POST['action'] === 'update_facility' && isset($_POST['facility_id'])) {
            $facility_id = (int)$_POST['facility_id'];
            $facility_name = sanitize_input($_POST['facility_name']);
            $capacity = (int)$_POST['capacity'];
            $description = sanitize_input($_POST['description']);
            $status = sanitize_input($_POST['status']);
            
            if (empty($facility_name)) {
                $_SESSION['error'] = "Facility name is required.";
            } else {
                $stmt = $conn->prepare("UPDATE gym_facilities SET name = ?, capacity = ?, description = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("sissi", $facility_name, $capacity, $description, $status, $facility_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Facility has been updated successfully.";
                } else {
                    $_SESSION['error'] = "Error updating facility: " . $conn->error;
                }
            }
        }
        
        // Delete facility
        elseif ($_POST['action'] === 'delete_facility' && isset($_POST['facility_id'])) {
            $facility_id = (int)$_POST['facility_id'];
            
            // Check if facility is in use
            $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM gym_bookings WHERE facility_id = ? AND booking_date >= CURDATE()");
            $check_stmt->bind_param("i", $facility_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                $_SESSION['error'] = "Cannot delete facility as it has upcoming bookings.";
            } else {
                $stmt = $conn->prepare("DELETE FROM gym_facilities WHERE id = ?");
                $stmt->bind_param("i", $facility_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Facility has been deleted successfully.";
                } else {
                    $_SESSION['error'] = "Error deleting facility: " . $conn->error;
                }
            }
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: gym_management.php");
    exit();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$date_filter = isset($_GET['date']) ? $_GET['date'] : 'all';
$facility_filter = isset($_GET['facility']) ? (int)$_GET['facility'] : 0;
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Build the query based on filters
$query_conditions = [];
$query_params = [];
$param_types = "";

if ($status_filter != 'all') {
    if ($status_filter == 'approved') {
        $query_conditions[] = "(status = 'approved' OR status = 'confirmed')";
    } else {
        $query_conditions[] = "status = ?";
        $query_params[] = $status_filter;
        $param_types .= "s";
    }
}

if ($date_filter == 'upcoming') {
    $query_conditions[] = "date >= CURDATE()";
} elseif ($date_filter == 'past') {
    $query_conditions[] = "date < CURDATE()";
}

$conditions_sql = !empty($query_conditions) ? "WHERE " . implode(" AND ", $query_conditions) : "";

// Get all requests with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$count_query = "SELECT COUNT(*) as total FROM bookings $conditions_sql";
$stmt = $conn->prepare($count_query);
if (!empty($query_params)) {
    $stmt->bind_param($param_types, ...$query_params);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

// Get requests with user information
$requests_query = "SELECT b.*, u.name as user_name, u.email as user_email 
                  FROM bookings b
                  LEFT JOIN users u ON b.user_id = u.id
                  $conditions_sql
                  ORDER BY b.date DESC, b.created_at DESC
                  LIMIT ?, ?";
$stmt = $conn->prepare($requests_query);
if (!empty($query_params)) {
    $stmt->bind_param($param_types . "ii", ...[...$query_params, $offset, $per_page]);
} else {
    $stmt->bind_param("ii", $offset, $per_page);
}
$stmt->execute();
$requests = $stmt->get_result();

// Get all facilities for filter dropdown
$facilities_query = "SELECT * FROM gym_facilities ORDER BY name ASC";
$facilities_result = $conn->query($facilities_query);

// Get booking statistics
$stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
              FROM bookings 
              WHERE facility_type = 'gym'";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get facilities for management
$facilities_management_query = "SELECT * FROM gym_facilities ORDER BY name ASC";
$facilities_management_result = $conn->query($facilities_management_query);
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">Gym Management</h1>
                <div class="flex items-center">
                    <span class="text-gray-700 mr-2"><?php echo $user_name; ?></span>
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
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                        <p><?php echo $_SESSION['success']; ?></p>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                        <p><?php echo $_SESSION['error']; ?></p>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <!-- Tabs -->
                <div class="mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <button class="tab-button border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="bookings">
                                Bookings
                            </button>
                            <button class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="facilities">
                                Facilities
                            </button>
                            <button class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="reports">
                                Reports
                            </button>
                        </nav>
                    </div>
                </div>
                
                <!-- Bookings Tab Content -->
                <div id="bookings-tab" class="tab-content">
                    <!-- Booking Statistics -->
                    <div class="mb-6 grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div class="bg-white rounded-lg shadow p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                                    <i class="fas fa-calendar-check text-blue-600"></i>
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-sm font-medium text-gray-500">Total Bookings</h2>
                                    <p class="text-lg font-semibold text-gray-900"><?php echo $stats['total']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-yellow-100 rounded-md p-3">
                                    <i class="fas fa-clock text-yellow-600"></i>
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-sm font-medium text-gray-500">Pending</h2>
                                    <p class="text-lg font-semibold text-gray-900"><?php echo $stats['pending']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                                    <i class="fas fa-check-circle text-green-600"></i>
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-sm font-medium text-gray-500">Approved</h2>
                                    <p class="text-lg font-semibold text-gray-900"><?php echo $stats['approved']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-red-100 rounded-md p-3">
                                    <i class="fas fa-times-circle text-red-600"></i>
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-sm font-medium text-gray-500">Rejected</h2>
                                    <p class="text-lg font-semibold text-gray-900"><?php echo $stats['rejected']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-gray-100 rounded-md p-3">
                                    <i class="fas fa-ban text-gray-600"></i>
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-sm font-medium text-gray-500">Cancelled</h2>
                                    <p class="text-lg font-semibold text-gray-900"><?php echo $stats['cancelled']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <div class="mb-6 bg-white rounded-lg shadow p-4">
                        <form action="gym_management.php" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div>
                                <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                <input type="date" id="date" name="date" value="<?php echo $date_filter; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                            </div>
                            <div>
                                <label for="facility" class="block text-sm font-medium text-gray-700 mb-1">Facility</label>
                                <select id="facility" name="facility" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                    <option value="0">All Facilities</option>
                                    <?php while ($facility = $facilities_result->fetch_assoc()): ?>
                                        <option value="<?php echo $facility['id']; ?>" <?php echo $facility_filter === $facility['id'] ? 'selected' : ''; ?>>
                                            <?php echo $facility['name']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input type="text" id="search" name="search" value="<?php echo $search; ?>" placeholder="Search bookings..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    <i class="fas fa-filter mr-1"></i> Filter
                                </button>
                                <a href="gym_management.php" class="ml-2 bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                    <i class="fas fa-sync-alt mr-1"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Bookings Table -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                            <h3 class="text-lg font-medium text-gray-900">Gym Reservations</h3>
                            <p class="mt-1 text-sm text-gray-500">Manage gym reservation requests</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attendees</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if ($requests->num_rows > 0): ?>
                                        <?php while ($request = $requests->fetch_assoc()): ?>
                                            <?php 
                                            $status_class = '';
                                            $status_text = ucfirst($request['status']);
                                            
                                            switch ($request['status']) {
                                                case 'pending':
                                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                                    break;
                                                case 'confirmed':
                                                    $status_class = 'bg-green-100 text-green-800';
                                                    $status_text = 'Approved';
                                                    break;
                                                case 'rejected':
                                                    $status_class = 'bg-red-100 text-red-800';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'bg-gray-100 text-gray-800';
                                                    break;
                                                default:
                                                    $status_class = 'bg-gray-100 text-gray-800';
                                                    break;
                                            }
                                            
                                            // Parse additional info if exists
                                            $additional_info = json_decode($request['additional_info'] ?? '{}', true) ?: [];
                                            $admin_remarks = $additional_info['admin_remarks'] ?? '';
                                            ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $request['booking_id']; ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <div class="font-medium"><?php echo $request['user_name']; ?></div>
                                                    <div class="text-xs text-gray-400"><?php echo $request['user_email']; ?></div>
                                                    <?php if (!empty($request['organization'])): ?>
                                                        <div class="text-xs text-gray-400"><?php echo $request['organization']; ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <div><?php echo date('F j, Y', strtotime($request['date'])); ?></div>
                                                    <div class="text-xs text-gray-400">
                                                        <?php echo date('h:i A', strtotime($request['start_time'])); ?> - 
                                                        <?php echo date('h:i A', strtotime($request['end_time'])); ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-500">
                                                    <div><?php echo $request['purpose']; ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $request['attendees']; ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                        <?php echo $status_text; ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <button type="button" class="text-blue-600 hover:text-blue-900 mr-3" onclick="viewBookingDetails(<?php echo htmlspecialchars(json_encode([
                                                        'id' => $request['booking_id'],
                                                        'user_name' => $request['user_name'],
                                                        'user_email' => $request['user_email'],
                                                        'facility_name' => $request['facility_type'],
                                                        'booking_date' => $request['date'],
                                                        'time_slot' => date('h:i A', strtotime($request['start_time'])) . ' - ' . date('h:i A', strtotime($request['end_time'])),
                                                        'purpose' => $request['purpose'],
                                                        'participants' => $request['attendees'],
                                                        'status' => $request['status'],
                                                        'admin_remarks' => $admin_remarks
                                                    ])); ?>)">
                                                        View
                                                    </button>
                                                    
                                                    <?php if ($request['status'] === 'pending'): ?>
                                                        <button type="button" class="text-green-600 hover:text-green-900 mr-3" onclick="openApproveModal('<?php echo $request['booking_id']; ?>')">
                                                            Approve
                                                        </button>
                                                        <button type="button" class="text-red-600 hover:text-red-900" onclick="openRejectModal('<?php echo $request['booking_id']; ?>')">
                                                            Reject
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No reservations found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Facilities Tab Content -->
                <div id="facilities-tab" class="tab-content hidden">
                    <!-- Add Facility Button -->
                    <div class="mb-6">
                        <button type="button" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" onclick="openAddFacilityModal()">
                            <i class="fas fa-plus mr-1"></i> Add New Facility
                        </button>
                    </div>
                    
                    <!-- Facilities Table -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                            <h3 class="text-lg font-medium text-gray-900">Gym Facilities</h3>
                            <p class="mt-1 text-sm text-gray-500">Manage gym facilities</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacity</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if ($facilities_management_result->num_rows > 0): ?>
                                        <?php while ($facility = $facilities_management_result->fetch_assoc()): ?>
                                            <?php 
                                            $status_class = '';
                                            
                                            switch ($facility['status']) {
                                                case 'active':
                                                    $status_class = 'bg-green-100 text-green-800';
                                                    break;
                                                case 'inactive':
                                                    $status_class = 'bg-red-100 text-red-800';
                                                    break;
                                                case 'maintenance':
                                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                                    break;
                                            }
                                            ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $facility['id']; ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $facility['name']; ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $facility['capacity']; ?> people</td>
                                                <td class="px-6 py-4 text-sm text-gray-500"><?php echo $facility['description']; ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                        <?php echo ucfirst($facility['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <button type="button" class="text-blue-600 hover:text-blue-900 mr-3" onclick="openEditFacilityModal(<?php echo htmlspecialchars(json_encode($facility)); ?>)">
                                                        Edit
                                                    </button>
                                                    <button type="button" class="text-red-600 hover:text-red-900" onclick="openDeleteFacilityModal(<?php echo $facility['id']; ?>, '<?php echo $facility['name']; ?>')">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No facilities found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Reports Tab Content -->
                <div id="reports-tab" class="tab-content hidden">
                    <!-- Reports Options -->
                    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Usage Report</h3>
                            <p class="text-sm text-gray-500 mb-4">Generate a report of gym facility usage over a specific period.</p>
                            <form action="gym_reports.php" method="GET" target="_blank">
                                <input type="hidden" name="report_type" value="usage">
                                <div class="mb-4">
                                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                    <input type="date" id="start_date" name="start_date" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                </div>
                                <div class="mb-4">
                                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                    <input type="date" id="end_date" name="end_date" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                </div>
                                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Generate Report
                                </button>
                            </form>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Facility Utilization</h3>
                            <p class="text-sm text-gray-500 mb-4">Generate a report showing utilization rates for each facility.</p>
                            <form action="gym_reports.php" method="GET" target="_blank">
                                <input type="hidden" name="report_type" value="utilization">
                                <div class="mb-4">
                                    <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                                    <input type="month" id="month" name="month" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                </div>
                                <div class="mb-4">
                                    <label for="facility_id" class="block text-sm font-medium text-gray-700 mb-1">Facility (Optional)</label>
                                    <select id="facility_id" name="facility_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                        <option value="">All Facilities</option>
                                        <?php 
                                        // Reset the facilities result pointer
                                        $facilities_result->data_seek(0);
                                        while ($facility = $facilities_result->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $facility['id']; ?>"><?php echo $facility['name']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Generate Report
                                </button>
                            </form>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Booking Status Report</h3>
                            <p class="text-sm text-gray-500 mb-4">Generate a report of bookings by status.</p>
                            <form action="gym_reports.php" method="GET" target="_blank">
                                <input type="hidden" name="report_type" value="status">
                                <div class="mb-4">
                                    <label for="status_filter" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select id="status_filter" name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                        <option value="">All Statuses</option>
                                        <option value="pending">Pending</option>
                                        <option value="approved">Approved</option>
                                        <option value="rejected">Rejected</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label for="report_period" class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                                    <select id="report_period" name="period" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                        <option value="week">Last Week</option>
                                        <option value="month" selected>Last Month</option>
                                        <option value="quarter">Last Quarter</option>
                                        <option value="year">Last Year</option>
                                    </select>
                                </div>
                                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Generate Report
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Booking Details Modal -->
<div id="bookingDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Booking Details</h3>
            <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeBookingDetailsModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="space-y-4">
            <div>
                <h4 class="text-sm font-medium text-gray-500">Booking ID</h4>
                <p id="detail-booking-id" class="mt-1 text-sm text-gray-900"></p>
            </div>
            <div>
                <h4 class="text-sm font-medium text-gray-500">User</h4>
                <p id="detail-user" class="mt-1 text-sm text-gray-900"></p>
                <p id="detail-email" class="text-xs text-gray-500"></p>
            </div>
            <div>
                <h4 class="text-sm font-medium text-gray-500">Facility</h4>
                <p id="detail-facility" class="mt-1 text-sm text-gray-900"></p>
            </div>
            <div>
                <h4 class="text-sm font-medium text-gray-500">Date & Time</h4>
                <p id="detail-datetime" class="mt-1 text-sm text-gray-900"></p>
            </div>
            <div>
                <h4 class="text-sm font-medium text-gray-500">Purpose</h4>
                <p id="detail-purpose" class="mt-1 text-sm text-gray-900"></p>
            </div>
            <div>
                <h4 class="text-sm font-medium text-gray-500">Participants</h4>
                <p id="detail-participants" class="mt-1 text-sm text-gray-900"></p>
            </div>
            <div>
                <h4 class="text-sm font-medium text-gray-500">Status</h4>
                <p id="detail-status" class="mt-1 text-sm"></p>
            </div>
            <div id="detail-remarks-container">
                <h4 class="text-sm font-medium text-gray-500">Admin Remarks</h4>
                <p id="detail-remarks" class="mt-1 text-sm text-gray-900"></p>
            </div>
        </div>
        <div class="mt-6 flex justify-end">
            <button type="button" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2" onclick="closeBookingDetailsModal()">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div id="approveModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Approve Booking</h3>
            <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeApproveModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="gym_management.php">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" id="approve_booking_id" name="booking_id">
            <div class="mb-4">
                <label for="approve_remarks" class="block text-sm font-medium text-gray-700 mb-1">Remarks (Optional)</label>
                <textarea id="approve_remarks" name="remarks" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"></textarea>
            </div>
            <div class="flex justify-end">
                <button type="button" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md mr-2 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2" onclick="closeApproveModal()">
                    Cancel
                </button>
                <button type="submit" class="bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Approve Booking
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Reject Booking</h3>
            <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeRejectModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="gym_management.php">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" id="reject_booking_id" name="booking_id">
            <div class="mb-4">
                <label for="reject_remarks" class="block text-sm font-medium text-gray-700 mb-1">Reason for Rejection</label>
                <textarea id="reject_remarks" name="remarks" rows="3" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"></textarea>
                <p class="mt-1 text-xs text-gray-500">Please provide a reason for rejecting this booking request.</p>
            </div>
            <div class="flex justify-end">
                <button type="button" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md mr-2 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2" onclick="closeRejectModal()">
                    Cancel
                </button>
                <button type="submit" class="bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    Reject Booking
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Facility Modal -->
<div id="addFacilityModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Add New Facility</h3>
            <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeAddFacilityModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="gym_management.php">
            <input type="hidden" name="action" value="add_facility">
            <div class="mb-4">
                <label for="facility_name" class="block text-sm font-medium text-gray-700 mb-1">Facility Name</label>
                <input type="text" id="facility_name" name="facility_name" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>
            <div class="mb-4">
                <label for="capacity" class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                <input type="number" id="capacity" name="capacity" min="1" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>
            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea id="description" name="description" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"></textarea>
            </div>
            <div class="flex justify-end">
                <button type="button" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md mr-2 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2" onclick="closeAddFacilityModal()">
                    Cancel
                </button>
                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Add Facility
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Facility Modal -->
<div id="editFacilityModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Edit Facility</h3>
            <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeEditFacilityModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="gym_management.php">
            <input type="hidden" name="action" value="update_facility">
            <input type="hidden" id="edit_facility_id" name="facility_id">
            <div class="mb-4">
                <label for="edit_facility_name" class="block text-sm font-medium text-gray-700 mb-1">Facility Name</label>
                <input type="text" id="edit_facility_name" name="facility_name" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>
            <div class="mb-4">
                <label for="edit_capacity" class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                <input type="number" id="edit_capacity" name="capacity" min="1" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
            </div>
            <div class="mb-4">
                <label for="edit_description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea id="edit_description" name="description" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"></textarea>
            </div>
            <div class="mb-4">
                <label for="edit_status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="edit_status" name="status" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>
            <div class="flex justify-end">
                <button type="button" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md mr-2 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2" onclick="closeEditFacilityModal()">
                    Cancel
                </button>
                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Update Facility
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Facility Modal -->
<div id="deleteFacilityModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Delete Facility</h3>
            <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeDeleteFacilityModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="gym_management.php">
            <input type="hidden" name="action" value="delete_facility">
            <input type="hidden" id="delete_facility_id" name="facility_id">
            <p class="mb-4 text-sm text-gray-700">Are you sure you want to delete the facility "<span id="delete_facility_name"></span>"? This action cannot be undone.</p>
            <div class="flex justify-end">
                <button type="button" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md mr-2 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2" onclick="closeDeleteFacilityModal()">
                    Cancel
                </button>
                <button type="submit" class="bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    Delete Facility
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Mobile menu toggle
    document.getElementById('menu-button').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
    });
    
    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => {
                btn.classList.remove('border-blue-500', 'text-blue-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });
            
            // Add active class to clicked button and show corresponding content
            button.classList.remove('border-transparent', 'text-gray-500');
            button.classList.add('border-blue-500', 'text-blue-600');
            
            const tabId = button.getAttribute('data-tab');
            document.getElementById(`${tabId}-tab`).classList.remove('hidden');
        });
    });
    
    // Booking details modal functions
    function viewBookingDetails(booking) {
        document.getElementById('detail-booking-id').textContent = booking.id;
        document.getElementById('detail-user').textContent = booking.user_name;
        document.getElementById('detail-email').textContent = booking.user_email;
        document.getElementById('detail-facility').textContent = booking.facility_name;
        
        const bookingDate = new Date(booking.booking_date);
        document.getElementById('detail-datetime').textContent = bookingDate.toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        }) + ', ' + booking.time_slot;
        
        document.getElementById('detail-purpose').textContent = booking.purpose;
        document.getElementById('detail-participants').textContent = booking.participants + ' participants';
        
        // Set status with appropriate styling
        const statusElement = document.getElementById('detail-status');
        statusElement.textContent = booking.status.charAt(0).toUpperCase() + booking.status.slice(1);
        
        // Reset classes
        statusElement.className = 'mt-1 text-sm px-2 inline-flex text-xs leading-5 font-semibold rounded-full';
        
        // Add appropriate class based on status
        switch (booking.status) {
            case 'pending':
                statusElement.classList.add('bg-yellow-100', 'text-yellow-800');
                break;
            case 'approved':
                statusElement.classList.add('bg-green-100', 'text-green-800');
                break;
            case 'rejected':
                statusElement.classList.add('bg-red-100', 'text-red-800');
                break;
            case 'cancelled':
                statusElement.classList.add('bg-gray-100', 'text-gray-800');
                break;
        }
        
        // Show/hide remarks
        const remarksContainer = document.getElementById('detail-remarks-container');
        const remarksElement = document.getElementById('detail-remarks');
        
        if (booking.admin_remarks) {
            remarksElement.textContent = booking.admin_remarks;
            remarksContainer.classList.remove('hidden');
        } else {
            remarksContainer.classList.add('hidden');
        }
        
        document.getElementById('bookingDetailsModal').classList.remove('hidden');
    }
    
    function closeBookingDetailsModal() {
        document.getElementById('bookingDetailsModal').classList.add('hidden');
    }
    
    // Approve modal functions
    function openApproveModal(bookingId) {
        document.getElementById('approve_booking_id').value = bookingId;
        document.getElementById('approveModal').classList.remove('hidden');
    }
    
    function closeApproveModal() {
        document.getElementById('approveModal').classList.add('hidden');
    }
    
    // Reject modal functions
    function openRejectModal(bookingId) {
        document.getElementById('reject_booking_id').value = bookingId;
        document.getElementById('rejectModal').classList.remove('hidden');
    }
    
    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
    }
    
    // Add facility modal functions
    function openAddFacilityModal() {
        document.getElementById('addFacilityModal').classList.remove('hidden');
    }
    
    function closeAddFacilityModal() {
        document.getElementById('addFacilityModal').classList.add('hidden');
    }
    
    // Edit facility modal functions
    function openEditFacilityModal(facility) {
        document.getElementById('edit_facility_id').value = facility.id;
        document.getElementById('edit_facility_name').value = facility.name;
        document.getElementById('edit_capacity').value = facility.capacity;
        document.getElementById('edit_description').value = facility.description;
        document.getElementById('edit_status').value = facility.status;
        
        document.getElementById('editFacilityModal').classList.remove('hidden');
    }
    
    function closeEditFacilityModal() {
        document.getElementById('editFacilityModal').classList.add('hidden');
    }
    
    // Delete facility modal functions
    function openDeleteFacilityModal(facilityId, facilityName) {
        document.getElementById('delete_facility_id').value = facilityId;
        document.getElementById('delete_facility_name').textContent = facilityName;
        
        document.getElementById('deleteFacilityModal').classList.remove('hidden');
    }
    
    function closeDeleteFacilityModal() {
        document.getElementById('deleteFacilityModal').classList.add('hidden');
    }
</script>

<?php include '../includes/footer.php'; ?>
