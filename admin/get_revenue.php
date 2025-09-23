<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is admin
require_admin();

// Get the period from the request
$period = isset($_GET['period']) ? $_GET['period'] : '';

// Initialize response array
$response = ['revenue' => 0];

if ($period === 'monthly') {
    // Get current month's revenue
    $query = "SELECT SUM(total_price) as revenue 
              FROM orders 
              WHERE status = 'completed' 
              AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
              AND YEAR(created_at) = YEAR(CURRENT_DATE())";
} elseif ($period === 'yearly') {
    // Get current year's revenue
    $query = "SELECT SUM(total_price) as revenue 
              FROM orders 
              WHERE status = 'completed' 
              AND YEAR(created_at) = YEAR(CURRENT_DATE())";
}

if (!empty($query)) {
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $response['revenue'] = $row['revenue'] ?? 0;
    }
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 