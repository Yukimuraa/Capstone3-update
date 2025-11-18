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

// Generate control number (using order_id)
$control_no = $order_id;
$order_date_formatted = date('F j, Y', strtotime($order['created_at']));
$order_time = date('g:i A', strtotime($order['created_at']));

// Generate official receipt number
$receipt_no = 'OR-' . date('Ymd') . '-' . substr($order_id, -6);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORDER OF PAYMENT - <?php echo $order_id; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { 
            size: A4 portrait; 
            margin: 5mm;
            orientation: portrait;
        }
        body { 
            font-family: 'Times New Roman', serif; 
            font-size: 8pt;
            padding: 0;
            margin: 0;
            width: 100%;
            height: 100vh;
        }
        .page {
            width: 100%;
            max-width: 200mm;
            min-height: 287mm;
            height: 100%;
            padding: 5mm;
            margin: 0 auto;
            background: white;
            position: relative;
        }
        .copies-container {
            display: grid;
            grid-template-rows: 1fr 1fr 1fr;
            gap: 0;
            height: 100%;
            width: 100%;
        }
        .copy {
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
            padding: 4mm;
            position: relative;
            width: 100%;
            display: flex;
            flex-direction: column;
            height: calc(100% / 3);
            box-sizing: border-box;
        }
        .copy:first-child {
            border-top: 2px solid #000;
        }
        .copy:last-child {
            border-bottom: 2px solid #000;
        }
        .copy:not(:first-child):not(:last-child) {
            border-top: 1px dashed #999;
            border-bottom: 1px dashed #999;
        }
        .header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 4px;
        }
        .logo {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 6px;
            flex-shrink: 0;
        }
        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .header-right {
            flex: 1;
        }
        .title {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            margin: 4px 0 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-row {
            display: flex;
            margin-bottom: 2px;
            font-size: 7pt;
            align-items: baseline;
        }
        .info-label {
            width: 50px;
            font-weight: bold;
            flex-shrink: 0;
        }
        .info-value {
            flex: 1;
            border-bottom: 1px solid #000;
            min-height: 12px;
            padding-left: 3px;
            font-size: 7pt;
        }
        .payment-section {
            margin: 6px 0;
            flex: 1;
            overflow: hidden;
        }
        .payment-header {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 4px;
            margin-bottom: 3px;
            font-weight: bold;
            font-size: 7pt;
            padding-bottom: 2px;
            border-bottom: 1.5px solid #000;
        }
        .payment-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 4px;
            margin-bottom: 2px;
            min-height: 14px;
            font-size: 6.5pt;
        }
        .payment-nature {
            border-bottom: 1px solid #000;
            padding: 2px 3px;
            min-height: 14px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .payment-amount {
            border-bottom: 1px solid #000;
            padding: 2px 3px;
            text-align: right;
            min-height: 14px;
        }
        .total-box {
            border: 1.5px solid #000;
            padding: 3px;
            margin: 4px 0;
            font-weight: bold;
            font-size: 8pt;
            text-align: right;
        }
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin: 6px 0;
        }
        .signature-box {
            text-align: center;
        }
        .signature-line {
            border-bottom: 1.5px solid #000;
            width: 100%;
            margin-bottom: 2px;
            min-height: 25px;
        }
        .signature-label {
            font-size: 6.5pt;
            font-weight: bold;
            margin-top: 2px;
        }
        .signature-role {
            font-size: 6pt;
            margin-top: 1px;
        }
        .receipt-info {
            margin: 6px 0;
        }
        .receipt-field {
            margin-bottom: 3px;
            font-size: 6.5pt;
        }
        .receipt-field-label {
            font-weight: bold;
            display: inline-block;
            width: 70px;
            font-size: 6.5pt;
        }
        .receipt-field-value {
            border-bottom: 1px solid #000;
            padding: 0 4px;
            min-width: 80px;
            display: inline-block;
            font-size: 6.5pt;
        }
        .document-info {
            position: absolute;
            bottom: 4mm;
            right: 6mm;
            font-size: 5.5pt;
            text-align: right;
            border-left: 1.5px dashed #000;
            padding-left: 4px;
            line-height: 1.3;
        }
        .copy-label {
            position: absolute;
            bottom: 4mm;
            left: 6mm;
            font-size: 7pt;
            font-weight: bold;
        }
        .no-print {
            text-align: center;
            padding: 20px;
            background: #f3f4f6;
        }
        .print-button {
            background: #3b82f6;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            margin: 10px;
        }
        @media print {
            .no-print { display: none !important; }
            body { 
                padding: 0; 
                margin: 0;
                width: 100%;
                height: 100%;
            }
            .page { 
                margin: 0; 
                padding: 5mm;
                border: none;
                width: 100%;
                height: 100%;
                max-width: 100%;
            }
            .copies-container {
                gap: 0;
            }
            .copy {
                border-top: 1px solid #000;
                border-bottom: 1px solid #000;
            }
            .copy:first-child {
                border-top: 2px solid #000;
            }
            .copy:last-child {
                border-bottom: 2px solid #000;
            }
            .copy:not(:first-child):not(:last-child) {
                border-top: 1px dashed #000;
                border-bottom: 1px dashed #000;
            }
            @page {
                size: A4 portrait;
                margin: 5mm;
                orientation: portrait;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-button" onclick="window.location.href='orders.php'">
            ← Back to Orders
        </button>
        <button class="print-button" onclick="window.print()">
            🖨️ Print Receipt
        </button>
    </div>

    <div class="page">
        <div class="copies-container">
            <?php 
            $copies = [
                ['name' => "Business Affairs Office's Copy", 'class' => ''],
                ['name' => "Cashier's Copy", 'class' => ''],
                ['name' => "Customer's Copy", 'class' => '']
            ];
            
            foreach ($copies as $copy): 
                $item_desc = htmlspecialchars($order['item_name']);
                if (!empty($order['size'])) {
                    $item_desc .= ' (Size: ' . htmlspecialchars($order['size']) . ')';
                }
                if (!empty($order['description'])) {
                    $item_desc .= ' - ' . htmlspecialchars(substr($order['description'], 0, 25));
                }
            ?>
            <div class="copy">
                <div class="header">
                    <div class="logo">
                        <img src="../image/CHMSUWebLOGO.png" alt="CHMSU Logo">
                    </div>
                    <div class="header-right">
                        <div class="info-row">
                            <span class="info-label">Control No.:</span>
                            <span class="info-value"><?php echo htmlspecialchars($control_no); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Date:</span>
                            <span class="info-value"><?php echo $order_date_formatted; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Payor:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="title">ORDER OF PAYMENT</div>

                <div class="payment-section">
                    <div class="payment-header">
                        <div>NATURE OF PAYMENT</div>
                        <div style="text-align: right;">AMOUNT</div>
                    </div>
                    <div class="payment-row">
                        <div class="payment-nature">
                            <?php echo $order['quantity']; ?>x <?php echo $item_desc; ?>
                        </div>
                        <div class="payment-amount">
                            ₱<?php echo number_format($order['total_price'], 2); ?>
                        </div>
                    </div>
                    
                    <div class="total-box">
                        TOTAL: ₱<?php echo number_format($order['total_price'], 2); ?>
                    </div>
                </div>

                <div class="signature-section">
                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <div class="signature-label">Issued by:</div>
                        <div class="signature-role">Business Affairs Clerk</div>
                    </div>
                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <div class="signature-label">Payment Received by:</div>
                        <div class="signature-role">Cashier Clerk</div>
                    </div>
                </div>

                <div class="receipt-info">
                    <div class="receipt-field">
                        <span class="receipt-field-label">Official Receipt No.:</span>
                        <span class="receipt-field-value"><?php echo $receipt_no; ?></span>
                    </div>
                    <div class="receipt-field">
                        <span class="receipt-field-label">Date:</span>
                        <span class="receipt-field-value"><?php echo $order_date_formatted; ?></span>
                    </div>
                    <div class="receipt-field">
                        <span class="receipt-field-label">Amount:</span>
                        <span class="receipt-field-value">₱<?php echo number_format($order['total_price'], 2); ?></span>
                    </div>
                </div>

                <div class="document-info">
                    <div>Document Code: F.04-BAO-CHMSU</div>
                    <div>Revision No.: 0</div>
                    <div>Effective Date: April 7, 2025</div>
                    <div>Page: Page 1 of 1</div>
                </div>

                <div class="copy-label">
                    <?php echo $copy['name']; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        window.onload = function() {
            // Optional: Auto-print when page loads
            // window.print();
        }
    </script>
</body>
</html>
