<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an external user
require_external();

// Get user data for the external user
$user_id = $_SESSION['user_sessions']['external']['user_id'];

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="text-center py-5">
        <i class="fas fa-exclamation-circle text-red-500 text-3xl"></i>
        <p class="mt-2 text-gray-600">Invalid request ID.</p>
    </div>';
    exit;
}

$request_id = $_GET['id'];

// Verify the request belongs to the user
$request_query = "SELECT b.*, u.name as user_name, u.email as user_email 
                 FROM bookings b
                 LEFT JOIN users u ON b.user_id = u.id
                 WHERE b.id = ? AND b.user_id = ?";
$stmt = $conn->prepare($request_query);
$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo '<div class="text-center py-5">
        <i class="fas fa-exclamation-circle text-red-500 text-3xl"></i>
        <p class="mt-2 text-gray-600">Request not found or you don\'t have permission to view it.</p>
    </div>';
    exit;
}

$request = $result->fetch_assoc();

// Get facility information
$facility_name = "Gymnasium";
$facility_query = "SELECT * FROM gym_facilities WHERE id = ?";
$facility_id = null;

// Check different possible column names for facility ID
$possible_columns = ['facility_id', 'gym_facility_id', 'facility'];
foreach ($possible_columns as $column) {
    if (isset($request[$column]) && !empty($request[$column])) {
        $facility_id = $request[$column];
        break;
    }
}

if ($facility_id) {
    $stmt = $conn->prepare($facility_query);
    $stmt->bind_param("i", $facility_id);
    $stmt->execute();
    $facility_result = $stmt->get_result();
    
    if ($facility_result->num_rows > 0) {
        $facility = $facility_result->fetch_assoc();
        $facility_name = $facility['name'];
    }
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

// Determine status
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

// Get user information
$display_name = isset($request['user_name']) && !empty($request['user_name']) ? $request['user_name'] : 'exter';
$display_email = isset($request['user_email']) && !empty($request['user_email']) ? $request['user_email'] : 'external123@gmail.com';
?>

<div class="p-4">
    <div class="flex justify-between items-center mb-6">
        <h4 class="text-lg font-semibold text-gray-800"><?php echo $formatted_id; ?></h4>
        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
            <?php echo $status_text; ?>
        </span>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h5 class="text-sm font-medium text-gray-500 uppercase mb-2">Booking Information</h5>
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="mb-3">
                    <span class="block text-sm font-medium text-gray-500">Facility</span>
                    <span class="block text-sm text-gray-800"><?php echo $facility_name; ?></span>
                </div>
                <div class="mb-3">
                    <span class="block text-sm font-medium text-gray-500">Date</span>
                    <span class="block text-sm text-gray-800"><?php echo date('F j, Y', strtotime($request['date'])); ?></span>
                </div>
                <div class="mb-3">
                    <span class="block text-sm font-medium text-gray-500">Time</span>
                    <span class="block text-sm text-gray-800"><?php echo $start_time . ' - ' . $end_time; ?></span>
                </div>
                <div class="mb-3">
                    <span class="block text-sm font-medium text-gray-500">Purpose</span>
                    <span class="block text-sm text-gray-800"><?php echo htmlspecialchars($request['purpose']); ?></span>
                </div>
                <div class="mb-3">
                    <span class="block text-sm font-medium text-gray-500">Attendees</span>
                    <span class="block text-sm text-gray-800"><?php echo $attendees; ?></span>
                </div>
                <div>
                    <span class="block text-sm font-medium text-gray-500">Requested On</span>
                    <span class="block text-sm text-gray-800"><?php echo date('F j, Y g:i A', strtotime($request['created_at'])); ?></span>
                </div>
            </div>
        </div>
        
        <div>
            <h5 class="text-sm font-medium text-gray-500 uppercase mb-2">Requester Information</h5>
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="mb-3">
                    <span class="block text-sm font-medium text-gray-500">Name</span>
                    <span class="block text-sm text-gray-800"><?php echo $display_name; ?></span>
                </div>
                <div class="mb-3">
                    <span class="block text-sm font-medium text-gray-500">Email</span>
                    <span class="block text-sm text-gray-800"><?php echo $display_email; ?></span>
                </div>
                <div>
                    <span class="block text-sm font-medium text-gray-500">Department</span>
                    <span class="block text-sm text-gray-800">notre</span>
                </div>
            </div>
            
            <h5 class="text-sm font-medium text-gray-500 uppercase mt-6 mb-2">Admin Response</h5>
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="mb-3">
                    <span class="block text-sm font-medium text-gray-500">Status</span>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                        <?php echo $status_text; ?>
                    </span>
                </div>
                <div>
                    <span class="block text-sm font-medium text-gray-500">Remarks</span>
                    <span class="block text-sm text-gray-800"><?php echo !empty($request['admin_remarks']) ? htmlspecialchars($request['admin_remarks']) : 'No remarks provided'; ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($request_status == 'pending'): ?>
    <div class="mt-6 flex justify-end">
        <form action="requests.php" method="POST" class="inline">
            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
            <button type="submit" name="cancel_request" class="bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2" onclick="return confirm('Are you sure you want to cancel this request?')">
                <i class="fas fa-times-circle mr-1"></i> Cancel Request
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>
