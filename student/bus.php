<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is student
require_student();

$page_title = "Bus Schedule - CHMSU BAO";
$base_url = "..";

$error = '';
$success = '';

// Function to check bus availability
function checkBusAvailability($conn, $date_covered, $no_of_vehicles) {
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
    
    return [
        'total_buses' => $total_buses,
        'booked_buses' => $booked_buses,
        'available_buses' => $available_buses,
        'can_book' => $available_buses >= $no_of_vehicles
    ];
}

// Function to check if specific bus is available on a date
function isBusAvailable($conn, $bus_number, $date_covered) {
    $query = "SELECT COUNT(*) as booked 
              FROM bus_bookings bb 
              JOIN bus_schedules bs ON bb.schedule_id = bs.id 
              JOIN buses b ON bb.bus_id = b.id 
              WHERE b.bus_number = ? AND bs.date_covered = ? AND bb.status = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $bus_number, $date_covered);
    $stmt->execute();
    $result = $stmt->get_result();
    $booked = $result->fetch_assoc()['booked'];
    
    return $booked == 0;
}

// Function to get bus ID by bus number
function getBusIdByNumber($conn, $bus_number) {
    $query = "SELECT id FROM buses WHERE bus_number = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $bus_number);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['id'] : null;
}

// Smart location resolver for PHP - maps landmarks and schools to geocodable addresses
function resolveLocationPHP($input) {
    $normalized = strtolower(trim($input));
    
    // School and University mappings - OSM-friendly format
    $locationMap = [
        // CHMSU Campuses
        'chmsu' => 'Talisay, Negros Occidental, Philippines',
        'chmsu talisay' => 'Talisay, Negros Occidental, Philippines',
        'chmsu main' => 'Talisay, Negros Occidental, Philippines',
        'chmsu main campus' => 'Talisay, Negros Occidental, Philippines',
        'talisay main campus' => 'Talisay, Negros Occidental, Philippines',
        'central philippines state university' => 'Talisay, Negros Occidental, Philippines',
        'cpsu' => 'Talisay, Negros Occidental, Philippines',
        'carlos hilado' => 'Talisay, Negros Occidental, Philippines',
        'chmsc' => 'Talisay, Negros Occidental, Philippines',
        
        'chmsu binalbagan' => 'Binalbagan, Negros Occidental, Philippines',
        'binalbagan campus' => 'Binalbagan, Negros Occidental, Philippines',
        
        'chmsu fortune towne' => 'Bacolod, Negros Occidental, Philippines',
        'fortune towne campus' => 'Bacolod, Negros Occidental, Philippines',
        'fortune towne' => 'Bacolod, Negros Occidental, Philippines',
        
        'chmsu alijis' => 'Bacolod, Negros Occidental, Philippines',
        'alijis campus' => 'Bacolod, Negros Occidental, Philippines',
        
        // Major landmarks
        'sm city bacolod' => 'Bacolod, Negros Occidental, Philippines',
        'sm bacolod' => 'Bacolod, Negros Occidental, Philippines',
        'ayala bacolod' => 'Bacolod, Negros Occidental, Philippines',
        'robinsons bacolod' => 'Bacolod, Negros Occidental, Philippines',
        'bacolod airport' => 'Silay, Negros Occidental, Philippines',
        'silay airport' => 'Silay, Negros Occidental, Philippines',
        
        // Other schools
        'uc bacolod' => 'Bacolod, Negros Occidental, Philippines',
        'usls' => 'Bacolod, Negros Occidental, Philippines',
        'la salle bacolod' => 'Bacolod, Negros Occidental, Philippines',
        'uno-r' => 'Bacolod, Negros Occidental, Philippines'
    ];
    
    // Normalize barangay/zone format
    if (preg_match('/(?:barangay|brgy|zone|purok)\s*\d*\s*(?:,?\s*)?(talisay|bacolod|silay|binalbagan|kabankalan|himamaylan|bago|cadiz|sagay|victorias|escalante|san carlos|la carlota|ilog|isabela)/i', $input, $matches)) {
        $cityPart = ucfirst(strtolower($matches[1]));
        return $cityPart . ", Negros Occidental, Philippines";
    }
    
    // Check exact match
    if (isset($locationMap[$normalized])) {
        return $locationMap[$normalized];
    }
    
    // Check if input contains any known location
    foreach ($locationMap as $key => $value) {
        if (stripos($normalized, $key) !== false) {
            return $value;
        }
    }
    
    // Check if input contains city names
    $cities = [
        'bacolod' => 'Bacolod',
        'talisay' => 'Talisay',
        'silay' => 'Silay',
        'binalbagan' => 'Binalbagan',
        'kabankalan' => 'Kabankalan',
        'himamaylan' => 'Himamaylan',
        'bago' => 'Bago',
        'cadiz' => 'Cadiz',
        'sagay' => 'Sagay',
        'victorias' => 'Victorias',
        'escalante' => 'Escalante',
        'san carlos' => 'San Carlos',
        'la carlota' => 'La Carlota',
        'ilog' => 'Ilog',
        'isabela' => 'Isabela'
    ];
    
    foreach ($cities as $key => $value) {
        if (stripos($normalized, $key) !== false) {
            return $value . ", Negros Occidental, Philippines";
        }
    }
    
    // Return input with proper formatting
    return $input . ", Negros Occidental, Philippines";
}

// Geocode location using Photon API (OpenStreetMap-based)
function geocodeLocationPHP($location) {
    // Resolve location first
    $resolvedLocation = resolveLocationPHP($location);
    
    $url = "https://photon.komoot.io/api/?q=" . urlencode($resolvedLocation) . "&limit=1";
    
    // Set up context with User-Agent header
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: CHMSU-BAO-Bus-Booking-System\r\n",
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['features']) && count($data['features']) > 0) {
        $feature = $data['features'][0];
        $properties = $feature['properties'];
        $coords = $feature['geometry']['coordinates'];
        
        // Photon returns [lon, lat] format
        return [
            'lat' => floatval($coords[1]),
            'lon' => floatval($coords[0]),
            'display_name' => $properties['name'] ?? $resolvedLocation,
            'city' => $properties['city'] ?? '',
            'state' => $properties['state'] ?? ''
        ];
    }
    
    return null;
}

// HERE API configuration
define('HERE_API_KEY', 'eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6IjE5MjhkNGUyYjNkZjRkMjE4YjJlYTFiODhiZTkxNWYzIiwiaCI6Im11cm11cjY0In0=');

// CHMSU Talisay as the fixed origin point
define('CHMSU_ORIGIN_LAT', 10.7358);
define('CHMSU_ORIGIN_LON', 122.9853);
define('CHMSU_ORIGIN_NAME', 'CHMSU - Carlos Hilado Memorial State University, Talisay City, Negros Occidental');

// Database of coordinates for major locations in Negros Occidental
function getHardcodedCoordinatesPHP($location) {
    $normalized = strtolower(trim($location));
    
    $locationCoordinates = [
        // Cities
        'talisay' => ['lat' => 10.7358, 'lon' => 122.9853, 'name' => 'Talisay City, Negros Occidental'],
        'bacolod' => ['lat' => 10.6760, 'lon' => 122.9500, 'name' => 'Bacolod City, Negros Occidental'],
        'silay' => ['lat' => 10.8000, 'lon' => 122.9667, 'name' => 'Silay City, Negros Occidental'],
        'binalbagan' => ['lat' => 10.1906, 'lon' => 122.8608, 'name' => 'Binalbagan, Negros Occidental'],
        'kabankalan' => ['lat' => 9.9906, 'lon' => 122.8111, 'name' => 'Kabankalan City, Negros Occidental'],
        'himamaylan' => ['lat' => 10.0989, 'lon' => 122.8711, 'name' => 'Himamaylan City, Negros Occidental'],
        'bago' => ['lat' => 10.5383, 'lon' => 122.8358, 'name' => 'Bago City, Negros Occidental'],
        'cadiz' => ['lat' => 10.9506, 'lon' => 123.2897, 'name' => 'Cadiz City, Negros Occidental'],
        'sagay' => ['lat' => 10.8969, 'lon' => 123.4167, 'name' => 'Sagay City, Negros Occidental'],
        'victorias' => ['lat' => 10.8972, 'lon' => 123.0739, 'name' => 'Victorias City, Negros Occidental'],
        'escalante' => ['lat' => 10.8394, 'lon' => 123.5017, 'name' => 'Escalante City, Negros Occidental'],
        'san carlos' => ['lat' => 10.4775, 'lon' => 123.3806, 'name' => 'San Carlos City, Negros Occidental'],
        'la carlota' => ['lat' => 10.4222, 'lon' => 122.9194, 'name' => 'La Carlota City, Negros Occidental'],
        'ilog' => ['lat' => 10.0422, 'lon' => 122.7553, 'name' => 'Ilog, Negros Occidental'],
        'isabela' => ['lat' => 10.2142, 'lon' => 122.9711, 'name' => 'Isabela, Negros Occidental'],
        
        // Schools & Institutions (mapped to city centers)
        'chmsu' => ['lat' => 10.7358, 'lon' => 122.9853, 'name' => 'CHMSU Talisay Campus'],
        'chmsu talisay' => ['lat' => 10.7358, 'lon' => 122.9853, 'name' => 'CHMSU Talisay Main Campus'],
        'chmsu binalbagan' => ['lat' => 10.1906, 'lon' => 122.8608, 'name' => 'CHMSU Binalbagan Campus'],
        'central philippine state university' => ['lat' => 10.7358, 'lon' => 122.9853, 'name' => 'CHMSU Talisay']
    ];
    
    // Direct match
    if (isset($locationCoordinates[$normalized])) {
        return $locationCoordinates[$normalized];
    }
    
    // Check if location contains city name or school name
    foreach ($locationCoordinates as $city => $coords) {
        if (stripos($normalized, $city) !== false) {
            return $coords;
        }
    }
    
    return null;
}

// Calculate distance using Haversine formula
function calculateHaversineDistancePHP($lat1, $lon1, $lat2, $lon2) {
    $R = 6371; // Radius of Earth in kilometers
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $R * $c;
    
    return round($distance); // Round to nearest km
}

// Get coordinates for destination using HERE Geocoding API
function getDestinationCoordinatesHERE($location) {
    $normalized = strtolower(trim($location));
    
    // First try hardcoded coordinates
    $hardcoded = getHardcodedCoordinatesPHP($location);
    if ($hardcoded) {
        return $hardcoded;
    }
    
    // Resolve location for better geocoding
    $resolvedLocation = resolveLocationPHP($location);
    
    $url = "https://geocode.search.hereapi.com/v1/geocode?q=" . urlencode($resolvedLocation) . "&apikey=" . HERE_API_KEY;
    
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: CHMSU-BAO-Bus-Booking-System\r\n"
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (!empty($data['items']) && isset($data['items'][0])) {
        $item = $data['items'][0];
        return [
            'lat' => floatval($item['position']['lat']),
            'lon' => floatval($item['position']['lng']),
            'name' => $item['title'],
            'display_name' => $item['address']['label']
        ];
    }
    
    return null;
}

// Calculate distance using HERE Routing API
function calculateDistanceHERE($destination) {
    // Get destination coordinates
    $destCoords = getDestinationCoordinatesHERE($destination);
    
    if (!$destCoords) {
        return null;
    }
    
    // HERE Routing API URL
    $url = "https://router.hereapi.com/v8/routes?" . http_build_query([
        'transportMode' => 'car',
        'origin' => CHMSU_ORIGIN_LAT . ',' . CHMSU_ORIGIN_LON,
        'destination' => $destCoords['lat'] . ',' . $destCoords['lon'],
        'return' => 'summary',
        'apikey' => HERE_API_KEY
    ]);
    
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: CHMSU-BAO-Bus-Booking-System\r\n"
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        // Fallback to Haversine calculation
        return calculateHaversineDistancePHP(
            CHMSU_ORIGIN_LAT, CHMSU_ORIGIN_LON,
            $destCoords['lat'], $destCoords['lon']
        );
    }
    
    $data = json_decode($response, true);
    
    if (!empty($data['routes']) && isset($data['routes'][0]['sections'][0]['summary']['length'])) {
        $distanceMeters = $data['routes'][0]['sections'][0]['summary']['length'];
        $distanceKm = round($distanceMeters / 1000);
        return $distanceKm;
    }
    
    // Fallback to Haversine calculation
    return calculateHaversineDistancePHP(
        CHMSU_ORIGIN_LAT, CHMSU_ORIGIN_LON,
        $destCoords['lat'], $destCoords['lon']
    );
}

// Function to get distance between two locations using HERE API (CHMSU as origin)
function getDistanceBetweenLocations($from, $to) {
    // Always use CHMSU Talisay as the origin point
    $origin = 'CHMSU - Carlos Hilado Memorial State University, Talisay City, Negros Occidental';
    
    // If "from" is not CHMSU, use "to" as destination
    $destination = $to;
    
    // If "from" is CHMSU, use "to" as destination
    if (stripos($from, 'chmsu') !== false || stripos($from, 'talisay') !== false) {
        $destination = $to;
    } else {
        // If "to" is CHMSU, use "from" as destination
        if (stripos($to, 'chmsu') !== false || stripos($to, 'talisay') !== false) {
            $destination = $from;
        } else {
            // If neither is CHMSU, use "to" as destination (CHMSU is always origin)
            $destination = $to;
        }
    }
    
    // Try HERE API first for accurate routing
    $distance = calculateDistanceHERE($destination);
    
    if ($distance !== null) {
        return $distance > 0 ? $distance : 5; // Minimum 5 km if very close
    }
    
    // Fallback: Try hardcoded coordinates
    $destCoords = getHardcodedCoordinatesPHP($destination);
    if ($destCoords) {
        $distance = calculateHaversineDistancePHP(
            CHMSU_ORIGIN_LAT, CHMSU_ORIGIN_LON,
            $destCoords['lat'], $destCoords['lon']
        );
        return $distance > 0 ? $distance : 5;
    }
    
    // Final fallback: Try OpenStreetMap
    $destCoords = geocodeLocationPHP($destination);
    if ($destCoords) {
        $distance = calculateHaversineDistancePHP(
            CHMSU_ORIGIN_LAT, CHMSU_ORIGIN_LON,
            $destCoords['lat'], $destCoords['lon']
        );
        return $distance > 0 ? $distance : 5;
    }
    
    // Ultimate fallback
    return 50;
}

// Function to calculate billing statement
function calculateBillingStatement($from_location, $to_location, $destination, $no_of_vehicles, $no_of_days) {
    // Get distance between locations
    $distance_km = getDistanceBetweenLocations($from_location, $to_location);
    $total_distance_km = $distance_km * 2; // Round trip
    
    // Default values
    $fuel_rate = 70.00;
    
    // Cost calculations per vehicle
    $computed_distance = $distance_km; // 2Km/L rate
    $runtime_liters = 25.00; // Default runtime in liters
    
    $fuel_cost = $computed_distance * $fuel_rate;
    $runtime_cost = $runtime_liters * $fuel_rate;
    $maintenance_cost = 5000.00;
    $standby_cost = 1500.00;
    $additive_cost = 1500.00;
    $rate_per_bus = 1500.00;
    
    $subtotal_per_vehicle = $fuel_cost + $runtime_cost + $maintenance_cost + $standby_cost + $additive_cost + $rate_per_bus;
    $total_amount = $subtotal_per_vehicle * $no_of_vehicles;
    
    return [
        'from_location' => $from_location,
        'to_location' => $to_location,
        'distance_km' => $distance_km,
        'total_distance_km' => $total_distance_km,
        'fuel_rate' => $fuel_rate,
        'computed_distance' => $computed_distance,
        'runtime_liters' => $runtime_liters,
        'fuel_cost' => $fuel_cost,
        'runtime_cost' => $runtime_cost,
        'maintenance_cost' => $maintenance_cost,
        'standby_cost' => $standby_cost,
        'additive_cost' => $additive_cost,
        'rate_per_bus' => $rate_per_bus,
        'subtotal_per_vehicle' => $subtotal_per_vehicle,
        'total_amount' => $total_amount
    ];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Handle cancel request
        if ($_POST['action'] === 'cancel_request') {
            $schedule_id = intval($_POST['schedule_id']);
            
            // Verify the schedule belongs to the current user and is pending
            $verify_stmt = $conn->prepare("SELECT status FROM bus_schedules WHERE id = ? AND user_id = ?");
            $verify_stmt->bind_param("ii", $schedule_id, $_SESSION['user_id']);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            
            if ($verify_result->num_rows === 0) {
                $error = 'Request not found or you do not have permission to cancel it.';
            } else {
                $schedule_data = $verify_result->fetch_assoc();
                
                if ($schedule_data['status'] !== 'pending') {
                    $error = 'Only pending requests can be cancelled.';
                } else {
                    // Update status to cancelled
                    $cancel_stmt = $conn->prepare("UPDATE bus_schedules SET status = 'cancelled' WHERE id = ?");
                    $cancel_stmt->bind_param("i", $schedule_id);
                    
                    if ($cancel_stmt->execute()) {
                        $success = 'Bus request cancelled successfully!';
                    } else {
                        $error = 'Error cancelling request: ' . $conn->error;
                    }
                }
            }
        }
        elseif ($_POST['action'] === 'add_schedule') {
            $school_name = sanitize_input($_POST['school_name']);
            $client = sanitize_input($_POST['client']);
            $from_location = sanitize_input($_POST['from_location']);
            $to_location = sanitize_input($_POST['to_location']);
            $destination = $from_location . ' - ' . $to_location; // Combined for destination field
            $purpose = sanitize_input($_POST['purpose']);
            $date_covered = $_POST['date_covered'];
            $vehicle = sanitize_input($_POST['vehicle']);
            $bus_no = sanitize_input($_POST['bus_no']);
            $no_of_days = intval($_POST['no_of_days']);
            $no_of_vehicles = intval($_POST['no_of_vehicles']);
            
            // Check if specific bus is available
            if (!isBusAvailable($conn, $bus_no, $date_covered)) {
                $error = "Bus {$bus_no} is already booked for {$date_covered}. Please select a different bus or date.";
            } elseif (empty($school_name) || empty($client) || empty($from_location) || empty($to_location) || empty($purpose) || empty($date_covered) || empty($vehicle) || empty($bus_no) || empty($no_of_days) || empty($no_of_vehicles)) {
                $error = 'All required fields must be filled.';
            } elseif ($from_location === $to_location) {
                $error = 'From and To locations must be different.';
            } else {
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Combine school name and client organization
                    $full_client = $school_name . ' - ' . $client;
                    
                    // Insert bus schedule
                    $stmt = $conn->prepare("INSERT INTO bus_schedules (client, destination, purpose, date_covered, vehicle, bus_no, no_of_days, no_of_vehicles, user_id, user_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'student', 'pending')");
                    $stmt->bind_param("ssssssiii", $full_client, $destination, $purpose, $date_covered, $vehicle, $bus_no, $no_of_days, $no_of_vehicles, $_SESSION['user_id']);
                    
                    if ($stmt->execute()) {
                        $schedule_id = $conn->insert_id;
                        
                        // Get bus ID and create bus booking record
                        $bus_id = getBusIdByNumber($conn, $bus_no);
                        if (!$bus_id) {
                            throw new Exception('Invalid bus number');
                        }
                        
                        // Insert bus booking record
                        $bus_booking_stmt = $conn->prepare("INSERT INTO bus_bookings (schedule_id, bus_id, booking_date, status) VALUES (?, ?, ?, 'active')");
                        $bus_booking_stmt->bind_param("iis", $schedule_id, $bus_id, $date_covered);
                        
                        if (!$bus_booking_stmt->execute()) {
                            throw new Exception('Error creating bus booking: ' . $conn->error);
                        }
                        
                        // Calculate billing statement
                        $billing = calculateBillingStatement($from_location, $to_location, $destination, $no_of_vehicles, $no_of_days);
                        
                        // Insert billing statement
                        $billing_stmt = $conn->prepare("INSERT INTO billing_statements 
                            (schedule_id, client, destination, purpose, date_covered, no_of_days, vehicle, bus_no, no_of_vehicles,
                             from_location, to_location, distance_km, total_distance_km, fuel_rate, computed_distance, runtime_liters,
                             fuel_cost, runtime_cost, maintenance_cost, standby_cost, additive_cost, rate_per_bus, subtotal_per_vehicle, total_amount,
                             prepared_by, recommending_approval) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        
                        $prepared_by = $_SESSION['user_name'] ?? 'Student';
                        $recommending = 'NEUYER JAN C. BALA-AN, Director, Business Affairs Office';
                        
                        $billing_stmt->bind_param("isssisissdddddddddddddddss", 
                            $schedule_id, 
                            $full_client, 
                            $destination, 
                            $purpose, 
                            $date_covered, 
                            $no_of_days, 
                            $vehicle, 
                            $bus_no, 
                            $no_of_vehicles,
                            $billing['from_location'], 
                            $billing['to_location'], 
                            $billing['distance_km'], 
                            $billing['total_distance_km'], 
                            $billing['fuel_rate'], 
                            $billing['computed_distance'], 
                            $billing['runtime_liters'],
                            $billing['fuel_cost'], 
                            $billing['runtime_cost'], 
                            $billing['maintenance_cost'], 
                            $billing['standby_cost'], 
                            $billing['additive_cost'], 
                            $billing['rate_per_bus'], 
                            $billing['subtotal_per_vehicle'], 
                            $billing['total_amount'],
                            $prepared_by, 
                            $recommending);
                        
                        if ($billing_stmt->execute()) {
                            $conn->commit();
                            $success = 'Bus schedule request submitted successfully! Billing statement generated. You will be notified once it\'s approved.';
                        } else {
                            throw new Exception('Error creating billing statement: ' . $conn->error);
                        }
                    } else {
                        throw new Exception('Error saving schedule: ' . $conn->error);
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = $e->getMessage();
                }
            }
        }
    }
}

// Get all bus schedules with billing information
$user_schedules_query = "SELECT bs.*, bst.total_amount, bst.payment_status 
                        FROM bus_schedules bs 
                        LEFT JOIN billing_statements bst ON bs.id = bst.schedule_id 
                        WHERE bs.user_id = ? 
                        ORDER BY bs.created_at DESC LIMIT 20";
$user_schedules_stmt = $conn->prepare($user_schedules_query);
$user_schedules_stmt->bind_param("i", $_SESSION['user_id']);
$user_schedules_stmt->execute();
$user_schedules = $user_schedules_stmt->get_result();

// Get statistics for current user
$stats_query = "SELECT 
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_requests
    FROM bus_schedules 
    WHERE user_id = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $_SESSION['user_id']);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/student_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Bus Schedule Management</h1>
                    <p class="text-sm text-gray-500">Request and manage your bus transportation needs</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700"><?php echo $_SESSION['user_name']; ?></span>
                    <button class="md:hidden rounded-md p-2 inline-flex items-center justify-center text-gray-500 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-emerald-500" id="menu-button">
                        <span class="sr-only">Open menu</span>
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </header>
        
        <!-- Main content -->
        <main class="flex-1 overflow-y-auto p-4">
            <div class="max-w-7xl mx-auto">
                <?php if ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium"><?php echo $success; ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
        <?php if ($error): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium"><?php echo $error; ?></p>
                            </div>
                        </div>
                    </div>
        <?php endif; ?>
                
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-bus text-blue-600 text-2xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Requests</dt>
                                        <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_requests']; ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Approved</dt>
                                        <dd class="text-lg font-medium text-gray-900"><?php echo $stats['approved_requests']; ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
                                        <dd class="text-lg font-medium text-gray-900"><?php echo $stats['pending_requests']; ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-times-circle text-red-600 text-2xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Rejected</dt>
                                        <dd class="text-lg font-medium text-gray-900"><?php echo $stats['rejected_requests']; ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold tracking-tight text-gray-900">My Bus Schedules</h2>
                    <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="openAddModal()">
                        <i class="fas fa-plus mr-2"></i> New Request
                    </button>
                </div>
                
                <!-- Schedules Table -->
                <div class="bg-white shadow overflow-hidden sm:rounded-md">
                    <?php if ($user_schedules->num_rows > 0): ?>
                        <ul class="divide-y divide-gray-200">
                            <?php while ($schedule = $user_schedules->fetch_assoc()): ?>
                                <li class="px-6 py-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-bus text-blue-600 text-xl"></i>
                                            </div>
                                            <div class="ml-4">
                                                <div class="flex items-center">
                                                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($schedule['destination']); ?></p>
                                                    <?php
                                                    // Display status badge based on actual status
                                                    $status_class = '';
                                                    $status_text = '';
                                                    $status_icon = '';
                                                    
                                                    switch($schedule['status']) {
                                                        case 'pending':
                                                            $status_class = 'bg-yellow-100 text-yellow-800';
                                                            $status_text = 'Pending';
                                                            $status_icon = 'fa-clock';
                                                            break;
                                                        case 'approved':
                                                            $status_class = 'bg-green-100 text-green-800';
                                                            $status_text = 'Approved';
                                                            $status_icon = 'fa-check-circle';
                                                            break;
                                                        case 'rejected':
                                                            $status_class = 'bg-red-100 text-red-800';
                                                            $status_text = 'Rejected';
                                                            $status_icon = 'fa-times-circle';
                                                            break;
                                                        case 'cancelled':
                                                            $status_class = 'bg-gray-100 text-gray-800';
                                                            $status_text = 'Cancelled';
                                                            $status_icon = 'fa-ban';
                                                            break;
                                                        case 'completed':
                                                            $status_class = 'bg-blue-100 text-blue-800';
                                                            $status_text = 'Completed';
                                                            $status_icon = 'fa-flag-checkered';
                                                            break;
                                                        default:
                                                            $status_class = 'bg-gray-100 text-gray-800';
                                                            $status_text = ucfirst($schedule['status']);
                                                            $status_icon = 'fa-info-circle';
                                                    }
                                                    ?>
                                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                                                        <i class="fas <?php echo $status_icon; ?> mr-1"></i>
                                                        <?php echo $status_text; ?>
                                                    </span>
                                                </div>
                                                <div class="mt-1">
                                                    <p class="text-sm text-gray-500">
                                                        <i class="fas fa-calendar mr-1"></i>
                                                        <?php echo date('M d, Y', strtotime($schedule['date_covered'])); ?>
                                                        <span class="mx-2">•</span>
                                                        <i class="fas fa-building mr-1"></i>
                                                        <?php echo htmlspecialchars($schedule['client']); ?>
                                                        <span class="mx-2">•</span>
                                                        <i class="fas fa-bus mr-1"></i>
                                                        Bus #<?php echo htmlspecialchars($schedule['bus_no']); ?>
                                                    </p>
                                                </div>
                                                <div class="mt-1">
                                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($schedule['purpose']); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-gray-500">
                                                <?php echo $schedule['no_of_days']; ?> day<?php echo $schedule['no_of_days'] > 1 ? 's' : ''; ?>
                                            </span>
                                            <?php if (isset($schedule['total_amount']) && $schedule['total_amount'] > 0): ?>
                                                <span class="text-sm font-semibold text-green-600">
                                                    ₱<?php echo number_format($schedule['total_amount'], 2); ?>
                                                </span>
                                            <?php endif; ?>
                                            <button type="button" class="text-blue-600 hover:text-blue-900" title="View Details" onclick="viewSchedule(<?php echo htmlspecialchars(json_encode($schedule)); ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($schedule['status'] === 'pending'): ?>
                                                <button type="button" class="text-red-600 hover:text-red-900" title="Cancel Request" onclick="openCancelModal(<?php echo $schedule['id']; ?>, '<?php echo htmlspecialchars($schedule['destination'], ENT_QUOTES); ?>')">
                                                    <i class="fas fa-times-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if (isset($schedule['total_amount']) && $schedule['total_amount'] > 0): ?>
                                                <button type="button" class="text-green-600 hover:text-green-900" title="Print Receipt" onclick="printReceipt(<?php echo $schedule['id']; ?>)">
                                                    <i class="fas fa-print"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <i class="fas fa-bus text-gray-400 text-6xl mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No bus schedules yet</h3>
                            <p class="text-gray-500 mb-4">Get started by creating your first bus schedule request.</p>
                            <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700" onclick="openAddModal()">
                                <i class="fas fa-plus mr-2"></i> Create Request
                            </button>
                        </div>
        <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Schedule Modal -->
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-2xl max-h-screen overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-medium text-gray-900">New Bus Schedule Request</h3>
            <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeAddModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_schedule">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="school_name" class="block text-sm font-medium text-gray-700 mb-1">School Name *</label>
                    <input type="text" id="school_name" name="school_name" required 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                           placeholder="e.g., CHMSU - Central Philippine State University"
                           value="<?php echo isset($_POST['school_name']) ? htmlspecialchars($_POST['school_name']) : 'CHMSU - Central Philippine State University'; ?>">
                </div>
                
                <div>
                    <label for="client" class="block text-sm font-medium text-gray-700 mb-1">Client/Organization *</label>
                    <input type="text" id="client" name="client" required 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                           placeholder="e.g., OSAS, Student Council, BSIT Department"
                           value="<?php echo isset($_POST['client']) ? htmlspecialchars($_POST['client']) : ''; ?>">
                </div>
                
                <div>
                    <label for="from_location" class="block text-sm font-medium text-gray-700 mb-1">From Location *</label>
                    <input type="text" id="from_location" name="from_location" 
                           required 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                           placeholder="CHMSU Talisay (Fixed Origin)"
                           value="CHMSU - Carlos Hilado Memorial State University, Talisay City"
                           readonly
                           style="background-color: #f3f4f6; cursor: not-allowed;">
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-university text-blue-600 mr-1"></i>
                        Fixed origin: CHMSU Talisay Campus
                    </p>
                </div>
                
                <div>
                    <label for="to_location" class="block text-sm font-medium text-gray-700 mb-1">To Location *</label>
                    <div class="relative">
                        <input type="text" id="to_location" name="to_location" 
                               required 
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                               placeholder="Type city, barangay, or purok (e.g., Bacolod, Barangay 5 Silay, Purok 2 Talisay)"
                               value=""
                               autocomplete="off"
                               oninput="handleLocationInput(event)"
                               onfocus="showLocationSuggestions()"
                               onblur="hideLocationSuggestions()">
                        <div id="location-suggestions" class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-y-auto">
                            <!-- Suggestions will be populated here -->
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-route text-green-600 mr-1"></i>
                        Distance calculated from CHMSU using Photon API (geocoding) + HERE/OSRM (routing)
                    </p>
                </div>
                
                <div class="md:col-span-2">
                    <div id="distance-display" class="hidden p-3 rounded-md text-sm bg-blue-100 text-blue-800">
                        <div class="flex items-center justify-between">
                            <span><i class="fas fa-route mr-2"></i>Distance (one-way):</span>
                            <span class="font-bold" id="distance-km">0 km</span>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <span><i class="fas fa-exchange-alt mr-2"></i>Total Distance (round trip):</span>
                            <span class="font-bold" id="total-distance-km">0 km</span>
                        </div>
                    </div>
                </div>
                
                <div class="md:col-span-2">
                    <label for="purpose" class="block text-sm font-medium text-gray-700 mb-1">Purpose *</label>
                    <input type="text" id="purpose" name="purpose" required 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                           value="<?php echo isset($_POST['purpose']) ? htmlspecialchars($_POST['purpose']) : 'Bayanihan'; ?>">
                </div>
                
                <div>
                    <label for="date_covered" class="block text-sm font-medium text-gray-700 mb-1">Date of Travel *</label>
                    <input type="date" id="date_covered" name="date_covered" required 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                           value="<?php echo isset($_POST['date_covered']) ? htmlspecialchars($_POST['date_covered']) : ''; ?>"
                           onchange="checkAvailability()">
                </div>
                
                <div>
                    <label for="vehicle" class="block text-sm font-medium text-gray-700 mb-1">Vehicle Type *</label>
                    <select id="vehicle" name="vehicle" required 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                            onchange="toggleBusNumber()">
                        <option value="Bus" selected>Bus</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        Only Bus is supported for now
                    </p>
                </div>
                
                <div>
                    <label for="bus_no" class="block text-sm font-medium text-gray-700 mb-1">Bus Number *</label>
                    <select id="bus_no" name="bus_no" required 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                            onchange="checkBusAvailability()">
                        <option value="1">Bus 1</option>
                        <option value="2">Bus 2</option>
                        <option value="3">Bus 3</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1" id="bus-availability-hint">
                        <i class="fas fa-bus mr-1"></i>
                        Availability updates when you pick a date
                    </p>
                </div>
                
                <div>
                    <label for="no_of_days" class="block text-sm font-medium text-gray-700 mb-1">Number of Days *</label>
                    <input type="number" id="no_of_days" name="no_of_days" min="1" max="30" required 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                           value="<?php echo isset($_POST['no_of_days']) ? htmlspecialchars($_POST['no_of_days']) : '1'; ?>">
                </div>
                
                <div>
                    <label for="no_of_vehicles" class="block text-sm font-medium text-gray-700 mb-1">Number of Vehicles *</label>
                    <input type="number" id="no_of_vehicles" name="no_of_vehicles" min="1" max="3" required 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                           value="<?php echo isset($_POST['no_of_vehicles']) ? htmlspecialchars($_POST['no_of_vehicles']) : '1'; ?>"
                           onchange="checkAvailability()">
                    <p class="text-xs text-gray-500 mt-1">Maximum 3 vehicles available</p>
                </div>
                
                <div class="md:col-span-2">
                    <div id="availability-status" class="hidden p-3 rounded-md text-sm">
                        <!-- Availability status will be shown here -->
                    </div>
                </div>
                
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2" onclick="closeAddModal()">
                    Cancel
                </button>
                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <i class="fas fa-paper-plane mr-2"></i> Submit Request
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Schedule Modal -->
<div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-medium text-gray-900">Schedule Details</h3>
            <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeViewModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div id="schedule-details" class="space-y-4">
            <!-- Schedule details will be populated here -->
        </div>
        
        <div class="flex justify-end mt-6">
            <button type="button" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2" onclick="closeViewModal()">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Cancel Request Confirmation Modal -->
<div id="cancelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <div class="flex items-center mb-4">
            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-medium text-gray-900">Cancel Bus Request</h3>
            </div>
        </div>
        
        <div class="mb-6">
            <p class="text-sm text-gray-600 mb-4">Are you sure you want to cancel this bus request?</p>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm font-medium text-gray-700">Destination:</p>
                <p class="text-sm text-gray-900" id="cancel-destination"></p>
            </div>
            <p class="text-xs text-gray-500 mt-3">
                <i class="fas fa-info-circle mr-1"></i>
                This action cannot be undone. The request status will be changed to "Cancelled".
            </p>
        </div>
        
        <form method="POST" id="cancelForm">
            <input type="hidden" name="action" value="cancel_request">
            <input type="hidden" name="schedule_id" id="cancel-schedule-id">
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeCancelModal()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    No, Keep It
                </button>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <i class="fas fa-times-circle mr-2"></i>
                    Yes, Cancel Request
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Mobile menu toggle
document.getElementById('menu-button').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('-translate-x-full');
});

// Modal functions
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
    // Update distance display with default values
    updateDistance();
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

function viewSchedule(schedule) {
    const details = document.getElementById('schedule-details');
    details.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-500">School & Organization</label>
                <p class="text-sm text-gray-900">${schedule.client}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Destination</label>
                <p class="text-sm text-gray-900">${schedule.destination}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Purpose</label>
                <p class="text-sm text-gray-900">${schedule.purpose}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Date of Travel</label>
                <p class="text-sm text-gray-900">${new Date(schedule.date_covered).toLocaleDateString()}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Vehicle Type</label>
                <p class="text-sm text-gray-900">${schedule.vehicle}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Vehicle Number</label>
                <p class="text-sm text-gray-900">${schedule.bus_no}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Number of Days</label>
                <p class="text-sm text-gray-900">${schedule.no_of_days}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Number of Vehicles</label>
                <p class="text-sm text-gray-900">${schedule.no_of_vehicles}</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-500">Requested On</label>
                <p class="text-sm text-gray-900">${new Date(schedule.created_at).toLocaleString()}</p>
            </div>
        </div>
    `;
    document.getElementById('viewModal').classList.remove('hidden');
}

function closeViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
}

function openCancelModal(scheduleId, destination) {
    document.getElementById('cancel-schedule-id').value = scheduleId;
    document.getElementById('cancel-destination').textContent = destination;
    document.getElementById('cancelModal').classList.remove('hidden');
}

function closeCancelModal() {
    document.getElementById('cancelModal').classList.add('hidden');
}

// HERE API configuration
const HERE_API_KEY = 'eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6IjE5MjhkNGUyYjNkZjRkMjE4YjJlYTFiODhiZTkxNWYzIiwiaCI6Im11cm11cjY0In0=';

// CHMSU Talisay as the fixed origin point
const CHMSU_ORIGIN_LAT = 10.7358;
const CHMSU_ORIGIN_LON = 122.9853;
const CHMSU_ORIGIN_NAME = 'CHMSU - Carlos Hilado Memorial State University, Talisay City, Negros Occidental';

// Local suggestions for Negros Occidental
const localSuggestions = [
    // Major Cities
    'Bacolod City, Negros Occidental',
    'Talisay City, Negros Occidental', 
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
    'Ilog, Negros Occidental',
    'Isabela, Negros Occidental',
    
    // Schools & Institutions
    'CHMSU Talisay Main Campus',
    'CHMSU Binalbagan Campus',
    'CHMSU Fortune Towne Campus',
    'CHMSU Alijis Campus',
    'University of Negros Occidental - Recoletos',
    'La Salle Bacolod',
    'University of St. La Salle',
    
    // Landmarks
    'SM City Bacolod',
    'Ayala Capitol Central',
    'Robinsons Place Bacolod',
    'Bacolod City Plaza',
    'Silay Airport',
    'Bacolod Airport',
    
    // Common Barangays (examples)
    'Barangay 1, Bacolod City',
    'Barangay 2, Bacolod City',
    'Barangay 3, Bacolod City',
    'Barangay 4, Bacolod City',
    'Barangay 5, Bacolod City',
    'Barangay 1, Talisay City',
    'Barangay 2, Talisay City',
    'Barangay 3, Talisay City',
    'Barangay 1, Silay City',
    'Barangay 2, Silay City',
    'Barangay 3, Silay City',
    
    // Common Puroks (examples)
    'Purok 1, Barangay 1, Bacolod City',
    'Purok 2, Barangay 1, Bacolod City',
    'Purok 3, Barangay 1, Bacolod City',
    'Purok 1, Barangay 2, Talisay City',
    'Purok 2, Barangay 2, Talisay City',
    'Purok 1, Barangay 1, Silay City',
    'Purok 2, Barangay 1, Silay City'
];

// Database of coordinates for major locations in Negros Occidental
const locationCoordinates = {
    // Cities
    'talisay': { lat: 10.7358, lon: 122.9853, name: 'Talisay City, Negros Occidental' },
    'bacolod': { lat: 10.6760, lon: 122.9500, name: 'Bacolod City, Negros Occidental' },
    'silay': { lat: 10.8000, lon: 122.9667, name: 'Silay City, Negros Occidental' },
    'binalbagan': { lat: 10.1906, lon: 122.8608, name: 'Binalbagan, Negros Occidental' },
    'kabankalan': { lat: 9.9906, lon: 122.8111, name: 'Kabankalan City, Negros Occidental' },
    'himamaylan': { lat: 10.0989, lon: 122.8711, name: 'Himamaylan City, Negros Occidental' },
    'bago': { lat: 10.5383, lon: 122.8358, name: 'Bago City, Negros Occidental' },
    'cadiz': { lat: 10.9506, lon: 123.2897, name: 'Cadiz City, Negros Occidental' },
    'sagay': { lat: 10.8969, lon: 123.4167, name: 'Sagay City, Negros Occidental' },
    'victorias': { lat: 10.8972, lon: 123.0739, name: 'Victorias City, Negros Occidental' },
    'escalante': { lat: 10.8394, lon: 123.5017, name: 'Escalante City, Negros Occidental' },
    'san carlos': { lat: 10.4775, lon: 123.3806, name: 'San Carlos City, Negros Occidental' },
    'la carlota': { lat: 10.4222, lon: 122.9194, name: 'La Carlota City, Negros Occidental' },
    'ilog': { lat: 10.0422, lon: 122.7553, name: 'Ilog, Negros Occidental' },
    'isabela': { lat: 10.2142, lon: 122.9711, name: 'Isabela, Negros Occidental' },
    
    // Schools & Institutions (mapped to city centers)
    'chmsu': { lat: 10.7358, lon: 122.9853, name: 'CHMSU Talisay Campus' },
    'chmsu talisay': { lat: 10.7358, lon: 122.9853, name: 'CHMSU Talisay Main Campus' },
    'chmsu binalbagan': { lat: 10.1906, lon: 122.8608, name: 'CHMSU Binalbagan Campus' },
    'central philippine state university': { lat: 10.7358, lon: 122.9853, name: 'CHMSU Talisay' }
};

// Get coordinates from hardcoded database
function getHardcodedCoordinates(location) {
    const normalized = location.toLowerCase().trim();
    
    // Direct city match
    if (locationCoordinates[normalized]) {
        return locationCoordinates[normalized];
    }
    
    // Check if location contains a city name
    for (const [city, coords] of Object.entries(locationCoordinates)) {
        if (normalized.includes(city)) {
            return coords;
        }
    }
    
    return null;
}

// Haversine formula to calculate distance between two coordinates
function calculateHaversineDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Radius of Earth in kilometers
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    const distance = R * c;
    return Math.round(distance); // Round to nearest km
}

// Get destination coordinates using HERE Geocoding API
async function getDestinationCoordinatesHERE(location) {
    // First try hardcoded coordinates
    const hardcoded = getHardcodedCoordinates(location);
    if (hardcoded) {
        return hardcoded;
    }
    
    // Resolve location for better geocoding
    const resolvedLocation = resolveLocation(location);
    
    const url = `https://geocode.search.hereapi.com/v1/geocode?q=${encodeURIComponent(resolvedLocation)}&apikey=${HERE_API_KEY}`;
    
    try {
        const response = await fetch(url, {
            headers: {
                'User-Agent': 'CHMSU-BAO-Bus-Booking-System'
            }
        });
        const data = await response.json();
        
        if (data.items && data.items.length > 0) {
            const item = data.items[0];
            return {
                lat: parseFloat(item.position.lat),
                lon: parseFloat(item.position.lng),
                name: item.title,
                display_name: item.address.label
            };
        }
        return null;
    } catch (error) {
        console.error('HERE Geocoding error:', error);
        return null;
    }
}

// Calculate distance using HERE Routing API
async function calculateDistanceHERE(destination) {
    // Get destination coordinates
    const destCoords = await getDestinationCoordinatesHERE(destination);
    
    if (!destCoords) {
        return null;
    }
    
    // HERE Routing API URL
    const url = `https://router.hereapi.com/v8/routes?transportMode=car&origin=${CHMSU_ORIGIN_LAT},${CHMSU_ORIGIN_LON}&destination=${destCoords.lat},${destCoords.lon}&return=summary&apikey=${HERE_API_KEY}`;
    
    try {
        const response = await fetch(url, {
            headers: {
                'User-Agent': 'CHMSU-BAO-Bus-Booking-System'
            }
        });
        const data = await response.json();
        
        if (data.routes && data.routes[0] && data.routes[0].sections && data.routes[0].sections[0] && data.routes[0].sections[0].summary && data.routes[0].sections[0].summary.length) {
            const distanceMeters = data.routes[0].sections[0].summary.length;
            const distanceKm = Math.round(distanceMeters / 1000);
            return distanceKm;
        }
        
        // Fallback to Haversine calculation
        return calculateHaversineDistance(
            CHMSU_ORIGIN_LAT, CHMSU_ORIGIN_LON,
            destCoords.lat, destCoords.lon
        );
    } catch (error) {
        console.error('HERE Routing error:', error);
        // Fallback to Haversine calculation
        return calculateHaversineDistance(
            CHMSU_ORIGIN_LAT, CHMSU_ORIGIN_LON,
            destCoords.lat, destCoords.lon
        );
    }
}

// Searchable dropdown functions
let suggestionTimeout = null;

function handleLocationInput(event) {
    const input = event.target.value;
    const suggestionsDiv = document.getElementById('location-suggestions');
    
    // Clear previous timeout
    if (suggestionTimeout) {
        clearTimeout(suggestionTimeout);
    }
    
    if (input.length < 2) {
        suggestionsDiv.classList.add('hidden');
        return;
    }
    
    // Debounce the search
    suggestionTimeout = setTimeout(() => {
        showSuggestions(input);
    }, 300);
}

function showLocationSuggestions() {
    const input = document.getElementById('to_location').value;
    if (input.length >= 2) {
        showSuggestions(input);
    }
}

function hideLocationSuggestions() {
    // Delay hiding to allow clicking on suggestions
    setTimeout(() => {
        const suggestionsDiv = document.getElementById('location-suggestions');
        suggestionsDiv.classList.add('hidden');
    }, 200);
}

async function showSuggestions(query) {
    const suggestionsDiv = document.getElementById('location-suggestions');
    const normalizedQuery = query.toLowerCase().trim();
    
    // Filter local suggestions
    const localMatches = localSuggestions.filter(suggestion => 
        suggestion.toLowerCase().includes(normalizedQuery)
    ).slice(0, 5);
    
    // Get HERE Autosuggest results
    let hereSuggestions = [];
    try {
        const url = `https://autosuggest.search.hereapi.com/v1/autosuggest?q=${encodeURIComponent(query)}&at=${CHMSU_ORIGIN_LAT},${CHMSU_ORIGIN_LON}&limit=5&apikey=${HERE_API_KEY}`;
        const response = await fetch(url, {
            headers: {
                'User-Agent': 'CHMSU-BAO-Bus-Booking-System'
            }
        });
        const data = await response.json();
        
        if (data.items) {
            hereSuggestions = data.items
                .filter(item => item.title && item.title.toLowerCase().includes('negros'))
                .slice(0, 3)
                .map(item => item.title);
        }
    } catch (error) {
        console.error('HERE Autosuggest error:', error);
    }
    
    // Combine and deduplicate suggestions
    const allSuggestions = [...localMatches, ...hereSuggestions];
    const uniqueSuggestions = [...new Set(allSuggestions)].slice(0, 8);
    
    if (uniqueSuggestions.length === 0) {
        suggestionsDiv.classList.add('hidden');
        return;
    }
    
    // Display suggestions
    suggestionsDiv.innerHTML = uniqueSuggestions.map(suggestion => `
        <div class="px-4 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0" 
             onclick="selectSuggestion('${suggestion.replace(/'/g, "\\'")}')">
            <div class="flex items-center">
                <i class="fas fa-map-marker-alt text-blue-500 mr-2"></i>
                <span class="text-sm">${suggestion}</span>
            </div>
        </div>
    `).join('');
    
    suggestionsDiv.classList.remove('hidden');
}

function selectSuggestion(suggestion) {
    document.getElementById('to_location').value = suggestion;
    document.getElementById('location-suggestions').classList.add('hidden');
    updateDistance();
}

// Keyboard navigation for dropdown
document.addEventListener('keydown', function(event) {
    const suggestionsDiv = document.getElementById('location-suggestions');
    const input = document.getElementById('to_location');
    
    if (document.activeElement !== input || suggestionsDiv.classList.contains('hidden')) {
        return;
    }
    
    const suggestions = suggestionsDiv.querySelectorAll('div[onclick]');
    const currentActive = suggestionsDiv.querySelector('.bg-blue-100');
    let activeIndex = -1;
    
    if (currentActive) {
        activeIndex = Array.from(suggestions).indexOf(currentActive);
    }
    
    switch(event.key) {
        case 'ArrowDown':
            event.preventDefault();
            activeIndex = Math.min(activeIndex + 1, suggestions.length - 1);
            break;
        case 'ArrowUp':
            event.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            break;
        case 'Enter':
            event.preventDefault();
            if (currentActive) {
                const suggestion = currentActive.textContent.trim();
                selectSuggestion(suggestion);
            }
            return;
        case 'Escape':
            suggestionsDiv.classList.add('hidden');
            return;
        default:
            return;
    }
    
    // Update active suggestion
    suggestions.forEach((suggestion, index) => {
        if (index === activeIndex) {
            suggestion.classList.add('bg-blue-100');
        } else {
            suggestion.classList.remove('bg-blue-100');
        }
    });
});

// Smart location resolver - maps common landmarks, schools, and institutions to geocodable addresses
function resolveLocation(input) {
    const normalized = input.toLowerCase().trim();
    
    // School and University mappings - using OSM-friendly formats
    const locationMap = {
        // CHMSU Campuses
        'chmsu': 'Talisay, Negros Occidental, Philippines',
        'chmsu talisay': 'Talisay, Negros Occidental, Philippines',
        'chmsu main': 'Talisay, Negros Occidental, Philippines',
        'chmsu main campus': 'Talisay, Negros Occidental, Philippines',
        'talisay main campus': 'Talisay, Negros Occidental, Philippines',
        'central philippines state university': 'Talisay, Negros Occidental, Philippines',
        'cpsu': 'Talisay, Negros Occidental, Philippines',
        'carlos hilado': 'Talisay, Negros Occidental, Philippines',
        'chmsc': 'Talisay, Negros Occidental, Philippines',
        
        'chmsu binalbagan': 'Binalbagan, Negros Occidental, Philippines',
        'binalbagan campus': 'Binalbagan, Negros Occidental, Philippines',
        
        'chmsu fortune towne': 'Bacolod, Negros Occidental, Philippines',
        'fortune towne campus': 'Bacolod, Negros Occidental, Philippines',
        'fortune towne': 'Bacolod, Negros Occidental, Philippines',
        
        'chmsu alijis': 'Bacolod, Negros Occidental, Philippines',
        'alijis campus': 'Bacolod, Negros Occidental, Philippines',
        
        // Major landmarks
        'sm city bacolod': 'Bacolod, Negros Occidental, Philippines',
        'sm bacolod': 'Bacolod, Negros Occidental, Philippines',
        'ayala bacolod': 'Bacolod, Negros Occidental, Philippines',
        'ayala capitol central': 'Bacolod, Negros Occidental, Philippines',
        'robinsons bacolod': 'Bacolod, Negros Occidental, Philippines',
        'bacolod airport': 'Silay, Negros Occidental, Philippines',
        'silay airport': 'Silay, Negros Occidental, Philippines',
        'bacolod city plaza': 'Bacolod, Negros Occidental, Philippines',
        'bacolod plaza': 'Bacolod, Negros Occidental, Philippines',
        
        // Other schools
        'uc bacolod': 'Bacolod, Negros Occidental, Philippines',
        'usls': 'Bacolod, Negros Occidental, Philippines',
        'la salle bacolod': 'Bacolod, Negros Occidental, Philippines',
        'university of negros occidental': 'Bacolod, Negros Occidental, Philippines',
        'uno-r': 'Bacolod, Negros Occidental, Philippines',
        
        // City halls
        'bacolod city hall': 'Bacolod, Negros Occidental, Philippines',
        'talisay city hall': 'Talisay, Negros Occidental, Philippines',
        'silay city hall': 'Silay, Negros Occidental, Philippines'
    };
    
    // Normalize barangay/zone format (e.g., "Barangay 5 Silay city" -> "Silay, Negros Occidental")
    const barangayPattern = /(?:barangay|brgy|zone|purok)\s*\d*\s*(?:,?\s*)?(talisay|bacolod|silay|binalbagan|kabankalan|himamaylan|bago|cadiz|sagay|victorias|escalante|san carlos|la carlota|ilog|isabela)/i;
    const barangayMatch = input.match(barangayPattern);
    if (barangayMatch) {
        const cityPart = barangayMatch[1].charAt(0).toUpperCase() + barangayMatch[1].slice(1).toLowerCase();
        return `${cityPart}, Negros Occidental, Philippines`;
    }
    
    // Check if input matches any known location (exact match)
    if (locationMap[normalized]) {
        return locationMap[normalized];
    }
    
    // Check if input contains any known location as substring
    for (const [key, value] of Object.entries(locationMap)) {
        if (normalized.includes(key)) {
            return value;
        }
    }
    
    // If input contains city names, extract and format them
    const cities = {
        'bacolod': 'Bacolod',
        'talisay': 'Talisay',
        'silay': 'Silay',
        'binalbagan': 'Binalbagan',
        'kabankalan': 'Kabankalan',
        'himamaylan': 'Himamaylan',
        'bago': 'Bago',
        'cadiz': 'Cadiz',
        'sagay': 'Sagay',
        'victorias': 'Victorias',
        'escalante': 'Escalante',
        'san carlos': 'San Carlos',
        'la carlota': 'La Carlota',
        'ilog': 'Ilog',
        'isabela': 'Isabela'
    };
    
    for (const [key, value] of Object.entries(cities)) {
        if (normalized.includes(key)) {
            return `${value}, Negros Occidental, Philippines`;
        }
    }
    
    // Return original input with proper formatting
    return `${input}, Negros Occidental, Philippines`;
}

// Geocode location using Photon API (OpenStreetMap-based)
async function geocodeLocation(location) {
    // Resolve location to geocodable address first
    const resolvedLocation = resolveLocation(location);
    
    const url = `https://photon.komoot.io/api/?q=${encodeURIComponent(resolvedLocation)}&limit=1`;
    
    try {
        const response = await fetch(url, {
            headers: {
                'User-Agent': 'CHMSU-BAO-Bus-Booking-System'
            }
        });
        const data = await response.json();
        
        if (data.features && data.features.length > 0) {
            const feature = data.features[0];
            const properties = feature.properties;
            const coords = feature.geometry.coordinates;
            
            // Photon returns [lon, lat] format
            return {
                lat: parseFloat(coords[1]),
                lon: parseFloat(coords[0]),
                display_name: properties.name || resolvedLocation,
                city: properties.city || '',
                state: properties.state || '',
                original_input: location,
                resolved_to: resolvedLocation
            };
        }
        return null;
    } catch (error) {
        console.error('Photon geocoding error:', error);
        return null;
    }
}

// Calculate and display distance using HERE API (CHMSU as origin)
async function updateDistance() {
    const fromLocation = document.getElementById('from_location').value.trim();
    const toLocation = document.getElementById('to_location').value.trim();
    const distanceDisplay = document.getElementById('distance-display');
    
    if (!fromLocation || !toLocation) {
        distanceDisplay.classList.add('hidden');
        return;
    }
    
    if (fromLocation.toLowerCase() === toLocation.toLowerCase()) {
        distanceDisplay.classList.remove('hidden');
        distanceDisplay.className = 'p-3 rounded-md text-sm bg-red-100 text-red-800';
        distanceDisplay.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Please enter different locations for departure and destination.';
        return;
    }
    
    // Show loading state
    distanceDisplay.classList.remove('hidden');
    distanceDisplay.className = 'p-3 rounded-md text-sm bg-gray-100 text-gray-700';
    distanceDisplay.innerHTML = `
        <div class="flex items-center">
            <div class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
            <span>Calculating distance from CHMSU using Photon + HERE API...</span>
        </div>
    `;
    
    try {
        // Determine destination (always from CHMSU to destination)
        let destination = toLocation;
        let originName = CHMSU_ORIGIN_NAME;
        
        // If "from" is CHMSU, use "to" as destination
        if (fromLocation.toLowerCase().includes('chmsu') || fromLocation.toLowerCase().includes('talisay')) {
            destination = toLocation;
        } else if (toLocation.toLowerCase().includes('chmsu') || toLocation.toLowerCase().includes('talisay')) {
            // If "to" is CHMSU, use "from" as destination
            destination = fromLocation;
        } else {
            // If neither is CHMSU, use "to" as destination (CHMSU is always origin)
            destination = toLocation;
        }
        
        // Try HERE API first for accurate routing
        let distance = await calculateDistanceHERE(destination);
        let usedHERE = false;
        let destCoords = null;
        
        if (distance !== null) {
            usedHERE = true;
            destCoords = await getDestinationCoordinatesHERE(destination);
        } else {
            // Fallback: Try hardcoded coordinates
            const hardcoded = getHardcodedCoordinates(destination);
            if (hardcoded) {
                distance = calculateHaversineDistance(
                    CHMSU_ORIGIN_LAT, CHMSU_ORIGIN_LON,
                    hardcoded.lat, hardcoded.lon
                );
                destCoords = hardcoded;
            } else {
                // Final fallback: Try OpenStreetMap
                const osmCoords = await geocodeLocation(destination);
                if (osmCoords) {
                    distance = calculateHaversineDistance(
                        CHMSU_ORIGIN_LAT, CHMSU_ORIGIN_LON,
                        osmCoords.lat, osmCoords.lon
                    );
                    destCoords = osmCoords;
                }
            }
        }
        
        if (!distance || !destCoords) {
            distanceDisplay.className = 'p-3 rounded-md text-sm bg-yellow-100 text-yellow-800';
            distanceDisplay.innerHTML = `
                <div>
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span class="font-semibold">Location not found</span>
                </div>
                <div class="mt-2 text-xs">
                    "${destination}" could not be found. Try using city names like: Talisay, Bacolod, Silay, Binalbagan, etc.
                </div>
                <div class="mt-2 text-xs text-gray-600">
                    Using estimated distance: <span class="font-bold">50 km</span> (one-way), <span class="font-bold">100 km</span> (round trip)
                </div>
            `;
            return;
        }
        
        const totalDistance = distance * 2; // Round trip
        
        // Display result
        distanceDisplay.className = 'p-3 rounded-md text-sm bg-green-100 text-green-800 border border-green-300';
        
        distanceDisplay.innerHTML = `
            <div class="flex items-center justify-between">
                <span><i class="fas fa-route mr-2"></i>Distance (one-way):</span>
                <span class="font-bold text-lg">${distance} km</span>
            </div>
            <div class="flex items-center justify-between mt-1">
                <span><i class="fas fa-exchange-alt mr-2"></i>Total Distance (round trip):</span>
                <span class="font-bold text-lg">${totalDistance} km</span>
            </div>
            <div class="mt-3 pt-2 border-t border-green-200 text-xs text-gray-700">
                <div class="mb-2">
                    <i class="fas fa-university mr-1 text-blue-600"></i>
                    <span class="font-semibold">From:</span> ${originName}
                </div>
                <div>
                    <i class="fas fa-map-marker-alt mr-1 text-green-600"></i>
                    <span class="font-semibold">To:</span> ${destCoords.name || destCoords.display_name}
                </div>
            </div>
            <div class="mt-2 text-xs text-gray-600 flex items-center">
                <i class="fas fa-${usedHERE ? 'route' : 'database'} mr-1"></i>
                ${usedHERE ? 'Calculated using Photon API + HERE routing' : 'Calculated using Photon API + coordinates database'}
            </div>
        `;
        
    } catch (error) {
        console.error('Distance calculation error:', error);
        distanceDisplay.className = 'p-3 rounded-md text-sm bg-red-100 text-red-800';
        distanceDisplay.innerHTML = `
            <div>
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span class="font-semibold">Error calculating distance</span>
            </div>
            <div class="mt-2 text-xs">
                Using estimated distance: <span class="font-bold">50 km</span> (one-way), <span class="font-bold">100 km</span> (round trip)
            </div>
        `;
    }
}

// Check bus availability
function checkAvailability() {
    const date = document.getElementById('date_covered').value;
    const vehicles = document.getElementById('no_of_vehicles').value;
    const statusDiv = document.getElementById('availability-status');
    const busNoSelect = document.getElementById('bus_no');
    const hint = document.getElementById('bus-availability-hint');
    
    if (!date || !vehicles) {
        statusDiv.classList.add('hidden');
        return;
    }
    
    // Make AJAX request to check availability
    fetch('check_bus_availability.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `date_covered=${date}&no_of_vehicles=${vehicles}`
    })
    .then(response => response.json())
    .then(data => {
        statusDiv.classList.remove('hidden');
        if (data.can_book) {
            statusDiv.className = 'p-3 rounded-md text-sm bg-green-100 text-green-800';
            statusDiv.innerHTML = `<i class="fas fa-check-circle mr-2"></i>${data.available_buses} buses available for this date.`;
        } else {
            statusDiv.className = 'p-3 rounded-md text-sm bg-red-100 text-red-800';
            statusDiv.innerHTML = `<i class="fas fa-exclamation-circle mr-2"></i>Only ${data.available_buses} buses available, but you requested ${vehicles}.`;
        }

        // Update Bus Number select options (1,2,3) availability
        const availabilityByNumber = { '1': true, '2': true, '3': true };
        if (data.buses) {
            data.buses.forEach(bus => {
                if (availabilityByNumber.hasOwnProperty(bus.bus_number)) {
                    availabilityByNumber[bus.bus_number] = bus.available;
                }
            });
        }

        // Enable/disable options
        Array.from(busNoSelect.options).forEach(opt => {
            const isAvail = availabilityByNumber[opt.value] !== false;
            opt.disabled = !isAvail;
            opt.textContent = `Bus ${opt.value}${!isAvail ? ' (Not available)' : ''}`;
        });

        // If selected option becomes unavailable, switch to first available
        if (busNoSelect.options[busNoSelect.selectedIndex]?.disabled) {
            const firstAvail = Array.from(busNoSelect.options).find(o => !o.disabled);
            if (firstAvail) busNoSelect.value = firstAvail.value;
        }

        // Update hint
        const availableList = Object.keys(availabilityByNumber).filter(k => availabilityByNumber[k]);
        hint.innerHTML = availableList.length
            ? `<i class=\"fas fa-bus mr-1\"></i>Available: Bus ${availableList.join(', Bus ')}`
            : `<i class=\"fas fa-bus mr-1\"></i>No buses available on selected date`;
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Check bus availability when bus number changes
function checkBusAvailability() {
    const date = document.getElementById('date_covered').value;
    const busNo = document.getElementById('bus_no').value;
    const hint = document.getElementById('bus-availability-hint');
    
    if (!date) {
        hint.innerHTML = '<i class="fas fa-bus mr-1"></i>Please select a date first';
        return;
    }
    
    // Make AJAX request to check specific bus availability
    fetch('check_bus_availability.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `date_covered=${date}&no_of_vehicles=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.buses) {
            const selectedBus = data.buses.find(bus => bus.bus_number === busNo);
            if (selectedBus) {
                if (selectedBus.available) {
                    hint.innerHTML = `<i class="fas fa-check-circle text-green-600 mr-1"></i>Bus ${busNo} is available for ${date}`;
                    hint.className = 'text-xs text-green-600 mt-1';
                } else {
                    hint.innerHTML = `<i class="fas fa-times-circle text-red-600 mr-1"></i>Bus ${busNo} is NOT available for ${date}`;
                    hint.className = 'text-xs text-red-600 mt-1';
                }
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        hint.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i>Error checking availability';
        hint.className = 'text-xs text-yellow-600 mt-1';
    });
}

// Print receipt
function printReceipt(scheduleId) {
    window.open(`print_bus_receipt.php?id=${scheduleId}`, '_blank');
}

// Close modals when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('fixed')) {
        closeAddModal();
        closeViewModal();
        closeCancelModal();
    }
    
    // Close location suggestions when clicking outside
    const suggestionsDiv = document.getElementById('location-suggestions');
    const toLocationInput = document.getElementById('to_location');
    
    if (!suggestionsDiv.contains(event.target) && event.target !== toLocationInput) {
        suggestionsDiv.classList.add('hidden');
    }
});
</script>

<?php include '../includes/footer.php'; ?>