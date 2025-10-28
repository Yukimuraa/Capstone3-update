<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is admin
require_admin();

$page_title = "Print Receipt - CHMSU BAO";
$base_url = "..";

// Get order ID
if (!isset($_GET['order_id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = sanitize_input($_GET['order_id']);

// Get order details
$stmt = $conn->prepare("SELECT o.*, i.name as item_name, i.description, u.name as customer_name, u.email as customer_email
                        FROM orders o
                        JOIN inventory i ON o.inventory_id = i.id
                        JOIN user_accounts u ON o.user_id = u.id
                        WHERE o.order_id = ?");
$stmt->bind_param("s", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: orders.php");
    exit();
}

$order = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Receipt - <?php echo $order_id; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 20px; }
        .receipt { max-width: 600px; margin: 0 auto; background: white; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }
        .header h1 { font-size: 28px; margin-bottom: 5px; }
        .header p { font-size: 14px; color: #666; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 14px; font-weight: bold; color: #333; margin-bottom: 10px; text-transform: uppercase; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .info-item { font-size: 13px; margin: 5px 0; }
        .info-label { font-weight: bold; color: #555; }
        .item-box { border: 2px solid #e5e7eb; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .item-title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .item-desc { color: #666; font-size: 13px; margin-bottom: 15px; }
        .item-details { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .total-section { margin-top: 20px; border-top: 2px solid #000; padding-top: 15px; }
        .total-row { display: flex; justify-between; margin: 8px 0; font-size: 14px; }
        .total-row.grand-total { font-size: 24px; font-weight: bold; margin-top: 15px; color: #059669; }
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
                        <span class="info-label">Name:</span> <?php echo htmlspecialchars($order['customer_name']); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span> <?php echo htmlspecialchars($order['customer_email']); ?>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div class="section-title">Order Details</div>
                    <div class="info-item">
                        <span class="info-label">Order ID:</span> <?php echo htmlspecialchars($order_id); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date:</span> <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status:</span> <span style="color: #10b981; font-weight: bold;">Completed</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Order Item</div>
            <div class="item-box">
                <div class="item-title"><?php echo htmlspecialchars($order['item_name']); ?></div>
                <?php if (!empty($order['description'])): ?>
                    <div class="item-desc"><?php echo htmlspecialchars($order['description']); ?></div>
                <?php endif; ?>
                <div class="item-details">
                    <?php if (!empty($order['size'])): ?>
                        <div class="info-item">
                            <span class="info-label">Size:</span> <?php echo htmlspecialchars($order['size']); ?>
                        </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <span class="info-label">Quantity:</span> <?php echo $order['quantity']; ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Unit Price:</span> ₱<?php echo number_format($order['total_price'] / $order['quantity'], 2); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Subtotal:</span> <strong>₱<?php echo number_format($order['total_price'], 2); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="total-section">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>₱<?php echo number_format($order['total_price'], 2); ?></span>
            </div>
            <div class="total-row grand-total">
                <span>TOTAL AMOUNT:</span>
                <span>₱<?php echo number_format($order['total_price'], 2); ?></span>
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
