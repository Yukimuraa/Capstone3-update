<?php
/**
 * Distance Calculator - Local only, no external APIs
 * Calculates distance from CHMSU to destination using Haversine formula
 */

require_once '../includes/negros_occidental_locations.php';

header('Content-Type: application/json');

$destination = isset($_POST['destination']) ? trim($_POST['destination']) : '';

if (empty($destination)) {
    echo json_encode(['error' => 'Destination is required']);
    exit;
}

$result = NegrosOccidentalLocations::getDistanceFromCHMSU($destination);

if (!$result) {
    echo json_encode(['error' => 'Location not found']);
    exit;
}

echo json_encode($result);
?>

