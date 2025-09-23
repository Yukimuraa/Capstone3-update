<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is student
require_student();

$page_title = "Order Receipt - CHMSU BAO";
$base_url = "..";

// Check if order ID is provided
if (!isset($_GET['order_id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = sanitize_input($_GET['order_id']);

// Get order details
$stmt = $conn->prepare("SELECT o.*, i.name as item_name, i.description as item_description, 
                        i.price as item_price, u.name as user_name, u.id_number, u.email, u.phone 
                        FROM orders o 
                        JOIN inventory i ON o.inventory_id = i.id 
                        JOIN users u ON o.user_id = u.id 
                        WHERE o.order_id = ? AND o.user_id = ?");
$stmt->bind_param("si", $order_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: orders.php");
    exit();
}

$order = $result->fetch_assoc();

// Get size information from requests table
$size_query = $conn->prepare("SELECT details FROM requests WHERE type = ? AND details LIKE ? AND user_id = ? ORDER BY created_at DESC LIMIT 1");
$item_order_type = $order['item_name'] . " Order";
$size_details = "Order for " . $order['quantity'] . " " . $order['item_name'] . "%";
$size_query->bind_param("ssi", $item_order_type, $size_details, $_SESSION['user_id']);
$size_query->execute();
$size_result = $size_query->get_result();
$size_info = '';

if ($size_result->num_rows > 0) {
    $details = $size_result->fetch_assoc()['details'];
    if (preg_match('/\(Size: (.*?)\)/', $details, $matches)) {
        $size_info = $matches[1];
    }
}

// Format date
$order_date = date('F j, Y', strtotime($order['created_at']));
$order_time = date('h:i A', strtotime($order['created_at']));

// Get school information
$school_name = "Carlos Hilado Memorial State University";
$school_address = "Talisay City, Negros Occidental";
$school_contact = "Phone: (034) 495-3461";
$school_email = "bao@chmsu.edu.ph";

// Generate receipt number (if not already in the database)
$receipt_number = "RCPT-" . date('Ymd', strtotime($order['created_at'])) . "-" . substr($order_id, -3);
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/student_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">Order Receipt</h1>
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
                <div class="mb-4 flex justify-between items-center">
                    <a href="orders.php" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Orders
                    </a>
                </div>
                
                <!-- Receipt -->
                <div id="receipt" class="bg-white rounded-lg shadow overflow-hidden">
                    <!-- Receipt Header -->
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-start">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900"><?php echo $school_name; ?></h2>
                                <p class="text-gray-600"><?php echo $school_address; ?></p>
                                <p class="text-gray-600"><?php echo $school_contact; ?></p>
                                <p class="text-gray-600"><?php echo $school_email; ?></p>
                            </div>
                            <div class="text-right">
                                <h3 class="text-xl font-bold text-gray-900">RECEIPT</h3>
                                <p class="text-gray-600"><?php echo $receipt_number; ?></p>
                                <p class="text-gray-600">Date: <?php echo $order_date; ?></p>
                                <p class="text-gray-600">Time: <?php echo $order_time; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Customer Information -->
                    <div class="p-6 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Customer Information</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Name:</p>
                                <p class="font-medium"><?php echo $order['user_name']; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">ID Number:</p>
                                <p class="font-medium"><?php echo $order['id_number']; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Email:</p>
                                <p class="font-medium"><?php echo $order['email']; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Phone:</p>
                                <p class="font-medium"><?php echo $order['phone'] ?? 'N/A'; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Details -->
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Order Details</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $order['order_id']; ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <div>
                                                <p class="font-medium"><?php echo $order['item_name']; ?></p>
                                                <p class="text-xs text-gray-400"><?php echo substr($order['item_description'], 0, 50) . (strlen($order['item_description']) > 50 ? '...' : ''); ?></p>
                                                <?php if (!empty($size_info)): ?>
                                                <p class="text-xs font-medium text-blue-600 mt-1">Size: <?php echo $size_info; ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $order['quantity']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">₱<?php echo number_format($order['item_price'], 2); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">₱<?php echo number_format($order['total_price'], 2); ?></td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">Subtotal:</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">₱<?php echo number_format($order['total_price'], 2); ?></td>
                                    </tr>
                                    <!-- <tr>
                                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">Tax (0%):</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">₱0.00</td>
                                    </tr> -->
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-base font-bold text-gray-900 text-right">Total:</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-base font-bold text-gray-900 text-right">₱<?php echo number_format($order['total_price'], 2); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Payment Status -->
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Payment Status</h3>
                                <p class="text-gray-600 mt-1">
                                    <?php if ($order['status'] == 'pending'): ?>
                                        Payment pending. Please proceed to the cashier for payment.
                                    <?php elseif ($order['status'] == 'approved'): ?>
                                        Payment received. Thank you for your order.
                                    <?php elseif ($order['status'] == 'completed'): ?>
                                        Order completed. Thank you for your business.
                                    <?php else: ?>
                                        Order rejected. Please contact the BAO for more information.
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div>
                                <?php if ($order['status'] == 'pending'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Pending
                                    </span>
                                <?php elseif ($order['status'] == 'approved'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Approved
                                    </span>
                                <?php elseif ($order['status'] == 'completed'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Completed
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Rejected
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="p-6 bg-gray-50">
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Thank you for your order!</p>
                            <p class="text-xs text-gray-500 mt-2">This receipt was generated on <?php echo date('F j, Y h:i A'); ?></p>
                            <p class="text-xs text-gray-500 mt-1">For any questions, please contact the Business Affairs Office.</p>
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

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #receipt, #receipt * {
            visibility: visible;
        }
        #receipt {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .shadow {
            box-shadow: none !important;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>
