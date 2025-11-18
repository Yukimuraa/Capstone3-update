<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is external
require_external();

$page_title = "Gymnasium Booking - CHMSU BAO";
$base_url = "..";

// Get user ID
$user_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Submit new booking
        if ($_POST['action'] === 'book') {
            $date = sanitize_input($_POST['date']);
            $start_time = sanitize_input($_POST['start_time']);
            $end_time = sanitize_input($_POST['end_time']);
            $purpose = sanitize_input($_POST['purpose']);
            $attendees = intval($_POST['attendees']);
            $organization = sanitize_input($_POST['organization']);
            $contact_person = sanitize_input($_POST['contact_person']);
            $contact_number = sanitize_input($_POST['contact_number']);
            
            // Normalize times to HH:MM:SS format for proper comparison
            $start_time = trim($start_time);
            $end_time = trim($end_time);
            $start_normalized = (strlen($start_time) === 5) ? $start_time . ':00' : $start_time;
            $end_normalized = (strlen($end_time) === 5) ? $end_time . ':00' : $end_time;
            
            // Validate booking date (must be in the future)
            $booking_date = new DateTime($date);
            $today = new DateTime();
            $today->setTime(0, 0, 0); // Set time to beginning of day
            
            if ($booking_date < $today) {
                $error_message = "Booking date must be in the future.";
            } 
			// Validate time (end time must be after start time)
			elseif ($start_time >= $end_time) {
                $error_message = "End time must be after start time.";
			}
			// Check if booking overlaps with lunch break (12:00 PM - 1:00 PM)
			// Allow full-day bookings (08:00-18:00) to bypass this check
			// Block bookings that start before 13:00 AND end after 12:00
			elseif (!(($start_normalized === '08:00:00') && ($end_normalized === '18:00:00'))) {
				// Convert times to comparable integers (remove colons)
				$start_int = (int)str_replace(':', '', $start_normalized);
				$end_int = (int)str_replace(':', '', $end_normalized);
				$lunch_start_int = 120000; // 12:00:00
				$lunch_end_int = 130000;   // 13:00:00
				
				// Check if booking overlaps with lunch: starts before 13:00 AND ends after 12:00
				if ($start_int < $lunch_end_int && $end_int > $lunch_start_int) {
					$error_message = "The gymnasium is unavailable from 12:00 PM to 1:00 PM (Lunch Break).";
				}
			}
            
            // If no validation errors, proceed to check overlaps and insert booking
            if (empty($error_message)) {
				// Use half-open interval overlap: existing.start < new.end AND existing.end > new.start
				// This allows adjacent bookings that touch at boundaries (e.g., 09:30 following 08:30–09:30)
				$check_query = "SELECT 1 FROM bookings 
							WHERE facility_type = 'gym' 
							  AND date = ? 
							  AND status IN ('pending','confirmed')
							  AND (start_time < ? AND end_time > ?)";
				$check_stmt = $conn->prepare($check_query);
				$check_stmt->bind_param("sss", $date, $end_time, $start_time);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $error_message = "The gymnasium is already booked for this time slot.";
                } else {
					// Generate a collision-resistant booking ID and retry on duplicates
					$year = date('Y');
					$additional_info = json_encode([
						'organization' => $organization,
						'contact_person' => $contact_person,
						'contact_number' => $contact_number
					]);
					
					$attempts = 0;
					$maxAttempts = 3;
					$success = false;
					
					while ($attempts < $maxAttempts && !$success) {
						$attempts++;
						// Find current max sequence for this year (positions: 'GYM-'(1-4) + YYYY(5-8) + '-'(9) + NNN(10-...))
						$seq_query = "SELECT MAX(CAST(SUBSTRING(booking_id, 10) AS UNSIGNED)) AS max_seq 
										FROM bookings 
										WHERE facility_type = 'gym' AND SUBSTRING(booking_id, 5, 4) = ?";
						$seq_stmt = $conn->prepare($seq_query);
						$seq_stmt->bind_param("s", $year);
						$seq_stmt->execute();
						$seq_res = $seq_stmt->get_result();
						$seq_row = $seq_res->fetch_assoc();
						$next_seq = intval($seq_row['max_seq'] ?? 0) + 1;
						$booking_id = "GYM-" . $year . "-" . str_pad((string)$next_seq, 3, '0', STR_PAD_LEFT);

						$stmt = $conn->prepare("INSERT INTO bookings (booking_id, user_id, facility_type, date, start_time, end_time, purpose, attendees, status, additional_info) VALUES (?, ?, 'gym', ?, ?, ?, ?, ?, 'pending', ?)");
						$stmt->bind_param("sissssis", $booking_id, $user_id, $date, $start_time, $end_time, $purpose, $attendees, $additional_info);
						
						try {
							if ($stmt->execute()) {
								$success = true;
								$success_message = "Your gymnasium booking request has been submitted successfully. Booking ID: " . $booking_id;
							}
						} catch (mysqli_sql_exception $e) {
							// Duplicate key, retry by recomputing next sequence
							if ($e->getCode() !== 1062) {
								$error_message = "Error submitting booking: " . $e->getMessage();
								break;
							}
							// else: loop to try again
						}
					}

					if (!$success && !isset($error_message)) {
						$error_message = "Error submitting booking: Could not generate a unique booking ID.";
					}
                }
            }
        }
        
        // Cancel booking
        elseif ($_POST['action'] === 'cancel' && isset($_POST['booking_id'])) {
            $booking_id = sanitize_input($_POST['booking_id']);
            
            // Check if booking exists and belongs to user
            $check_query = "SELECT * FROM bookings WHERE booking_id = ? AND user_id = ? AND status = 'pending'";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("si", $booking_id, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                $error_message = "Invalid booking or booking cannot be cancelled.";
            } else {
                // Update booking status
                $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?");
                $stmt->bind_param("s", $booking_id);
                
                if ($stmt->execute()) {
                    $success_message = "Booking cancelled successfully.";
                } else {
                    $error_message = "Error cancelling booking: " . $conn->error;
                }
            }
        }
    }
}

// Get user's bookings
$bookings_query = "SELECT * FROM bookings WHERE user_id = ? AND facility_type = 'gym' ORDER BY date DESC, start_time DESC";
$bookings_stmt = $conn->prepare($bookings_query);
$bookings_stmt->bind_param("i", $user_id);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();

// Note: We no longer fully disable dates on the calendar to allow partial-day bookings
?>

<?php include '../includes/header.php'; ?>

<div class="flex h-screen bg-gray-100">
    <?php include '../includes/external_sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top header -->
        <header class="bg-white shadow-sm z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">Gymnasium Reservation</h1>
                <div class="flex items-center">
                    <span class="text-gray-700 mr-2"><?php echo $_SESSION['user_name']; ?></span>
                    <button class="md:hidden rounded-md p-2 inline-flex items-center justify-center text-gray-500 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-500" id="menu-button">
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
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                        <p><?php echo $success_message; ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                        <p><?php echo $error_message; ?></p>
                    </div>
                <?php endif; ?>
                
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Booking Calendar -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                            <h3 class="text-lg font-medium text-gray-900">Reservation Calendar</h3>
                            <p class="mt-1 text-sm text-gray-500">View available dates for gymnasium Reservation</p>
                        </div>
                        <div class="p-4">
                            <div id="booking-calendar" class="bg-white p-2 rounded-lg"></div>
                            <div id="availability" class="mt-4 hidden">
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <h4 class="text-sm font-semibold text-blue-800 mb-3">Available Sessions on <span id="avail-date" class="font-normal text-blue-600"></span></h4>
                                    <div id="loading-indicator" class="text-center py-4 hidden">
                                        <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                                        <p class="mt-2 text-sm text-gray-600">Loading availability...</p>
                                    </div>
                                    <div id="session-buttons" class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-3"></div>
                                    <div id="booked-list" class="text-xs text-gray-600 bg-gray-50 p-2 rounded"></div>
                                    <div class="mt-2 text-xs text-gray-500">
                                        <i class="fas fa-info-circle mr-1"></i> Click on an available session to book that time slot
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center gap-4">
                                <div class="flex items-center gap-1">
                                    <div class="h-4 w-4 rounded-full bg-green-500"></div>
                                    <span class="text-sm">Available</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <div class="h-4 w-4 rounded-full bg-gray-300"></div>
                                    <span class="text-sm">Booked</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Booking Guidelines -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                            <h3 class="text-lg font-medium text-gray-900">Reservation Guidelines</h3>
                            <p class="mt-1 text-sm text-gray-500">Important information for gymnasium Reservation</p>
                        </div>
                        <div class="p-4">
                            <ul class="list-disc pl-5 space-y-2 text-sm text-gray-700">
                                <li>Reservation must be made at least 3 days in advance.</li>
                                <li>Maximum capacity of the gymnasium is 500 people.</li>
                                <li>Reservation are subject to approval by the administration.</li>
                                <li>Cancellations must be made at least 24 hours before the scheduled time.</li>
                                <li>The organization is responsible for cleaning up after the event.</li>
                                <li>Any damages to the facility will be charged to the Reservation organization.</li>
                                <li>For inquiries, please contact the BAO office at (034) 123-4567.</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- My Bookings -->
                <div class="mt-6 bg-white rounded-lg shadow">
                    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                        <h3 class="text-lg font-medium text-gray-900">My Reservation</h3>
                        <p class="mt-1 text-sm text-gray-500">View your gymnasium Reservation history</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reservation ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($bookings_result->num_rows > 0): ?>
                                    <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                                        <?php 
                                        $additional_info = json_decode($booking['additional_info'], true);
                                        $status_class = '';
                                        $status_text = ucfirst($booking['status']);
                                        
                                        switch ($booking['status']) {
                                            case 'pending':
                                                $status_class = 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'confirmed':
                                                $status_class = 'bg-green-100 text-green-800';
                                                break;
                                            case 'rejected':
                                                $status_class = 'bg-red-100 text-red-800';
                                                break;
                                            case 'cancelled':
                                                $status_class = 'bg-gray-100 text-gray-800';
                                                break;
                                        }
                                        ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $booking['booking_id']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('F j, Y', strtotime($booking['date'])); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php 
                                                echo date('h:i A', strtotime($booking['start_time'])) . ' - ' . 
                                                     date('h:i A', strtotime($booking['end_time'])); 
                                                ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500"><?php echo $booking['purpose']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <button type="button" class="text-amber-600 hover:text-amber-900 mr-3" onclick="viewBookingDetails(<?php echo htmlspecialchars(json_encode($booking)); ?>, <?php echo htmlspecialchars(json_encode($additional_info)); ?>)">
                                                    View
                                                </button>
                                                
                                                <?php if ($booking['status'] === 'pending'): ?>
                                                    <form method="POST" action="gym.php" class="inline">
                                                        <input type="hidden" name="action" value="cancel">
                                                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                                            Cancel
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No bookings found</td>
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

<!-- Booking Modal -->
<div id="bookingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Reservation Gymnasium</h3>
            <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeBookingModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="gym.php">
            <input type="hidden" name="action" value="book">
            <div class="mb-4">
                <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" id="date" name="date" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-500 focus:ring-opacity-50">
                <p class="mt-1 text-xs text-gray-500">Select an available date</p>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="start_time" class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                    <input type="time" id="start_time" name="start_time" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-500 focus:ring-opacity-50">
                </div>
                <div>
                    <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                    <input type="time" id="end_time" name="end_time" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-500 focus:ring-opacity-50">
                </div>
            </div>
            <div class="mb-4">
                <label for="purpose" class="block text-sm font-medium text-gray-700 mb-1">Purpose/Event Type</label>
                <select id="purpose" name="purpose" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-500 focus:ring-opacity-50">
                    <option value="">Select event type</option>
                    <option value="Graduation Ceremony">Graduation Ceremony</option>
                    <option value="Sports Tournament">Sports Tournament</option>
                    <option value="Conference">Conference</option>
                    <option value="Cultural Event">Cultural Event</option>
                    <option value="School Program">School Program</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="attendees" class="block text-sm font-medium text-gray-700 mb-1">Expected Number of Attendees</label>
                <input type="number" id="attendees" name="attendees" min="1" max="500" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-500 focus:ring-opacity-50">
                <p class="mt-1 text-xs text-gray-500">Maximum capacity: 500 people</p>
            </div>
            <div class="mb-4">
                <label for="organization" class="block text-sm font-medium text-gray-700 mb-1">Organization Name</label>
                <input type="text" id="organization" name="organization" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-500 focus:ring-opacity-50">
            </div>
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
                    <input type="text" id="contact_person" name="contact_person" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-500 focus:ring-opacity-50">
                </div>
                <div>
                    <label for="contact_number" class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                    <input type="text" id="contact_number" name="contact_number" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-500 focus:ring-opacity-50">
                </div>
            </div>
            <div class="flex justify-end">
                <button type="button" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md mr-2 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2" onclick="closeBookingModal()">
                    Cancel
                </button>
                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                    Submit Booking
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Booking Details Modal -->
<div id="detailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Reservation Details</h3>
            <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeDetailsModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="space-y-4">
            <div>
                <h4 class="text-sm font-medium text-gray-500">Reservation ID</h4>
                <p id="detail-booking-id" class="mt-1 text-sm text-gray-900"></p>
            </div>
            <div>
                <h4 class="text-sm font-medium text-gray-500">Date & Time</h4>
                <p id="detail-datetime" class="mt-1 text-sm text-gray-900"></p>
            </div>
            <div>
                <h4 class="text-sm font-medium text-gray-500">Purpose</h4>
                <p id="detail-purpose" class="mt-1 text-sm text-gray-900"></p>
            </div>
            <div>
                <h4 class="text-sm font-medium text-gray-500">Attendees</h4>
                <p id="detail-attendees" class="mt-1 text-sm text-gray-900"></p>
            </div>
            <div>
                <h4 class="text-sm font-medium text-gray-500">Organization</h4>
                <p id="detail-organization" class="mt-1 text-sm text-gray-900"></p>
            </div>
            <div>
                <h4 class="text-sm font-medium text-gray-500">Contact Person</h4>
                <p id="detail-contact-person" class="mt-1 text-sm text-gray-900"></p>
            </div>
            <div>
                <h4 class="text-sm font-medium text-gray-500">Contact Number</h4>
                <p id="detail-contact-number" class="mt-1 text-sm text-gray-900"></p>
            </div>
            <div>
                <h4 class="text-sm font-medium text-gray-500">Status</h4>
                <p id="detail-status" class="mt-1 text-sm"></p>
            </div>
            <div id="detail-rejection-reason-container" class="hidden">
                <h4 class="text-sm font-medium text-gray-500">Rejection Reason</h4>
                <p id="detail-rejection-reason" class="mt-1 text-sm text-gray-900"></p>
            </div>
        </div>
        <div class="mt-6 flex justify-end">
            <button type="button" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2" onclick="closeDetailsModal()">
                Close
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<script>
    // Mobile menu toggle
    document.getElementById('menu-button').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
    });
    
    // Booking modal functions
    function openBookingModal() {
        document.getElementById('bookingModal').classList.remove('hidden');
    }
    
    function closeBookingModal() {
        document.getElementById('bookingModal').classList.add('hidden');
    }
    
    // Details modal functions
    function viewBookingDetails(booking, additionalInfo) {
        document.getElementById('detail-booking-id').textContent = booking.booking_id;
        document.getElementById('detail-datetime').textContent = formatDate(booking.date) + ', ' + 
                                                               formatTime(booking.start_time) + ' - ' + 
                                                               formatTime(booking.end_time);
        document.getElementById('detail-purpose').textContent = booking.purpose;
        document.getElementById('detail-attendees').textContent = booking.attendees;
        document.getElementById('detail-organization').textContent = additionalInfo.organization;
        document.getElementById('detail-contact-person').textContent = additionalInfo.contact_person;
        document.getElementById('detail-contact-number').textContent = additionalInfo.contact_number;
        
        // Set status with appropriate styling
        const statusElement = document.getElementById('detail-status');
        statusElement.textContent = booking.status.charAt(0).toUpperCase() + booking.status.slice(1);
        
        // Reset classes
        statusElement.className = 'mt-1 text-sm px-2 inline-flex text-xs leading-5 font-semibold rounded-full';
        
        // Add appropriate class based on status
        switch (booking.status) {
            case 'pending':
                statusElement.classList.add('bg-yellow-100', 'text-yellow-800');
                break;
            case 'confirmed':
                statusElement.classList.add('bg-green-100', 'text-green-800');
                break;
            case 'rejected':
                statusElement.classList.add('bg-red-100', 'text-red-800');
                break;
            case 'cancelled':
                statusElement.classList.add('bg-gray-100', 'text-gray-800');
                break;
        }
        
        // Show rejection reason if available
        const rejectionContainer = document.getElementById('detail-rejection-reason-container');
        const rejectionReason = document.getElementById('detail-rejection-reason');
        
        if (booking.status === 'rejected' && additionalInfo.rejection_reason) {
            rejectionReason.textContent = additionalInfo.rejection_reason;
            rejectionContainer.classList.remove('hidden');
        } else {
            rejectionContainer.classList.add('hidden');
        }
        
        document.getElementById('detailsModal').classList.remove('hidden');
    }
    
    function closeDetailsModal() {
        document.getElementById('detailsModal').classList.add('hidden');
    }
    
    // Helper functions
    function formatDate(dateString) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    }
    
    function formatTime(timeString) {
        const [hours, minutes] = timeString.split(':');
        const date = new Date();
        date.setHours(hours);
        date.setMinutes(minutes);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    
    // Initialize calendar
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize flatpickr calendar (do not disable entire dates)
        flatpickr("#booking-calendar", {
            inline: true,
            minDate: "today",
            dateFormat: "Y-m-d",
            onChange: function(selectedDates, dateStr) {
                if (selectedDates.length > 0) {
                    document.getElementById('date').value = dateStr;
                    
                    // Show availability section and loading indicator
                    const avail = document.getElementById('availability');
                    const loadingIndicator = document.getElementById('loading-indicator');
                    const sessionsDiv = document.getElementById('session-buttons');
                    const bookedDiv = document.getElementById('booked-list');
                    
                    avail.classList.remove('hidden');
                    loadingIndicator.classList.remove('hidden');
                    sessionsDiv.innerHTML = '';
                    bookedDiv.innerHTML = '';
                    
                    // Fetch availability for selected date (assuming single gym facility)
                    const facilityIdField = document.querySelector('select[name="facility_id"], #facility_id');
                    const facilityId = facilityIdField ? facilityIdField.value || 1 : 1;
                    fetch(`get_gym_availability.php?date=${encodeURIComponent(dateStr)}&facility_id=${encodeURIComponent(facilityId)}`)
                        .then(r => {
                            console.log('Response status:', r.status);
                            if (!r.ok) {
                                throw new Error(`HTTP error! status: ${r.status}`);
                            }
                            return r.json();
                        })
                        .then(data => {
                            console.log('Availability data:', data);
                            // Hide loading indicator
                            loadingIndicator.classList.add('hidden');
                            
                            // Get the date span element
                            const dateSpan = document.getElementById('avail-date');
                            
                            // Format date for display
                            const selectedDate = new Date(dateStr);
                            const formattedDate = selectedDate.toLocaleDateString('en-US', { 
                                weekday: 'long', 
                                year: 'numeric', 
                                month: 'long', 
                                day: 'numeric' 
                            });
                            dateSpan.textContent = formattedDate;
                            
                            sessionsDiv.innerHTML = '';
                            bookedDiv.innerHTML = '';
                            
                            // Check if date is blocked by school event
                            if (data.is_blocked) {
                                const blockedDiv = document.createElement('div');
                                blockedDiv.className = 'text-center py-6 px-4';
                                const eventIcon = data.blocked_info?.event_type === 'ceremony' ? '🎓' : 
                                                 data.blocked_info?.event_type === 'intramurals' ? '🏅' : '🚫';
                                blockedDiv.innerHTML = `
                                    <div class="text-6xl mb-3">${eventIcon}</div>
                                    <p class="text-lg font-bold text-red-600 mb-2">Date Blocked</p>
                                    <p class="text-md font-semibold text-gray-800 mb-1">${data.blocked_info?.event_name || 'School Event'}</p>
                                    <p class="text-sm text-gray-600">${data.message || 'This date is not available for booking.'}</p>
                                    <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded">
                                        <p class="text-xs text-red-700">${data.blocked_info?.description || ''}</p>
                                    </div>
                                `;
                                sessionsDiv.appendChild(blockedDiv);
                                return; // Stop processing
                            }
                            
                            // Check if month is allowed (for external users)
                            if (data.month_allowed === false) {
                                const blockedDiv = document.createElement('div');
                                blockedDiv.className = 'text-center py-6 px-4';
                                blockedDiv.innerHTML = `
                                    <div class="text-6xl mb-3">📅</div>
                                    <p class="text-lg font-bold text-orange-600 mb-2">Month Not Available</p>
                                    <p class="text-sm text-gray-600 mb-3">${data.message || 'External users can only book during specific months.'}</p>
                                    <div class="mt-4 p-3 bg-orange-50 border border-orange-200 rounded">
                                        <p class="text-xs text-orange-700 font-semibold">Allowed Months:</p>
                                        <p class="text-xs text-orange-700">${(data.allowed_months || []).join(', ')}</p>
                                    </div>
                                `;
                                sessionsDiv.appendChild(blockedDiv);
                                return; // Stop processing
                            }
                            
                            // Check if any sessions are available
                            const availableSessions = data.sessions.filter(s => s.available);
                            const bookedSessions = data.sessions.filter(s => !s.available);
                            
                            if (availableSessions.length === 0) {
                                // All sessions are booked
                                const noAvailability = document.createElement('div');
                                noAvailability.className = 'text-center py-4 text-gray-600';
                                noAvailability.innerHTML = `
                                    <i class="fas fa-calendar-times text-2xl mb-2 text-red-500"></i>
                                    <p class="font-semibold">Fully Booked</p>
                                    <p class="text-sm">This date is completely booked. Please select another date.</p>
                                `;
                                sessionsDiv.appendChild(noAvailability);
                            } else {
                                // Show available sessions
                                availableSessions.forEach(s => {
                                    const btn = document.createElement('button');
                                    btn.type = 'button';
                                    
                                    // Special styling for whole day booking
                                    const isWholeDay = s.type === 'whole_day';
                                    const btnClass = isWholeDay 
                                        ? 'py-4 px-4 rounded-lg text-sm bg-blue-100 text-blue-800 hover:bg-blue-200 border-2 border-blue-400 transition-colors font-bold'
                                        : 'py-3 px-4 rounded-lg text-sm bg-green-100 text-green-800 hover:bg-green-200 border border-green-300 transition-colors';
                                    
                                    btn.innerHTML = `
                                        <div class="text-left">
                                            <div class="font-semibold">${s.label.split(' (')[0]}</div>
                                            <div class="text-xs opacity-75">${s.start.substring(0,5)} - ${s.end.substring(0,5)}</div>
                                            ${isWholeDay ? '<div class="text-xs font-bold text-blue-600 mt-1">🌟 FULL DAY ACCESS</div>' : ''}
                                        </div>
                                    `;
                                    btn.className = btnClass;
                                    btn.addEventListener('click', () => {
                                        document.getElementById('start_time').value = s.start.slice(0,5);
                                        document.getElementById('end_time').value = s.end.slice(0,5);
                                        openBookingModal();
                                    });
                                    sessionsDiv.appendChild(btn);
                                });
                                
                                // Additionally, compute dynamic free windows (like 14:30 - 18:00) and offer as custom slots
                                // Respect lunch break: 12:00 - 13:00
                                const dayStart = '08:00:00';
                                const lunchStart = '12:00:00';
                                const lunchEnd = '13:00:00';
                                const dayEnd = '18:00:00';
                                const bookedTimes = (data.booked || []).map(b => ({ start: b.start, end: b.end }))
                                    .sort((a, b) => a.start.localeCompare(b.start));
                                let currentTime = dayStart;
                                const freeWindows = [];
                                
                                bookedTimes.forEach(booking => {
                                    if (currentTime < booking.start) {
                                        // Split available window if it crosses lunch break
                                        if (currentTime < lunchStart && booking.start > lunchStart) {
                                            // Add morning slot (before lunch)
                                            if (currentTime < lunchStart) {
                                                freeWindows.push({ start: currentTime, end: lunchStart });
                                            }
                                            // Add afternoon slot (after lunch) if booking starts after lunch
                                            if (booking.start > lunchEnd) {
                                                freeWindows.push({ start: lunchEnd, end: booking.start });
                                            }
                                        } else if (currentTime >= lunchEnd || booking.start <= lunchStart) {
                                            // Normal window that doesn't cross lunch
                                            freeWindows.push({ start: currentTime, end: booking.start });
                                        }
                                    }
                                    if (booking.end > currentTime) {
                                        // Skip lunch break when updating current time
                                        if (booking.end > lunchStart && booking.end < lunchEnd) {
                                            currentTime = lunchEnd;
                                        } else if (currentTime < lunchStart && booking.end >= lunchStart) {
                                            currentTime = (booking.end >= lunchEnd) ? booking.end : lunchEnd;
                                        } else {
                                            currentTime = booking.end;
                                        }
                                    }
                                });
                                
                                // Check remaining time after last booking
                                if (currentTime < dayEnd) {
                                    // Split if it crosses lunch break
                                    if (currentTime < lunchStart) {
                                        freeWindows.push({ start: currentTime, end: lunchStart });
                                        freeWindows.push({ start: lunchEnd, end: dayEnd });
                                    } else if (currentTime >= lunchEnd) {
                                        freeWindows.push({ start: currentTime, end: dayEnd });
                                    }
                                    // If currentTime is between lunchStart and lunchEnd, start from lunchEnd
                                    else if (currentTime >= lunchStart && currentTime < lunchEnd) {
                                        freeWindows.push({ start: lunchEnd, end: dayEnd });
                                    }
                                }
                                // Render free windows of at least 60 minutes as selectable custom slots
                                freeWindows.forEach(w => {
                                    const [sh, sm] = w.start.split(':').map(Number);
                                    const [eh, em] = w.end.split(':').map(Number);
                                    const diffMinutes = (eh * 60 + em) - (sh * 60 + sm);
                                    if (diffMinutes >= 60) {
                                        const btn = document.createElement('button');
                                        btn.type = 'button';
                                        btn.className = 'py-3 px-4 rounded-lg text-sm bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-300 transition-colors';
                                        btn.innerHTML = `
                                            <div class="text-left">
                                                <div class="font-semibold">Available Window</div>
                                                <div class="text-xs opacity-75">${w.start.substring(0,5)} - ${w.end.substring(0,5)}</div>
                                            </div>
                                        `;
                                        btn.addEventListener('click', () => {
                                            document.getElementById('start_time').value = w.start.slice(0,5);
                                            document.getElementById('end_time').value = w.end.slice(0,5);
                                            openBookingModal();
                                        });
                                        sessionsDiv.appendChild(btn);
                                    }
                                });
                            }
                            
                            // Show booked sessions
                            if (bookedSessions.length > 0) {
                                const bookedTitle = document.createElement('div');
                                bookedTitle.className = 'font-semibold text-gray-700 mb-2';
                                bookedTitle.textContent = 'Booked Sessions:';
                                bookedDiv.appendChild(bookedTitle);
                                
                                bookedSessions.forEach(s => {
                                    const isWholeDay = s.type === 'whole_day';
                                    const itemClass = isWholeDay 
                                        ? 'flex items-center justify-between py-2 px-3 bg-red-100 rounded mb-2 border border-red-300'
                                        : 'flex items-center justify-between py-1 px-2 bg-red-50 rounded mb-1';
                                    
                                    const item = document.createElement('div');
                                    item.className = itemClass;
                                    item.innerHTML = `
                                        <span class="text-sm text-gray-700">${s.label.split(' (')[0]}</span>
                                        <span class="text-xs text-red-600 font-medium">${isWholeDay ? 'FULL DAY BOOKED' : 'Booked'}</span>
                                    `;
                                    bookedDiv.appendChild(item);
                                });
                                
                                // If there's a whole day booking, show a special notice
                                const hasWholeDayBooking = bookedSessions.some(s => s.type === 'whole_day');
                                if (hasWholeDayBooking) {
                                    const notice = document.createElement('div');
                                    notice.className = 'mt-3 p-3 bg-red-50 border border-red-200 rounded-lg';
                                    notice.innerHTML = `
                                        <div class="flex items-center">
                                            <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                                            <div>
                                                <p class="text-sm font-semibold text-red-800">Full Day Reserved</p>
                                                <p class="text-xs text-red-600">This date is completely booked for the entire day (8 AM - 6 PM)</p>
                                            </div>
                                        </div>
                                    `;
                                    bookedDiv.appendChild(notice);
                                }
                            }
                            
                            avail.classList.remove('hidden');
                        })
                        .catch(error => {
                            // Hide loading indicator
                            loadingIndicator.classList.add('hidden');
                            
                            // Show error message
                            sessionsDiv.innerHTML = `
                                <div class="text-center py-4 text-red-600">
                                    <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                                    <p class="font-semibold">Error Loading Availability</p>
                                    <p class="text-sm">Unable to check availability. Please try again or contact support.</p>
                                    <button onclick="openBookingModal()" class="mt-2 bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
                                        Book Manually
                                    </button>
                                </div>
                            `;
                            console.error('Error fetching availability:', error);
                        });
                }
            }
        });
        
        // Initialize date picker in booking form
        flatpickr("#date", {
            minDate: "today",
            dateFormat: "Y-m-d"
        });
    });
</script>

<?php include '../includes/footer.php'; ?>
