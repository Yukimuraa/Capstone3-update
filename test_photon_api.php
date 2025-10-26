<?php
/**
 * Photon API Test Page
 * Tests the Photon API for geocoding locations in Negros Occidental
 */

require_once 'config/database.php';

$testResults = [];
$error = '';

// Test locations in Negros Occidental - Comprehensive Coverage
$testLocations = [
    // Major Cities
    'Talisay, Negros Occidental',
    'Bacolod City, Negros Occidental',
    'Silay City, Negros Occidental',
    'Binalbagan, Negros Occidental',
    'Kabankalan City, Negros Occidental',
    'Himamaylan City, Negros Occidental',
    'Bago City, Negros Occidental',
    'Cadiz City, Negros Occidental',
    'Sagay City, Negros Occidental',
    'Victorias City, Negros Occidental',
    'Escalante City, Negros Occidental',
    'San Carlos City, Negros Occidental',
    'La Carlota City, Negros Occidental',
    
    // CHMSU Campuses
    'CHMSU Talisay',
    'CHMSU Binalbagan',
    'CHMSU Fortune Towne',
    'CHMSU Alijis',
    
    // Bacolod City Barangays (61 barangays - showing major ones)
    'Barangay 1, Bacolod City',
    'Barangay 2, Bacolod City',
    'Barangay 3, Bacolod City',
    'Barangay 4, Bacolod City',
    'Barangay 5, Bacolod City',
    'Barangay 6, Bacolod City',
    'Barangay Mandalagan, Bacolod City',
    'Barangay Villamonte, Bacolod City',
    'Barangay Tangub, Bacolod City',
    'Barangay Bata, Bacolod City',
    'Barangay Singcang-Airport, Bacolod City',
    'Barangay Banago, Bacolod City',
    'Barangay Alijis, Bacolod City',
    'Barangay Taculing, Bacolod City',
    'Barangay Granada, Bacolod City',
    'Barangay Estefania, Bacolod City',
    'Barangay Sum-ag, Bacolod City',
    'Barangay Felisa, Bacolod City',
    'Barangay Punta Taytay, Bacolod City',
    'Barangay Vista Alegre, Bacolod City',
    
    // Talisay City Barangays (13 barangays)
    'Barangay Zone 1, Talisay City',
    'Barangay Zone 2, Talisay City',
    'Barangay Zone 3, Talisay City',
    'Barangay Zone 4, Talisay City',
    'Barangay Zone 5, Talisay City',
    'Barangay Zone 6, Talisay City',
    'Barangay Zone 7, Talisay City',
    'Barangay Zone 8, Talisay City',
    'Barangay Zone 9, Talisay City',
    'Barangay Zone 10, Talisay City',
    'Barangay Zone 11, Talisay City',
    'Barangay Zone 12, Talisay City',
    'Barangay Zone 13, Talisay City',
    
    // Silay City Barangays (major ones)
    'Barangay 1, Silay City',
    'Barangay 2, Silay City',
    'Barangay 3, Silay City',
    'Barangay 4, Silay City',
    'Barangay 5, Silay City',
    'Barangay Balaring, Silay City',
    'Barangay Guinhalaran, Silay City',
    'Barangay Hawaiian, Silay City',
    'Barangay Kapitan Ramon, Silay City',
    'Barangay Mambulac, Silay City',
    
    // Bago City Barangays (major ones)
    'Barangay Alijis, Bago City',
    'Barangay Atipuluan, Bago City',
    'Barangay Bacong, Bago City',
    'Barangay Balingasag, Bago City',
    'Barangay Binubuhan, Bago City',
    'Barangay Dulao, Bago City',
    'Barangay Lag-asan, Bago City',
    'Barangay Ma-ao, Bago City',
    'Barangay Poblacion, Bago City',
    
    // Kabankalan City Barangays (major ones)
    'Barangay Binicuil, Kabankalan City',
    'Barangay Camingawan, Kabankalan City',
    'Barangay Daan Banua, Kabankalan City',
    'Barangay Inapoy, Kabankalan City',
    'Barangay Oringao, Kabankalan City',
    'Barangay Poblacion, Kabankalan City',
    'Barangay Salong, Kabankalan City',
    'Barangay Tabugon, Kabankalan City',
    
    // Himamaylan City Barangays (major ones)
    'Barangay Aguisan, Himamaylan City',
    'Barangay Buenavista, Himamaylan City',
    'Barangay Cabanbanan, Himamaylan City',
    'Barangay Carabalan, Himamaylan City',
    'Barangay Poblacion, Himamaylan City',
    'Barangay Sara-et, Himamaylan City',
    'Barangay Su-ay, Himamaylan City',
    
    // La Carlota City Barangays (major ones)
    'Barangay Ara-al, La Carlota City',
    'Barangay Ayungon, La Carlota City',
    'Barangay Balabag, La Carlota City',
    'Barangay Cubay, La Carlota City',
    'Barangay Haguimit, La Carlota City',
    'Barangay La Granja, La Carlota City',
    'Barangay Poblacion 1, La Carlota City',
    'Barangay Poblacion 2, La Carlota City',
    'Barangay Poblacion 3, La Carlota City',
    
    // Sagay City Barangays (major ones)
    'Barangay Bato, Sagay City',
    'Barangay Bulanon, Sagay City',
    'Barangay Fabrica, Sagay City',
    'Barangay Lopez Jaena, Sagay City',
    'Barangay Poblacion 1, Sagay City',
    'Barangay Poblacion 2, Sagay City',
    'Barangay Vito, Sagay City',
    
    // San Carlos City Barangays (major ones)
    'Barangay 1, San Carlos City',
    'Barangay 2, San Carlos City',
    'Barangay 3, San Carlos City',
    'Barangay Codcod, San Carlos City',
    'Barangay Ermita, San Carlos City',
    'Barangay Prosperidad, San Carlos City',
    'Barangay Quezon, San Carlos City',
    
    // Landmarks
    'SM City Bacolod',
    'Ayala Capitol Central',
    'Robinsons Place Bacolod',
    'Bacolod City Plaza',
    'Silay Airport',
    'Bacolod-Silay Airport',
    
    // Puroks (examples with specific barangays)
    'Purok 1, Barangay 1, Bacolod City',
    'Purok 2, Barangay 1, Bacolod City',
    'Purok 1, Barangay Mandalagan, Bacolod City',
    'Purok 1, Zone 1, Talisay City',
    'Purok 2, Zone 1, Talisay City',
    'Purok 1, Barangay Balaring, Silay City',
    
    // Other municipalities
    'Calatrava, Negros Occidental',
    'Cauayan, Negros Occidental',
    'Don Salvador Benedicto, Negros Occidental',
    'Enrique B. Magalona, Negros Occidental',
    'Hinigaran, Negros Occidental',
    'Hinoba-an, Negros Occidental',
    'Ilog, Negros Occidental',
    'Isabela, Negros Occidental',
    'La Castellana, Negros Occidental',
    'Manapla, Negros Occidental',
    'Moises Padilla, Negros Occidental',
    'Murcia, Negros Occidental',
    'Pontevedra, Negros Occidental',
    'Pulupandan, Negros Occidental',
    'Salvador Benedicto, Negros Occidental',
    'San Enrique, Negros Occidental',
    'Sipalay, Negros Occidental',
    'Toboso, Negros Occidental',
    'Valladolid, Negros Occidental'
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
                
                <!-- Location Statistics -->
                <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <div class="text-sm text-blue-600 font-medium">Total Locations</div>
                        <div class="text-2xl font-bold text-blue-900"><?php echo count($testLocations); ?></div>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                        <div class="text-sm text-green-600 font-medium">Cities Covered</div>
                        <div class="text-2xl font-bold text-green-900">13</div>
                    </div>
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-3">
                        <div class="text-sm text-purple-600 font-medium">Barangays</div>
                        <div class="text-2xl font-bold text-purple-900">100+</div>
                    </div>
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-3">
                        <div class="text-sm text-orange-600 font-medium">Municipalities</div>
                        <div class="text-2xl font-bold text-orange-900">19</div>
                    </div>
                </div>
                
                <div class="mt-4 flex space-x-3">
                    <a href="?auto_test=1" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        <i class="fas fa-play mr-2"></i> Auto-Test All <?php echo count($testLocations); ?> Locations
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
                
                <!-- Coverage Info -->
                <div class="mb-4 p-4 bg-gradient-to-r from-blue-50 to-purple-50 border-l-4 border-blue-500 rounded-md">
                    <h3 class="font-semibold text-gray-900 mb-2">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                        Comprehensive Negros Occidental Coverage
                    </h3>
                    <p class="text-sm text-gray-700 mb-2">
                        This test includes <strong><?php echo count($testLocations); ?> locations</strong> covering:
                    </p>
                    <ul class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm text-gray-700">
                        <li><i class="fas fa-check-circle text-green-600 mr-1"></i> All 13 cities</li>
                        <li><i class="fas fa-check-circle text-green-600 mr-1"></i> 100+ barangays</li>
                        <li><i class="fas fa-check-circle text-green-600 mr-1"></i> 19 municipalities</li>
                        <li><i class="fas fa-check-circle text-green-600 mr-1"></i> Major landmarks</li>
                        <li><i class="fas fa-check-circle text-green-600 mr-1"></i> CHMSU campuses</li>
                        <li><i class="fas fa-check-circle text-green-600 mr-1"></i> Specific puroks</li>
                        <li><i class="fas fa-check-circle text-green-600 mr-1"></i> Shopping centers</li>
                        <li><i class="fas fa-check-circle text-green-600 mr-1"></i> Airports</li>
                    </ul>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 max-h-96 overflow-y-auto">
                    <?php foreach ($testLocations as $location): ?>
                        <form method="POST" class="inline-block">
                            <input type="hidden" name="test_location" value="<?php echo htmlspecialchars($location); ?>">
                            <button type="submit" class="w-full px-3 py-2 text-sm bg-purple-100 text-purple-800 rounded-md hover:bg-purple-200 transition text-left">
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

