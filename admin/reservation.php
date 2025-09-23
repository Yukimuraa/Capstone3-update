<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is admin
require_admin();

// Get user data for the admin
$user_id = $_SESSION['user_sessions']['admin']['user_id'];
$user_name = $_SESSION['user_sessions']['admin']['user_name'];

$page_title = "Gym Reservations - CHMSU BAO";
$base_url = "..";

// Process actions if submitted
if (isset($_POST['action']) && isset($_POST['reservation_id'])) {
    $reservation_id = $_POST['reservation_id'];
    $action = $_POST['action'];
    $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';
    
    // Verify the reservation exists
    $verify_query = "SELECT * FROM bookings WHERE id = ?";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $reservation = $result->fetch_assoc();
        
        // Update status based on action
        $new_status = '';
        switch ($action) {
            case 'approve':
                $new_status = 'confirmed';
                break;
            case 'reject':
                $new_status = 'rejected';
                break;
            case 'cancel':
                $new_status = 'cancelled';
                break;
            default:
                $_SESSION['error'] = "Invalid action.";
                header("Location: reservation.php");
                exit();
        }
        
        // Update the reservation
        $update_query = "UPDATE bookings SET status = ?, admin_remarks = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssi", $new_status, $remarks, $reservation_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Reservation has been " . ($new_status == 'confirmed' ? 'approved' : $new_status) . " successfully.";
        } else {
            $_SESSION['error'] = "Failed to update reservation. Please try again.";
        }
    } else {
        $_SESSION['error'] = "Invalid reservation.";
    }
    
    // Redirect to avoid form resubmission
    header("Location: reservation.php");
    exit();
}

// Determine the correct facility column name in the bookings table
$facility_column = "facility_id"; // Default column name

// Check if facility_id exists in the bookings table
$check_column = "SHOW COLUMNS FROM bookings LIKE 'facility_id'";
$column_result = $conn->query($check_column);

if ($column_result->num_rows == 0) {
    // If facility_id doesn't exist, try 'facility'
    $check_column = "SHOW COLUMNS FROM bookings LIKE 'facility'";
    $column_result = $conn->query($check_column);
    
    if ($column_result->num_rows > 0) {
        $facility_column = "facility";
    } else {
        // If neither exists, try 'gym_facility_id'
        $check_column = "SHOW COLUMNS FROM bookings LIKE 'gym_facility_id'";
        $column_result = $conn->query($check_column);
        
        if ($column_result->num_rows > 0) {
            $facility_column = "gym_facility_id";
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$date_filter = isset($_GET['date']) ? $_GET['date'] : 'all';
$facility_filter = isset($_GET['facility']) ? $_GET['facility'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query based on filters
$query_conditions = [];
$query_params = [];
$param_types = "";

if ($status_filter != 'all') {
    $query_conditions[] = "b.status = ?";
    $query_params[] = $status_filter;
    $param_types .= "s";
}

if ($date_filter == 'upcoming') {
    $query_conditions[] = "b.date >= CURDATE()";
} elseif ($date_filter == 'past') {
    $query_conditions[] = "b.date < CURDATE()";
} elseif ($date_filter == 'today') {
    $query_conditions[] = "b.date = CURDATE()";
} elseif ($date_filter == 'week') {
    $query_conditions[] = "b.date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
} elseif ($date_filter == 'month') {
    $query_conditions[] = "b.date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 MONTH)";
}

if ($facility_filter != 'all') {
    $query_conditions[] = "b.$facility_column = ?";
    $query_params[] = $facility_filter;
    $param_types .= "i";
}

if (!empty($search)) {
    $search_param = "%$search%";
    $query_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR b.purpose LIKE ? OR b.booking_id LIKE ?)";
    $query_params[] = $search_param;
    $query_params[] = $search_param;
    $query_params[] = $search_param;
    $query_params[] = $search_param;
    $param_types .= "ssss";
}

$conditions_sql = !empty($query_conditions) ? "WHERE " . implode(" AND ", $query_conditions) : "";

// Get all reservations with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Count total reservations
$count_query = "SELECT COUNT(*) as total FROM bookings b 
                LEFT JOIN users u ON b.user_id = u.id 
                $conditions_sql";

if (!empty($param_types)) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($param_types, ...$query_params);
    $stmt->execute();
    $total_result = $stmt->get_result();
} else {
    $total_result = $conn->query($count_query);
}

$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

// Get reservations with user and facility info
$reservations_query = "SELECT b.*, u.name as user_name, u.email as user_email, u.organization, 
                      gf.name as facility_name 
                      FROM bookings b 
                      LEFT JOIN users u ON b.user_id = u.id 
                      LEFT JOIN gym_facilities gf ON b.$facility_column = gf.id 
                      $conditions_sql
                      ORDER BY b.date DESC, b.created_at DESC
                      LIMIT ?, ?";

if (!empty($param_types)) {
    $stmt = $conn->prepare($reservations_query);
    $stmt->bind_param($param_types . "ii", ...[...$query_params, $offset, $per_page]);
    $stmt->execute();
    $reservations = $stmt->get_result();
} else {
    $stmt = $conn->prepare($reservations_query);
    $stmt->bind_param("ii", $offset, $per_page);
    $stmt->execute();
    $reservations = $stmt->get_result();
}

// Get all facilities for filter dropdown
$facilities_query = "SELECT * FROM gym_facilities ORDER BY name";
$facilities_result = $conn->query($facilities_query);
$facilities = [];
if ($facilities_result && $facilities_result->num_rows > 0) {
    while ($facility = $facilities_result->fetch_assoc()) {
        $facilities[] = $facility;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <?php include '../includes/header.php'; ?>
            
            <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="max-w-7xl mx-auto">
                    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Gym Reservations</h1>
                    <p class="text-gray-600 mb-6">Manage gym reservation requests</p>
                    
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
                    
                    <!-- Debug Info (only visible with debug parameter) -->
                    <?php if (isset($_GET['debug'])): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <h2 class="text-lg font-semibold text-gray-700 mb-4">Debug Information</h2>
                        <div class="bg-gray-100 p-4 rounded overflow-auto">
                            <p class="font-mono text-sm">Facility Column: <?php echo $facility_column; ?></p>
                            <p class="font-mono text-sm mt-2">Reservations Query:</p>
                            <pre class="font-mono text-xs mt-1"><?php 
                                $debug_query = str_replace("b.$facility_column", "b.$facility_column /* Using detected column */", $reservations_query);
                                echo htmlspecialchars($debug_query); 
                            ?></pre>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Filters -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <h2 class="text-lg font-semibold text-gray-700 mb-4">Filter Reservations</h2>
                        
                        <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                <select id="date" name="date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                    <option value="all" <?php echo $date_filter == 'all' ? 'selected' : ''; ?>>All Dates</option>
                                    <option value="today" <?php echo $date_filter == 'today' ? 'selected' : ''; ?>>Today</option>
                                    <option value="upcoming" <?php echo $date_filter == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                                    <option value="week" <?php echo $date_filter == 'week' ? 'selected' : ''; ?>>Next 7 Days</option>
                                    <option value="month" <?php echo $date_filter == 'month' ? 'selected' : ''; ?>>Next 30 Days</option>
                                    <option value="past" <?php echo $date_filter == 'past' ? 'selected' : ''; ?>>Past</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="facility" class="block text-sm font-medium text-gray-700 mb-1">Facility</label>
                                <select id="facility" name="facility" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                    <option value="all" <?php echo $facility_filter == 'all' ? 'selected' : ''; ?>>All Facilities</option>
                                    <?php foreach ($facilities as $facility): ?>
                                        <option value="<?php echo $facility['id']; ?>" <?php echo $facility_filter == $facility['id'] ? 'selected' : ''; ?>>
                                            <?php echo $facility['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, Email, Purpose..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            </div>
                            
                            <div class="flex items-end">
                                <button type="submit" class="bg-emerald-600 text-white py-2 px-4 rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                    Apply Filters
                                </button>
                                
                                <a href="reservation.php" class="ml-2 text-sm text-gray-600 hover:text-gray-900 flex items-center">
                                    <i class="fas fa-times mr-1"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Reservations Table -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="px-4 py-5 border-b border-gray-200 sm:px-6 flex justify-between items-center">
                            <h2 class="text-lg font-medium text-gray-900">Gym Reservations</h2>
                            <span class="text-sm text-gray-500">Total: <?php echo $total_rows; ?> reservation(s)</span>
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
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if ($reservations->num_rows > 0): ?>
                                        <?php 
                                        $count = 0;
                                        while ($reservation = $reservations->fetch_assoc()): 
                                            $count++;
                                            // Format booking ID
                                            $booking_id = isset($reservation['booking_id']) && !empty($reservation['booking_id']) 
                                                ? $reservation['booking_id'] 
                                                : 'GYM-' . date('Y') . '-' . str_pad($reservation['id'], 3, '0', STR_PAD_LEFT);
                                            
                                            // Determine status class and text
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            // Handle empty or null status
                                            $reservation_status = !empty($reservation['status']) ? strtolower($reservation['status']) : 'unknown';
                                            
                                            switch ($reservation_status) {
                                                case 'pending':
                                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                                    $status_text = 'Pending';
                                                    break;
                                                case 'confirmed':
                                                    $status_class = 'bg-green-100 text-green-800';
                                                    $status_text = 'Approved';
                                                    break;
                                                case 'rejected':
                                                    $status_class = 'bg-red-100 text-red-800';
                                                    $status_text = 'Rejected';
                                                    break;
                                                case 'cancelled':
                                                case 'cancel':
                                                case 'canceled':
                                                    $status_class = 'bg-gray-100 text-gray-800';
                                                    $status_text = 'Cancelled';
                                                    break;
                                                case 'unknown':
                                                case '':
                                                    $status_class = 'bg-gray-100 text-gray-800';
                                                    $status_text = 'Unknown';
                                                    break;
                                                default:
                                                    $status_class = 'bg-blue-100 text-blue-800';
                                                    $status_text = ucfirst($reservation_status);
                                                    break;
                                            }
                                            
                                            // Generate time slots based on ID for demo purposes
                                            $time_slots = [
                                                '07:30 AM - 05:30 PM',
                                                '08:30 AM - 01:30 PM',
                                                '08:30 AM - 03:30 PM',
                                                '09:30 AM - 03:30 PM',
                                                '10:00 AM - 04:00 PM',
                                            ];
                                            
                                            $time_slot = isset($reservation['time_slot']) && !empty($reservation['time_slot']) 
                                                ? $reservation['time_slot'] 
                                                : (isset($reservation['time']) && !empty($reservation['time']) 
                                                    ? $reservation['time'] 
                                                    : $time_slots[$count % count($time_slots)]);
                                            
                                            // Generate attendees count based on ID for demo purposes
                                            $attendees = isset($reservation['attendees']) && !empty($reservation['attendees']) 
                                                ? $reservation['attendees'] 
                                                : (80 + ($count * 10) % 50);
                                        ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo $booking_id; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo $reservation['user_name'] ?? 'Unknown User'; ?></div>
                                                <div class="text-sm text-gray-500"><?php echo $reservation['user_email'] ?? ''; ?></div>
                                                <?php if (!empty($reservation['organization'])): ?>
                                                    <div class="text-xs text-gray-400"><?php echo $reservation['organization']; ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo date('F j, Y', strtotime($reservation['date'])); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo $time_slot; ?></div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900">
                                                    <span class="tooltip" title="<?php echo htmlspecialchars($reservation['purpose'] ?? 'No purpose specified'); ?>">
                                                        <?php 
                                                        $purpose = $reservation['purpose'] ?? 'No purpose specified';
                                                        echo strlen($purpose) > 30 ? substr(htmlspecialchars($purpose), 0, 30) . '...' : htmlspecialchars($purpose); 
                                                        ?>
                                                    </span>
                                                </div>
                                                <div class="text-sm text-gray-500"><?php echo $reservation['facility_name'] ?? 'Unknown Facility'; ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $attendees; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <button type="button" class="text-blue-600 hover:text-blue-900" onclick="viewDetails(<?php echo $reservation['id']; ?>)">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    
                                                    <?php if ($reservation_status == 'pending'): ?>
                                                        <button type="button" class="text-green-600 hover:text-green-900" onclick="approveReservation(<?php echo $reservation['id']; ?>)">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                        
                                                        <button type="button" class="text-red-600 hover:text-red-900" onclick="rejectReservation(<?php echo $reservation['id']; ?>)">
                                                            <i class="fas fa-times"></i> Reject
                                                        </button>
                                                    <?php elseif ($reservation_status == 'confirmed'): ?>
                                                        <button type="button" class="text-gray-600 hover:text-gray-900" onclick="cancelReservation(<?php echo $reservation['id']; ?>)">
                                                            <i class="fas fa-ban"></i> Cancel
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
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
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 sm:px-6">
                                <div class="flex justify-between items-center">
                                    <div class="text-sm text-gray-700">
                                        Showing <span class="font-medium"><?php echo min(($page - 1) * $per_page + 1, $total_rows); ?></span> to <span class="font-medium"><?php echo min($page * $per_page, $total_rows); ?></span> of <span class="font-medium"><?php echo $total_rows; ?></span> results
                                    </div>
                                    
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <?php if ($page > 1): ?>
                                            <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>&facility=<?php echo $facility_filter; ?>&search=<?php echo urlencode($search); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <span class="sr-only">Previous</span>
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php 
                                        $start_page = max(1, min($page - 2, $total_pages - 4));
                                        $end_page = min($total_pages, max($page + 2, 5));
                                        
                                        for ($i = $start_page; $i <= $end_page; $i++): 
                                        ?>
                                            <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>&facility=<?php echo $facility_filter; ?>&search=<?php echo urlencode($search); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $i == $page ? 'text-emerald-600 bg-emerald-50' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>&facility=<?php echo $facility_filter; ?>&search=<?php echo urlencode($search); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <span class="sr-only">Next</span>
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </nav>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- View Reservation Modal -->
    <div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-xl font-semibold text-gray-700">Reservation Details</h3>
                <button id="closeViewModal" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div id="viewModalContent" class="mt-4">
                <!-- Content will be loaded dynamically -->
                <div class="text-center py-10">
                    <i class="fas fa-spinner fa-spin text-emerald-500 text-3xl"></i>
                    <p class="mt-2 text-gray-600">Loading details...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Modals -->
    <!-- Approve Modal -->
    <div id="approveModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/3 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-xl font-semibold text-gray-700">Approve Reservation</h3>
                <button class="closeActionModal text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form action="" method="POST" class="mt-4">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="reservation_id" id="approveReservationId">
                
                <div class="mb-4">
                    <label for="approveRemarks" class="block text-sm font-medium text-gray-700 mb-1">Remarks (Optional)</label>
                    <textarea id="approveRemarks" name="remarks" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="Add any notes or instructions..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" class="closeActionModal px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Approve Reservation
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/3 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-xl font-semibold text-gray-700">Reject Reservation</h3>
                <button class="closeActionModal text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form action="" method="POST" class="mt-4">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="reservation_id" id="rejectReservationId">
                
                <div class="mb-4">
                    <label for="rejectRemarks" class="block text-sm font-medium text-gray-700 mb-1">Reason for Rejection <span class="text-red-500">*</span></label>
                    <textarea id="rejectRemarks" name="remarks" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="Provide a reason for rejecting this reservation..." required></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" class="closeActionModal px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                        Reject Reservation
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Cancel Modal -->
    <div id="cancelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/3 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-xl font-semibold text-gray-700">Cancel Reservation</h3>
                <button class="closeActionModal text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form action="" method="POST" class="mt-4">
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="reservation_id" id="cancelReservationId">
                
                <div class="mb-4">
                    <p class="text-gray-700 mb-3">Are you sure you want to cancel this reservation?</p>
                    <label for="cancelRemarks" class="block text-sm font-medium text-gray-700 mb-1">Reason for Cancellation <span class="text-red-500">*</span></label>
                    <textarea id="cancelRemarks" name="remarks" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="Provide a reason for cancelling this reservation..." required></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" class="closeActionModal px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        No, Keep It
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                        Yes, Cancel Reservation
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Mobile menu toggle
        document.getElementById('menu-button')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        });
        
        // View Modal functionality
        const viewModal = document.getElementById('viewModal');
        const closeViewModal = document.getElementById('closeViewModal');
        const viewModalContent = document.getElementById('viewModalContent');
        
        function viewDetails(reservationId) {
            viewModal.classList.remove('hidden');
            
            // In a real application, you would fetch the details from the server
            // For now, we'll simulate it with a timeout
            setTimeout(() => {
                // Sample reservation details HTML
                const detailsHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Reservation ID</h4>
                            <p class="text-base">GYM-2025-${String(reservationId).padStart(3, '0')}</p>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Status</h4>
                            <p class="text-base">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Approved
                                </span>
                            </p>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Requester</h4>
                            <p class="text-base">External User</p>
                            <p class="text-sm text-gray-500">external123@gmail.com</p>
                            <p class="text-xs text-gray-400">notre</p>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Facility</h4>
                            <p class="text-base">Basketball Court</p>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Date</h4>
                            <p class="text-base">May 15, 2025</p>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Time</h4>
                            <p class="text-base">08:30 AM - 01:30 PM</p>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Purpose</h4>
                            <p class="text-base">Conference</p>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Attendees</h4>
                            <p class="text-base">100</p>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Description</h4>
                        <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded">
                            This is a conference for the department of notre. We will be discussing the upcoming events and activities for the school year.
                        </p>
                    </div>
                    
                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Admin Remarks</h4>
                        <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded">
                            Approved. Please make sure to clean up after the event.
                        </p>
                    </div>
                    
                    <div class="border-t pt-4 flex justify-end space-x-3">
                        <button type="button" id="closeDetailsBtn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                            Close
                        </button>
                    </div>
                `;
                
                viewModalContent.innerHTML = detailsHTML;
                
                // Add event listener to the close button in the details
                document.getElementById('closeDetailsBtn').addEventListener('click', function() {
                    viewModal.classList.add('hidden');
                });
            }, 500);
        }
        
        closeViewModal.addEventListener('click', function() {
            viewModal.classList.add('hidden');
        });
        
        // Action Modal functionality
        function approveReservation(reservationId) {
            document.getElementById('approveReservationId').value = reservationId;
            document.getElementById('approveModal').classList.remove('hidden');
        }
        
        function rejectReservation(reservationId) {
            document.getElementById('rejectReservationId').value = reservationId;
            document.getElementById('rejectModal').classList.remove('hidden');
        }
        
        function cancelReservation(reservationId) {
            document.getElementById('cancelReservationId').value = reservationId;
            document.getElementById('cancelModal').classList.remove('hidden');
        }
        
        // Close action modals
        document.querySelectorAll('.closeActionModal').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('approveModal').classList.add('hidden');
                document.getElementById('rejectModal').classList.add('hidden');
                document.getElementById('cancelModal').classList.add('hidden');
            });
        });
        
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === viewModal) {
                viewModal.classList.add('hidden');
            }
            if (event.target === document.getElementById('approveModal')) {
                document.getElementById('approveModal').classList.add('hidden');
            }
            if (event.target === document.getElementById('rejectModal')) {
                document.getElementById('rejectModal').classList.add('hidden');
            }
            if (event.target === document.getElementById('cancelModal')) {
                document.getElementById('cancelModal').classList.add('hidden');
            }
        });
    </script>
</body>
</html>
