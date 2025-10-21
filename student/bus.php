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

// Function to calculate billing statement
function calculateBillingStatement($destination, $no_of_vehicles, $no_of_days) {
    // Default values based on the image
    $fuel_rate = 70.00;
    $distance_km = 78.00; // Default distance for Talisay - Binalbagan
    $total_distance_km = $distance_km * 2; // Round trip
    
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
        'from_location' => 'CHMSU-Talisay',
        'to_location' => 'CHMSU-Binalbagan',
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
        if ($_POST['action'] === 'add_schedule') {
            $client = sanitize_input($_POST['client']);
            $destination = sanitize_input($_POST['destination']);
            $purpose = sanitize_input($_POST['purpose']);
            $date_covered = $_POST['date_covered'];
            $vehicle = sanitize_input($_POST['vehicle']);
            $bus_no = sanitize_input($_POST['bus_no']);
            $no_of_days = intval($_POST['no_of_days']);
            $no_of_vehicles = intval($_POST['no_of_vehicles']);
            
            // Check bus availability
            $availability = checkBusAvailability($conn, $date_covered, $no_of_vehicles);
            
            if (!$availability['can_book']) {
                $error = "Not enough buses available. Only {$availability['available_buses']} buses are available for this date, but you requested {$no_of_vehicles} buses.";
            } elseif (empty($client) || empty($destination) || empty($purpose) || empty($date_covered) || empty($vehicle) || empty($bus_no) || empty($no_of_days) || empty($no_of_vehicles)) {
                $error = 'All required fields must be filled.';
            } else {
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Insert bus schedule
                    $stmt = $conn->prepare("INSERT INTO bus_schedules (client, destination, purpose, date_covered, vehicle, bus_no, no_of_days, no_of_vehicles, user_id, user_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'student', 'pending')");
                    $stmt->bind_param("ssssssiii", $client, $destination, $purpose, $date_covered, $vehicle, $bus_no, $no_of_days, $no_of_vehicles, $_SESSION['user_id']);
                    
                    if ($stmt->execute()) {
                        $schedule_id = $conn->insert_id;
                        
                        // Calculate billing statement
                        $billing = calculateBillingStatement($destination, $no_of_vehicles, $no_of_days);
                        
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
                            $client, 
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

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_requests,
    0 as approved_requests,
    0 as pending_requests,
    0 as rejected_requests
    FROM bus_schedules";
$stats_result = $conn->query($stats_query);
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
                                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        Submitted
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
                                            <button type="button" class="text-blue-600 hover:text-blue-900" onclick="viewSchedule(<?php echo htmlspecialchars(json_encode($schedule)); ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if (isset($schedule['total_amount']) && $schedule['total_amount'] > 0): ?>
                                                <button type="button" class="text-green-600 hover:text-green-900" onclick="printReceipt(<?php echo $schedule['id']; ?>)">
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
                    <label for="client" class="block text-sm font-medium text-gray-700 mb-1">Client/Organization *</label>
                    <input type="text" id="client" name="client" required 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                           value="<?php echo isset($_POST['client']) ? htmlspecialchars($_POST['client']) : 'OSAS'; ?>">
                </div>
                
                <div>
                    <label for="destination" class="block text-sm font-medium text-gray-700 mb-1">Destination *</label>
                    <input type="text" id="destination" name="destination" required 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                           value="<?php echo isset($_POST['destination']) ? htmlspecialchars($_POST['destination']) : 'Talisay - Binalbagan'; ?>">
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
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <option value="Bus" <?php echo (isset($_POST['vehicle']) && $_POST['vehicle'] === 'Bus') ? 'selected' : ''; ?>>Bus</option>
                        <option value="Van" <?php echo (isset($_POST['vehicle']) && $_POST['vehicle'] === 'Van') ? 'selected' : ''; ?>>Van</option>
                        <option value="Jeepney" <?php echo (isset($_POST['vehicle']) && $_POST['vehicle'] === 'Jeepney') ? 'selected' : ''; ?>>Jeepney</option>
                    </select>
                </div>
                
                <div>
                    <label for="bus_no" class="block text-sm font-medium text-gray-700 mb-1">Vehicle Number *</label>
                    <input type="text" id="bus_no" name="bus_no" required 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                           value="<?php echo isset($_POST['bus_no']) ? htmlspecialchars($_POST['bus_no']) : '1'; ?>">
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

<script>
// Mobile menu toggle
document.getElementById('menu-button').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('-translate-x-full');
});

// Modal functions
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

function viewSchedule(schedule) {
    const details = document.getElementById('schedule-details');
    details.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-500">Client/Organization</label>
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

// Check bus availability
function checkAvailability() {
    const date = document.getElementById('date_covered').value;
    const vehicles = document.getElementById('no_of_vehicles').value;
    const statusDiv = document.getElementById('availability-status');
    
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
    })
    .catch(error => {
        console.error('Error:', error);
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
    }
});
</script>

<?php include '../includes/footer.php'; ?>