<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is student
require_student();

$page_title = "My Requests - CHMSU BAO";
$base_url = "..";

$success_message = '';
$error_message = '';

// Handle form submission for new request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_request') {
    $type = sanitize_input($_POST['request_type']);
    $details = sanitize_input($_POST['details']);
    
    if (empty($type) || empty($details)) {
        $error_message = "All fields are required";
    } else {
        // Generate request ID
        $request_id = 'REQ-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        // Insert new request
        $stmt = $conn->prepare("INSERT INTO requests (request_id, user_id, type, details, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->bind_param("siss", $request_id, $_SESSION['user_id'], $type, $details);
        
        if ($stmt->execute()) {
            $success_message = "Your request has been submitted successfully. Request ID: " . $request_id;
        } else {
            $error_message = "Error submitting request: " . $conn->error;
        }
    }
}

// Get all requests for the current user
$query = "SELECT * FROM requests WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

// Get request types for dropdown
$types_query = "SELECT DISTINCT type FROM requests ORDER BY type";
$types_result = $conn->query($types_query);
$request_types = [];
if ($types_result->num_rows > 0) {
    while ($row = $types_result->fetch_assoc()) {
        $request_types[] = $row['type'];
    }
}

// Add some default request types if none exist
if (empty($request_types)) {
    $request_types = [
        'PE Uniform Order',
        'Graduation Cord',
        'Logo Patch',
        'ID Lace',
        'General Inquiry',
        'Document Request',
        'Other'
    ];
}
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/student_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">My Requests</h1>
                <div class="flex items-center">
                    <span class="text-gray-700 mr-2"><?php echo $_SESSION['user_name']; ?></span>
                    <button class="md:hidden rounded-md p-2 inline-flex items-center justify-center text-gray-500 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-emerald-500" id="menu-button">
                        <span class="sr-only">Open menu</span>
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </header>
        
        <!-- Main content -->
        <main class="flex-1 overflow-y-auto p-4">
            <div class="max-w-7xl mx-auto">
                <?php if (!empty($success_message)): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                        <p><?php echo $success_message; ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                        <p><?php echo $error_message; ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- New Request Form -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                        <h3 class="text-lg font-medium text-gray-900">Submit a New Request</h3>
                    </div>
                    <div class="p-6">
                        <form action="requests.php" method="POST">
                            <input type="hidden" name="action" value="submit_request">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <label for="request_type" class="block text-sm font-medium text-gray-700 mb-1">Request Type</label>
                                    <select id="request_type" name="request_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50" required>
                                        <option value="">Select Request Type</option>
                                        <?php foreach ($request_types as $type): ?>
                                            <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label for="details" class="block text-sm font-medium text-gray-700 mb-1">Request Details</label>
                                    <textarea id="details" name="details" rows="4" class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50" placeholder="Please provide details about your request..." required></textarea>
                                </div>
                            </div>
                            
                            <div class="mt-6 flex justify-end">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                                    <i class="fas fa-paper-plane mr-2"></i> Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Requests List -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-4 py-5 border-b border-gray-200 sm:px-6 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">My Request History</h3>
                        <span class="text-sm text-gray-500"><?php echo $result->num_rows; ?> requests found</span>
                    </div>
                    
                    <?php if ($result->num_rows > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php while ($request = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo $request['request_id']; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $request['type']; ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                                <?php echo $request['details']; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($request['status'] == 'pending'): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        Pending
                                                    </span>
                                                <?php elseif ($request['status'] == 'approved'): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        Approved
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        Rejected
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo format_date($request['created_at']); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-clipboard-list text-gray-400 text-4xl mb-2"></i>
                            <p class="text-gray-500 mb-4">You haven't submitted any requests yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Request Information -->
                <div class="bg-white rounded-lg shadow mt-6 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Request Information</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Request Process</h4>
                            <p class="mt-1 text-sm text-gray-600">
                                After submitting a request, the Business Affairs Office will review it and update the status accordingly.
                                You can check the status of your requests on this page.
                            </p>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Request Status</h4>
                            <ul class="mt-1 text-sm text-gray-600 list-disc list-inside space-y-1">
                                <li><span class="font-medium">Pending</span>: Your request has been submitted and is awaiting review.</li>
                                <li><span class="font-medium">Approved</span>: Your request has been approved and is being processed.</li>
                                <li><span class="font-medium">Rejected</span>: Your request could not be processed. Please contact the BAO office for more information.</li>
                            </ul>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Contact Information</h4>
                            <p class="mt-1 text-sm text-gray-600">
                                For any questions regarding your requests, please contact the Business Affairs Office:
                                <br>
                                Email: bao@chmsu.edu.ph
                                <br>
                                Phone: (123) 456-7890
                            </p>
                        </div>
                    </div>
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