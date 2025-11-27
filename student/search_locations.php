<?php
/**
 * Location Search API - Local only, no external APIs
 * Returns location suggestions for autocomplete
 */

require_once '../includes/negros_occidental_locations.php';

header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query) || strlen($query) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$results = NegrosOccidentalLocations::searchLocations($query, 10);

echo json_encode(['results' => $results]);
?>














































