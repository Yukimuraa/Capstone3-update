<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is student
require_student();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_covered = $_POST['date_covered'] ?? '';
    $no_of_vehicles = intval($_POST['no_of_vehicles'] ?? 0);
    
    if (empty($date_covered) || $no_of_vehicles <= 0) {
        echo json_encode(['error' => 'Invalid parameters']);
        exit;
    }
    
    // Get total available buses
    $total_buses_query = "SELECT COUNT(*) as total FROM buses WHERE status = 'available'";
    $total_buses_result = $conn->query($total_buses_query);
    $total_buses = $total_buses_result->fetch_assoc()['total'];
    
    // Get booked buses for the specific date
    $booked_query = "SELECT COUNT(DISTINCT bb.bus_id) as booked 
                     FROM bus_bookings bb 
                     JOIN bus_schedules bs ON bb.schedule_id = bs.id 
                     WHERE bs.date_covered = ? AND bb.status = 'active'";
    $booked_stmt = $conn->prepare($booked_query);
    $booked_stmt->bind_param("s", $date_covered);
    $booked_stmt->execute();
    $booked_result = $booked_stmt->get_result();
    $booked_buses = $booked_result->fetch_assoc()['booked'];
    
    $available_buses = $total_buses - $booked_buses;
    
    echo json_encode([
        'total_buses' => $total_buses,
        'booked_buses' => $booked_buses,
        'available_buses' => $available_buses,
        'can_book' => $available_buses >= $no_of_vehicles
    ]);
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>

