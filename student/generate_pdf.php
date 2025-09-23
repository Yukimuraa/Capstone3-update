<?php

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../vendor/autoload.php';

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', '../logs/error.log'); // Log errors to a file

// Check if user is student
require_student();

// Check if order ID is provided
if (!isset($_POST['order_id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = sanitize_input($_POST['order_id']);

// Get order details
$stmt = $conn->prepare("SELECT o.*, i.name as item_name, i.description as item_description, 
                        i.price as item_price, u.name as user_name, u.id_number, u.email 
                        FROM orders o 
                        JOIN inventory i ON o.inventory_id = i.id 
                        JOIN users u ON o.user_id = u.id 
                        WHERE o.order_id = ? AND o.user_id = ?");
$stmt->bind_param("si", $order_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Order not found.');
}

$order = $result->fetch_assoc();

// Format date
$order_date = date('F j, Y', strtotime($order['created_at']));
$order_time = date('h:i A', strtotime($order['created_at']));

// Get school information
$school_name = "Carlos Hilado Memorial State University";
$school_address = "Talisay City, Negros Occidental";
$school_contact = "Phone: (034) 495-3461";
$school_email = "bao@chmsu.edu.ph";

// Generate receipt number
$receipt_number = "RCPT-" . date('Ymd', strtotime($order['created_at'])) . "-" . substr($order_id, -3);

// Clear output buffer
if (ob_get_length()) {
    ob_end_clean();
}

// Set the appropriate content type for PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="receipt_' . $order_id . '.pdf"');

// Create PDF content
if (!class_exists('TCPDF')) {
    die('TCPDF library is not loaded. Please ensure it is installed via Composer using "composer require tecnickcom/tcpdf".');
}

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('CHMSU BAO System');
$pdf->SetAuthor('CHMSU BAO');
$pdf->SetTitle('Order Receipt');
$pdf->SetSubject('Order Receipt');
$pdf->SetKeywords('CHMSU, BAO, Order, Receipt');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->setImageScale(1.25);
$pdf->SetFont('helvetica', '', 10);
$pdf->AddPage();

// Create the receipt content
$html = '
<style>
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th {
        font-weight: bold;
        text-align: left;
        background-color: #f3f4f6;
        padding: 8px;
        border-bottom: 1px solid #e5e7eb;
    }
    td {
        padding: 8px;
        border-bottom: 1px solid #e5e7eb;
    }
    .text-right {
        text-align: right;
    }
    .header {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .subheader {
        font-size: 14px;
        color: #6b7280;
        margin-bottom: 20px;
    }
    .section-title {
        font-size: 16px;
        font-weight: bold;
        margin-top: 20px;
        margin-bottom: 10px;
        padding-bottom: 5px;
        border-bottom: 1px solid #e5e7eb;
    }
    .footer {
        text-align: center;
        font-size: 10px;
        color: #6b7280;
        margin-top: 30px;
    }
</style>

<table>
    <tr>
        <td>
            <div class="header">' . $school_name . '</div>
            <div class="subheader">' . $school_address . '<br>' . $school_contact . '<br>' . $school_email . '</div>
        </td>
        <td class="text-right">
            <div class="header">RECEIPT</div>
            <div class="subheader">' . $receipt_number . '<br>Date: ' . $order_date . '<br>Time: ' . $order_time . '</div>
        </td>
    </tr>
</table>

<div class="section-title">Customer Information</div>
<table>
    <tr>
        <td width="50%">
            <strong>Name:</strong> ' . $order['user_name'] . '<br>
            <strong>ID Number:</strong> ' . $order['id_number'] . '
        </td>
        <td width="50%">
            <strong>Email:</strong> ' . $order['email'] . '<br>
            <strong>Phone:</strong> ' . ($order['phone'] ?? 'N/A') . '
        </td>
    </tr>
</table>

<div class="section-title">Order Details</div>
<table>
    <tr>
        <th width="20%">Order ID</th>
        <th width="30%">Item</th>
        <th width="10%">Quantity</th>
        <th width="20%" class="text-right">Unit Price</th>
        <th width="20%" class="text-right">Total</th>
    </tr>
    <tr>
        <td>' . $order['order_id'] . '</td>
        <td>
            <strong>' . $order['item_name'] . '</strong><br>
            <small>' . substr($order['item_description'], 0, 50) . (strlen($order['item_description']) > 50 ? '...' : '') . '</small>
        </td>
        <td>' . $order['quantity'] . '</td>
        <td class="text-right">₱' . number_format($order['item_price'], 2) . '</td>
        <td class="text-right">₱' . number_format($order['total_price'], 2) . '</td>
    </tr>
    <tr>
        <td colspan="4" class="text-right"><strong>Subtotal:</strong></td>
        <td class="text-right">₱' . number_format($order['total_price'], 2) . '</td>
    </tr>
    <tr>
        <td colspan="4" class="text-right"><strong>Tax (0%):</strong></td>
        <td class="text-right">₱0.00</td>
    </tr>
    <tr>
        <td colspan="4" class="text-right"><strong>Total:</strong></td>
        <td class="text-right"><strong>₱' . number_format($order['total_price'], 2) . '</strong></td>
    </tr>
</table>

<div class="section-title">Payment Status</div>
<p>';

if ($order['status'] == 'pending') {
    $html .= 'Payment pending. Please proceed to the cashier for payment.';
} elseif ($order['status'] == 'approved') {
    $html .= 'Payment received. Thank you for your order.';
} elseif ($order['status'] == 'completed') {
    $html .= 'Order completed. Thank you for your business.';
} else {
    $html .= 'Order rejected. Please contact the BAO for more information.';
}

$html .= ' <strong>Status: ' . ucfirst($order['status']) . '</strong></p>

<div class="footer">
    Thank you for your order!<br>
    This receipt was generated on ' . date('F j, Y h:i A') . '<br>
    For any questions, please contact the Business Affairs Office.
</div>
';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('receipt_' . $order_id . '.pdf', 'D');