<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is admin
require_admin();

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get booking details
$query = "SELECT b.*, f.name as facility_name, f.description as facility_description, 
          u.name as user_name, u.email as user_email
          FROM bookings b 
          JOIN facilities f ON b.facility_id = f.id 
          JOIN users u ON b.user_id = u.id 
          WHERE b.id = ? AND b.status = 'approved'";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Booking not found or not approved.");
}

$booking = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Receipt - CHMSU BAO</title>
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
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="receipt bg-white shadow-lg rounded-lg p-8 max-w-2xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">CHMSU BAO</h1>
                <p class="text-gray-600">Facility Booking Receipt</p>
            </div>

            <!-- Receipt Details -->
            <div class="border-t border-b border-gray-200 py-4 mb-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Receipt No:</p>
                        <p class="font-semibold">#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Date:</p>
                        <p class="font-semibold"><?php echo date('F d, Y', strtotime($booking['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Booking Information -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-4">Booking Details</h2>
                <div class="space-y-2">
                    <div class="grid grid-cols-2">
                        <p class="text-gray-600">Facility:</p>
                        <p class="font-medium"><?php echo $booking['facility_name']; ?></p>
                    </div>
                    <div class="grid grid-cols-2">
                        <p class="text-gray-600">Date:</p>
                        <p class="font-medium"><?php echo date('F d, Y', strtotime($booking['booking_date'])); ?></p>
                    </div>
                    <div class="grid grid-cols-2">
                        <p class="text-gray-600">Time:</p>
                        <p class="font-medium">
                            <?php echo date('h:i A', strtotime($booking['start_time'])); ?> - 
                            <?php echo date('h:i A', strtotime($booking['end_time'])); ?>
                        </p>
                    </div>
                    <div class="grid grid-cols-2">
                        <p class="text-gray-600">Purpose:</p>
                        <p class="font-medium"><?php echo $booking['purpose']; ?></p>
                    </div>
                    <div class="grid grid-cols-2">
                        <p class="text-gray-600">Participants:</p>
                        <p class="font-medium"><?php echo $booking['participants']; ?> person(s)</p>
                    </div>
                </div>
            </div>

            <!-- User Information -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-4">User Information</h2>
                <div class="space-y-2">
                    <div class="grid grid-cols-2">
                        <p class="text-gray-600">Name:</p>
                        <p class="font-medium"><?php echo $booking['user_name']; ?></p>
                    </div>
                    <div class="grid grid-cols-2">
                        <p class="text-gray-600">Email:</p>
                        <p class="font-medium"><?php echo $booking['user_email']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <?php if (!empty($booking['notes'])): ?>
            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-2">Additional Notes</h2>
                <p class="text-gray-700"><?php echo $booking['notes']; ?></p>
            </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="border-t border-gray-200 pt-4 mt-6">
                <p class="text-sm text-gray-600 text-center">
                    This receipt serves as proof of your approved facility booking.<br>
                    Please present this receipt when using the facility.
                </p>
            </div>

            <!-- Print Button -->
            <div class="mt-8 text-center no-print">
                <button onclick="window.print()" 
                        class="bg-emerald-600 text-white px-6 py-2 rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                    Print Receipt
                </button>
            </div>
        </div>
    </div>
</body>
</html> 