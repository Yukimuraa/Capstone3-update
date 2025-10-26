<?php
/**
 * Fix Table References Script
 * This script updates SQL queries from 'users' to 'user_accounts' in PHP files
 */

$base_dir = dirname(__DIR__);

$files_to_fix = [
    'admin/gym_bookings.php',
    'admin/get_gym_events.php',
    'admin/orders.php',
    'admin/receipt.php',
    'admin/requests.php',
    'admin/view_request.php',
    'admin/reservation.php',
    'admin/print_receipt.php',
    'admin/print_order_receipt.php',
    'admin/gym_management.php',
    'admin/bookings.php',
    'external/requests.php',
    'external/get_request_details.php',
    'staff/dashboard.php',
    'staff/calendar.php',
    'student/receipt.php',
    'student/generate_pdf.php',
];

echo "<!DOCTYPE html>";
echo "<html><head><title>Fix Table References</title>";
echo "<style>body { font-family: monospace; max-width: 1000px; margin: 20px auto; padding: 20px; background: #1e1e1e; color: #d4d4d4; }";
echo ".success { color: #4ec9b0; } .error { color: #f48771; } .info { color: #569cd6; } .warning { color: #dcdcaa; }";
echo "pre { background: #2d2d2d; padding: 10px; border-radius: 5px; overflow-x: auto; }";
echo "</style></head><body>";

echo "<h1>🔧 Fix Table References</h1>";
echo "<p class='info'>Updating SQL queries from 'users' to 'user_accounts'...</p>";
echo "<hr>";

$total_fixed = 0;
$total_files = 0;

foreach ($files_to_fix as $file_path) {
    $full_path = $base_dir . '/' . $file_path;
    
    if (!file_exists($full_path)) {
        echo "<p class='warning'>⚠ Skip: $file_path (file not found)</p>";
        continue;
    }
    
    $content = file_get_contents($full_path);
    $original_content = $content;
    
    // Replace JOIN users with JOIN user_accounts
    $content = preg_replace('/\bJOIN\s+users\s+/i', 'JOIN user_accounts ', $content);
    
    // Replace FROM users with FROM user_accounts (only when not part of another word)
    $content = preg_replace('/\bFROM\s+users\s+/i', 'FROM user_accounts ', $content);
    
    // Replace INTO users with INTO user_accounts
    $content = preg_replace('/\bINTO\s+users\s+/i', 'INTO user_accounts ', $content);
    
    // Replace UPDATE users with UPDATE user_accounts
    $content = preg_replace('/\bUPDATE\s+users\s+/i', 'UPDATE user_accounts ', $content);
    
    if ($content !== $original_content) {
        // Count changes
        $changes = substr_count($content, 'user_accounts') - substr_count($original_content, 'user_accounts');
        
        // Write back
        if (file_put_contents($full_path, $content)) {
            echo "<p class='success'>✓ Fixed: $file_path ($changes replacement(s))</p>";
            $total_fixed += $changes;
            $total_files++;
        } else {
            echo "<p class='error'>✗ Error: Could not write to $file_path</p>";
        }
    } else {
        echo "<p class='info'>• Skip: $file_path (no changes needed)</p>";
    }
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p class='success'><strong>Files Updated:</strong> $total_files</p>";
echo "<p class='success'><strong>Total Replacements:</strong> $total_fixed</p>";

if ($total_files > 0) {
    echo "<p class='success' style='font-size: 18px;'>✓ All table references have been updated!</p>";
} else {
    echo "<p class='info'>ℹ No files needed updating.</p>";
}

echo "<p><a href='../login.php' style='color: #569cd6; text-decoration: none;'>→ Go to Login</a></p>";

echo "</body></html>";
?>








