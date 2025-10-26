<?php
/**
 * Distance Calculator - Local only, no external APIs
 * Calculates distance between two locations (including campus-to-campus)
 */

require_once '../includes/negros_occidental_locations.php';

header('Content-Type: application/json');

$fromLocation = isset($_POST['from_location']) ? trim($_POST['from_location']) : '';
$toLocation = isset($_POST['to_location']) ? trim($_POST['to_location']) : '';

// Legacy support for old 'destination' parameter
if (empty($toLocation) && isset($_POST['destination'])) {
    $toLocation = trim($_POST['destination']);
    $fromLocation = 'CHMSU Talisay'; // Default from location
}

if (empty($fromLocation) || empty($toLocation)) {
    echo json_encode(['error' => 'Both locations are required']);
    exit;
}

// Check if both are CHMSU campuses (campus-to-campus)
$fromIsCHMSU = (stripos($fromLocation, 'chmsu') !== false || stripos($fromLocation, 'carlos hilado') !== false);
$toIsCHMSU = (stripos($toLocation, 'chmsu') !== false || stripos($toLocation, 'carlos hilado') !== false);

if ($fromIsCHMSU && $toIsCHMSU) {
    // Both are CHMSU campuses - calculate distance between them
    $fromData = NegrosOccidentalLocations::findLocation($fromLocation);
    $toData = NegrosOccidentalLocations::findLocation($toLocation);
    
    if (!$fromData || !$toData) {
        echo json_encode(['error' => 'One or both campus locations not found']);
        exit;
    }
    
    $distance = NegrosOccidentalLocations::calculateDistance(
        $fromData['lat'], 
        $fromData['lon'],
        $toData['lat'], 
        $toData['lon']
    );
    
    echo json_encode([
        'destination' => $toData['name'],
        'from_location' => $fromData['name'],
        'distance_km' => max($distance, 5), // Minimum 5km
        'total_distance_km' => max($distance, 5) * 2, // Round trip
        'lat' => $toData['lat'],
        'lon' => $toData['lon'],
        'type' => $toData['type']
    ]);
    exit;
}

// If FROM is CHMSU Talisay, calculate to destination
if (stripos($fromLocation, 'talisay') !== false && $fromIsCHMSU) {
    $result = NegrosOccidentalLocations::getDistanceFromCHMSU($toLocation);
    if ($result) {
        echo json_encode($result);
        exit;
    }
}

// If TO is CHMSU Talisay, calculate from origin
if (stripos($toLocation, 'talisay') !== false && $toIsCHMSU) {
    $result = NegrosOccidentalLocations::getDistanceFromCHMSU($fromLocation);
    if ($result) {
        echo json_encode($result);
        exit;
    }
}

// Default: calculate distance to destination from CHMSU Talisay
$result = NegrosOccidentalLocations::getDistanceFromCHMSU($toLocation);

if (!$result) {
    echo json_encode(['error' => 'Location not found']);
    exit;
}

echo json_encode($result);
?>


