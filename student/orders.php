<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is student
require_student();

$page_title = "My Orders - CHMSU BAO";
$base_url = "..";

// Get status filter
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : 'all';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order']) && isset($_POST['order_id'])) {
    $order_id = sanitize_input($_POST['order_id']);
    
    // Verify the order belongs to the current user
    $verify_stmt = $conn->prepare("SELECT status FROM orders WHERE order_id = ? AND user_id = ?");
    $verify_stmt->bind_param("si", $order_id, $_SESSION['user_id']);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        $order_data = $verify_result->fetch_assoc();
        
        // Only allow cancellation of pending orders
        if ($order_data['status'] == 'pending') {
            // Cancel all orders in the batch if batch_id exists, otherwise cancel just this order
            $batch_stmt = $conn->prepare("SELECT batch_id FROM orders WHERE order_id = ?");
            $batch_stmt->bind_param("s", $order_id);
            $batch_stmt->execute();
            $batch_result = $batch_stmt->get_result();
            
            if ($batch_result->num_rows > 0) {
                $batch_data = $batch_result->fetch_assoc();
                
                if (!empty($batch_data['batch_id'])) {
                    // Cancel all orders in the batch
                    $cancel_stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE batch_id = ? AND user_id = ?");
                    $cancel_stmt->bind_param("si", $batch_data['batch_id'], $_SESSION['user_id']);
                } else {
                    // Cancel just this order
                    $cancel_stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ? AND user_id = ?");
                    $cancel_stmt->bind_param("si", $order_id, $_SESSION['user_id']);
                }
                
                if ($cancel_stmt->execute()) {
                    $_SESSION['success'] = "Order cancelled successfully!";
                } else {
                    $_SESSION['error'] = "Error cancelling order: " . $conn->error;
                }
            }
        } else {
            $_SESSION['error'] = "Only pending orders can be cancelled.";
        }
    } else {
        $_SESSION['error'] = "Order not found or you don't have permission to cancel it.";
    }
    
    // Redirect to avoid form resubmission
    header("Location: orders.php");
    exit();
}

// Get user's orders with status filter
$query = "SELECT o.*, i.name as item_name, i.description as item_description 
          FROM orders o 
          JOIN inventory i ON o.inventory_id = i.id 
          WHERE o.user_id = ?";
          
if ($status_filter == 'pending') {
    $query .= " AND o.status = 'pending'";
} elseif ($status_filter == 'completed') {
    $query .= " AND o.status = 'completed'";
} elseif ($status_filter == 'cancelled') {
    $query .= " AND o.status = 'cancelled'";
}
// 'all' shows all statuses

$query .= " ORDER BY o.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

// Group orders by batch_id (per customer, per batch = 1 row)
$all_batches = [];
$user_name = $_SESSION['user_name'] ?? '';

while ($order = $result->fetch_assoc()) {
    // Use batch_id as key, or order_id if no batch_id
    $key = !empty($order['batch_id']) ? $order['batch_id'] : $order['order_id'];
    
    if (!isset($all_batches[$key])) {
        $all_batches[$key] = [
            'batch_id' => $order['batch_id'],
            'order_id' => $order['order_id'],
            'display_id' => !empty($order['batch_id']) ? $order['batch_id'] : $order['order_id'],
            'orders' => [],
            'total_amount' => 0,
            'statuses' => []
        ];
    }
    
    $all_batches[$key]['orders'][] = $order;
    $all_batches[$key]['total_amount'] += $order['total_price'];
    
    if (!in_array($order['status'], $all_batches[$key]['statuses'])) {
        $all_batches[$key]['statuses'][] = $order['status'];
    }
}

// Determine display status for each batch
foreach ($all_batches as $key => &$batch) {
    $batch['display_status'] = count($batch['statuses']) == 1 ? $batch['statuses'][0] : 'mixed';
    $batch['item_count'] = count($batch['orders']);
}
unset($batch);

// Calculate pagination for batches
$total_batches = count($all_batches);
$total_pages = ceil($total_batches / $per_page);

// Get batches for current page
$batches = array_slice($all_batches, $offset, $per_page, true);

// Get user profile picture
$user_stmt = $conn->prepare("SELECT profile_pic FROM user_accounts WHERE id = ?");
$user_stmt->bind_param("i", $_SESSION['user_id']);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$profile_pic = $user_data['profile_pic'] ?? '';
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/student_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">My Orders</h1>
                <div class="flex items-center gap-3">
                    <a href="profile.php" class="flex items-center">
                        <?php if (!empty($profile_pic) && file_exists('../' . $profile_pic)): ?>
                            <img src="../<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover border-2 border-gray-300 hover:border-blue-500 transition-colors cursor-pointer">
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center border-2 border-gray-300 hover:border-blue-500 transition-colors cursor-pointer">
                                <i class="fas fa-user text-gray-600"></i>
                            </div>
                        <?php endif; ?>
                    </a>
                    <span class="text-gray-700 hidden sm:inline"><?php echo $_SESSION['user_name']; ?></span>
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
                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-gray-900">Order History</h2>
                        <p class="text-gray-500">View and manage your orders</p>
                    </div>
                    <div class="flex gap-3">
                        <a href="inventory.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                            <i class="fas fa-plus mr-2"></i> New Order
                        </a>
                    </div>
                </div>
                
                <!-- Filter Buttons -->
                <div class="mb-4 flex gap-2">
                    <a href="orders.php?status=all" class="px-4 py-2 rounded-md text-sm font-medium <?php echo $status_filter == 'all' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                        All Orders
                    </a>
                    <a href="orders.php?status=pending" class="px-4 py-2 rounded-md text-sm font-medium <?php echo $status_filter == 'pending' ? 'bg-yellow-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                        Pending
                    </a>
                    <a href="orders.php?status=completed" class="px-4 py-2 rounded-md text-sm font-medium <?php echo $status_filter == 'completed' ? 'bg-green-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'; ?>">
                        Completed
                    </a>
                </div>
                
                <!-- Orders Table -->
                <div class="bg-white shadow overflow-hidden sm:rounded-md">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch/Order #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">-</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($batches)): ?>
                                    <?php foreach ($batches as $batch): ?>
                                        <tr class="bg-blue-50 hover:bg-blue-100">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                                <?php echo htmlspecialchars($batch['display_id']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($user_name); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $batch['item_count']; ?> items
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                -
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                                ₱<?php echo number_format($batch['total_amount'], 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($batch['display_status'] == 'pending'): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        Pending
                                                    </span>
                                                <?php elseif ($batch['display_status'] == 'approved'): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        Approved
                                                    </span>
                                                <?php elseif ($batch['display_status'] == 'completed'): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        Completed
                                                    </span>
                                                <?php elseif ($batch['display_status'] == 'mixed'): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                        Mixed
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        Rejected
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex items-center gap-2">
                                                    <a href="receipt.php?order_id=<?php echo $batch['orders'][0]['order_id']; ?>" class="inline-flex items-center px-3 py-1.5 border border-blue-300 shadow-sm text-sm font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                                        <i class="fas fa-receipt mr-1.5"></i> View Receipt
                                                    </a>
                                                    <?php if ($batch['display_status'] == 'pending'): ?>
                                                        <button type="button" onclick="openCancelModal('<?php echo htmlspecialchars($batch['display_id'], ENT_QUOTES); ?>', '<?php echo $batch['orders'][0]['order_id']; ?>')" class="inline-flex items-center px-3 py-1.5 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                                                        <i class="fas fa-times-circle mr-1.5"></i> Cancel
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-8 text-center">
                                            <i class="fas fa-shopping-cart text-gray-400 text-4xl mb-2"></i>
                                            <p class="text-gray-500">You haven't placed any orders yet.</p>
                                            <div class="mt-4">
                                                <a href="inventory.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                                                    Browse Available Items
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="mt-6 flex items-center justify-between bg-white px-4 py-3 rounded-lg shadow">
                        <div class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?php echo $total_batches > 0 ? $offset + 1 : 0; ?></span> to 
                            <span class="font-medium"><?php echo min($offset + $per_page, $total_batches); ?></span> of 
                            <span class="font-medium"><?php echo $total_batches; ?></span> result<?php echo $total_batches != 1 ? 's' : ''; ?>
                        </div>
                        <div class="flex gap-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo $status_filter != 'all' ? '&status=' . urlencode($status_filter) : ''; ?>" 
                                   class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    <i class="fas fa-chevron-left mr-1"></i>Previous
                                </a>
                            <?php else: ?>
                                <span class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-400 bg-gray-100 cursor-not-allowed">
                                    <i class="fas fa-chevron-left mr-1"></i>Previous
                                </span>
                            <?php endif; ?>
                            
                            <?php
                            // Show page numbers
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1): ?>
                                <a href="?page=1<?php echo $status_filter != 'all' ? '&status=' . urlencode($status_filter) : ''; ?>" 
                                   class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">1</a>
                                <?php if ($start_page > 2): ?>
                                    <span class="px-3 py-2 text-gray-500">...</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="px-3 py-2 border border-blue-500 rounded-md text-sm font-medium text-white bg-blue-600"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?><?php echo $status_filter != 'all' ? '&status=' . urlencode($status_filter) : ''; ?>" 
                                       class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <span class="px-3 py-2 text-gray-500">...</span>
                                <?php endif; ?>
                                <a href="?page=<?php echo $total_pages; ?><?php echo $status_filter != 'all' ? '&status=' . urlencode($status_filter) : ''; ?>" 
                                   class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"><?php echo $total_pages; ?></a>
                            <?php endif; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $status_filter != 'all' ? '&status=' . urlencode($status_filter) : ''; ?>" 
                                   class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Next<i class="fas fa-chevron-right ml-1"></i>
                                </a>
                            <?php else: ?>
                                <span class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-400 bg-gray-100 cursor-not-allowed">
                                    Next<i class="fas fa-chevron-right ml-1"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Cancel Confirmation Modal -->
<div id="cancelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-5">Cancel Order</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Are you sure you want to cancel order <span id="cancelOrderId" class="font-semibold text-gray-900"></span>? This action cannot be undone.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <form id="cancelOrderForm" method="POST" action="orders.php">
                    <input type="hidden" name="cancel_order" value="1">
                    <input type="hidden" name="order_id" id="cancelOrderIdInput">
                    <button type="button" onclick="closeCancelModal()" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        No
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-24 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300">
                        Yes, Cancel
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Mobile menu toggle
    document.getElementById('menu-button').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
    });
    
    // Cancel Modal Functions
    function openCancelModal(batchId, orderId) {
        document.getElementById('cancelOrderId').textContent = batchId;
        document.getElementById('cancelOrderIdInput').value = orderId;
        document.getElementById('cancelModal').classList.remove('hidden');
    }
    
    function closeCancelModal() {
        document.getElementById('cancelModal').classList.add('hidden');
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('cancelModal');
        if (event.target == modal) {
            closeCancelModal();
        }
    }
</script>

    <script src="<?php echo $base_url ?? ''; ?>/assets/js/main.js"></script>
</body>
</html>
