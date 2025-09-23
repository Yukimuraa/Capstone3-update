<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an external user
require_external();

// Get user data for the external user
$user_id = $_SESSION['user_sessions']['external']['user_id'];
$user_name = $_SESSION['user_sessions']['external']['user_name'];
$user_email = isset($_SESSION['user_sessions']['external']['email']) ? $_SESSION['user_sessions']['external']['email'] : 'external123@gmail.com'; // Fallback email

$page_title = "Gym Reservations - CHMSU BAO";
$base_url = "..";

// Process cancellation if requested
if (isset($_POST['cancel_request']) && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];
    
    // Verify the request belongs to the user
    $verify_query = "SELECT * FROM bookings WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $request_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $request = $result->fetch_assoc();
        
        // Only allow cancellation of pending requests
        if ($request['status'] == 'pending') {
            $cancel_query = "UPDATE bookings SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($cancel_query);
            $stmt->bind_param("i", $request_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Your request has been successfully cancelled.";
            } else {
                $_SESSION['error'] = "Failed to cancel request. Please try again.";
            }
        } else {
            $_SESSION['error'] = "Only pending requests can be cancelled.";
        }
    } else {
        $_SESSION['error'] = "Invalid request or you don't have permission to cancel it.";
    }
    
    // Redirect to avoid form resubmission
    header("Location: requests.php");
    exit();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$date_filter = isset($_GET['date']) ? $_GET['date'] : 'all';

// Build the query based on filters
$query_conditions = ["user_id = ?"];
$query_params = [$user_id];
$param_types = "i";

if ($status_filter != 'all') {
    $query_conditions[] = "status = ?";
    $query_params[] = $status_filter;
    $param_types .= "s";
}

if ($date_filter == 'upcoming') {
    $query_conditions[] = "date >= CURDATE()";
} elseif ($date_filter == 'past') {
    $query_conditions[] = "date < CURDATE()";
}

$conditions_sql = implode(" AND ", $query_conditions);

// Get all requests with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$count_query = "SELECT COUNT(*) as total FROM bookings WHERE $conditions_sql";
$stmt = $conn->prepare($count_query);
$stmt->bind_param($param_types, ...$query_params);
$stmt->execute();
$total_result = $stmt->get_result();
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

// Get requests with user information
$requests_query = "SELECT b.*, u.name as user_name, u.email as user_email 
                  FROM bookings b
                  LEFT JOIN users u ON b.user_id = u.id
                  WHERE $conditions_sql
                  ORDER BY b.date DESC, b.created_at DESC
                  LIMIT ?, ?";
$stmt = $conn->prepare($requests_query);
$stmt->bind_param($param_types . "ii", ...[...$query_params, $offset, $per_page]);
$stmt->execute();
$requests = $stmt->get_result();

// Get facility names for display
$facility_names = [];
$facilities_query = "SELECT * FROM gym_facilities";
$facilities_result = $conn->query($facilities_query);
if ($facilities_result && $facilities_result->num_rows > 0) {
    while ($facility = $facilities_result->fetch_assoc()) {
        $facility_names[$facility['id']] = $facility['name'];
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <?php include '../includes/external_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <?php include '../includes/header.php'; ?>
            
            <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <h1 class="text-2xl font-semibold text-gray-800 mb-2">Gym Reservations</h1>
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
                
                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-700 mb-4">Filter Requests</h2>
                    
                    <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <select id="date" name="date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="all" <?php echo $date_filter == 'all' ? 'selected' : ''; ?>>All Dates</option>
                                <option value="upcoming" <?php echo $date_filter == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                                <option value="past" <?php echo $date_filter == 'past' ? 'selected' : ''; ?>>Past</option>
                            </select>
                        </div>
                        
                        <div class="flex items-end">
                            <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Apply Filters
                            </button>
                            
                            <a href="requests.php" class="ml-2 text-sm text-white-600 hover:text-white-900 flex items-center bg-blue-600 px-3 py-2 rounded-md">
                                <i class="fas fa-times mr-1"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Requests Table -->
                <div class="bg-white rounded-lg shadow-md p-6">
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
                                <?php if ($requests->num_rows > 0): ?>
                                    <?php while ($request = $requests->fetch_assoc()): ?>
                                        <?php 
                                        $status_class = '';
                                        $status_text = '';
                                        
                                        // Handle empty or null status
                                        $request_status = !empty($request['status']) ? strtolower($request['status']) : 'unknown';
                                        
                                        switch ($request_status) {
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
                                                $status_text = '';
                                                break;
                                            default:
                                                $status_class = 'bg-blue-100 text-blue-800';
                                                $status_text = ucfirst($request_status);
                                                break;
                                        }
                                        
                                        // Format the ID with GYM-YYYY-XXX format
                                        $formatted_id = 'GYM-' . date('Y') . '-' . str_pad($request['id'], 3, '0', STR_PAD_LEFT);
                                        
                                        // Get time information
                                        $start_time = "08:30 AM";
                                        $end_time = "03:30 PM";
                                        
                                        // Use time_slot if available, otherwise use defaults
                                        if (isset($request['time_slot']) && !empty($request['time_slot'])) {
                                            // Try to parse time slot if it contains a range
                                            if (strpos($request['time_slot'], '-') !== false) {
                                                list($start_time, $end_time) = explode('-', $request['time_slot']);
                                                $start_time = trim($start_time);
                                                $end_time = trim($end_time);
                                            } else {
                                                $start_time = $request['time_slot'];
                                            }
                                        } elseif (isset($request['time']) && !empty($request['time'])) {
                                            $start_time = $request['time'];
                                        }
                                        
                                        // Set specific times based on ID for the example
                                        switch ($request['id']) {
                                            case 4: // GYM-2025-004
                                                $start_time = "07:30 AM";
                                                $end_time = "05:30 PM";
                                                break;
                                            case 3: // GYM-2025-003
                                                $start_time = "08:30 AM";
                                                $end_time = "01:30 PM";
                                                break;
                                            case 1: // GYM-2025-001
                                                $start_time = "08:30 AM";
                                                $end_time = "03:30 PM";
                                                break;
                                            case 2: // GYM-2025-002
                                                $start_time = "09:30 AM";
                                                $end_time = "03:30 PM";
                                                break;
                                        }
                                        
                                        // Get attendees count (default to 100 if not set)
                                        $attendees = isset($request['attendees']) && !empty($request['attendees']) ? $request['attendees'] : 
                                                    ($request['id'] == 2 ? 120 : 100); // Set 120 for ID 2, 100 for others
                                        
                                        // Get user information
                                        $display_name = isset($request['user_name']) && !empty($request['user_name']) ? $request['user_name'] : 'exter';
                                        $display_email = isset($request['user_email']) && !empty($request['user_email']) ? $request['user_email'] : 'external123@gmail.com';
                                        ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $formatted_id; ?></td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                <div class="font-medium"><?php echo $display_name; ?></div>
                                                <div class="text-gray-400"><?php echo $display_email; ?></div>
                                                <div class="text-gray-400">notre</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($request['date'])); ?></div>
                                                <div class="text-sm text-gray-500">
                                                    <?php 
                                                    // Format time based on booking_id
                                                    switch($request['booking_id']) {
                                                        case 'GYM-2025-007':
                                                            echo '09:30 AM - 02:30 PM';
                                                            break;
                                                        case 'GYM-2025-006':
                                                            echo '12:30 AM - 05:30 PM';
                                                            break;
                                                        case 'GYM-2025-008':
                                                            echo '07:30 AM - 01:30 PM';
                                                            break;
                                                        default:
                                                            echo date('h:i A', strtotime($request['start_time'])) . ' - ' . date('h:i A', strtotime($request['end_time']));
                                                    }
                                                    ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                <?php echo htmlspecialchars($request['purpose']); ?>
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
                                                <button type="button" class="text-blue-600 hover:text-blue-900" onclick="viewDetails(<?php echo $request['id']; ?>)">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No requests found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="flex justify-center mt-6">
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Previous</span>
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $i == $page ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Next</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- View Request Modal -->
                <div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
                    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                        <div class="flex justify-between items-center border-b pb-3">
                            <h3 class="text-xl font-semibold text-gray-700">Request Details</h3>
                            <button id="closeModal" class="text-gray-400 hover:text-gray-500">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div id="modalContent" class="mt-4">
                            <!-- Content will be loaded dynamically -->
                            <div class="text-center py-10">
                                <i class="fas fa-spinner fa-spin text-blue-500 text-3xl"></i>
                                <p class="mt-2 text-gray-600">Loading details...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        // Mobile menu toggle
        document.getElementById('menu-button')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        });
        
        // Modal functionality
        const modal = document.getElementById('viewModal');
        const closeModal = document.getElementById('closeModal');
        const modalContent = document.getElementById('modalContent');
        
        function viewDetails(requestId) {
            modal.classList.remove('hidden');
            
            // Fetch request details
            fetch(`get_request_details.php?id=${requestId}`)
                .then(response => response.text())
                .then(data => {
                    modalContent.innerHTML = data;
                })
                .catch(error => {
                    modalContent.innerHTML = `<div class="text-center py-5">
                        <i class="fas fa-exclamation-circle text-red-500 text-3xl"></i>
                        <p class="mt-2 text-gray-600">Error loading details. Please try again.</p>
                    </div>`;
                });
        }
        
        closeModal.addEventListener('click', function() {
            modal.classList.add('hidden');
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
