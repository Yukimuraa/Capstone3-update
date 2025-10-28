<?php
// Check Bus Requests - Diagnostic Tool
require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Bus Requests Diagnostic</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; max-width: 1200px; margin: 30px auto; padding: 20px; background: #f5f5f5; }";
echo "table { width: 100%; border-collapse: collapse; background: white; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }";
echo "th { background: #4CAF50; color: white; padding: 12px; text-align: left; }";
echo "td { padding: 10px; border-bottom: 1px solid #ddd; }";
echo "tr:nth-child(even) { background: #f9f9f9; }";
echo ".badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }";
echo ".pending { background: #fff3cd; color: #856404; }";
echo ".approved { background: #d4edda; color: #155724; }";
echo ".rejected { background: #f8d7da; color: #721c24; }";
echo "h1 { color: #333; }";
echo "h2 { color: #555; margin-top: 30px; }";
echo ".info-box { background: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; }";
echo "</style>";
echo "</head><body>";

echo "<h1>🚍 Bus Requests Diagnostic Tool</h1>";

// Check if bus_schedules table exists
$tables_check = $conn->query("SHOW TABLES LIKE 'bus_schedules'");
if ($tables_check->num_rows == 0) {
    echo "<div style='background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0;'>";
    echo "<p style='color: #721c24;'><strong>❌ ERROR:</strong> The <code>bus_schedules</code> table does not exist!</p>";
    echo "</div>";
    exit;
}

// Get ALL bus schedules
$all_schedules = $conn->query("SELECT * FROM bus_schedules ORDER BY created_at DESC");
$total_count = $all_schedules->num_rows;

echo "<div class='info-box'>";
echo "<p><strong>Total Bus Requests Found:</strong> <span style='font-size: 24px; color: #2196F3;'>$total_count</span></p>";
echo "</div>";

if ($total_count == 0) {
    echo "<div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>";
    echo "<p style='color: #856404;'><strong>⚠️ WARNING:</strong> No bus requests found in the database!</p>";
    echo "<p>If you submitted a request, it may not have been saved properly.</p>";
    echo "</div>";
} else {
    // Count by status
    $status_counts = $conn->query("SELECT 
        status, 
        COUNT(*) as count,
        DATE_FORMAT(MIN(date_covered), '%M %Y') as earliest_date,
        DATE_FORMAT(MAX(date_covered), '%M %Y') as latest_date
        FROM bus_schedules 
        GROUP BY status");
    
    echo "<h2>Requests by Status</h2>";
    echo "<table>";
    echo "<tr><th>Status</th><th>Count</th><th>Date Range</th></tr>";
    while ($row = $status_counts->fetch_assoc()) {
        $badge_class = strtolower($row['status']);
        echo "<tr>";
        echo "<td><span class='badge $badge_class'>" . strtoupper($row['status']) . "</span></td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "<td>" . $row['earliest_date'] . " to " . $row['latest_date'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Count by month
    $month_counts = $conn->query("SELECT 
        DATE_FORMAT(date_covered, '%M %Y') as month_year,
        COUNT(*) as count,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count
        FROM bus_schedules 
        GROUP BY DATE_FORMAT(date_covered, '%Y-%m')
        ORDER BY date_covered DESC");
    
    echo "<h2>Requests by Month</h2>";
    echo "<table>";
    echo "<tr><th>Month</th><th>Total Requests</th><th>Pending</th></tr>";
    while ($row = $month_counts->fetch_assoc()) {
        $current_month = date('F Y');
        $highlight = ($row['month_year'] == $current_month) ? "style='background: #fff3cd;'" : "";
        echo "<tr $highlight>";
        echo "<td><strong>" . $row['month_year'] . "</strong>" . ($row['month_year'] == $current_month ? " <span style='color: #856404;'>← Current Month</span>" : "") . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "<td>" . $row['pending_count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show all schedules
    echo "<h2>All Bus Requests (Latest First)</h2>";
    echo "<table>";
    echo "<tr>";
    echo "<th>ID</th>";
    echo "<th>Client</th>";
    echo "<th>Destination</th>";
    echo "<th>Purpose</th>";
    echo "<th>Date Covered</th>";
    echo "<th>User Type</th>";
    echo "<th>Status</th>";
    echo "<th>Created At</th>";
    echo "</tr>";
    
    $all_schedules_data = $conn->query("SELECT * FROM bus_schedules ORDER BY created_at DESC");
    while ($schedule = $all_schedules_data->fetch_assoc()) {
        $badge_class = strtolower($schedule['status']);
        $date_month = date('F Y', strtotime($schedule['date_covered']));
        $current_month = date('F Y');
        $is_current_month = ($date_month == $current_month);
        
        echo "<tr" . ($is_current_month ? " style='background: #fffacd;'" : "") . ">";
        echo "<td>" . $schedule['id'] . "</td>";
        echo "<td>" . htmlspecialchars($schedule['client']) . "</td>";
        echo "<td>" . htmlspecialchars($schedule['destination']) . "</td>";
        echo "<td>" . htmlspecialchars($schedule['purpose']) . "</td>";
        echo "<td><strong>" . date('M d, Y', strtotime($schedule['date_covered'])) . "</strong><br><small style='color: #666;'>(" . $date_month . ")</small></td>";
        echo "<td>" . ucfirst($schedule['user_type']) . "</td>";
        echo "<td><span class='badge $badge_class'>" . strtoupper($schedule['status']) . "</span></td>";
        echo "<td>" . date('M d, Y H:i:s', strtotime($schedule['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='info-box'>";
    echo "<p><strong>💡 TIP:</strong> The admin page only shows requests for the <strong>current month (" . date('F Y') . ")</strong>.</p>";
    echo "<p>If your requests are for different months, they won't appear on the admin page unless you modify it to show all months.</p>";
    echo "</div>";
}

// Check buses table
$buses_check = $conn->query("SELECT COUNT(*) as count FROM buses");
$buses_count = $buses_check->fetch_assoc()['count'];

echo "<h2>Bus Inventory</h2>";
echo "<div class='info-box'>";
echo "<p><strong>Total Buses in System:</strong> <span style='font-size: 24px; color: #2196F3;'>$buses_count</span></p>";
echo "</div>";

if ($buses_count == 0) {
    echo "<div style='background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0;'>";
    echo "<p style='color: #721c24;'><strong>❌ ERROR:</strong> No buses found in the system! You need to add buses first.</p>";
    echo "</div>";
} else {
    $buses_list = $conn->query("SELECT * FROM buses ORDER BY bus_number");
    echo "<table>";
    echo "<tr><th>Bus Number</th><th>Vehicle Type</th><th>Capacity</th><th>Status</th></tr>";
    while ($bus = $buses_list->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($bus['bus_number']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($bus['vehicle_type']) . "</td>";
        echo "<td>" . $bus['capacity'] . " seats</td>";
        echo "<td>" . ucfirst($bus['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();

echo "</body></html>";
?>













