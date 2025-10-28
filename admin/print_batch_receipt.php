<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is admin
require_admin();

$page_title = "Print Batch Receipt - CHMSU BAO";
$base_url = "..";

// Get batch ID
if (!isset($_GET['batch_id'])) {
    header("Location: orders.php");
    exit();
}

$batch_id = sanitize_input($_GET['batch_id']);

// Get all orders in this batch
$stmt = $conn->prepare("SELECT o.*, i.name as item_name, i.description, u.name as customer_name, u.email as customer_email
                        FROM orders o
                        JOIN inventory i ON o.inventory_id = i.id
                        JOIN user_accounts u ON o.user_id = u.id
                        WHERE o.batch_id = ?
                        ORDER BY o.id ASC");
$stmt->bind_param("s", $batch_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
$total_amount = 0;
$customer_name = '';
$customer_email = '';
$order_date = '';

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
    $total_amount += $row['total_price'];
    if (empty($customer_name)) {
        $customer_name = $row['customer_name'];
        $customer_email = $row['customer_email'];
        $order_date = $row['created_at'];
    }
}

if (count($orders) === 0) {
    header("Location: orders.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Receipt - <?php echo $batch_id; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 20px; }
        .receipt { max-width: 800px; margin: 0 auto; background: white; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }
        .header h1 { font-size: 28px; margin-bottom: 5px; }
        .header p { font-size: 14px; color: #666; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 14px; font-weight: bold; color: #333; margin-bottom: 10px; text-transform: uppercase; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .info-item { font-size: 13px; }
        .info-label { font-weight: bold; color: #555; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        thead { background: #f3f4f6; }
        th { padding: 12px; text-align: left; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #ddd; }
        td { padding: 10px 12px; font-size: 13px; border-bottom: 1px solid #e5e7eb; }
        .total-section { margin-top: 20px; border-top: 2px solid #000; padding-top: 15px; }
        .total-row { display: flex; justify-between; margin: 8px 0; font-size: 14px; }
        .total-row.grand-total { font-size: 20px; font-weight: bold; margin-top: 15px; color: #059669; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 2px solid #ddd; text-align: center; }
        .footer p { font-size: 12px; color: #666; margin: 5px 0; }
        .print-button { background: #3b82f6; color: white; padding: 12px 24px; border: none; border-radius: 5px; font-size: 14px; cursor: pointer; margin: 20px 0; }
        .print-button:hover { background: #2563eb; }
        .completed-stamp { display: inline-block; padding: 8px 20px; background: #10b981; color: white; font-weight: bold; border-radius: 5px; font-size: 14px; }
        @media print {
            body { padding: 0; }
            .print-button, .no-print { display: none !important; }
            .receipt { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button class="print-button" onclick="window.location.href='orders.php'" style="background: #6b7280; margin-right: 10px;">
            ← Back to Orders
        </button>
        <button class="print-button" onclick="window.print()">
            🖨️ Print Receipt
        </button>
    </div>

    <div class="receipt">
        <div class="header">
            <h1>CHMSU BUSINESS AFFAIRS OFFICE</h1>
            <p>Carlos Hilado Memorial State University</p>
            <p style="margin-top: 10px; font-size: 16px; font-weight: bold;">OFFICIAL RECEIPT</p>
            <p style="margin-top: 5px;">
                <span class="completed-stamp">✓ COMPLETED</span>
            </p>
        </div>

        <div class="section">
            <div class="info-grid">
                <div>
                    <div class="section-title">Customer Information</div>
                    <div class="info-item">
                        <span class="info-label">Name:</span> <?php echo htmlspecialchars($customer_name); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span> <?php echo htmlspecialchars($customer_email); ?>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div class="section-title">Order Details</div>
                    <div class="info-item">
                        <span class="info-label">Batch ID:</span> <?php echo htmlspecialchars($batch_id); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date:</span> <?php echo date('F j, Y g:i A', strtotime($order_date)); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Total Items:</span> <?php echo count($orders); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Order Items</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 45%;">Item Description</th>
                        <th>Size</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Unit Price</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($order['item_name']); ?></strong>
                                <?php if (!empty($order['description'])): ?>
                                    <br><small style="color: #666;"><?php echo htmlspecialchars(substr($order['description'], 0, 60)); ?><?php echo strlen($order['description']) > 60 ? '...' : ''; ?></small>
                                <?php endif; ?>
                                <br><small style="color: #999;">Order ID: <?php echo htmlspecialchars($order['order_id']); ?></small>
                            </td>
                            <td><?php echo !empty($order['size']) ? htmlspecialchars($order['size']) : '-'; ?></td>
                            <td style="text-align: center;"><?php echo $order['quantity']; ?></td>
                            <td style="text-align: right;">₱<?php echo number_format($order['total_price'] / $order['quantity'], 2); ?></td>
                            <td style="text-align: right;"><strong>₱<?php echo number_format($order['total_price'], 2); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="total-section">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>₱<?php echo number_format($total_amount, 2); ?></span>
            </div>
            <div class="total-row">
                <span>Number of Items:</span>
                <span><?php echo count($orders); ?></span>
            </div>
            <div class="total-row grand-total">
                <span>TOTAL AMOUNT:</span>
                <span>₱<?php echo number_format($total_amount, 2); ?></span>
            </div>
        </div>

        <div class="section" style="margin-top: 50px;">
            <div class="info-grid">
                <div style="text-align: center;">
                    <div style="margin-bottom: 60px;">
                        <div style="border-bottom: 2px solid #000; width: 80%; margin: 0 auto 5px auto;"></div>
                        <p style="font-size: 12px; font-weight: bold;">Issued by:</p>
                        <p style="font-size: 11px;">Business Affairs Personnel</p>
                    </div>
                </div>
                <div style="text-align: center;">
                    <div style="margin-bottom: 60px;">
                        <div style="border-bottom: 2px solid #000; width: 80%; margin: 0 auto 5px auto;"></div>
                        <p style="font-size: 12px; font-weight: bold;">Received by:</p>
                        <p style="font-size: 11px;">Cashier's Office Personnel</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p><strong>Important:</strong> This is an official receipt. Please keep this for your records.</p>
            <p>For inquiries, please contact the Business Affairs Office.</p>
            <p style="margin-top: 15px; font-size: 11px;">This is a computer-generated receipt. Generated on <?php echo date('F j, Y g:i A'); ?></p>
        </div>
    </div>

    <script>
        // Auto-print when page loads (optional, can be removed if not desired)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>

