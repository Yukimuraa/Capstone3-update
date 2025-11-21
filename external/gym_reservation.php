<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is external
require_external();

// Get user data
$user_id = $_SESSION['user_sessions']['external']['user_id'];
$user_name = $_SESSION['user_sessions']['external']['user_name'];

$page_title = "Gym Reservation - CHMSU BAO";
$base_url = "..";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $facility_id = $_POST['facility_id'];
    $booking_date = $_POST['booking_date'];
    $time_slot = $_POST['time_slot'] ?? '';
    $purpose = $_POST['purpose'];
    $participants = $_POST['participants'];

    // Derive start and end time from selected option
    $start_time = null;
    $end_time = null;
    if ($time_slot === 'custom') {
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
    } elseif (!empty($time_slot) && strpos($time_slot, '-') !== false) {
        list($start_time, $end_time) = array_map('trim', explode('-', $time_slot));
    }

    // Basic validation
    if (empty($start_time) || empty($end_time)) {
        $error_message = "Please select a valid time or slot.";
    } elseif ($start_time >= $end_time) {
        $error_message = "End time must be after start time.";
    } else {
    
    // Check if the time slot is available
    $check_query = "SELECT * FROM bookings 
                   WHERE facility_id = ? 
                   AND booking_date = ? 
                   AND ((start_time <= ? AND end_time > ?) 
                   OR (start_time < ? AND end_time >= ?)
                   OR (start_time >= ? AND end_time <= ?))";
    
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("isssssss", $facility_id, $booking_date, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "This time slot is already booked. Please choose another time.";
        } else {
            // Insert the booking
            $insert_query = "INSERT INTO bookings (facility_id, user_id, user_type, booking_date, start_time, end_time, purpose, participants, status) 
                            VALUES (?, ?, 'external', ?, ?, ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iissssi", $facility_id, $user_id, $booking_date, $start_time, $end_time, $purpose, $participants);
            
            if ($stmt->execute()) {
                $success_message = "Reservation request submitted successfully!";
            } else {
                $error_message = "Error submitting reservation request.";
            }
        }
    }
}

// Get available gym facilities
$facilities_query = "SELECT * FROM facilities WHERE type = 'gym' AND status = 'active'";
$facilities = $conn->query($facilities_query);

// Get user's existing reservations
$reservations_query = "SELECT b.*, f.name as facility_name 
                      FROM bookings b 
                      JOIN facilities f ON b.facility_id = f.id 
                      WHERE b.user_id = ? AND b.user_type = 'external' 
                      ORDER BY b.booking_date DESC, b.start_time DESC";
$stmt = $conn->prepare($reservations_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reservations = $stmt->get_result();
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/external_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">Gym Reservation</h1>
                <div class="flex items-center">
                    <span class="text-gray-700 mr-2"><?php echo $user_name; ?></span>
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
                <?php if (isset($success_message)): ?>
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Reservation Form -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                        <h3 class="text-lg font-medium text-gray-900">Make a Reservation</h3>
                    </div>
                    <div class="p-4">
                        <form action="gym_reservation.php" method="POST" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="facility_id" class="block text-sm font-medium text-gray-700">Facility</label>
                                    <select name="facility_id" id="facility_id" required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                                        <option value="">Select a facility</option>
                                        <?php while ($facility = $facilities->fetch_assoc()): ?>
                                            <option value="<?php echo $facility['id']; ?>"><?php echo $facility['name']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="booking_date" class="block text-sm font-medium text-gray-700">Date</label>
                                    <input type="date" name="booking_date" id="booking_date" required
                                           min="<?php echo date('Y-m-d'); ?>"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                                </div>
                                <div>
                                    <label for="time_slot" class="block text-sm font-medium text-gray-700">Time</label>
                                    <select name="time_slot" id="time_slot" required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                                        <option value="">Select time</option>
                                        <option value="08:00:00-11:00:00">Morning (8:00 AM - 11:00 AM)</option>
                                        <option value="13:00:00-17:30:00">Afternoon (1:00 PM - 5:30 PM)</option>
                                        <option value="custom">Custom time</option>
                                    </select>
                                </div>
                                <div id="custom_time_fields" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="start_time" class="block text-sm font-medium text-gray-700">Start Time</label>
                                        <input type="time" name="start_time" id="start_time"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                                    </div>
                                    <div>
                                        <label for="end_time" class="block text-sm font-medium text-gray-700">End Time</label>
                                        <input type="time" name="end_time" id="end_time"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                                    </div>
                                </div>
                                <div>
                                    <label for="purpose" class="block text-sm font-medium text-gray-700">Purpose</label>
                                    <input type="text" name="purpose" id="purpose" required
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                                </div>
                                <div>
                                    <label for="participants" class="block text-sm font-medium text-gray-700">Number of Participants</label>
                                    <input type="number" name="participants" id="participants" required min="1"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500 focus:ring-opacity-50">
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit"
                                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                                    Submit Reservation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Existing Reservations -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                        <h3 class="text-lg font-medium text-gray-900">Your Reservations</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Facility</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($reservations->num_rows > 0): ?>
                                    <?php while ($reservation = $reservations->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $reservation['facility_name']; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('M d, Y', strtotime($reservation['booking_date'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('h:i A', strtotime($reservation['start_time'])); ?> - <?php echo date('h:i A', strtotime($reservation['end_time'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $reservation['purpose']; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                $status_classes = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                                    'approved' => 'bg-green-100 text-green-800',
                                                    'rejected' => 'bg-red-100 text-red-800',
                                                    'cancelled' => 'bg-gray-100 text-gray-800'
                                                ];
                                                $status_class = $status_classes[$reservation['status']] ?? 'bg-gray-100 text-gray-800';
                                                ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                    <?php echo ucfirst($reservation['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No reservations found
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Mobile menu toggle
    document.getElementById('menu-button').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
    });
    
    // Toggle custom time fields
    const timeSlotSelect = document.getElementById('time_slot');
    const customFields = document.getElementById('custom_time_fields');
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');

    timeSlotSelect.addEventListener('change', function() {
        const isCustom = this.value === 'custom';
        customFields.classList.toggle('hidden', !isCustom);
        // Clear custom inputs if switching away
        if (!isCustom) {
            startTimeInput.value = '';
            endTimeInput.value = '';
        }
    });

    // Time validation for custom times
    endTimeInput.addEventListener('change', function() {
        const startTime = startTimeInput.value;
        const endTime = this.value;
        if (startTime && endTime && startTime >= endTime) {
            alert('End time must be after start time');
            this.value = '';
        }
    });
</script>

    <script src="<?php echo $base_url ?? ''; ?>/assets/js/main.js"></script>
</body>
</html> 