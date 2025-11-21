<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user_sessions'])) {
    header("Location: ../login.php");
    exit();
}

// Get user ID (works for both student and staff)
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['user_name'];
    $user_type = 'student';
} else {
    // Staff user
    $user_id = $_SESSION['user_sessions']['staff']['user_id'];
    $user_name = $_SESSION['user_sessions']['staff']['user_name'];
    $user_type = 'staff';
}

$page_title = "Batch Order Receipt - CHMSU BAO";
$base_url = "..";

// Get batch ID from URL
if (!isset($_GET['batch_id'])) {
    header("Location: orders.php");
    exit();
}

$batch_id = sanitize_input($_GET['batch_id']);

// Get orders for this batch
$stmt = $conn->prepare("SELECT o.*, i.name as item_name, i.description 
                        FROM orders o 
                        JOIN inventory i ON o.inventory_id = i.id 
                        WHERE o.batch_id = ? AND o.user_id = ? 
                        ORDER BY o.created_at ASC");
$stmt->bind_param("si", $batch_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
$total_amount = 0;
$order_date = '';
$status = '';

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
    $total_amount += $row['total_price'];
    if (empty($order_date)) {
        $order_date = $row['created_at'];
        $status = $row['status'];
    }
}

// If no orders found, redirect
if (count($orders) === 0) {
    header("Location: orders.php");
    exit();
}

// Get user details
$user_stmt = $conn->prepare("SELECT name, email FROM user_accounts WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_info = $user_stmt->get_result()->fetch_assoc();
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php 
    if ($user_type === 'student') {
        include '../includes/student_sidebar.php';
    } else {
        include '../includes/staff_sidebar.php';
    }
    ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">Batch Order Receipt</h1>
                <div class="flex items-center gap-3">
                    <button onclick="window.print()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-print mr-2"></i> Print Receipt
                    </button>
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
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-lg shadow-lg p-8" id="receipt-content">
                    <!-- Header -->
                    <div class="border-b-2 border-gray-300 pb-6 mb-6">
                        <div class="text-center">
                            <h2 class="text-3xl font-bold text-gray-900 mb-2">CHMSU Business Affairs Office</h2>
                            <p class="text-gray-600">Carlos Hilado Memorial State University</p>
                            <p class="text-sm text-gray-500">Batch Order Receipt</p>
                        </div>
                    </div>
                    
                    <!-- Order Information -->
                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 uppercase mb-3">Customer Information</h3>
                            <p class="text-gray-900 font-medium"><?php echo $user_info['name']; ?></p>
                            <p class="text-sm text-gray-600"><?php echo $user_info['email']; ?></p>
                        </div>
                        <div class="text-right">
                            <h3 class="text-sm font-medium text-gray-500 uppercase mb-3">Order Information</h3>
                            <p class="text-gray-900"><span class="font-medium">Batch ID:</span> <?php echo $batch_id; ?></p>
                            <p class="text-sm text-gray-600"><span class="font-medium">Date:</span> <?php echo date('F j, Y g:i A', strtotime($order_date)); ?></p>
                            <p class="text-sm">
                                <span class="font-medium">Status:</span> 
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php 
                                    if ($status == 'pending') echo 'bg-yellow-100 text-yellow-800';
                                    elseif ($status == 'approved') echo 'bg-green-100 text-green-800';
                                    elseif ($status == 'completed') echo 'bg-blue-100 text-blue-800';
                                    else echo 'bg-red-100 text-red-800';
                                    ?>">
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Order Items Table -->
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Order Items</h3>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Size</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td class="px-4 py-3 text-xs text-gray-500"><?php echo $order['order_id']; ?></td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900"><?php echo $order['item_name']; ?></div>
                                            <div class="text-xs text-gray-500"><?php echo substr($order['description'], 0, 50); ?><?php echo strlen($order['description']) > 50 ? '...' : ''; ?></div>
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-900">
                                            <?php echo !empty($order['size']) ? $order['size'] : '-'; ?>
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-900"><?php echo $order['quantity']; ?></td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-900">₱<?php echo number_format($order['total_price'] / $order['quantity'], 2); ?></td>
                                        <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">₱<?php echo number_format($order['total_price'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Total -->
                    <div class="border-t-2 border-gray-300 pt-6">
                        <div class="flex justify-end">
                            <div class="w-64">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-600">Subtotal:</span>
                                    <span class="text-gray-900">₱<?php echo number_format($total_amount, 2); ?></span>
                                </div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-600">Number of Items:</span>
                                    <span class="text-gray-900"><?php echo count($orders); ?></span>
                                </div>
                                <div class="flex justify-between items-center pt-3 border-t border-gray-300">
                                    <span class="text-lg font-bold text-gray-900">Total Amount:</span>
                                    <span class="text-2xl font-bold text-emerald-600">₱<?php echo number_format($total_amount, 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer Note -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <div class="bg-blue-50 rounded-lg p-4">
                            <p class="text-xs text-blue-800">
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong>Note:</strong> Please present this receipt to the cashier for payment verification. 
                                All orders must be paid within 7 days from approval date.
                            </p>
                        </div>
                        <p class="text-xs text-gray-500 text-center mt-4">
                            This is a computer-generated receipt. No signature required.
                        </p>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="mt-6 flex justify-center gap-3 print:hidden">
                    <a href="orders.php" class="inline-flex items-center px-6 py-3 border border-gray-300 rounded-md shadow-sm text-base font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Orders
                    </a>
                    <button onclick="window.print()" class="inline-flex items-center px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-emerald-600 hover:bg-emerald-700">
                        <i class="fas fa-print mr-2"></i> Print Receipt
                    </button>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #receipt-content, #receipt-content * {
            visibility: visible;
        }
        #receipt-content {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .print\\:hidden {
            display: none !important;
        }
    }
</style>

<script>
    // Mobile menu toggle
    const menuButton = document.getElementById('menu-button');
    if (menuButton) {
        menuButton.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        });
    }
</script>

    <script src="<?php echo $base_url ?? ''; ?>/assets/js/main.js"></script>
</body>
</html>


