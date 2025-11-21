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
        // Mark all orders in batch as completed and deduct inventory
        $batch_id = $_POST['batch_id'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Get all orders in batch
            $get_orders = $conn->prepare("SELECT o.*, i.quantity as current_quantity, i.size_quantities FROM orders o JOIN inventory i ON o.inventory_id = i.id WHERE o.batch_id = ? AND o.status = 'pending'");
            $get_orders->bind_param("s", $batch_id);
            $get_orders->execute();
            $orders_result = $get_orders->get_result();
            
            // Deduct inventory for each order
            while ($order = $orders_result->fetch_assoc()) {
                $new_quantity = $order['current_quantity'] - $order['quantity'];
                $in_stock = $new_quantity > 0 ? 1 : 0;
                
                // Handle size_quantities if size is provided
                $size_quantities_json = $order['size_quantities'] ?? null;
                if (!empty($order['size']) && !empty($size_quantities_json)) {
                    $size_quantities = json_decode($size_quantities_json, true);
                    if (is_array($size_quantities) && isset($size_quantities[$order['size']])) {
                        // Decrease the specific size quantity
                        $size_quantities[$order['size']] = max(0, $size_quantities[$order['size']] - $order['quantity']);
                        // Re-encode the updated size quantities
                        $size_quantities_json = json_encode($size_quantities);
                    }
                }
                
                // Update inventory
                $update_inventory = $conn->prepare("UPDATE inventory SET quantity = ?, in_stock = ?, size_quantities = ? WHERE id = ?");
                $update_inventory->bind_param("iiss", $new_quantity, $in_stock, $size_quantities_json, $order['inventory_id']);
                $update_inventory->execute();
            }
            
            // Mark all orders as completed
        $update_query = "UPDATE orders SET status = 'completed' WHERE batch_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("s", $batch_id);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
        
            // Redirect to batch receipt for printing
            header("Location: print_batch_receipt.php?batch_id=" . urlencode($batch_id));
            exit();
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error_message = "Error completing order: " . $e->getMessage();
        }
    } elseif (isset($_POST['mark_complete']) && isset($_POST['order_id'])) {
        // Mark single order as completed and deduct inventory
        $order_id = (int)$_POST['order_id'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Get order details with current inventory
            $get_order = $conn->prepare("SELECT o.*, i.quantity as current_quantity, i.size_quantities FROM orders o JOIN inventory i ON o.inventory_id = i.id WHERE o.id = ? AND o.status = 'pending'");
            $get_order->bind_param("i", $order_id);
            $get_order->execute();
            $order_result = $get_order->get_result();
            
            if ($order_result->num_rows === 0) {
                throw new Exception("Order not found or already processed");
            }
            
            $order = $order_result->fetch_assoc();
            
            // Deduct inventory
            $new_quantity = $order['current_quantity'] - $order['quantity'];
            $in_stock = $new_quantity > 0 ? 1 : 0;
            
            // Handle size_quantities if size is provided
            $size_quantities_json = $order['size_quantities'] ?? null;
            if (!empty($order['size']) && !empty($size_quantities_json)) {
                $size_quantities = json_decode($size_quantities_json, true);
                if (is_array($size_quantities) && isset($size_quantities[$order['size']])) {
                    // Decrease the specific size quantity
                    $size_quantities[$order['size']] = max(0, $size_quantities[$order['size']] - $order['quantity']);
                    // Re-encode the updated size quantities
                    $size_quantities_json = json_encode($size_quantities);
                }
            }
            
            // Update inventory
            $update_inventory = $conn->prepare("UPDATE inventory SET quantity = ?, in_stock = ?, size_quantities = ? WHERE id = ?");
            $update_inventory->bind_param("iiss", $new_quantity, $in_stock, $size_quantities_json, $order['inventory_id']);
            $update_inventory->execute();
            
            // Mark order as completed
            $update_query = "UPDATE orders SET status = 'completed' WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            // Get order_id for receipt
            $order_data = $conn->prepare("SELECT order_id FROM orders WHERE id = ?");
            $order_data->bind_param("i", $order_id);
            $order_data->execute();
            $order_info = $order_data->get_result()->fetch_assoc();
            
            // Redirect to receipt
            header("Location: print_order_receipt.php?order_id=" . urlencode($order_info['order_id']));
            exit();
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error_message = "Error completing order: " . $e->getMessage();
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

// Get search parameter
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Get all orders with user details and item information, grouped by batch_id
$query = "SELECT o.*, u.name as user_name, u.email as user_email, i.name as item_name, i.price as item_price 
          FROM orders o 
          JOIN user_accounts u ON o.user_id = u.id 
          JOIN inventory i ON o.inventory_id = i.id";

// Add search filter if search term provided
if (!empty($search)) {
    $search_term = "%$search%";
    $query .= " WHERE (o.order_id LIKE ? OR o.batch_id LIKE ? OR u.name LIKE ? OR i.name LIKE ?)";
}

$query .= " ORDER BY o.created_at DESC, o.batch_id, o.id";

if (!empty($search)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $search_term, $search_term, $search_term, $search_term);
    $stmt->execute();
    $orders = $stmt->get_result();
} else {
    $orders = $conn->query($query);
}
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
                
                <!-- Search Bar -->
                <div class="mb-6 bg-white rounded-lg shadow p-4">
                    <form method="GET" action="orders.php" class="flex gap-4 items-end">
                        <div class="flex-1">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-search mr-1"></i>Search Orders
                            </label>
                            <input 
                                type="text" 
                                name="search" 
                                id="search" 
                                value="<?php echo htmlspecialchars($search); ?>" 
                                placeholder="Search by Order ID, Batch ID, Customer Name, or Item Name..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                            >
                        </div>
                        <div class="flex gap-2">
                            <button 
                                type="submit" 
                                class="px-6 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                <i class="fas fa-search mr-1"></i>Search
                            </button>
                            <?php if (!empty($search)): ?>
                                <a 
                                    href="orders.php" 
                                    class="px-6 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                    <i class="fas fa-times mr-1"></i>Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <?php if (!empty($search)): ?>
                        <div class="mt-3 text-sm text-gray-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            Showing results for: <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
                            (<?php echo $orders->num_rows; ?> order<?php echo $orders->num_rows != 1 ? 's' : ''; ?> found)
                        </div>
                    <?php endif; ?>
                </div>
                
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
                                                            <div class="flex gap-2">
                                                                <button onclick="viewBatchOrder('<?php echo $current_batch; ?>')" 
                                                                        class="inline-flex items-center px-3 py-1 border border-blue-600 rounded-md text-sm font-medium text-blue-600 bg-white hover:bg-blue-50">
                                                                    <i class="fas fa-eye mr-1"></i> View
                                                                </button>
                                                                <form action="orders.php" method="POST" style="display: inline;">
                                                                    <input type="hidden" name="batch_id" value="<?php echo $current_batch; ?>">
                                                                    <input type="hidden" name="mark_complete_batch" value="1">
                                                                    <button type="submit" class="inline-flex items-center px-3 py-1 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                                                        <i class="fas fa-check mr-1"></i> Mark Complete & Print
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        <?php elseif ($first_item['status'] === 'completed'): ?>
                                                            <div class="flex gap-2">
                                                                <button onclick="viewBatchOrder('<?php echo $current_batch; ?>')" 
                                                                        class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                                                    <i class="fas fa-eye mr-1"></i> View
                                                                </button>
                                                                <a href="print_batch_receipt.php?batch_id=<?php echo $current_batch; ?>" 
                                                                   class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                                                                   target="_blank">
                                                                    <i class="fas fa-print mr-1"></i> Print Receipt
                                                                </a>
                                                            </div>
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
                                                        <div class="flex gap-2">
                                                            <button onclick="viewSingleOrder(<?php echo $order['id']; ?>)" 
                                                                    class="inline-flex items-center px-3 py-1 border border-blue-600 rounded-md text-sm font-medium text-blue-600 bg-white hover:bg-blue-50">
                                                                <i class="fas fa-eye mr-1"></i> View
                                                            </button>
                                                            <form action="orders.php" method="POST" style="display: inline;">
                                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                                <input type="hidden" name="mark_complete" value="1">
                                                                <button type="submit" class="inline-flex items-center px-3 py-1 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                                                    <i class="fas fa-check mr-1"></i> Mark Complete & Print
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php elseif ($order['status'] === 'completed'): ?>
                                                        <div class="flex gap-2">
                                                            <button onclick="viewSingleOrder(<?php echo $order['id']; ?>)" 
                                                                    class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                                                <i class="fas fa-eye mr-1"></i> View
                                                            </button>
                                                            <a href="print_order_receipt.php?order_id=<?php echo $order['order_id']; ?>" 
                                                               class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                                                               target="_blank">
                                                                <i class="fas fa-print mr-1"></i> Print Receipt
                                                            </a>
                                                        </div>
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
                                                    <div class="flex gap-2">
                                                        <button onclick="viewBatchOrder('<?php echo $current_batch; ?>')" 
                                                                class="inline-flex items-center px-3 py-1 border border-blue-600 rounded-md text-sm font-medium text-blue-600 bg-white hover:bg-blue-50">
                                                            <i class="fas fa-eye mr-1"></i> View
                                                        </button>
                                                        <form action="orders.php" method="POST" style="display: inline;">
                                                            <input type="hidden" name="batch_id" value="<?php echo $current_batch; ?>">
                                                            <input type="hidden" name="mark_complete_batch" value="1">
                                                            <button type="submit" class="inline-flex items-center px-3 py-1 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                                                <i class="fas fa-check mr-1"></i> Mark Complete & Print
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php elseif ($first_item['status'] === 'completed'): ?>
                                                    <div class="flex gap-2">
                                                        <button onclick="viewBatchOrder('<?php echo $current_batch; ?>')" 
                                                                class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                                            <i class="fas fa-eye mr-1"></i> View
                                                        </button>
                                                        <a href="print_batch_receipt.php?batch_id=<?php echo $current_batch; ?>" 
                                                           class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                                                           target="_blank">
                                                            <i class="fas fa-print mr-1"></i> Print Receipt
                                                        </a>
                                                    </div>
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

<!-- Order Details Modal -->
<div id="orderModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between border-b pb-3 mb-4">
            <h3 class="text-lg font-medium text-gray-900">Order Details</h3>
            <button onclick="closeOrderModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div id="orderDetailsContent">
            <!-- Content will be loaded dynamically -->
        </div>
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
    
    // Store order data
    let ordersData = <?php 
        // Reset result pointer
        $orders->data_seek(0);
        $all_orders = [];
        while ($row = $orders->fetch_assoc()) {
            $all_orders[] = $row;
        }
        echo json_encode($all_orders); 
    ?>;

    function viewBatchOrder(batchId) {
        const batchOrders = ordersData.filter(o => o.batch_id === batchId);
        if (batchOrders.length === 0) return;
        
        const firstOrder = batchOrders[0];
        let totalAmount = 0;
        let itemsHtml = '';
        
        batchOrders.forEach(order => {
            totalAmount += parseFloat(order.total_price);
            itemsHtml += `
                <tr class="border-b">
                    <td class="py-2">${order.item_name}${order.size ? ' (' + order.size + ')' : ''}</td>
                    <td class="py-2 text-center">${order.quantity}</td>
                    <td class="py-2 text-right">₱${parseFloat(order.total_price).toFixed(2)}</td>
                </tr>
            `;
        });
        
        const content = `
            <div class="space-y-4">
                <div class="bg-blue-50 p-4 rounded">
                    <h4 class="font-medium text-blue-900">Batch Order: ${batchId}</h4>
                    <p class="text-sm text-blue-700">${batchOrders.length} items</p>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Customer:</p>
                        <p class="font-medium">${firstOrder.user_name}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Email:</p>
                        <p class="font-medium">${firstOrder.user_email}</p>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-medium mb-2">Order Items:</h4>
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-2 text-left">Item</th>
                                <th class="py-2 text-center">Qty</th>
                                <th class="py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>${itemsHtml}</tbody>
                        <tfoot class="bg-gray-50 font-bold">
                            <tr>
                                <td class="py-2" colspan="2">TOTAL:</td>
                                <td class="py-2 text-right">₱${totalAmount.toFixed(2)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button onclick="closeOrderModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </button>
                    <form action="orders.php" method="POST" style="display: inline;">
                        <input type="hidden" name="batch_id" value="${batchId}">
                        <input type="hidden" name="mark_complete_batch" value="1">
                        <button type="submit" class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                            <i class="fas fa-check mr-1"></i> Mark Complete & Print
                        </button>
                    </form>
                </div>
            </div>
        `;
        
        document.getElementById('orderDetailsContent').innerHTML = content;
        document.getElementById('orderModal').classList.remove('hidden');
    }

    function viewSingleOrder(orderId) {
        const order = ordersData.find(o => o.id == orderId);
        if (!order) return;
        
        const content = `
            <div class="space-y-4">
                <div class="bg-blue-50 p-4 rounded">
                    <h4 class="font-medium text-blue-900">Order #${order.order_id}</h4>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Customer:</p>
                        <p class="font-medium">${order.user_name}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Email:</p>
                        <p class="font-medium">${order.user_email}</p>
                    </div>
                </div>
                
                <div class="border rounded p-4">
                    <h4 class="font-medium mb-2">Order Details:</h4>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Item:</span>
                            <span class="font-medium">${order.item_name}</span>
                        </div>
                        ${order.size ? `<div class="flex justify-between">
                            <span class="text-gray-600">Size:</span>
                            <span class="font-medium">${order.size}</span>
                        </div>` : ''}
                        <div class="flex justify-between">
                            <span class="text-gray-600">Quantity:</span>
                            <span class="font-medium">${order.quantity}</span>
                        </div>
                        <div class="flex justify-between border-t pt-2">
                            <span class="font-bold">Total:</span>
                            <span class="font-bold text-lg">₱${parseFloat(order.total_price).toFixed(2)}</span>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button onclick="closeOrderModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </button>
                    <form action="orders.php" method="POST" style="display: inline;">
                        <input type="hidden" name="order_id" value="${order.id}">
                        <input type="hidden" name="mark_complete" value="1">
                        <button type="submit" class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                            <i class="fas fa-check mr-1"></i> Mark Complete & Print
                        </button>
                    </form>
                </div>
            </div>
        `;
        
        document.getElementById('orderDetailsContent').innerHTML = content;
        document.getElementById('orderModal').classList.remove('hidden');
    }

    function closeOrderModal() {
        document.getElementById('orderModal').classList.add('hidden');
    }
</script>

    <script src="<?php echo $base_url ?? ''; ?>/assets/js/main.js"></script>
</body>
</html>

