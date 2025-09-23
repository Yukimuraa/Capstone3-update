<?php
/**
 * Sanitize user input
 * 
 * @param string $data The data to sanitize
 * @return string The sanitized data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Send headers to prevent browser caching of protected pages
 */
function send_no_cache_headers() {
    if (headers_sent()) {
        return;
    }
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
}

/**
 * Check if user is logged in for a specific user type
 * 
 * @param string $user_type The user type to check
 * @return bool True if user is logged in as the specified type, false otherwise
 */
function is_logged_in($user_type = null) {
    // If no specific user type is provided, check if any user is logged in
    if ($user_type === null) {
        return isset($_SESSION['active_user_type']) && 
               isset($_SESSION['user_sessions'][$_SESSION['active_user_type']]);
    }
    
    // Check if the specific user type is logged in
    return isset($_SESSION['user_sessions'][$user_type]);
}

/**
 * Set the active user type for the current request
 * 
 * @param string $user_type The user type to set as active
 * @return bool True if successful, false if the user type is not logged in
 */
function set_active_user_type($user_type) {
    if (isset($_SESSION['user_sessions'][$user_type])) {
        $_SESSION['active_user_type'] = $user_type;
        
        // For backward compatibility, set the session variables directly
        $_SESSION['user_id'] = $_SESSION['user_sessions'][$user_type]['user_id'];
        $_SESSION['user_name'] = $_SESSION['user_sessions'][$user_type]['user_name'];
        $_SESSION['user_email'] = $_SESSION['user_sessions'][$user_type]['user_email'];
        $_SESSION['user_type'] = $_SESSION['user_sessions'][$user_type]['user_type'];
        
        return true;
    }
    return false;
}

/**
 * Get user data for the current active user type
 * 
 * @param string $key The user data key to retrieve
 * @return mixed The user data value or null if not found
 */
function get_user_data($key = null) {
    if (!isset($_SESSION['active_user_type']) || 
        !isset($_SESSION['user_sessions'][$_SESSION['active_user_type']])) {
        return null;
    }
    
    $user_data = $_SESSION['user_sessions'][$_SESSION['active_user_type']];
    
    if ($key === null) {
        return $user_data;
    }
    
    return $user_data[$key] ?? null;
}

/**
 * Redirect user if not logged in
 * 
 * @param string $redirect_url URL to redirect to if not logged in
 */
function require_login($redirect_url = '../login.php') {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    send_no_cache_headers();
    
    if (!is_logged_in()) {
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Redirect user if not admin
 * 
 * @param string $redirect_url URL to redirect to if not admin
 */
function require_admin($redirect_url = '../login.php') {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    send_no_cache_headers();
    
    // Check if admin is logged in
    if (!isset($_SESSION['user_sessions']['admin'])) {
        header("Location: $redirect_url");
        exit();
    }
    
    // Set admin as the active user type
    set_active_user_type('admin');
}

/**
 * Redirect user if not staff
 * 
 * @param string $redirect_url URL to redirect to if not staff
 */
function require_staff($redirect_url = '../login.php') {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    send_no_cache_headers();
    
    // Check if staff is logged in
    if (!isset($_SESSION['user_sessions']['staff'])) {
        header("Location: $redirect_url");
        exit();
    }
    
    // Set staff as the active user type
    set_active_user_type('staff');
}

/**
 * Redirect user if not student
 * 
 * @param string $redirect_url URL to redirect to if not student
 */
function require_student($redirect_url = '../login.php') {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    send_no_cache_headers();
    
    // Check if student is logged in
    if (!isset($_SESSION['user_sessions']['student'])) {
        header("Location: $redirect_url");
        exit();
    }
    
    // Set student as the active user type
    set_active_user_type('student');
}

/**
 * Redirect user if not external
 * 
 * @param string $redirect_url URL to redirect to if not external
 */
function require_external($redirect_url = '../login.php') {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    send_no_cache_headers();
    
    // Check if external is logged in
    if (!isset($_SESSION['user_sessions']['external'])) {
        header("Location: $redirect_url");
        exit();
    }
    
    // Set external as the active user type
    set_active_user_type('external');
}

/**
 * Check if user has admin role
 * 
 * @return bool True if user is admin, false otherwise
 */
function is_admin() {
    return isset($_SESSION['user_sessions']['admin']);
}

/**
 * Check if user has staff role
 * 
 * @return bool True if user is staff, false otherwise
 */
function is_staff() {
    return isset($_SESSION['user_sessions']['staff']);
}

/**
 * Check if user has student role
 * 
 * @return bool True if user is student, false otherwise
 */
function is_student() {
    return isset($_SESSION['user_sessions']['student']);
}

/**
 * Check if user has external role
 * 
 * @return bool True if user is external, false otherwise
 */
function is_external() {
    return isset($_SESSION['user_sessions']['external']);
}

/**
 * Generate a random string
 * 
 * @param int $length Length of the random string
 * @return string Random string
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Format date to a readable format
 * 
 * @param string $date Date string
 * @param string $format Format string
 * @return string Formatted date
 */
function format_date($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}
