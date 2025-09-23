<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is admin
require_admin();

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get order details with user information and item
$query = "SELECT o.*, u.name as user_name, u.email as user_email, u.department as user_department, i.name as item_name, i.price as item_price, i.description as item_description, o.quantity, o.total_price FROM orders o JOIN users u ON o.user_id = u.id JOIN inventory i ON o.inventory_id = i.id WHERE o.id = ? AND o.status = 'approved'";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Order not found or not approved.");
}

$order = $result->fetch_assoc();

// Get size information from requests table
$size_query = $conn->prepare("SELECT details FROM requests WHERE type = ? AND details LIKE ? AND user_id = ? ORDER BY created_at DESC LIMIT 1");
$item_order_type = $order['item_name'] . " Order";
$size_details = "Order for " . $order['quantity'] . " " . $order['item_name'] . "%";
$size_query->bind_param("ssi", $item_order_type, $size_details, $order['user_id']);
$size_query->execute();
$size_result = $size_query->get_result();
$size_info = '';

if ($size_result->num_rows > 0) {
    $details = $size_result->fetch_assoc()['details'];
    if (preg_match('/\(Size: (.*?)\)/', $details, $matches)) {
        $size_info = $matches[1];
    }
}

// Get admin name for 'Issued by'
$admin_name = isset($_SESSION['user_sessions']['admin']['user_name']) ? $_SESSION['user_sessions']['admin']['user_name'] : 'Business Affairs Personnel';

// Use order fields for receipt
$receipt_no = str_pad($order['id'], 6, '0', STR_PAD_LEFT);
$date = date('F d, Y', strtotime($order['created_at']));
$payor = $order['user_name'];
$nature_of_payment = $order['purpose'] ?? $order['order_type'] ?? 'N/A';
$amount = isset($order['amount']) ? number_format($order['amount'], 2) : (isset($order['total']) ? number_format($order['total'], 2) : '0.00');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Receipt - CHMSU BAO</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none;
            }
            .receipt {
                width: 100%;
                margin: 0;
                padding: 20px;
            }
            .page-break {
                page-break-before: always;
            }
        }
        .receipt-table th, .receipt-table td { border: 1px solid #d1d5db; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="receipt bg-white shadow-lg rounded-lg p-8 max-w-2xl mx-auto border border-gray-300">
            <div class="flex items-center justify-between mb-2">
                <!-- <img src="/assets/css/CHMSULOGGG.png" alt="CHMSU Logo" class="h-16 w-16 object-contain"> -->
                 <img src="../assets/css/CHMSULOGGG.png" alt="CHMSU Logo" class="h-16 w-16 object-contain">
                <div class="text-right">
                    <span class="block text-xs text-gray-500">Control No.:</span>
                    <span class="block font-bold text-lg">#<?php echo $receipt_no; ?></span>
                </div>
            </div>
            <div class="text-center mb-2">
                <h1 class="text-xl font-bold text-gray-800 leading-tight">CARLOS HILADO MEMORIAL STATE UNIVERSITY</h1>
                <div class="text-xs text-gray-600">Alijis Campus (Binalbagan Extension)</div>
                <div class="text-xs text-gray-600">Business Affairs Office</div>
                <div class="text-xs text-gray-600">Tel. No. (034) 712-0400/15 local 144</div>
            </div>
            <div class="flex justify-between mb-2">
                <div class="text-xs text-gray-600">Date: <span class="font-semibold text-gray-900"><?php echo $date; ?></span></div>
                <div class="text-xs text-gray-600">Official Receipt #: ____________________</div>
            </div>
            <div class="mb-2">
                <div class="text-xs text-gray-600">Payor: <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($payor); ?></span></div>
            </div>
            <div class="mb-2">
                <div class="text-xs text-gray-600">Nature of Payment: <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($nature_of_payment); ?></span></div>
            </div>
            <div class="mb-4">
                <table class="w-full text-xs receipt-table border-collapse">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-2 py-1 text-left">Item</th>
                            <th class="px-2 py-1 text-right">Qty</th>
                            <th class="px-2 py-1 text-right">Unit Price</th>
                            <th class="px-2 py-1 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-2 py-1 text-left">
                                <?php echo htmlspecialchars($order['item_name']); ?>
                                <?php if (!empty($size_info)): ?>
                                <div class="text-xs text-blue-700 font-medium mt-1">Size: <?php echo htmlspecialchars($size_info); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-2 py-1 text-right"><?php echo $order['quantity']; ?></td>
                            <td class="px-2 py-1 text-right">&#8369; <?php echo number_format($order['item_price'], 2); ?></td>
                            <td class="px-2 py-1 text-right font-bold">&#8369; <?php echo number_format($order['total_price'], 2); ?></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="px-2 py-1 text-right font-bold">Total</td>
                            <td class="px-2 py-1 text-right font-bold text-lg">&#8369; <?php echo number_format($order['total_price'], 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="flex justify-between mb-2">
                <div class="text-xs text-gray-600">Issued by:<br><span class="font-semibold text-gray-900"><?php echo htmlspecialchars($admin_name); ?></span><br><span class="text-xs">Business Affairs Personnel</span></div>
                <div class="text-xs text-gray-600">Payment Received:<br>_________________________<br><span class="text-xs">Cashier / Office Personnel</span></div>
            </div>
            <div class="flex justify-between items-end mt-6">
                <div class="text-xs text-gray-500">BAD-TAF-91<br>REVISION 3<br>JUNE 8, 2023</div>
                <div class="text-xs text-gray-500">Business Affairs Copy</div>
            </div>
            <div class="mt-8 text-center no-print">
                <button onclick="window.print()" 
                        class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                    Print Receipt
                </button>
            </div>
        </div>
    </div>
</body>
</html> 