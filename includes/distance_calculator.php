<?php
/**
 * Distance Calculator using Photon API (OpenStreetMap) and OSRM
 * Free, no API key required
 * Works for Philippine locations including barangays and puroks
 */

class DistanceCalculator {
    
    private $photonApiUrl = "https://photon.komoot.io/api/";
    private $osrmApiUrl = "http://router.project-osrm.org/route/v1/driving/";
    
    /**
     * Geocode an address using Photon API
     * @param string $address - Full address (e.g., "Talisay, Negros Occidental, Philippines")
     * @return array|false - Returns ['lat' => float, 'lon' => float, 'display_name' => string] or false
     */
    public function geocodeAddress($address) {
        $url = $this->photonApiUrl . "?q=" . urlencode($address) . "&limit=1";
        
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
            return false;
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['features']) && count($data['features']) > 0) {
            $feature = $data['features'][0];
            return [
                'lat' => $feature['geometry']['coordinates'][1],
                'lon' => $feature['geometry']['coordinates'][0],
                'display_name' => $feature['properties']['name'] ?? $address,
                'city' => $feature['properties']['city'] ?? '',
                'state' => $feature['properties']['state'] ?? '',
                'country' => $feature['properties']['country'] ?? ''
            ];
        }
        
        return false;
    }
    
    /**
     * Search for locations with detailed results
     * @param string $query - Search query
     * @param int $limit - Number of results (default: 5)
     * @return array - Array of location results
     */
    public function searchLocations($query, $limit = 5) {
        $url = $this->photonApiUrl . "?q=" . urlencode($query) . "&limit=" . $limit;
        
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
            return [];
        }
        
        $data = json_decode($response, true);
        $results = [];
        
        if (isset($data['features'])) {
            foreach ($data['features'] as $feature) {
                $results[] = [
                    'lat' => $feature['geometry']['coordinates'][1],
                    'lon' => $feature['geometry']['coordinates'][0],
                    'name' => $feature['properties']['name'] ?? 'Unknown',
                    'city' => $feature['properties']['city'] ?? '',
                    'state' => $feature['properties']['state'] ?? '',
                    'country' => $feature['properties']['country'] ?? '',
                    'type' => $feature['properties']['type'] ?? '',
                    'display' => $this->formatDisplayName($feature['properties'])
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Calculate distance and duration between two locations using OSRM
     * @param string $origin - Origin address or "lat,lon"
     * @param string $destination - Destination address or "lat,lon"
     * @return array|false - Returns ['distance_km' => float, 'duration_minutes' => float, 'route' => array] or false
     */
    public function calculateDistance($origin, $destination) {
        // Check if origin and destination are already coordinates
        if (!$this->isCoordinates($origin)) {
            $originGeo = $this->geocodeAddress($origin);
            if (!$originGeo) {
                return false;
            }
            $originCoords = $originGeo['lon'] . "," . $originGeo['lat'];
        } else {
            $originCoords = $origin;
        }
        
        if (!$this->isCoordinates($destination)) {
            $destGeo = $this->geocodeAddress($destination);
            if (!$destGeo) {
                return false;
            }
            $destCoords = $destGeo['lon'] . "," . $destGeo['lat'];
        } else {
            $destCoords = $destination;
        }
        
        // Call OSRM API
        $url = $this->osrmApiUrl . $originCoords . ";" . $destCoords . "?overview=full&geometries=geojson";
        
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
            return false;
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['routes']) && count($data['routes']) > 0) {
            $route = $data['routes'][0];
            return [
                'distance_km' => round($route['distance'] / 1000, 2),
                'distance_meters' => $route['distance'],
                'duration_minutes' => round($route['duration'] / 60, 2),
                'duration_seconds' => $route['duration'],
                'geometry' => $route['geometry'] ?? null
            ];
        }
        
        return false;
    }
    
    /**
     * Calculate straight-line distance between two coordinates (fallback)
     * @param float $lat1, float $lon1, float $lat2, float $lon2
     * @return float - Distance in kilometers
     */
    public function calculateStraightLineDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;
        
        return round($distance, 2);
    }
    
    /**
     * Format display name from properties
     */
    private function formatDisplayName($properties) {
        $parts = [];
        
        if (!empty($properties['name'])) $parts[] = $properties['name'];
        if (!empty($properties['street'])) $parts[] = $properties['street'];
        if (!empty($properties['city'])) $parts[] = $properties['city'];
        if (!empty($properties['state'])) $parts[] = $properties['state'];
        if (!empty($properties['country'])) $parts[] = $properties['country'];
        
        return implode(', ', $parts);
    }
    
    /**
     * Check if string is coordinates format (lat,lon)
     */
    private function isCoordinates($string) {
        return preg_match('/^-?\d+\.?\d*,-?\d+\.?\d*$/', str_replace(' ', '', $string));
    }
}
?>

