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
