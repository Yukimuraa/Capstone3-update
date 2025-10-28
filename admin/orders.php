<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is admin
require_admin();

// Get user data based on active user type (admin or secretary)
$active_type = $_SESSION['active_user_type'];
$user_id = $_SESSION['user_sessions'][$active_type]['user_id'];
$user_name = $_SESSION['user_sessions'][$active_type]['user_name'];

$page_title = "Order Management - CHMSU BAO";
$base_url = "..";

// Handle status updates and mark complete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_complete_batch']) && isset($_POST['batch_id'])) {
        // Mark all orders in batch as completed
        $batch_id = $_POST['batch_id'];
        
        $update_query = "UPDATE orders SET status = 'completed' WHERE batch_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("s", $batch_id);
        
        if ($stmt->execute()) {
            // Redirect to batch receipt for printing
            header("Location: print_batch_receipt.php?batch_id=" . urlencode($batch_id));
            exit();
        } else {
            $error_message = "Error completing order.";
        }
    } elseif (isset($_POST['mark_complete']) && isset($_POST['order_id'])) {
        // Mark single order as completed
        $order_id = (int)$_POST['order_id'];
        
        $update_query = "UPDATE orders SET status = 'completed' WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $order_id);
        
        if ($stmt->execute()) {
            // Get order details for receipt
            $get_order = $conn->prepare("SELECT order_id FROM orders WHERE id = ?");
            $get_order->bind_param("i", $order_id);
            $get_order->execute();
            $order_data = $get_order->get_result()->fetch_assoc();
            
            // Redirect to receipt
            header("Location: print_order_receipt.php?order_id=" . urlencode($order_data['order_id']));
            exit();
        } else {
            $error_message = "Error completing order.";
        }
    } elseif (isset($_POST['order_id']) && isset($_POST['status'])) {
        $order_id = (int)$_POST['order_id'];
        $status = $_POST['status'];
        
        $update_query = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $status, $order_id);
        
        if ($stmt->execute()) {
            $success_message = "Order status updated successfully!";
        } else {
            $error_message = "Error updating order status.";
        }
    }
}

// Get all orders with user details and item information, grouped by batch_id
$query = "SELECT o.*, u.name as user_name, u.email as user_email, i.name as item_name, i.price as item_price 
          FROM orders o 
          JOIN user_accounts u ON o.user_id = u.id 
          JOIN inventory i ON o.inventory_id = i.id 
          ORDER BY o.created_at DESC, o.batch_id, o.id";
$orders = $conn->query($query);
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">Order Management</h1>
                <div class="flex items-center">
                    <span class="text-gray-700 mr-2"><?php echo $user_name; ?></span>
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
                <?php if (isset($success_message)): ?>
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Orders Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch/Order #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($orders->num_rows > 0): ?>
                                    <?php 
                                    $current_batch = null;
                                    $batch_items = [];
                                    $batch_total = 0;
                                    
                                    while ($order = $orders->fetch_assoc()): 
                                        // Group by batch_id
                                        if ($order['batch_id'] && $order['batch_id'] != $current_batch) {
                                            // Display previous batch if exists
                                            if ($current_batch && count($batch_items) > 0) {
                                                $first_item = $batch_items[0];
                                                ?>
                                                <tr class="bg-blue-50">
                                                    <td class="px-6 py-4 text-sm font-bold text-gray-900">
                                                        <?php echo $current_batch; ?>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-900">
                                                        <?php echo $first_item['user_name']; ?>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-500">
                                                        <?php echo count($batch_items); ?> items
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-500">
                                                        -
                                                    </td>
                                                    <td class="px-6 py-4 text-sm font-bold text-gray-900">
                                                        ₱<?php echo number_format($batch_total, 2); ?>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <?php
                                                        $status_classes = [
                                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                                            'completed' => 'bg-blue-100 text-blue-800',
                                                            'cancelled' => 'bg-gray-100 text-gray-800'
                                                        ];
                                                        $status_class = $status_classes[$first_item['status']] ?? 'bg-gray-100 text-gray-800';
                                                        ?>
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                            <?php echo ucfirst($first_item['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm">
                                                        <?php if ($first_item['status'] === 'pending'): ?>
                                                            <form action="orders.php" method="POST" style="display: inline;">
                                                                <input type="hidden" name="batch_id" value="<?php echo $current_batch; ?>">
                                                                <input type="hidden" name="mark_complete_batch" value="1">
                                                                <button type="submit" class="inline-flex items-center px-3 py-1 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                                                    <i class="fas fa-check mr-1"></i> Mark Complete & Print
                                                                </button>
                                                            </form>
                                                        <?php elseif ($first_item['status'] === 'completed'): ?>
                                                            <a href="print_batch_receipt.php?batch_id=<?php echo $current_batch; ?>" 
                                                               class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                                                               target="_blank">
                                                                <i class="fas fa-print mr-1"></i> Print Receipt
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                            
                                            // Start new batch
                                            $current_batch = $order['batch_id'];
                                            $batch_items = [$order];
                                            $batch_total = $order['total_price'];
                                        } elseif ($order['batch_id'] == $current_batch) {
                                            // Add to current batch
                                            $batch_items[] = $order;
                                            $batch_total += $order['total_price'];
                                        } else {
                                            // Single order (no batch)
                                            ?>
                                            <tr>
                                                <td class="px-6 py-4 text-sm text-gray-900">
                                                    <?php echo $order['order_id']; ?>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900">
                                                    <?php echo $order['user_name']; ?>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-500">
                                                    <?php echo $order['item_name']; ?>
                                                    <?php if (!empty($order['size'])): ?>
                                                        <span class="text-gray-400">(<?php echo $order['size']; ?>)</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-500"><?php echo $order['quantity']; ?></td>
                                                <td class="px-6 py-4 text-sm text-gray-500">
                                                    ₱<?php echo number_format($order['total_price'], 2); ?>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <?php
                                                    $status_classes = [
                                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                                        'completed' => 'bg-blue-100 text-blue-800',
                                                        'cancelled' => 'bg-gray-100 text-gray-800'
                                                    ];
                                                    $status_class = $status_classes[$order['status']] ?? 'bg-gray-100 text-gray-800';
                                                    ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-sm">
                                                    <?php if ($order['status'] === 'pending'): ?>
                                                        <form action="orders.php" method="POST" style="display: inline;">
                                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                            <input type="hidden" name="mark_complete" value="1">
                                                            <button type="submit" class="inline-flex items-center px-3 py-1 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                                                <i class="fas fa-check mr-1"></i> Mark Complete & Print
                                                            </button>
                                                        </form>
                                                    <?php elseif ($order['status'] === 'completed'): ?>
                                                        <a href="print_order_receipt.php?order_id=<?php echo $order['order_id']; ?>" 
                                                           class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                                                           target="_blank">
                                                            <i class="fas fa-print mr-1"></i> Print Receipt
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    endwhile;
                                    
                                    // Display last batch if exists
                                    if ($current_batch && count($batch_items) > 0) {
                                        $first_item = $batch_items[0];
                                        ?>
                                        <tr class="bg-blue-50">
                                            <td class="px-6 py-4 text-sm font-bold text-gray-900">
                                                <?php echo $current_batch; ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                <?php echo $first_item['user_name']; ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                <?php echo count($batch_items); ?> items
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                -
                                            </td>
                                            <td class="px-6 py-4 text-sm font-bold text-gray-900">
                                                ₱<?php echo number_format($batch_total, 2); ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php
                                                $status_classes = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                                    'completed' => 'bg-blue-100 text-blue-800',
                                                    'cancelled' => 'bg-gray-100 text-gray-800'
                                                ];
                                                $status_class = $status_classes[$first_item['status']] ?? 'bg-gray-100 text-gray-800';
                                                ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                    <?php echo ucfirst($first_item['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm">
                                                <?php if ($first_item['status'] === 'pending'): ?>
                                                    <form action="orders.php" method="POST" style="display: inline;">
                                                        <input type="hidden" name="batch_id" value="<?php echo $current_batch; ?>">
                                                        <input type="hidden" name="mark_complete_batch" value="1">
                                                        <button type="submit" class="inline-flex items-center px-3 py-1 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                                            <i class="fas fa-check mr-1"></i> Mark Complete & Print
                                                        </button>
                                                    </form>
                                                <?php elseif ($first_item['status'] === 'completed'): ?>
                                                    <a href="print_batch_receipt.php?batch_id=<?php echo $current_batch; ?>" 
                                                       class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                                                       target="_blank">
                                                        <i class="fas fa-print mr-1"></i> Print Receipt
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No orders found
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Status Update Modal -->
<div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900">Update Order Status</h3>
            <form action="orders.php" method="POST" class="mt-4">
                <input type="hidden" name="order_id" id="modal_order_id">
                <div class="mb-4">
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeStatusModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Mobile menu toggle
    document.getElementById('menu-button').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
    });
    
    // Status modal functions
    function openStatusModal(orderId, currentStatus) {
        document.getElementById('modal_order_id').value = orderId;
        document.getElementById('status').value = currentStatus;
        document.getElementById('statusModal').classList.remove('hidden');
    }
    
    function closeStatusModal() {
        document.getElementById('statusModal').classList.add('hidden');
    }
</script>

<?php include '../includes/footer.php'; ?>

