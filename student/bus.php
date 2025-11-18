<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/negros_occidental_locations.php';

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

// LOCAL DISTANCE CALCULATION - No APIs required
// Uses hardcoded coordinates for all Negros Occidental locations

// Function to get distance between two locations
function getDistanceBetweenLocations($from, $to) {
    require_once '../includes/negros_occidental_locations.php';
    
    // Check if both are CHMSU campuses (campus to campus)
    $fromIsCHMSU = (stripos($from, 'chmsu') !== false || stripos($from, 'carlos hilado') !== false);
    $toIsCHMSU = (stripos($to, 'chmsu') !== false || stripos($to, 'carlos hilado') !== false);
    
    if ($fromIsCHMSU && $toIsCHMSU) {
        // Both are CHMSU campuses - calculate distance between them
        $fromLocation = NegrosOccidentalLocations::findLocation($from);
        $toLocation = NegrosOccidentalLocations::findLocation($to);
        
        if ($fromLocation && $toLocation) {
            $distance = NegrosOccidentalLocations::calculateDistance(
                $fromLocation['lat'], 
                $fromLocation['lon'],
                $toLocation['lat'], 
                $toLocation['lon']
            );
            return max($distance, 5); // Minimum 5km
        }
    }
    
    // If FROM is CHMSU Talisay, calculate to destination
    if (stripos($from, 'talisay') !== false && $fromIsCHMSU) {
        $result = NegrosOccidentalLocations::getDistanceFromCHMSU($to);
        if ($result) {
            return max($result['distance_km'], 5);
        }
    }
    
    // If TO is CHMSU Talisay, calculate from origin
    if (stripos($to, 'talisay') !== false && $toIsCHMSU) {
        $result = NegrosOccidentalLocations::getDistanceFromCHMSU($from);
        if ($result) {
            return max($result['distance_km'], 5);
        }
    }
    
    // Default: use TO as destination from CHMSU Talisay
    $result = NegrosOccidentalLocations::getDistanceFromCHMSU($to);
    if ($result) {
        return max($result['distance_km'], 5);
    }
    
    // Ultimate fallback
    return 50;
}

// Function to calculate billing statement
function calculateBillingStatement($from_location, $to_location, $destination, $no_of_vehicles, $no_of_days, $to_location_continuation = '') {
    global $conn;
    
    // Get distance from CHMSU to first location
    $distance_km = getDistanceBetweenLocations($from_location, $to_location);
    
    // If continuation exists, add distance from first location to continuation
    if (!empty($to_location_continuation)) {
        $continuation_distance = getDistanceBetweenLocations($to_location, $to_location_continuation);
        $distance_km += $continuation_distance;
    }
    
    $total_distance_km = $distance_km * 2; // Round trip
    
    // Get current fuel rate from settings (default to 70.00 if not set)
    $fuel_rate = 70.00;
    $fuel_rate_query = $conn->query("SELECT setting_value FROM bus_settings WHERE setting_key = 'fuel_rate'");
    if ($fuel_rate_query && $fuel_rate_query->num_rows > 0) {
        $fuel_rate = floatval($fuel_rate_query->fetch_assoc()['setting_value']);
    }
    
    // Cost calculations per vehicle
    $computed_distance = $distance_km; // 2Km/L rate
    
    // Get cost breakdown settings from database (with defaults)
    $cost_settings = [
        'runtime_liters' => 25.00,
        'maintenance_cost' => 5000.00,
        'standby_cost' => 1500.00,
        'additive_cost' => 1500.00,
        'rate_per_bus' => 1500.00
    ];
    
    // Load cost settings from database
    foreach ($cost_settings as $key => $default_value) {
        $setting_query = $conn->query("SELECT setting_value FROM bus_settings WHERE setting_key = '$key'");
        if ($setting_query && $setting_query->num_rows > 0) {
            $cost_settings[$key] = floatval($setting_query->fetch_assoc()['setting_value']);
        }
    }
    
    $runtime_liters = $cost_settings['runtime_liters'];
    $maintenance_cost = $cost_settings['maintenance_cost'];
    $standby_cost = $cost_settings['standby_cost'];
    $additive_cost = $cost_settings['additive_cost'];
    $rate_per_bus = $cost_settings['rate_per_bus'];
    
    $fuel_cost = $computed_distance * $fuel_rate;
    $runtime_cost = $runtime_liters * $fuel_rate;
    
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
            $to_location_continuation = isset($_POST['to_location_continuation']) ? trim(sanitize_input($_POST['to_location_continuation'])) : '';
            
            // Build destination string with optional continuation
            $destination = $from_location . ' - ' . $to_location;
            if (!empty($to_location_continuation)) {
                $destination .= ' - ' . $to_location_continuation;
            }
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
                        
                        // Update bus status to booked when booking is created
                        $update_bus_status = $conn->prepare("UPDATE buses SET status = 'booked' WHERE id = ?");
                        $update_bus_status->bind_param("i", $bus_id);
                        $update_bus_status->execute();
                        
                        // Calculate billing statement (include continuation if provided)
                        $billing = calculateBillingStatement($from_location, $to_location, $destination, $no_of_vehicles, $no_of_days, $to_location_continuation);
                        
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

// Get all available buses
$buses_query = "SELECT id, bus_number, vehicle_type, capacity, status FROM buses ORDER BY bus_number ASC";
$buses_result = $conn->query($buses_query);
$available_buses = [];
while ($bus = $buses_result->fetch_assoc()) {
    $available_buses[] = $bus;
}
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
        
        <form id="busRequestForm" method="POST" action="">
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
                        <i class="fas fa-database text-green-600 mr-1"></i>
                        Distance calculated from CHMSU using local database (all Negros Occidental locations)
                    </p>
                </div>
                
                <div>
                    <label for="to_location_continuation" class="block text-sm font-medium text-gray-700 mb-1">To Another Location (Optional)</label>
                    <div class="relative">
                        <input type="text" id="to_location_continuation" name="to_location_continuation" 
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                               placeholder="e.g., CHMSU Fortune Towne Campus, CHMSU Binalbagan Campus"
                               value=""
                               autocomplete="off"
                               oninput="handleLocationInputContinuation(event); updateDistance();"
                               onfocus="showLocationSuggestionsContinuation()"
                               onblur="hideLocationSuggestionsContinuation()"
                               onchange="updateDistance()">
                        <div id="location-suggestions-continuation" class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-y-auto">
                            <!-- Suggestions will be populated here -->
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-info-circle text-blue-600 mr-1"></i>
                        Optional: Add a continuation destination (e.g., another CHMSU campus)
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
                        <option value="">Select Bus</option>
                        <?php foreach ($available_buses as $bus): ?>
                            <option value="<?php echo htmlspecialchars($bus['bus_number']); ?>">
                                Bus <?php echo htmlspecialchars($bus['bus_number']); ?> 
                                (<?php echo htmlspecialchars($bus['vehicle_type']); ?> - <?php echo $bus['capacity']; ?> seats)
                            </option>
                        <?php endforeach; ?>
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
                <button type="button" onclick="showConfirmModal()" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <i class="fas fa-paper-plane mr-2"></i> Submit Request
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">
                <i class="fas fa-check-circle text-blue-600 mr-2"></i>Confirm Bus Request
            </h3>
            <button onclick="closeConfirmModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="mb-6">
            <p class="text-gray-600 mb-4">Please review your request details before submitting:</p>
            
            <div class="bg-gray-50 rounded-lg p-4 space-y-3 text-sm">
                <div class="flex justify-between border-b border-gray-200 pb-2">
                    <span class="text-gray-600 font-medium">School:</span>
                    <span class="text-gray-900 font-semibold text-right" id="confirm-school">-</span>
                </div>
                <div class="flex justify-between border-b border-gray-200 pb-2">
                    <span class="text-gray-600 font-medium">Client/Organization:</span>
                    <span class="text-gray-900 font-semibold text-right" id="confirm-client">-</span>
                </div>
                <div class="flex justify-between border-b border-gray-200 pb-2">
                    <span class="text-gray-600 font-medium">From:</span>
                    <span class="text-gray-900 font-semibold text-right" id="confirm-from">-</span>
                </div>
                <div class="flex justify-between border-b border-gray-200 pb-2">
                    <span class="text-gray-600 font-medium">To:</span>
                    <span class="text-gray-900 font-semibold text-right" id="confirm-to">-</span>
                </div>
                <div class="flex justify-between border-b border-gray-200 pb-2">
                    <span class="text-gray-600 font-medium">Purpose:</span>
                    <span class="text-gray-900 font-semibold text-right" id="confirm-purpose">-</span>
                </div>
                <div class="flex justify-between border-b border-gray-200 pb-2">
                    <span class="text-gray-600 font-medium">Date of Travel:</span>
                    <span class="text-gray-900 font-semibold text-right" id="confirm-date">-</span>
                </div>
                <div class="flex justify-between border-b border-gray-200 pb-2">
                    <span class="text-gray-600 font-medium">Bus Number:</span>
                    <span class="text-gray-900 font-semibold text-right" id="confirm-bus">-</span>
                </div>
                <div class="flex justify-between border-b border-gray-200 pb-2">
                    <span class="text-gray-600 font-medium">Number of Days:</span>
                    <span class="text-gray-900 font-semibold text-right" id="confirm-days">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Number of Vehicles:</span>
                    <span class="text-gray-900 font-semibold text-right" id="confirm-vehicles">-</span>
                </div>
            </div>
            
            <div class="mt-4 p-3 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                <p class="text-sm text-yellow-700">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Important:</strong> Once submitted, your request will be sent to the admin for approval.
                </p>
            </div>
        </div>
        
        <div class="flex justify-end space-x-3">
            <button type="button" onclick="closeConfirmModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                <i class="fas fa-times mr-1"></i> Cancel
            </button>
            <button type="button" onclick="submitBusRequest()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <i class="fas fa-check mr-1"></i> Yes, Submit Request
            </button>
        </div>
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

// CHMSU Talisay as the fixed origin point (LOCAL ONLY - NO APIs)
const CHMSU_ORIGIN_NAME = 'CHMSU - Carlos Hilado Memorial State University, Talisay City, Negros Occidental';

// Local suggestions for Negros Occidental - COMPREHENSIVE COVERAGE (Excluding Talisay - system is located there)
const localSuggestions = [
    // Major Cities
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
    'Ilog, Negros Occidental',
    'Isabela, Negros Occidental',
    
    // CHMSU Campuses (excluding Talisay - main campus location)
    'CHMSU Binalbagan Campus',
    'CHMSU Fortune Towne Campus',
    'CHMSU Alijis Campus',
    
    // Bacolod City Barangays (61 total - ALL included) - matches database format
    'Barangay 1, Bacolod',
    'Barangay 2, Bacolod',
    'Barangay 3, Bacolod',
    'Barangay 4, Bacolod',
    'Barangay 5, Bacolod',
    'Barangay 6, Bacolod',
    'Barangay 7, Bacolod',
    'Barangay 8, Bacolod',
    'Barangay 9, Bacolod',
    'Barangay 10, Bacolod',
    'Barangay 11, Bacolod',
    'Barangay 12, Bacolod',
    'Barangay 13, Bacolod',
    'Barangay 14, Bacolod',
    'Barangay 15, Bacolod',
    'Barangay 16, Bacolod',
    'Barangay 17, Bacolod',
    'Barangay 18, Bacolod',
    'Barangay 19, Bacolod',
    'Barangay 20, Bacolod',
    'Barangay 21, Bacolod',
    'Barangay 22, Bacolod',
    'Barangay 23, Bacolod',
    'Barangay 24, Bacolod',
    'Barangay 25, Bacolod',
    'Barangay 26, Bacolod',
    'Barangay 27, Bacolod',
    'Barangay 28, Bacolod',
    'Barangay 29, Bacolod',
    'Barangay 30, Bacolod',
    'Barangay 31, Bacolod',
    'Barangay 32, Bacolod',
    'Barangay 33, Bacolod',
    'Barangay 34, Bacolod',
    'Barangay 35, Bacolod',
    'Barangay 36, Bacolod',
    'Barangay 37, Bacolod',
    'Barangay 38, Bacolod',
    'Barangay 39, Bacolod',
    'Barangay 40, Bacolod',
    'Barangay 41, Bacolod',
    'Barangay Mandalagan, Bacolod',
    'Barangay Villamonte, Bacolod',
    'Barangay Tangub, Bacolod',
    'Barangay Bata, Bacolod',
    'Barangay Singcang-Airport, Bacolod',
    'Barangay Banago, Bacolod',
    'Barangay Alijis, Bacolod',
    'Barangay Taculing, Bacolod',
    'Barangay Granada, Bacolod',
    'Barangay Estefania, Bacolod',
    'Barangay Sum-ag, Bacolod',
    'Barangay Felisa, Bacolod',
    'Barangay Punta Taytay, Bacolod',
    'Barangay Vista Alegre, Bacolod',
    'Barangay Pahanocoy, Bacolod',
    'Barangay Handumanan, Bacolod',
    'Barangay Montevista, Bacolod',
    'Barangay Cabug, Bacolod',
    'Barangay Alangilan, Bacolod',
    
    // Silay City Barangays - matches database format
    'Barangay 1, Silay',
    'Barangay 2, Silay',
    'Barangay 3, Silay',
    'Barangay 4, Silay',
    'Barangay 5, Silay',
    'Barangay 6, Silay',
    'Barangay Balaring, Silay',
    'Barangay Guinhalaran, Silay',
    'Barangay Hawaiian, Silay',
    'Barangay Kapitan Ramon, Silay',
    'Barangay Mambulac, Silay',
    'Barangay E. Lopez, Silay',
    'Barangay Lantad, Silay',
    'Barangay Rizal, Silay',
    
    // Bago City Barangays
    'Barangay Alijis, Bago City',
    'Barangay Atipuluan, Bago City',
    'Barangay Bacong, Bago City',
    'Barangay Balingasag, Bago City',
    'Barangay Binubuhan, Bago City',
    'Barangay Dulao, Bago City',
    'Barangay Lag-asan, Bago City',
    'Barangay Ma-ao, Bago City',
    'Barangay Poblacion, Bago City',
    
    // La Carlota City Barangays (Complete)
    'Barangay Ara-al, La Carlota',
    'Barangay Ayungon, La Carlota',
    'Barangay Balabag, La Carlota',
    'Barangay Batuan, La Carlota',
    'Barangay Consuelo, La Carlota',
    'Barangay Cubay, La Carlota',
    'Barangay Haguimit, La Carlota',
    'Barangay I (Poblacion), La Carlota',
    'Barangay II (Poblacion), La Carlota',
    'Barangay La Granja, La Carlota',
    'Barangay Nagasi, La Carlota',
    'Barangay RSB (Rafael Salas), La Carlota',
    'Barangay San Miguel, La Carlota',
    'Barangay Yubo, La Carlota',
    
    // More Bago City Barangays
    'Barangay Abuanan, Bago City',
    'Barangay Alianza, Bago City',
    'Barangay Busay, Bago City',
    'Barangay Calumangan, Bago City',
    'Barangay Caridad, Bago City',
    'Barangay Mailum, Bago City',
    'Barangay Malingin, Bago City',
    'Barangay Pacol, Bago City',
    'Barangay Sagasa, Bago City',
    'Barangay Taloc, Bago City',
    
    // Kabankalan City Barangays (Complete)
    'Barangay Bantayan, Kabankalan City',
    'Barangay Binicuil, Kabankalan City',
    'Barangay Camansi, Kabankalan City',
    'Barangay Camingawan, Kabankalan City',
    'Barangay Carol-an, Kabankalan City',
    'Barangay Daan Banua, Kabankalan City',
    'Barangay Hilamonan, Kabankalan City',
    'Barangay Inapoy, Kabankalan City',
    'Barangay Locotan, Kabankalan City',
    'Barangay Magatas, Kabankalan City',
    'Barangay Magballo, Kabankalan City',
    'Barangay Oringao, Kabankalan City',
    'Barangay Orong, Kabankalan City',
    'Barangay Pinaguinpinan, Kabankalan City',
    'Barangay Salong, Kabankalan City',
    'Barangay Tabugon, Kabankalan City',
    'Barangay Tagoc, Kabankalan City',
    'Barangay Tagukon, Kabankalan City',
    'Barangay Talubangi, Kabankalan City',
    'Barangay Tampalon, Kabankalan City',
    'Barangay Tan-awan, Kabankalan City',
    'Barangay Tapi, Kabankalan City',
    'Barangay Tiling, Kabankalan City',
    
    // Himamaylan City Barangays (Complete)
    'Barangay 1 (Poblacion I), Himamaylan City',
    'Barangay 2 (Poblacion II), Himamaylan City',
    'Barangay 3 (Poblacion III), Himamaylan City',
    'Barangay 4 (Poblacion IV), Himamaylan City',
    'Barangay 5 (Poblacion V), Himamaylan City',
    'Barangay 6 (Poblacion VI), Himamaylan City',
    'Barangay Aguisan, Himamaylan City',
    'Barangay Buenavista, Himamaylan City',
    'Barangay Cabadiangan, Himamaylan City',
    'Barangay Cabanbanan, Himamaylan City',
    'Barangay Carabalan, Himamaylan City',
    'Barangay Caradio-an, Himamaylan City',
    'Barangay Mambagaton, Himamaylan City',
    'Barangay Nabali-an, Himamaylan City',
    'Barangay San Antonio, Himamaylan City',
    'Barangay San Jose, Himamaylan City',
    'Barangay San Pablo, Himamaylan City',
    'Barangay Sara-et, Himamaylan City',
    'Barangay Suay, Himamaylan City',
    'Barangay Talaban, Himamaylan City',
    'Barangay To-oy, Himamaylan City',
    
    // Sagay City Barangays (Complete)
    'Barangay Andres Bonifacio, Sagay',
    'Barangay Bato, Sagay',
    'Barangay Baviera, Sagay',
    'Barangay Bulanon, Sagay',
    'Barangay Campo Himoga-an, Sagay',
    'Barangay Colonia Divina, Sagay',
    'Barangay Fabrica, Sagay',
    'Barangay General Luna, Sagay',
    'Barangay Himoga-an Baybay, Sagay',
    'Barangay Lopez Jaena, Sagay',
    'Barangay Malubon, Sagay',
    'Barangay Old Sagay, Sagay',
    'Barangay Paraiso, Sagay',
    'Barangay Poblacion I, Sagay',
    'Barangay Poblacion II, Sagay',
    'Barangay Poblacion III, Sagay',
    'Barangay Poblacion IV, Sagay',
    'Barangay Poblacion V, Sagay',
    'Barangay Puey, Sagay',
    'Barangay Rafaela Barrera, Sagay',
    'Barangay Rizal, Sagay',
    'Barangay Sewahon I, Sagay',
    'Barangay Sewahon II, Sagay',
    'Barangay Taba-ao, Sagay',
    'Barangay Tadlong, Sagay',
    'Barangay Vito, Sagay',
    
    // San Carlos City Barangays (Complete)
    'Barangay 3, San Carlos',
    'Barangay 4, San Carlos',
    'Barangay 5, San Carlos',
    'Barangay Bagonbon, San Carlos',
    'Barangay Buluangan, San Carlos',
    'Barangay Codcod, San Carlos',
    'Barangay Ermita, San Carlos',
    'Barangay Guadalupe, San Carlos',
    'Barangay I (Poblacion I), San Carlos',
    'Barangay II (Poblacion II), San Carlos',
    'Barangay Nataban, San Carlos',
    'Barangay Palampas, San Carlos',
    'Barangay Prosperidad, San Carlos',
    'Barangay Punao, San Carlos',
    'Barangay Quezon, San Carlos',
    'Barangay Rizal, San Carlos',
    'Barangay San Antonio, San Carlos',
    'Barangay San Juan, San Carlos',
    'Barangay San Pedro, San Carlos',
    
    // Cadiz City Barangays (Complete)
    'Barangay Andres Bonifacio, Cadiz',
    'Barangay Bandila, Cadiz',
    'Barangay Banquerohan, Cadiz',
    'Barangay 1 (Poblacion), Cadiz',
    'Barangay 2 (Poblacion), Cadiz',
    'Barangay 3 (Poblacion), Cadiz',
    'Barangay 4 (Poblacion), Cadiz',
    'Barangay 5 (Poblacion), Cadiz',
    'Barangay 6 (Poblacion), Cadiz',
    'Barangay 7 (Poblacion), Cadiz',
    'Barangay 8 (Poblacion), Cadiz',
    'Barangay 9 (Poblacion), Cadiz',
    'Barangay 10 (Poblacion), Cadiz',
    'Barangay Cabahug, Cadiz',
    'Barangay Caduhaan, Cadiz',
    'Barangay Celestino Villacin, Cadiz',
    'Barangay Daga, Cadiz',
    'Barangay Luna, Cadiz',
    'Barangay Mabini, Cadiz',
    'Barangay Magsaysay, Cadiz',
    'Barangay Sicaba, Cadiz',
    'Barangay Tiglawigan, Cadiz',
    'Barangay Tinampa-an, Cadiz',
    
    // Victorias City Barangays (Complete)
    'Barangay Bago, Victorias',
    'Barangay Canlandog, Victorias',
    'Barangay Daan Banua, Victorias',
    'Barangay I (Poblacion I), Victorias',
    'Barangay II (Poblacion II), Victorias',
    'Barangay III (Poblacion III), Victorias',
    'Barangay IV (Poblacion IV), Victorias',
    'Barangay V (Poblacion V), Victorias',
    'Barangay VI (Poblacion VI), Victorias',
    'Barangay VII (Poblacion VII), Victorias',
    'Barangay VIII (Poblacion VIII), Victorias',
    'Barangay IX (Poblacion IX), Victorias',
    'Barangay X (Poblacion X), Victorias',
    'Barangay XI (Poblacion XI), Victorias',
    'Barangay XII (Poblacion XII), Victorias',
    'Barangay XIII (Poblacion XIII), Victorias',
    'Barangay XIV (Poblacion XIV), Victorias',
    'Barangay XV (Poblacion XV), Victorias',
    'Barangay XVI (Poblacion XVI), Victorias',
    'Barangay XVII (Poblacion XVII), Victorias',
    'Barangay XVIII (Poblacion XVIII), Victorias',
    'Barangay XIX (Poblacion XIX), Victorias',
    'Barangay XX (Poblacion XX), Victorias',
    'Barangay XXI (Poblacion XXI), Victorias',
    'Barangay XXII (Poblacion XXII), Victorias',
    'Barangay XXIII (Poblacion XXIII), Victorias',
    'Barangay XXIV (Poblacion XXIV), Victorias',
    'Barangay XXV (Poblacion XXV), Victorias',
    'Barangay XXVI (Poblacion XXVI), Victorias',
    'Barangay Malaya, Victorias',
    'Barangay Malingin, Victorias',
    'Barangay San Miguel, Victorias',
    
    // Escalante City Barangays (Complete)
    'Barangay Alimango, Escalante',
    'Barangay Balintawak, Escalante',
    'Barangay Binaguiohan, Escalante',
    'Barangay Dian-ay, Escalante',
    'Barangay Hacienda Fe, Escalante',
    'Barangay Japitan, Escalante',
    'Barangay Jonob-jonob, Escalante',
    'Barangay Libertad, Escalante',
    'Barangay Mabini, Escalante',
    'Barangay Magsaysay, Escalante',
    'Barangay Malasibog, Escalante',
    'Barangay Old Poblacion, Escalante',
    'Barangay Paitan, Escalante',
    'Barangay Pinapugasan, Escalante',
    'Barangay Rizal, Escalante',
    'Barangay Sampinit, Escalante',
    'Barangay Udtongan, Escalante',
    'Barangay Washington, Escalante',
    
    // Sipalay City Barangays
    'Barangay Cabadiangan, Sipalay City',
    'Barangay Camindangan, Sipalay City',
    'Barangay Gil Montilla, Sipalay City',
    'Barangay Maricalum, Sipalay City',
    
    // Schools & Universities
    'University of Negros Occidental - Recoletos',
    'La Salle Bacolod',
    'University of St. La Salle',
    'University of Bacolod',
    'STI College Bacolod',
    'Carlos Hilado Memorial State College',
    
    // Landmarks & Shopping Centers
    'SM City Bacolod',
    'Ayala Capitol Central',
    'Robinsons Place Bacolod',
    'Bacolod City Plaza',
    'Bacolod Public Plaza',
    'The Negros Museum',
    'Capitol Park and Lagoon',
    'CityMall Bacolod',
    
    // Airports
    'Silay Airport',
    'Bacolod Airport',
    'Bacolod-Silay International Airport',
    
    // Other Municipalities
    'Calatrava, Negros Occidental',
    'Cauayan, Negros Occidental',
    'Don Salvador Benedicto, Negros Occidental',
    'Enrique B. Magalona, Negros Occidental',
    'Hinigaran, Negros Occidental',
    'Hinoba-an, Negros Occidental',
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

// NO API FUNCTIONS NEEDED - All calculations done locally on server

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
    }, 300); // Increased delay to 300ms
}

// Continuation location input handlers
function handleLocationInputContinuation(event) {
    const input = event.target.value;
    const suggestionsDiv = document.getElementById('location-suggestions-continuation');
    
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
        showSuggestionsContinuation(input);
    }, 300);
}

function showLocationSuggestionsContinuation() {
    const input = document.getElementById('to_location_continuation').value;
    if (input.length >= 2) {
        showSuggestionsContinuation(input);
    }
}

function hideLocationSuggestionsContinuation() {
    // Delay hiding to allow clicking on suggestions
    setTimeout(() => {
        const suggestionsDiv = document.getElementById('location-suggestions-continuation');
        suggestionsDiv.classList.add('hidden');
    }, 300); // Increased delay to 300ms
}

function showSuggestionsContinuation(query) {
    const suggestionsDiv = document.getElementById('location-suggestions-continuation');
    const normalizedQuery = query.toLowerCase().trim();
    
    // Filter local suggestions only - no API calls
    const matches = localSuggestions.filter(suggestion => 
        suggestion.toLowerCase().includes(normalizedQuery)
    ).slice(0, 10);
    
    if (matches.length === 0) {
        suggestionsDiv.classList.add('hidden');
        return;
    }
    
    // Display suggestions - use onmousedown instead of onclick (fires before blur)
    suggestionsDiv.innerHTML = matches.map(suggestion => `
        <div class="px-4 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0" 
             onmousedown="selectSuggestionContinuation('${suggestion.replace(/'/g, "\\'")}'); event.preventDefault();">
            <div class="flex items-center">
                <i class="fas fa-map-marker-alt text-blue-500 mr-2"></i>
                <span class="text-sm">${suggestion}</span>
            </div>
        </div>
    `).join('');
    
    suggestionsDiv.classList.remove('hidden');
}

function selectSuggestionContinuation(suggestion) {
    document.getElementById('to_location_continuation').value = suggestion;
    document.getElementById('location-suggestions-continuation').classList.add('hidden');
    updateDistance(); // Recalculate distance when continuation is selected
}

function showSuggestions(query) {
    const suggestionsDiv = document.getElementById('location-suggestions');
    const normalizedQuery = query.toLowerCase().trim();
    
    // Filter local suggestions only - no API calls
    const matches = localSuggestions.filter(suggestion => 
        suggestion.toLowerCase().includes(normalizedQuery)
    ).slice(0, 10);
    
    if (matches.length === 0) {
        suggestionsDiv.classList.add('hidden');
        return;
    }
    
    // Display suggestions - use onmousedown instead of onclick (fires before blur)
    suggestionsDiv.innerHTML = matches.map(suggestion => `
        <div class="px-4 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0" 
             onmousedown="selectSuggestion('${suggestion.replace(/'/g, "\\'")}'); event.preventDefault();">
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

// NO GEOCODING OR RESOLVING FUNCTIONS NEEDED - All done locally in PHP

// Calculate and display distance using LOCAL DATABASE (NO APIs)
async function updateDistance() {
    const fromLocation = document.getElementById('from_location').value.trim();
    const toLocation = document.getElementById('to_location').value.trim();
    const toLocationContinuation = document.getElementById('to_location_continuation').value.trim();
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
            <span>Calculating distance from CHMSU using local database...</span>
        </div>
    `;
    
    try {
        let totalDistanceOneWay = 0;
        let routeDetails = [];
        
        // Calculate distance from CHMSU to first location
        const formData1 = new FormData();
        formData1.append('from_location', fromLocation);
        formData1.append('to_location', toLocation);
        
        const response1 = await fetch('calculate_distance.php', {
            method: 'POST',
            body: formData1
        });
        
        const result1 = await response1.json();
        
        if (result1.error) {
            distanceDisplay.className = 'p-3 rounded-md text-sm bg-yellow-100 text-yellow-800';
            distanceDisplay.innerHTML = `
                <div>
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span class="font-semibold">Location not found</span>
                </div>
                <div class="mt-2 text-xs">
                    "${toLocation}" could not be found in the database. Try using: Bacolod City, Talisay City, or specific barangays.
                </div>
                <div class="mt-2 text-xs text-gray-600">
                    Using estimated distance: <span class="font-bold">50 km</span> (one-way), <span class="font-bold">100 km</span> (round trip)
                </div>
            `;
            return;
        }
        
        totalDistanceOneWay += result1.distance_km;
        routeDetails.push({
            from: result1.from_location || CHMSU_ORIGIN_NAME,
            to: result1.destination,
            distance: result1.distance_km
        });
        
        // If continuation exists, calculate distance from first location to continuation
        if (toLocationContinuation) {
            const formData2 = new FormData();
            formData2.append('from_location', toLocation);
            formData2.append('to_location', toLocationContinuation);
            
            const response2 = await fetch('calculate_distance.php', {
                method: 'POST',
                body: formData2
            });
            
            const result2 = await response2.json();
            
            if (!result2.error) {
                totalDistanceOneWay += result2.distance_km;
                routeDetails.push({
                    from: result2.from_location || toLocation,
                    to: result2.destination,
                    distance: result2.distance_km
                });
            }
        }
        
        const totalDistanceRoundTrip = totalDistanceOneWay * 2;
        
        // Display result
        distanceDisplay.className = 'p-3 rounded-md text-sm bg-green-100 text-green-800 border border-green-300';
        
        let routeHtml = '';
        routeDetails.forEach((route, index) => {
            routeHtml += `
                <div class="${index > 0 ? 'mt-2' : ''}">
                    <i class="fas fa-map-marker-alt mr-1 text-green-600"></i>
                    <span class="font-semibold">${index === 0 ? 'To' : 'Then to'}:</span> ${route.to} <span class="text-gray-600">(${route.distance} km)</span>
                </div>
            `;
        });
        
        distanceDisplay.innerHTML = `
            <div class="flex items-center justify-between">
                <span><i class="fas fa-route mr-2"></i>Distance (one-way):</span>
                <span class="font-bold text-lg">${totalDistanceOneWay.toFixed(1)} km</span>
            </div>
            <div class="flex items-center justify-between mt-1">
                <span><i class="fas fa-exchange-alt mr-2"></i>Total Distance (round trip):</span>
                <span class="font-bold text-lg">${totalDistanceRoundTrip.toFixed(1)} km</span>
            </div>
            <div class="mt-3 pt-2 border-t border-green-200 text-xs text-gray-700">
                <div class="mb-2">
                    <i class="fas fa-university mr-1 text-blue-600"></i>
                    <span class="font-semibold">From:</span> ${routeDetails[0].from}
                </div>
                ${routeHtml}
            </div>
            <div class="mt-2 text-xs text-gray-600 flex items-center">
                <i class="fas fa-database mr-1"></i>
                Calculated using local Negros Occidental database
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

        // Update Bus Number select options availability
        const availabilityByNumber = {};
        
        // Initialize all buses as available
        Array.from(busNoSelect.options).forEach(opt => {
            if (opt.value) {
                availabilityByNumber[opt.value] = true;
            }
        });
        
        // Mark unavailable buses from API response
        if (data.buses) {
            data.buses.forEach(bus => {
                if (availabilityByNumber.hasOwnProperty(bus.bus_number)) {
                    availabilityByNumber[bus.bus_number] = bus.available;
                }
            });
        }

        // Enable/disable options and update text
        Array.from(busNoSelect.options).forEach(opt => {
            if (opt.value === "") return; // Skip the "Select Bus" option
            
            const isAvail = availabilityByNumber[opt.value] !== false;
            opt.disabled = !isAvail;
            
            // Get original bus info from option text
            const busInfo = opt.getAttribute('data-original-text') || opt.textContent;
            if (!opt.hasAttribute('data-original-text')) {
                opt.setAttribute('data-original-text', opt.textContent);
            }
            
            opt.textContent = busInfo + (!isAvail ? ' (Not available)' : '');
        });

        // If selected option becomes unavailable, switch to first available
        if (busNoSelect.options[busNoSelect.selectedIndex]?.disabled) {
            const firstAvail = Array.from(busNoSelect.options).find(o => !o.disabled && o.value);
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

// Confirmation Modal Functions
function showConfirmModal() {
    // Get form values
    const form = document.getElementById('busRequestForm');
    const formData = new FormData(form);
    
    // Validate form first
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Get bus number text (from the selected option)
    const busSelect = document.getElementById('bus_no');
    const busText = busSelect.options[busSelect.selectedIndex].text;
    
    // Populate confirmation modal with form values
    document.getElementById('confirm-school').textContent = formData.get('school') || '-';
    document.getElementById('confirm-client').textContent = formData.get('client') || '-';
    document.getElementById('confirm-from').textContent = formData.get('from_location') || '-';
    document.getElementById('confirm-to').textContent = formData.get('to_location') || '-';
    document.getElementById('confirm-purpose').textContent = formData.get('purpose') || '-';
    document.getElementById('confirm-date').textContent = formData.get('date_covered') ? new Date(formData.get('date_covered')).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : '-';
    document.getElementById('confirm-bus').textContent = busText || '-';
    document.getElementById('confirm-days').textContent = formData.get('no_of_days') || '-';
    document.getElementById('confirm-vehicles').textContent = formData.get('no_of_vehicles') || '-';
    
    // Show confirmation modal
    document.getElementById('confirmModal').classList.remove('hidden');
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.add('hidden');
}

function submitBusRequest() {
    // Close confirmation modal
    closeConfirmModal();
    
    // Submit the form
    document.getElementById('busRequestForm').submit();
}

// Close modals when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('fixed')) {
        closeAddModal();
        closeViewModal();
        closeCancelModal();
        closeConfirmModal();
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