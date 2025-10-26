<?php
/**
 * Photon API Test Page
 * Tests the Photon API for geocoding locations in Negros Occidental
 */

require_once 'config/database.php';

$testResults = [];
$error = '';

// Test locations in Negros Occidental
$testLocations = [
    'Talisay, Negros Occidental',
    'Bacolod City, Negros Occidental',
    'CHMSU Talisay',
    'Barangay 5, Talisay City',
    'Purok 2, Barangay 1, Bacolod',
    'SM City Bacolod',
    'Silay Airport',
    'Binalbagan, Negros Occidental'
];

// Function to test Photon API
function testPhotonAPI($location) {
    $url = "https://photon.komoot.io/api/?q=" . urlencode($location) . "&limit=3";
    
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: CHMSU-Bus-System/1.0',
            'timeout' => 10
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return ['error' => 'Failed to connect to Photon API'];
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['features']) || empty($data['features'])) {
        return ['error' => 'No results found'];
    }
    
    $results = [];
    foreach ($data['features'] as $feature) {
        $props = $feature['properties'];
        $coords = $feature['geometry']['coordinates'];
        
        $results[] = [
            'name' => $props['name'] ?? 'Unknown',
            'city' => $props['city'] ?? '',
            'state' => $props['state'] ?? '',
            'country' => $props['country'] ?? '',
            'type' => $props['type'] ?? '',
            'lat' => $coords[1],
            'lon' => $coords[0],
            'display' => ($props['name'] ?? '') . ', ' . 
                        ($props['city'] ?? '') . ', ' . 
                        ($props['state'] ?? '') . ', ' . 
                        ($props['country'] ?? '')
        ];
    }
    
    return ['success' => true, 'results' => $results];
}

// Process test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_location'])) {
    $location = $_POST['test_location'];
    $testResults[$location] = testPhotonAPI($location);
}

// Auto-test all locations if requested
if (isset($_GET['auto_test'])) {
    foreach ($testLocations as $location) {
        $testResults[$location] = testPhotonAPI($location);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photon API Test - CHMSU Bus System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-map-marked-alt text-blue-600 mr-2"></i>
                    Photon API Test Tool
                </h1>
                <p class="text-gray-600">Test geocoding for Negros Occidental locations using Photon API (OpenStreetMap)</p>
                <div class="mt-4 flex space-x-3">
                    <a href="?auto_test=1" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        <i class="fas fa-play mr-2"></i> Auto-Test All Locations
                    </a>
                    <a href="test_photon_api.php" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                        <i class="fas fa-refresh mr-2"></i> Clear Results
                    </a>
                </div>
            </div>

            <!-- Manual Test Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-search-location text-green-600 mr-2"></i>
                    Manual Test
                </h2>
                <form method="POST" class="flex space-x-3">
                    <input type="text" name="test_location" 
                           placeholder="Enter location (e.g., Talisay, Negros Occidental)"
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           required>
                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        <i class="fas fa-search mr-2"></i> Test
                    </button>
                </form>
            </div>

            <!-- Predefined Test Locations -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-list text-purple-600 mr-2"></i>
                    Quick Test Locations
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                    <?php foreach ($testLocations as $location): ?>
                        <form method="POST" class="inline-block">
                            <input type="hidden" name="test_location" value="<?php echo htmlspecialchars($location); ?>">
                            <button type="submit" class="w-full px-3 py-2 text-sm bg-purple-100 text-purple-800 rounded-md hover:bg-purple-200 transition">
                                <i class="fas fa-map-marker-alt mr-1"></i>
                                <?php echo htmlspecialchars($location); ?>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Test Results -->
            <?php if (!empty($testResults)): ?>
                <div class="space-y-4">
                    <?php foreach ($testResults as $location => $result): ?>
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-semibold mb-3 flex items-center justify-between">
                                <span>
                                    <i class="fas fa-map-pin text-blue-600 mr-2"></i>
                                    <?php echo htmlspecialchars($location); ?>
                                </span>
                                <?php if (isset($result['error'])): ?>
                                    <span class="px-3 py-1 text-sm bg-red-100 text-red-800 rounded-full">
                                        <i class="fas fa-times-circle mr-1"></i> Failed
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 text-sm bg-green-100 text-green-800 rounded-full">
                                        <i class="fas fa-check-circle mr-1"></i> Success
                                    </span>
                                <?php endif; ?>
                            </h3>

                            <?php if (isset($result['error'])): ?>
                                <div class="bg-red-50 border border-red-200 rounded-md p-4">
                                    <p class="text-red-700">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        <?php echo htmlspecialchars($result['error']); ?>
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($result['results'] as $index => $item): ?>
                                        <div class="border border-gray-200 rounded-md p-4 bg-gray-50">
                                            <div class="flex items-start justify-between mb-2">
                                                <h4 class="font-semibold text-gray-900">
                                                    Result #<?php echo $index + 1; ?>: <?php echo htmlspecialchars($item['name']); ?>
                                                </h4>
                                                <?php if ($item['type']): ?>
                                                    <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                                                        <?php echo htmlspecialchars($item['type']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                                <div>
                                                    <span class="text-gray-600"><i class="fas fa-city mr-1"></i> City:</span>
                                                    <span class="font-medium"><?php echo htmlspecialchars($item['city']) ?: 'N/A'; ?></span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-600"><i class="fas fa-map mr-1"></i> State:</span>
                                                    <span class="font-medium"><?php echo htmlspecialchars($item['state']) ?: 'N/A'; ?></span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-600"><i class="fas fa-globe mr-1"></i> Country:</span>
                                                    <span class="font-medium"><?php echo htmlspecialchars($item['country']) ?: 'N/A'; ?></span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-600"><i class="fas fa-location-arrow mr-1"></i> Coordinates:</span>
                                                    <span class="font-medium"><?php echo number_format($item['lat'], 6); ?>, <?php echo number_format($item['lon'], 6); ?></span>
                                                </div>
                                            </div>
                                            <div class="mt-3 pt-3 border-t border-gray-300">
                                                <p class="text-sm text-gray-700">
                                                    <i class="fas fa-tag mr-1"></i>
                                                    <strong>Full Display:</strong> <?php echo htmlspecialchars($item['display']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- API Information -->
            <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                    About Photon API
                </h2>
                <div class="prose max-w-none text-gray-700">
                    <ul class="space-y-2">
                        <li><i class="fas fa-check text-green-600 mr-2"></i> <strong>Free to use</strong> - No API key required</li>
                        <li><i class="fas fa-check text-green-600 mr-2"></i> <strong>OpenStreetMap-based</strong> - Uses OSM data for geocoding</li>
                        <li><i class="fas fa-check text-green-600 mr-2"></i> <strong>Supports Philippine locations</strong> - Including barangays and puroks</li>
                        <li><i class="fas fa-check text-green-600 mr-2"></i> <strong>Returns structured JSON</strong> - Easy to parse and use</li>
                        <li><i class="fas fa-check text-green-600 mr-2"></i> <strong>Endpoint:</strong> <code class="bg-gray-100 px-2 py-1 rounded">https://photon.komoot.io/api/</code></li>
                    </ul>
                    <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                        <p class="text-sm">
                            <i class="fas fa-lightbulb text-yellow-600 mr-2"></i>
                            <strong>Tip:</strong> Photon API works best with specific location names like city names, landmarks, and major roads. 
                            For barangays and puroks, include the city name for better accuracy.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

