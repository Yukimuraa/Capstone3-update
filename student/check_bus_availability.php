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
    
    // Per-bus availability for the date
    $buses = [];
    $buses_query = "SELECT b.id, b.bus_number FROM buses b ORDER BY CAST(b.bus_number AS UNSIGNED) ASC";
    $buses_result = $conn->query($buses_query);
    
    // Preload booked bus ids for the date
    $booked_ids = [];
    $booked_query = "SELECT DISTINCT bb.bus_id 
                     FROM bus_bookings bb 
                     JOIN bus_schedules bs ON bb.schedule_id = bs.id 
                     WHERE bs.date_covered = ? AND bb.status = 'active'";
    $booked_stmt = $conn->prepare($booked_query);
    $booked_stmt->bind_param("s", $date_covered);
    $booked_stmt->execute();
    $booked_result = $booked_stmt->get_result();
    while ($row = $booked_result->fetch_assoc()) {
        $booked_ids[(int)$row['bus_id']] = true;
    }
    
    while ($row = $buses_result->fetch_assoc()) {
        $busId = (int)$row['id'];
        $isAvailable = !isset($booked_ids[$busId]);
        $buses[] = [
            'id' => $busId,
            'bus_number' => $row['bus_number'],
            'available' => $isAvailable
        ];
    }
    
    $total_buses = count($buses);
    $booked_buses = count($booked_ids);
    $available_buses = $total_buses - $booked_buses;
    
    echo json_encode([
        'total_buses' => $total_buses,
        'booked_buses' => $booked_buses,
        'available_buses' => $available_buses,
        'can_book' => $available_buses >= $no_of_vehicles,
        'buses' => $buses
    ]);
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>

