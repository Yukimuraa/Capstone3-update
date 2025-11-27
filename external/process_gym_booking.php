<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an external user
require_external();

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get user ID from session
    $user_id = $_SESSION['user_sessions']['external']['user_id'];
    
    // Get form data
    $facility_id = (int)($_POST['facility_id'] ?? 0);
    $date = sanitize_input($_POST['date'] ?? '');
    $time_slot = sanitize_input($_POST['time_slot'] ?? '');
    $purpose = sanitize_input($_POST['purpose'] ?? '');
    $participants = (int)($_POST['participants'] ?? 0);
    
    // Validate form data
    $errors = [];
    
    if ($facility_id <= 0) {
        $errors[] = "Please select a valid facility";
    }
    
    if (empty($date)) {
        $errors[] = "Date is required";
    } else {
        // Check if date is in the future
        $booking_date = new DateTime($date);
        $today = new DateTime();
        $today->setTime(0, 0, 0); // Set time to beginning of day for comparison
        
        if ($booking_date < $today) {
            $errors[] = "Booking date must be in the future";
        }
        
        // Check if external user is booking within allowed months
        $booking_month = (int)$booking_date->format('n'); // 1-12
        $rules_check = $conn->query("SELECT allowed_months, is_active FROM gym_booking_rules WHERE user_type = 'external' AND is_active = 1 LIMIT 1");
        
        if ($rules_check && $rules_check->num_rows > 0) {
            $rules = $rules_check->fetch_assoc();
            $allowed_months = array_map('intval', explode(',', $rules['allowed_months']));
            
            if (!in_array($booking_month, $allowed_months)) {
                $month_names = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                $allowed_month_names = array_map(function($m) use ($month_names) { return $month_names[$m]; }, $allowed_months);
                $errors[] = "External users can only book gym facilities during: " . implode(', ', $allowed_month_names) . ". Please contact the BAO office for special requests.";
            }
        }
        
        // Check if the date is blocked by a school event
        $blocked_check = $conn->prepare("SELECT event_name, event_type, blocked_for_user_types 
                                          FROM gym_blocked_dates 
                                          WHERE ? BETWEEN start_date AND end_date 
                                          AND is_active = 1
                                          AND (blocked_for_user_types = 'all' OR blocked_for_user_types = 'external')");
        $blocked_check->bind_param("s", $date);
        $blocked_check->execute();
        $blocked_result = $blocked_check->get_result();
        
        if ($blocked_result->num_rows > 0) {
            $blocked_event = $blocked_result->fetch_assoc();
            $errors[] = "The gym is not available on this date due to: " . htmlspecialchars($blocked_event['event_name']) . ". Please choose a different date.";
        }
    }
    
    if (empty($time_slot)) {
        $errors[] = "Time slot is required";
    }
    
    if (empty($purpose)) {
        $errors[] = "Purpose is required";
    }
    
    if ($participants <= 0 || $participants > 50) {
        $errors[] = "Number of participants must be between 1 and 50";
    }
    
    // Check if the facility exists and is active
    $facility_check = $conn->prepare("SELECT * FROM gym_facilities WHERE id = ? AND status = 'active'");
    $facility_check->bind_param("i", $facility_id);
    $facility_check->execute();
    $facility_result = $facility_check->get_result();
    
    if ($facility_result->num_rows === 0) {
        $errors[] = "The selected facility is not available";
    } else {
        $facility = $facility_result->fetch_assoc();
        
        // Check if participants exceed facility capacity
        if ($participants > $facility['capacity']) {
            $errors[] = "The number of participants exceeds the facility capacity of " . $facility['capacity'];
        }
    }
    
    // If there are no errors, proceed with booking
    if (empty($errors)) {
        // Check if the facility is available at the requested time
        $availability_check = $conn->prepare("SELECT * FROM bookings WHERE facility_id = ? AND date = ? AND time_slot = ? AND status IN ('pending', 'confirmed') AND facility_type = 'gym'");
        $availability_check->bind_param("iss", $facility_id, $date, $time_slot);
        $availability_check->execute();
        $availability_result = $availability_check->get_result();
        
        if ($availability_result->num_rows > 0) {
            // Facility is already booked
            $_SESSION['error'] = "The selected facility is not available at the requested time. Please choose a different time or facility.";
            header("Location: dashboard.php");
            exit();
        }
        
        // Insert booking into database
        $insert_stmt = $conn->prepare("INSERT INTO bookings (user_id, facility_id, facility_type, date, time_slot, purpose, participants, status, created_at) VALUES (?, ?, 'gym', ?, ?, ?, ?, 'pending', NOW())");
        $insert_stmt->bind_param("iisssi", $user_id, $facility_id, $date, $time_slot, $purpose, $participants);
        
        if ($insert_stmt->execute()) {
            // Booking successful
            // Send notification to user
            require_once '../includes/notification_functions.php';
            $booking_id = $conn->insert_id;
            $date_formatted = date('F j, Y', strtotime($date));
            create_notification($user_id, "Gym Reservation Submitted", "Your gym reservation for {$date_formatted} has been submitted and is pending approval.", "booking", "external/dashboard.php");
            
            // Send notification to all admins
            $user_name = $_SESSION['user_sessions']['external']['user_name'] ?? 'User';
            create_notification_for_admins("New Gym Reservation Request", "{$user_name} has submitted a new gym reservation for {$date_formatted}. Please review and approve.", "booking", "admin/gym_bookings.php");
            
            $_SESSION['success'] = "Your gym reservation request has been submitted successfully. It is pending approval.";
            header("Location: dashboard.php");
            exit();
        } else {
            // Error inserting booking
            $_SESSION['error'] = "There was an error processing your reservation. Please try again. Error: " . $conn->error;
            header("Location: dashboard.php");
            exit();
        }
    } else {
        // There are validation errors
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: dashboard.php");
        exit();
    }
} else {
    // If not a POST request, redirect to dashboard
    header("Location: dashboard.php");
    exit();
}
?>
