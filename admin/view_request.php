<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is admin
require_admin();

$page_title = "View Request - CHMSU BAO";
$base_url = "..";

$success_message = '';
$error_message = '';

// Check if request ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: requests.php");
    exit();
}

$request_id = intval($_GET['id']);

// Get request details
$query = "SELECT r.*, u.name as user_name, u.email as user_email, u.user_type 
          FROM requests r 
          JOIN users u ON r.user_id = u.id 
          WHERE r.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if request exists
if ($result->num_rows === 0) {
    header("Location: requests.php");
    exit();
}

$request = $result->fetch_assoc();

// Handle request actions (approve/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Handle approve/reject actions
        if ($_POST['action'] === 'approve' || $_POST['action'] === 'reject') {
            $status = ($_POST['action'] === 'approve') ? 'approved' : 'rejected';
            $admin_notes = isset($_POST['admin_notes']) ? sanitize_input($_POST['admin_notes']) : '';
            
            // Update request status
            $update_stmt = $conn->prepare("UPDATE requests SET status = ?, updated_at = NOW() WHERE id = ?");
            $update_stmt->bind_param("si", $status, $request_id);
            
            if ($update_stmt->execute()) {
                // Check if request_comments table exists, create if not
                $table_check = $conn->query("SHOW TABLES LIKE 'request_comments'");
                if ($table_check->num_rows == 0) {
                    // Create the table
                    $create_table_sql = "CREATE TABLE IF NOT EXISTS `request_comments` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `request_id` INT NOT NULL,
                        `user_id` INT NOT NULL,
                        `comment` TEXT NOT NULL,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (`request_id`) REFERENCES `requests`(`id`) ON DELETE CASCADE,
                        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
                    )";
                    $conn->query($create_table_sql);
                }
                
                // Add admin comment if provided
                if (!empty($admin_notes)) {
                    $comment_stmt = $conn->prepare("INSERT INTO request_comments (request_id, user_id, comment) VALUES (?, ?, ?)");
                    $comment_stmt->bind_param("iis", $request_id, $_SESSION['user_id'], $admin_notes);
                    $comment_stmt->execute();
                }
                
                // If this is an order request and it's approved, update the related orders
                if ($status === 'approved' && strpos(strtolower($request['type']), 'order') !== false) {
                    // Find related orders
                    $orders_update_query = "UPDATE orders 
                                          SET status = ? 
                                          WHERE user_id = ? 
                                          AND status = 'pending'
                                          AND created_at BETWEEN DATE_SUB(?, INTERVAL 1 DAY) AND DATE_ADD(?, INTERVAL 1 DAY)";
                    $orders_status = 'approved';
                    $orders_stmt = $conn->prepare($orders_update_query);
                    $orders_stmt->bind_param("siss", $orders_status, $request['user_id'], $request['created_at'], $request['created_at']);
                    $orders_stmt->execute();
                    
                    // Add a note about orders being updated
                    if ($orders_stmt->affected_rows > 0) {
                        $orders_note = "Related orders have been automatically approved.";
                        $comment_stmt = $conn->prepare("INSERT INTO request_comments (request_id, user_id, comment) VALUES (?, ?, ?)");
                        $comment_stmt->bind_param("iis", $request_id, $_SESSION['user_id'], $orders_note);
                        $comment_stmt->execute();
                    }
                }
                
                // Similarly, if rejected, update related orders to rejected
                if ($status === 'rejected' && strpos(strtolower($request['type']), 'order') !== false) {
                    $orders_update_query = "UPDATE orders 
                                          SET status = ? 
                                          WHERE user_id = ? 
                                          AND status = 'pending'
                                          AND created_at BETWEEN DATE_SUB(?, INTERVAL 1 DAY) AND DATE_ADD(?, INTERVAL 1 DAY)";
                    $orders_status = 'rejected';
                    $orders_stmt = $conn->prepare($orders_update_query);
                    $orders_stmt->bind_param("siss", $orders_status, $request['user_id'], $request['created_at'], $request['created_at']);
                    $orders_stmt->execute();
                    
                    if ($orders_stmt->affected_rows > 0) {
                        $orders_note = "Related orders have been automatically rejected.";
                        $comment_stmt = $conn->prepare("INSERT INTO request_comments (request_id, user_id, comment) VALUES (?, ?, ?)");
                        $comment_stmt->bind_param("iis", $request_id, $_SESSION['user_id'], $orders_note);
                        $comment_stmt->execute();
                    }
                }
                
                $action_text = ($status === 'approved') ? 'approved' : 'rejected';
                $success_message = "Request has been $action_text successfully.";
                
                // Refresh request data
                $stmt->execute();
                $result = $stmt->get_result();
                $request = $result->fetch_assoc();
            } else {
                $error_message = "Error updating request: " . $conn->error;
            }
        }
        // Handle adding comment
        elseif ($_POST['action'] === 'add_comment') {
            $comment = sanitize_input($_POST['comment']);
            
            if (empty($comment)) {
                $error_message = "Comment cannot be empty";
            } else {
                // Check if request_comments table exists, create if not
                $table_check = $conn->query("SHOW TABLES LIKE 'request_comments'");
                if ($table_check->num_rows == 0) {
                    // Create the table
                    $create_table_sql = "CREATE TABLE IF NOT EXISTS `request_comments` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `request_id` INT NOT NULL,
                        `user_id` INT NOT NULL,
                        `comment` TEXT NOT NULL,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (`request_id`) REFERENCES `requests`(`id`) ON DELETE CASCADE,
                        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
                    )";
                    $conn->query($create_table_sql);
                }
                
                // Insert comment
                $stmt = $conn->prepare("INSERT INTO request_comments (request_id, user_id, comment) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $request_id, $_SESSION['user_id'], $comment);
                
                if ($stmt->execute()) {
                    $success_message = "Your comment has been added successfully.";
                } else {
                    $error_message = "Error adding comment: " . $conn->error;
                }
            }
        }
        // Add a new action to update related orders manually
        elseif ($_POST['action'] === 'update_orders') {
            $orders_status = sanitize_input($_POST['orders_status']);
            
            if (empty($orders_status)) {
                $error_message = "Status cannot be empty";
            } else {
                // Update related orders
                $orders_update_query = "UPDATE orders 
                                      SET status = ? 
                                      WHERE user_id = ? 
                                      AND created_at BETWEEN DATE_SUB(?, INTERVAL 1 DAY) AND DATE_ADD(?, INTERVAL 1 DAY)";
                $orders_stmt = $conn->prepare($orders_update_query);
                $orders_stmt->bind_param("siss", $orders_status, $request['user_id'], $request['created_at'], $request['created_at']);
                
                if ($orders_stmt->execute() && $orders_stmt->affected_rows > 0) {
                    $success_message = "Related orders have been updated to " . ucfirst($orders_status) . ".";
                    
                    // Add a comment about the manual update
                    $orders_note = "Related orders have been manually updated to " . ucfirst($orders_status) . ".";
                    $comment_stmt = $conn->prepare("INSERT INTO request_comments (request_id, user_id, comment) VALUES (?, ?, ?)");
                    $comment_stmt->bind_param("iis", $request_id, $_SESSION['user_id'], $orders_note);
                    $comment_stmt->execute();
                } else {
                    $error_message = "No orders were updated or an error occurred.";
                }
            }
        }
    }
}

// Check if request_comments table exists
$has_comments_table = true;
$table_check = $conn->query("SHOW TABLES LIKE 'request_comments'");
if ($table_check->num_rows == 0) {
    $has_comments_table = false;
}

// Get request comments if table exists
$comments_result = false;
if ($has_comments_table) {
    $comments_query = "SELECT c.*, u.name as user_name, u.user_type 
                      FROM request_comments c 
                      JOIN users u ON c.user_id = u.id 
                      WHERE c.request_id = ? 
                      ORDER BY c.created_at ASC";
    $stmt = $conn->prepare($comments_query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $comments_result = $stmt->get_result();
}

// Get related orders if this is an order request
$related_orders = false;
if (strpos(strtolower($request['type']), 'order') !== false) {
    $orders_query = "SELECT o.*, i.name as item_name 
                    FROM orders o 
                    JOIN inventory i ON o.inventory_id = i.id 
                    WHERE o.user_id = ? AND o.created_at BETWEEN DATE_SUB(?, INTERVAL 1 DAY) AND DATE_ADD(?, INTERVAL 1 DAY)";
    $stmt = $conn->prepare($orders_query);
    $stmt->bind_param("iss", $request['user_id'], $request['created_at'], $request['created_at']);
    $stmt->execute();
    $related_orders = $stmt->get_result();
}
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">View Request</h1>
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
            <div class="max-w-4xl mx-auto">
                <div class="mb-4">
                    <a href="requests.php" class="inline-flex items-center text-sm text-emerald-600 hover:text-emerald-700">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Requests
                    </a>
                </div>
                
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
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Main Request Details -->
                    <div class="md:col-span-2">
                        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h2 class="text-xl font-bold text-gray-900"><?php echo $request['type']; ?></h2>
                                        <p class="text-sm text-gray-500">Request ID: <?php echo $request['request_id']; ?></p>
                                    </div>
                                    <div>
                                        <?php if ($request['status'] == 'pending'): ?>
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                Pending
                                            </span>
                                        <?php elseif ($request['status'] == 'approved'): ?>
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Approved
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Rejected
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="px-6 py-4">
                                <div class="mb-4">
                                    <h3 class="text-sm font-medium text-gray-500">Request Details</h3>
                                    <div class="mt-2 p-4 bg-gray-50 rounded-md">
                                        <p class="whitespace-pre-line"><?php echo $request['details']; ?></p>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h3 class="text-sm font-medium text-gray-500">Submission Date</h3>
                                    <p class="mt-1"><?php echo format_date($request['created_at'], 'F j, Y \a\t g:i a'); ?></p>
                                </div>
                                
                                <div class="mb-4">
                                    <h3 class="text-sm font-medium text-gray-500">Last Updated</h3>
                                    <p class="mt-1"><?php echo format_date($request['updated_at'], 'F j, Y \a\t g:i a'); ?></p>
                                </div>
                                
                                <?php if ($related_orders && $related_orders->num_rows > 0): ?>
                                <div class="mt-6">
                                    <div class="flex justify-between items-center mb-2">
                                        <h3 class="text-sm font-medium text-gray-500">Related Orders</h3>
                                        
                                        <!-- Add a form to update related orders status -->
                                        <form action="view_request.php?id=<?php echo $request_id; ?>" method="POST" class="flex items-center">
                                            <input type="hidden" name="action" value="update_orders">
                                            <select name="orders_status" class="text-xs rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50 mr-2">
                                                <option value="pending">Pending</option>
                                                <option value="approved">Approved</option>
                                                <option value="completed">Completed</option>
                                                <option value="rejected">Rejected</option>
                                            </select>
                                            <button type="submit" class="inline-flex items-center px-2 py-1 border border-transparent rounded-md shadow-sm text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                                                Update Orders
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <div class="overflow-x-auto bg-gray-50 rounded-md">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Order ID</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Item</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Quantity</th>
                                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Total</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                <?php 
                                                // Reset the result pointer to the beginning
                                                $related_orders->data_seek(0);
                                                while ($order = $related_orders->fetch_assoc()): 
                                                ?>
                                                <tr>
                                                    <td class="px-4 py-2 text-sm"><?php echo $order['order_id']; ?></td>
                                                    <td class="px-4 py-2 text-sm"><?php echo $order['item_name']; ?></td>
                                                    <td class="px-4 py-2 text-sm"><?php echo $order['quantity']; ?></td>
                                                    <td class="px-4 py-2 text-sm text-right">₱<?php echo number_format($order['total_price'], 2); ?></td>
                                                    <td class="px-4 py-2 text-sm">
                                                        <?php if ($order['status'] == 'pending'): ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                                        <?php elseif ($order['status'] == 'approved'): ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Approved</span>
                                                        <?php elseif ($order['status'] == 'completed'): ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Completed</span>
                                                        <?php else: ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Rejected</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Comments Section -->
                        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-medium text-gray-900">Comments and Updates</h3>
                            </div>
                            
                            <div class="px-6 py-4">
                                <?php if ($has_comments_table && $comments_result && $comments_result->num_rows > 0): ?>
                                    <div class="space-y-4 mb-6">
                                        <?php while ($comment = $comments_result->fetch_assoc()): ?>
                                            <div class="flex space-x-3">
                                                <div class="flex-shrink-0">
                                                    <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                        <span class="text-gray-600 font-medium"><?php echo substr($comment['user_name'], 0, 1); ?></span>
                                                    </div>
                                                </div>
                                                <div class="flex-1 bg-gray-50 rounded-lg px-4 py-2 sm:px-6 sm:py-4">
                                                    <div class="sm:flex sm:justify-between sm:items-baseline">
                                                        <div>
                                                            <p class="text-sm font-medium text-gray-900">
                                                                <?php echo $comment['user_name']; ?>
                                                                <span class="ml-1 text-xs text-gray-500">(<?php echo ucfirst($comment['user_type']); ?>)</span>
                                                            </p>
                                                        </div>
                                                        <p class="mt-1 text-xs text-gray-500 sm:mt-0">
                                                            <?php echo format_date($comment['created_at'], 'F j, Y \a\t g:i a'); ?>
                                                        </p>
                                                    </div>
                                                    <div class="mt-2 text-sm text-gray-700">
                                                        <p><?php echo $comment['comment']; ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4 text-gray-500">
                                        <p>No comments or updates yet.</p>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Add Comment Form -->
                                <div class="mt-4">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Add a Comment</h4>
                                    <form action="view_request.php?id=<?php echo $request_id; ?>" method="POST">
                                        <input type="hidden" name="action" value="add_comment">
                                        <div class="mb-3">
                                            <textarea name="comment" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50" placeholder="Add a comment or update..."></textarea>
                                        </div>
                                        <div class="flex justify-end">
                                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                                                <i class="fas fa-comment mr-2"></i> Add Comment
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sidebar with User Info and Actions -->
                    <div class="md:col-span-1">
                        <!-- User Information -->
                        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h3 class="text-lg font-medium text-gray-900">Requester Information</h3>
                            </div>
                            <div class="px-6 py-4">
                                <div class="flex items-center mb-4">
                                    <div class="h-12 w-12 rounded-full bg-gray-300 flex items-center justify-center mr-4">
                                        <span class="text-gray-600 font-medium text-lg"><?php echo substr($request['user_name'], 0, 1); ?></span>
                                    </div>
                                    <div>
                                        <h4 class="text-md font-medium"><?php echo $request['user_name']; ?></h4>
                                        <p class="text-sm text-gray-500"><?php echo ucfirst($request['user_type']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="mt-4 space-y-2">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Email</p>
                                        <p class="text-sm"><?php echo $request['user_email']; ?></p>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Request Count</p>
                                        <?php
                                        $user_requests_query = "SELECT COUNT(*) as count FROM requests WHERE user_id = ?";
                                        $stmt = $conn->prepare($user_requests_query);
                                        $stmt->bind_param("i", $request['user_id']);
                                        $stmt->execute();
                                        $user_requests_result = $stmt->get_result();
                                        $user_requests_count = $user_requests_result->fetch_assoc()['count'];
                                        ?>
                                        <p class="text-sm"><?php echo $user_requests_count; ?> total requests</p>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <a href="users.php?id=<?php echo $request['user_id']; ?>" class="text-emerald-600 hover:text-emerald-700 text-sm font-medium">
                                        <i class="fas fa-user mr-1"></i> View User Details
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <?php if ($request['status'] == 'pending'): ?>
                        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h3 class="text-lg font-medium text-gray-900">Actions</h3>
                            </div>
                            <div class="px-6 py-4">
                                <form action="view_request.php?id=<?php echo $request_id; ?>" method="POST">
                                    <div class="mb-4">
                                        <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-1">Admin Notes (Optional)</label>
                                        <textarea id="admin_notes" name="admin_notes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50" placeholder="Add notes about this decision..."></textarea>
                                    </div>
                                    
                                    <div class="space-y-3">
                                        <button type="submit" name="action" value="approve" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                            <i class="fas fa-check mr-2"></i> Approve Request
                                        </button>
                                        
                                        <button type="submit" name="action" value="reject" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                            <i class="fas fa-times mr-2"></i> Reject Request
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Quick Links -->
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h3 class="text-lg font-medium text-gray-900">Quick Links</h3>
                            </div>
                            <div class="px-6 py-4">
                                <ul class="space-y-2">
                                    <li>
                                        <a href="requests.php" class="text-emerald-600 hover:text-emerald-700 text-sm font-medium">
                                            <i class="fas fa-list mr-2"></i> All Requests
                                        </a>
                                    </li>
                                    <li>
                                        <a href="requests.php?status=pending" class="text-emerald-600 hover:text-emerald-700 text-sm font-medium">
                                            <i class="fas fa-clock mr-2"></i> Pending Requests
                                        </a>
                                    </li>
                                    <li>
                                        <a href="users.php" class="text-emerald-600 hover:text-emerald-700 text-sm font-medium">
                                            <i class="fas fa-users mr-2"></i> Manage Users
                                        </a>
                                    </li>
                                    <li>
                                        <a href="reports.php" class="text-emerald-600 hover:text-emerald-700 text-sm font-medium">
                                            <i class="fas fa-chart-bar mr-2"></i> Reports
                                        </a>
                                    </li>
                                </ul>
                            </div>
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