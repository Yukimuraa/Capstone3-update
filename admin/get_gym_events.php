<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_admin();
header('Content-Type: application/json');

// Optional date range FullCalendar passes
$start = $_GET['start'] ?? null; // ISO date
$end = $_GET['end'] ?? null; // ISO date

$query = "SELECT b.booking_id, b.date as booking_date, b.start_time, b.end_time, b.status, u.name as user_name, b.purpose
          FROM bookings b
          JOIN users u ON u.id = b.user_id
          WHERE b.facility_type = 'gym'";

$params = [];
$types = '';

if ($start) {
    $query .= " AND b.date >= ?";
    $params[] = $start;
    $types .= 's';
}
if ($end) {
    $query .= " AND b.date < ?"; // FullCalendar 'end' is exclusive
    $params[] = $end;
    $types .= 's';
}

$query .= " ORDER BY b.date, b.start_time";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

$statusColors = [
    'pending' => '#fbbf24', // amber-400
    'confirmed' => '#10b981', // emerald-500
    'rejected' => '#ef4444', // red-500
    'cancelled' => '#6b7280'  // gray-500
];

$events = [];
while ($row = $res->fetch_assoc()) {
    $date = $row['booking_date'];
    $startDateTime = $date . 'T' . $row['start_time'];
    $endDateTime = $date . 'T' . $row['end_time'];
    $status = $row['status'];
    $color = $statusColors[$status] ?? '#6b7280';
    $events[] = [
        'id' => $row['booking_id'],
        'title' => $row['purpose'] ?: 'Gym Reservation',
        'start' => $startDateTime,
        'end' => $endDateTime,
        'color' => $color,
        'extendedProps' => [
            'status' => $status,
            'user' => $row['user_name']
        ]
    ];
}

echo json_encode($events);
<?php









