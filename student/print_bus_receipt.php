<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is student
require_student();

$schedule_id = $_GET['id'] ?? 0;

if (!$schedule_id) {
    die('Invalid schedule ID');
}

// Get billing statement data
$query = "SELECT bs.*, bst.* 
          FROM bus_schedules bs 
          JOIN billing_statements bst ON bs.id = bst.schedule_id 
          WHERE bs.id = ? AND bs.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $schedule_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Billing statement not found');
}

$billing = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Statement - USE OF VEHICLE</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .header h2 {
            margin: 5px 0 0 0;
            font-size: 18px;
            color: #666;
        }
        .header .department {
            margin-top: 10px;
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        .form-section {
            margin-bottom: 25px;
        }
        .form-row {
            display: flex;
            margin-bottom: 15px;
            align-items: center;
        }
        .form-row label {
            font-weight: bold;
            min-width: 150px;
            margin-right: 10px;
        }
        .form-row input, .form-row span {
            flex: 1;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        .form-row input {
            background: #f9f9f9;
        }
        .itinerary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .itinerary-table th,
        .itinerary-table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        .itinerary-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .cost-breakdown {
            margin: 20px 0;
        }
        .cost-breakdown h3 {
            margin-bottom: 15px;
            color: #333;
        }
        .cost-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .cost-item.total {
            font-weight: bold;
            font-size: 18px;
            background-color: #ffffcc;
            padding: 10px;
            margin-top: 10px;
            border: 2px solid #333;
        }
        .payment-instructions {
            margin: 30px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-left: 4px solid #333;
        }
        .notes {
            margin: 20px 0;
            font-size: 12px;
            color: #666;
        }
        .signatures {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            text-align: center;
            width: 200px;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            height: 40px;
            margin-bottom: 5px;
        }
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .print-button:hover {
            background: #0056b3;
        }
        @media print {
            .print-button {
                display: none;
            }
            body {
                background: white;
            }
            .receipt-container {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">
        <i class="fas fa-print"></i> Print Receipt
    </button>

    <div class="receipt-container">
        <div class="header">
            <h1>Billing Statement</h1>
            <h2>USE OF VEHICLE</h2>
            <div class="department">OSAS</div>
        </div>

        <div class="form-section">
            <div class="form-row">
                <label>Client:</label>
                <span><?php echo htmlspecialchars($billing['client']); ?></span>
            </div>
            <div class="form-row">
                <label>Destination:</label>
                <span><?php echo htmlspecialchars($billing['destination']); ?></span>
            </div>
            <div class="form-row">
                <label>Purpose:</label>
                <span><?php echo htmlspecialchars($billing['purpose']); ?></span>
            </div>
            <div class="form-row">
                <label>Dates Covered:</label>
                <span><?php echo date('F d, Y', strtotime($billing['date_covered'])); ?></span>
            </div>
            <div class="form-row">
                <label>No. of Days:</label>
                <span><?php echo $billing['no_of_days']; ?></span>
            </div>
            <div class="form-row">
                <label>Vehicle:</label>
                <span><?php echo htmlspecialchars($billing['vehicle']); ?></span>
            </div>
            <div class="form-row">
                <label>Bus No.:</label>
                <span><?php echo htmlspecialchars($billing['bus_no']); ?></span>
            </div>
            <div class="form-row">
                <label>No. of Vehicle/s:</label>
                <span><?php echo $billing['no_of_vehicles']; ?></span>
            </div>
        </div>

        <div class="form-section">
            <h3>Itinerary (<?php echo date('F, Y', strtotime($billing['date_covered'])); ?>)</h3>
            <table class="itinerary-table">
                <thead>
                    <tr>
                        <th>From</th>
                        <th>To</th>
                        <th>Remarks</th>
                        <th>Distance in (KM)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($billing['from_location']); ?></td>
                        <td><?php echo htmlspecialchars($billing['to_location']); ?></td>
                        <td>Land</td>
                        <td><?php echo number_format($billing['distance_km'], 2); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo htmlspecialchars($billing['to_location']); ?></td>
                        <td><?php echo htmlspecialchars($billing['from_location']); ?></td>
                        <td>Land</td>
                        <td><?php echo number_format($billing['distance_km'], 2); ?></td>
                    </tr>
                    <tr style="font-weight: bold;">
                        <td colspan="3">Total distance in kilometers:</td>
                        <td><?php echo number_format($billing['total_distance_km'], 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="form-section">
            <div class="form-row">
                <label>Computed distance (2Km/L):</label>
                <span><?php echo number_format($billing['computed_distance'], 2); ?></span>
            </div>
            <div class="form-row">
                <label>Fuel Rate (Php):</label>
                <span><?php echo number_format($billing['fuel_rate'], 2); ?></span>
            </div>
            <div class="form-row">
                <label>Run Time (L):</label>
                <span><?php echo number_format($billing['runtime_liters'], 2); ?></span>
            </div>
        </div>

        <div class="cost-breakdown">
            <h3>Cost Breakdown per Vehicle (in Php):</h3>
            <div class="cost-item">
                <span>Fuel:</span>
                <span>₱<?php echo number_format($billing['fuel_cost'], 2); ?></span>
            </div>
            <div class="cost-item">
                <span>Runtime:</span>
                <span>₱<?php echo number_format($billing['runtime_cost'], 2); ?></span>
            </div>
            <div class="cost-item">
                <span>Maintenance Cost:</span>
                <span>₱<?php echo number_format($billing['maintenance_cost'], 2); ?></span>
            </div>
            <div class="cost-item">
                <span>Standby Cost:</span>
                <span>₱<?php echo number_format($billing['standby_cost'], 2); ?></span>
            </div>
            <div class="cost-item">
                <span>Additive Cost:</span>
                <span>₱<?php echo number_format($billing['additive_cost'], 2); ?></span>
            </div>
            <div class="cost-item">
                <span>Rate per Bus:</span>
                <span>₱<?php echo number_format($billing['rate_per_bus'], 2); ?></span>
            </div>
            <div class="cost-item total">
                <span>Subtotal (Cost Breakdown per Vehicle):</span>
                <span>₱<?php echo number_format($billing['subtotal_per_vehicle'], 2); ?></span>
            </div>
        </div>

        <div class="payment-instructions">
            <p><strong>Please pay at the Cashier's Office the amount of ....................</strong></p>
            <p><strong>Inter Office Memo: Please charge against fund / budget of the amount of ....................</strong></p>
            <div class="cost-item total" style="margin-top: 15px;">
                <span>Total Amount Due:</span>
                <span>₱<?php echo number_format($billing['total_amount'], 2); ?></span>
            </div>
        </div>

        <div class="notes">
            <p><strong>Important Notes:</strong></p>
            <ul>
                <li>ROUTES/DESTINATIONS not declared in the itinerary will be billed accordingly.</li>
                <li>Billed amount is exclusive of Port Entrance/Terminal Fees.</li>
                <li>Accident claim is up to the allowable amount in the insurance coverage.</li>
                <li>CHMSU will not be held liable for additional claims.</li>
            </ul>
        </div>

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div><strong>Prepared by:</strong></div>
                <div><?php echo htmlspecialchars($billing['prepared_by']); ?></div>
                <div>Clerk, Business Affairs Office</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div><strong>Recommending Approval:</strong></div>
                <div><?php echo htmlspecialchars($billing['recommending_approval']); ?></div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div><strong>Approved:</strong></div>
                <div><?php echo htmlspecialchars($billing['approved_by'] ?? ''); ?></div>
            </div>
        </div>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>
</body>
</html>














