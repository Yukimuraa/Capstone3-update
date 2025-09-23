<?php
// This is a diagnostic file to help troubleshoot session issues
session_start();

echo "<h1>Session Diagnostic Tool</h1>";
echo "<p>Session ID: " . session_id() . "</p>";

echo "<h2>Session Variables:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Cookie Information:</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h2>Session Configuration:</h2>";
echo "<p>session.save_path: " . ini_get('session.save_path') . "</p>";
echo "<p>session.cookie_path: " . ini_get('session.cookie_path') . "</p>";
echo "<p>session.cookie_domain: " . ini_get('session.cookie_domain') . "</p>";
echo "<p>session.cookie_secure: " . ini_get('session.cookie_secure') . "</p>";
echo "<p>session.cookie_httponly: " . ini_get('session.cookie_httponly') . "</p>";
echo "<p>session.cookie_samesite: " . ini_get('session.cookie_samesite') . "</p>";

echo "<h2>PHP Info:</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

echo "<h2>Test Session Writing:</h2>";
$_SESSION['test_value'] = 'This is a test value set at ' . date('Y-m-d H:i:s');
echo "<p>Test value set. Refresh the page to see if it persists.</p>";

echo "<h2>Actions:</h2>";
echo "<p><a href='test_session.php?clear=1'>Clear Session</a></p>";
echo "<p><a href='login.php'>Go to Login</a></p>";

// Clear session if requested
if (isset($_GET['clear'])) {
    session_unset();
    session_destroy();
    echo "<p>Session cleared. <a href='test_session.php'>Refresh</a> to start a new session.</p>";
}
?>
