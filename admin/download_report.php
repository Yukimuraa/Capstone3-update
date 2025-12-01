<?php
session_start();
// Set cache-busting headers to ensure fresh dates
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Set timezone to ensure correct date/time
// Force timezone to Asia/Manila for consistent results
date_default_timezone_set('Asia/Manila');

// Create DateTime object with current time and timezone
$now_dt = new DateTime('now', new DateTimeZone('Asia/Manila'));
$current_timestamp = $now_dt->getTimestamp();

require_once '../config/database.php';
require_once '../includes/functions.php';

require_admin();

$report_type = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';
$format = isset($_GET['format']) ? sanitize_input($_GET['format']) : 'pdf';

if (!in_array($report_type, ['gym', 'bus', 'inventory', 'billing', 'user', 'summary'])) {
    die('Invalid report type');
}

if (!in_array($format, ['pdf', 'excel'])) {
    die('Invalid format');
}

// Get filter parameters
$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : '';
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$department = isset($_GET['department']) ? sanitize_input($_GET['department']) : '';
$role = isset($_GET['role']) ? sanitize_input($_GET['role']) : '';
$service = isset($_GET['service']) ? sanitize_input($_GET['service']) : '';
$report_type_param = isset($_GET['report_type']) ? sanitize_input($_GET['report_type']) : '';
$mode = isset($_GET['mode']) ? sanitize_input($_GET['mode']) : 'daily';
$date = isset($_GET['date']) ? sanitize_input($_GET['date']) : date('Y-m-d');
$month = isset($_GET['month']) ? sanitize_input($_GET['month']) : date('Y-m');
$year = isset($_GET['year']) ? (int)sanitize_input($_GET['year']) : (int)date('Y');

// Fetch data based on report type
$data = [];
$headers = [];
$title = '';

switch ($report_type) {
    case 'bus':
        $title = 'Bus Reservation Report';
        $headers = ['Reservation ID', 'Requester/Dept', 'Destination', 'Date', 'Vehicles', 'Status', 'Total Amount'];
        
        $query = "SELECT s.id, s.client, s.destination, s.purpose, s.date_covered, s.vehicle, s.bus_no, s.no_of_days, s.no_of_vehicles, s.status,
                         bs.total_amount, bs.payment_status, bs.payment_date
                  FROM bus_schedules s
                  LEFT JOIN billing_statements bs ON bs.schedule_id = s.id
                  WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if (!empty($start_date)) { $query .= " AND s.date_covered >= ?"; $params[] = $start_date; $types .= "s"; }
        if (!empty($end_date))   { $query .= " AND s.date_covered <= ?"; $params[] = $end_date;   $types .= "s"; }
        if (!empty($status))     { $query .= " AND s.status = ?";         $params[] = $status;     $types .= "s"; }
        if (!empty($department)) { $query .= " AND s.client LIKE ?";      $params[] = "%$department%"; $types .= "s"; }
        
        $query .= " ORDER BY s.date_covered DESC";
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) { $stmt->bind_param($types, ...$params); }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $total_amount = 0.00;
        while ($row = $result->fetch_assoc()) {
            $amount = (float)($row['total_amount'] ?? 0);
            $total_amount += $amount;
            $data[] = [
                $row['id'],
                $row['client'],
                $row['destination'],
                date('M d, Y', strtotime($row['date_covered'])),
                $row['no_of_vehicles'],
                ucfirst($row['status']),
                '₱' . number_format($amount, 2)
            ];
        }
        break;
        
    case 'inventory':
        $title = 'Inventory Report';
        $headers = ['Item', 'Qty Issued', 'Revenue'];
        
        $orders_sql = "SELECT i.id as item_id, i.name as item_name, SUM(o.quantity) as qty_issued, SUM(o.total_price) as revenue
                       FROM orders o JOIN inventory i ON o.inventory_id = i.id
                       WHERE o.status IN ('approved','completed')";
        $params = []; $types = "";
        if (!empty($start_date)) { $orders_sql .= " AND DATE(o.created_at) >= ?"; $params[] = $start_date; $types .= "s"; }
        if (!empty($end_date))   { $orders_sql .= " AND DATE(o.created_at) <= ?"; $params[] = $end_date;   $types .= "s"; }
        $orders_sql .= " GROUP BY i.id, i.name ORDER BY revenue DESC";
        
        $stmt = $conn->prepare($orders_sql);
        if (!empty($params)) { $stmt->bind_param($types, ...$params); }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $total_revenue = 0.00;
        while ($row = $result->fetch_assoc()) {
            $revenue = (float)$row['revenue'];
            $total_revenue += $revenue;
            $data[] = [
                $row['item_name'],
                number_format($row['qty_issued']),
                '₱' . number_format($revenue, 2)
            ];
        }
        
        // Get Remaining Stock data
        $stock_data = [];
        try {
            $stock_sql = "SELECT name, stock_quantity AS remaining FROM inventory ORDER BY name ASC";
            $stock_result = $conn->query($stock_sql);
            if ($stock_result) {
                while ($stock_row = $stock_result->fetch_assoc()) {
                    $stock_data[] = [
                        $stock_row['name'],
                        number_format($stock_row['remaining'])
                    ];
                }
            }
        } catch (mysqli_sql_exception $e) {
            // Fallback schema without stock_quantity
            $stock_sql = "SELECT name, quantity AS remaining FROM inventory ORDER BY name ASC";
            $stock_result = $conn->query($stock_sql);
            if ($stock_result) {
                while ($stock_row = $stock_result->fetch_assoc()) {
                    $stock_data[] = [
                        $stock_row['name'],
                        number_format($stock_row['remaining'])
                    ];
                }
            }
        }
        break;
        
    case 'billing':
        $title = 'Billing Summary Report';
        $headers = ['Billing ID', 'Service', 'Requester / Details', 'Amount', 'Payment Status', 'Created'];
        
        $rows = [];
        
        if ($service === '' || $service === 'bus') {
            $sql = "SELECT 'Bus' as service, bs.id as billing_id, s.client as requester, s.destination as details, bs.total_amount as amount,
                        bs.payment_status as pay_status, bs.payment_date as paid_at, bs.created_at as created_at
                    FROM billing_statements bs JOIN bus_schedules s ON s.id = bs.schedule_id WHERE 1=1";
            $params = []; $types = "";
            if (!empty($status)) { $sql .= " AND bs.payment_status = ?"; $params[] = $status; $types .= "s"; }
            if (!empty($start_date)) { $sql .= " AND DATE(bs.created_at) >= ?"; $params[] = $start_date; $types .= "s"; }
            if (!empty($end_date))   { $sql .= " AND DATE(bs.created_at) <= ?"; $params[] = $end_date;   $types .= "s"; }
            $stmt = $conn->prepare($sql);
            if (!empty($params)) { $stmt->bind_param($types, ...$params); }
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) { $rows[] = $r; }
        }
        
        if ($service === '' || $service === 'items') {
            $sql2 = "SELECT 'Items' as service, o.id as billing_id, u.name as requester, i.name as details, o.total_price as amount,
                        CASE WHEN o.status IN ('completed') THEN 'paid' ELSE 'pending' END as pay_status, o.updated_at as paid_at, o.created_at as created_at
                    FROM orders o JOIN user_accounts u ON u.id = o.user_id JOIN inventory i ON i.id = o.inventory_id WHERE 1=1";
            $params2 = []; $types2 = "";
            if (!empty($status)) { 
                if ($status === 'paid') { $sql2 .= " AND o.status = 'completed'"; }
                elseif ($status === 'pending') { $sql2 .= " AND o.status IN ('pending','approved')"; }
                elseif ($status === 'cancelled') { $sql2 .= " AND o.status = 'cancelled'"; }
            }
            if (!empty($start_date)) { $sql2 .= " AND DATE(o.created_at) >= ?"; $params2[] = $start_date; $types2 .= "s"; }
            if (!empty($end_date))   { $sql2 .= " AND DATE(o.created_at) <= ?"; $params2[] = $end_date;   $types2 .= "s"; }
            $stmt2 = $conn->prepare($sql2);
            if (!empty($params2)) { $stmt2->bind_param($types2, ...$params2); }
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            while ($r = $res2->fetch_assoc()) { $rows[] = $r; }
        }
        
        foreach ($rows as $r) {
            $data[] = [
                $r['billing_id'],
                $r['service'],
                $r['requester'] . ' — ' . $r['details'],
                '₱' . number_format((float)$r['amount'], 2),
                ucfirst($r['pay_status']),
                date('M d, Y', strtotime($r['created_at']))
            ];
        }
        break;
        
    case 'user':
        $title = 'User Accounts Report';
        $headers = ['User ID', 'Name', 'Email', 'Role', 'Status', 'Date Registered'];
        
        $sql = "SELECT id, name, email, user_type, status, created_at FROM user_accounts WHERE 1=1";
        $params = []; $types = "";
        if (!empty($role)) { $sql .= " AND user_type = ?"; $params[] = $role; $types .= "s"; }
        if (!empty($status)) { $sql .= " AND status = ?"; $params[] = $status; $types .= "s"; }
        if (!empty($start_date)) { $sql .= " AND DATE(created_at) >= ?"; $params[] = $start_date; $types .= "s"; }
        if (!empty($end_date))   { $sql .= " AND DATE(created_at) <= ?"; $params[] = $end_date;   $types .= "s"; }
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) { $stmt->bind_param($types, ...$params); }
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                $row['id'],
                $row['name'],
                $row['email'],
                ucfirst($row['user_type']),
                ucfirst($row['status']),
                date('M d, Y', strtotime($row['created_at']))
            ];
        }
        break;
        
    case 'summary':
        $title = 'Transaction Summary Report';
        $headers = ['Service', 'Requests', 'Approved', 'Collected'];
        
        if ($mode === 'daily') {
            $start = $date; $end = $date;
        } elseif ($mode === 'monthly') {
            $start = $month.'-01'; $end = date('Y-m-t', strtotime($start));
        } else {
            $start = sprintf('%04d-01-01', $year);
            $end = sprintf('%04d-12-31', $year);
        }
        
        $count_bookings = (int)$conn->query("SELECT COUNT(*) as c FROM bookings WHERE facility_type='gym' AND DATE(created_at) BETWEEN '".$conn->real_escape_string($start)."' AND '".$conn->real_escape_string($end)."'")->fetch_assoc()['c'] ?? 0;
        $count_bus = (int)$conn->query("SELECT COUNT(*) as c FROM bus_schedules WHERE DATE(date_covered) BETWEEN '".$conn->real_escape_string($start)."' AND '".$conn->real_escape_string($end)."'")->fetch_assoc()['c'] ?? 0;
        $count_orders = (int)$conn->query("SELECT COUNT(*) as c FROM orders WHERE DATE(created_at) BETWEEN '".$conn->real_escape_string($start)."' AND '".$conn->real_escape_string($end)."'")->fetch_assoc()['c'] ?? 0;
        $approved_bookings = (int)$conn->query("SELECT COUNT(*) as c FROM bookings WHERE facility_type='gym' AND (status='confirmed' OR status='approved') AND DATE(updated_at) BETWEEN '".$conn->real_escape_string($start)."' AND '".$conn->real_escape_string($end)."'")->fetch_assoc()['c'] ?? 0;
        $approved_bus = (int)$conn->query("SELECT COUNT(*) as c FROM bus_schedules WHERE status='approved' AND DATE(date_covered) BETWEEN '".$conn->real_escape_string($start)."' AND '".$conn->real_escape_string($end)."'")->fetch_assoc()['c'] ?? 0;
        $completed_orders = (int)$conn->query("SELECT COUNT(*) as c FROM orders WHERE status='completed' AND DATE(updated_at) BETWEEN '".$conn->real_escape_string($start)."' AND '".$conn->real_escape_string($end)."'")->fetch_assoc()['c'] ?? 0;
        $col_orders = (float)($conn->query("SELECT SUM(total_price) as s FROM orders WHERE status='completed' AND DATE(updated_at) BETWEEN '".$conn->real_escape_string($start)."' AND '".$conn->real_escape_string($end)."'")->fetch_assoc()['s'] ?? 0);
        $col_bus = (float)($conn->query("SELECT SUM(total_amount) as s FROM billing_statements WHERE payment_status='paid' AND DATE(payment_date) BETWEEN '".$conn->real_escape_string($start)."' AND '".$conn->real_escape_string($end)."'")->fetch_assoc()['s'] ?? 0);
        
        // Gym collections: sum total from cost_breakdown in additional_info for confirmed bookings with or_number
        $gym_collections_query = "SELECT additional_info FROM bookings WHERE facility_type='gym' AND (status='confirmed' OR status='approved') AND or_number IS NOT NULL AND or_number != '' AND DATE(updated_at) BETWEEN '".$conn->real_escape_string($start)."' AND '".$conn->real_escape_string($end)."'";
        $gym_collections_result = $conn->query($gym_collections_query);
        $col_gym = 0.0;
        if ($gym_collections_result) {
            while ($row = $gym_collections_result->fetch_assoc()) {
                if (!empty($row['additional_info'])) {
                    $additional_info = json_decode($row['additional_info'], true);
                    if (isset($additional_info['cost_breakdown']['total'])) {
                        $col_gym += (float)$additional_info['cost_breakdown']['total'];
                    }
                }
            }
        }
        
        $data[] = ['Gym', number_format($count_bookings), number_format($approved_bookings), '₱' . number_format($col_gym, 2)];
        $data[] = ['Bus', number_format($count_bus), number_format($approved_bus), '₱' . number_format($col_bus, 2)];
        $data[] = ['Item Sales', number_format($count_orders), number_format($completed_orders), '₱' . number_format($col_orders, 2)];
        
        // Calculate totals
        $total_requests = $count_bookings + $count_bus + $count_orders;
        $total_approved = $approved_bookings + $approved_bus + $completed_orders;
        $total_collected = $col_gym + $col_bus + $col_orders;
        break;
        
    case 'gym':
        // Gym reports - show detailed booking information
        $title = 'Gym Bookings Detailed Report';
        $headers = ['Booking ID', 'Status', 'Facility', 'Date', 'Time', 'Purpose', 'Attendees', 'Requested On', 'Requester Name', 'Department/Organization', 'Equipment/Services'];
        
        // Get date filters
        $start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
        $end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : date('Y-m-d');
        $status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
        
        // Build query to get detailed booking information
        $query = "SELECT b.booking_id, b.status, b.date, b.start_time, b.end_time, b.purpose, b.attendees, 
                         b.created_at, b.or_number, b.additional_info,
                         u.name as user_name, u.email as user_email, u.organization
                  FROM bookings b
                  LEFT JOIN user_accounts u ON b.user_id = u.id
                  WHERE b.facility_type = 'gym' AND b.date BETWEEN ? AND ?";
        
        $params = [$start_date, $end_date];
        $types = "ss";
        
        // Apply status filter
        if (!empty($status_filter)) {
            if ($status_filter === 'approved') {
                $query .= " AND (b.status = 'approved' OR b.status = 'confirmed')";
            } else {
                $query .= " AND b.status = ?";
                $params[] = $status_filter;
                $types .= "s";
            }
        }
        
        $query .= " ORDER BY b.date DESC, b.created_at DESC";
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Parse additional_info JSON
            $additional_info = [];
            if (!empty($row['additional_info'])) {
                $additional_info = json_decode($row['additional_info'], true);
            }
            
            // Get facility name
            $facility_name = "Gymnasium"; // Default
            if (isset($additional_info['facility_id']) && !empty($additional_info['facility_id'])) {
                $facility_id = intval($additional_info['facility_id']);
                $facility_stmt = $conn->prepare("SELECT name FROM gym_facilities WHERE id = ?");
                $facility_stmt->bind_param("i", $facility_id);
                $facility_stmt->execute();
                $facility_result = $facility_stmt->get_result();
                if ($facility_result->num_rows > 0) {
                    $facility_row = $facility_result->fetch_assoc();
                    $facility_name = $facility_row['name'];
                }
            }
            
            // Format booking ID (already in GYM-YYYY-XXX format)
            $booking_id = $row['booking_id'];
            
            // Format status
            $status_display = ucfirst($row['status']);
            if ($row['status'] === 'confirmed') {
                $status_display = 'Approved';
            }
            
            // Format date
            $date_formatted = date('F j, Y', strtotime($row['date']));
            
            // Format time
            $time_formatted = date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time']));
            
            // Format requested on
            $requested_on = date('F j, Y g:i A', strtotime($row['created_at']));
            
            // Get equipment/services
            $equipment = isset($additional_info['equipment']) ? $additional_info['equipment'] : 'None selected';
            
            // Get organization/department
            $organization = !empty($row['organization']) ? $row['organization'] : 'N/A';
            
            $data[] = [
                $booking_id,
                $status_display,
                $facility_name,
                $date_formatted,
                $time_formatted,
                $row['purpose'],
                $row['attendees'] ?? 'N/A',
                $requested_on,
                $row['user_name'] ?? 'N/A',
                $organization,
                $equipment
            ];
        }
        break;
}

// Generate file based on format
if ($format === 'pdf') {
    // Check if TCPDF is available
    $tcpdf_available = false;
    if (file_exists('../vendor/autoload.php')) {
        require_once '../vendor/autoload.php';
        if (class_exists('TCPDF')) {
            $tcpdf_available = true;
        }
    }
    
    if ($tcpdf_available) {
        // Use TCPDF for PDF generation
        // Clear output buffer
        if (ob_get_length()) {
            ob_end_clean();
        }
        
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('CHMSU BAO System');
        $pdf->SetAuthor('CHMSU BAO');
        $pdf->SetTitle($title);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();
        
        // Header - use DateTime for accurate timezone-aware dates
        $now_dt = new DateTime('now', new DateTimeZone('Asia/Manila'));
        $html = '<h1 style="text-align:center; font-size:18px; margin-bottom:10px;">' . htmlspecialchars($title) . '</h1>';
        $html .= '<p style="text-align:center; font-size:10px; color:#666; margin-bottom:15px;">Generated on: ' . $now_dt->format('F j, Y, g:i a') . '</p>';
        
        // Table
        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">';
        $html .= '<thead><tr style="background-color:#f3f4f6; font-weight:bold;">';
        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        
        // Add total row for bus reports
        if ($report_type === 'bus' && isset($total_amount)) {
            $html .= '<tr style="background-color:#f3f4f6; font-weight:bold;">';
            $html .= '<td colspan="' . (count($headers) - 1) . '" style="text-align:right;">Total Amount:</td>';
            $html .= '<td style="text-align:right;">₱' . number_format($total_amount, 2) . '</td>';
            $html .= '</tr>';
        }
        
        // Add total row for inventory reports
        if ($report_type === 'inventory' && isset($total_revenue)) {
            $html .= '<tr style="background-color:#f3f4f6; font-weight:bold;">';
            $html .= '<td colspan="' . (count($headers) - 1) . '" style="text-align:right;">Total Revenue:</td>';
            $html .= '<td style="text-align:right;">₱' . number_format($total_revenue, 2) . '</td>';
            $html .= '</tr>';
        }
        
        // Add total row for summary reports
        if ($report_type === 'summary' && isset($total_requests) && isset($total_approved) && isset($total_collected)) {
            $html .= '<tr style="background-color:#f3f4f6; font-weight:bold;">';
            $html .= '<td style="text-align:right;">Total:</td>';
            $html .= '<td style="text-align:right;">' . number_format($total_requests) . '</td>';
            $html .= '<td style="text-align:right;">' . number_format($total_approved) . '</td>';
            $html .= '<td style="text-align:right;">₱' . number_format($total_collected, 2) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        
        // Add Remaining Stock section for inventory reports
        if ($report_type === 'inventory' && isset($stock_data) && !empty($stock_data)) {
            $html .= '<br><br><h2 style="font-size:14px; margin-bottom:10px;">Remaining Stock</h2>';
            $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">';
            $html .= '<thead><tr style="background-color:#f3f4f6; font-weight:bold;">';
            $html .= '<th>Item</th><th>Remaining</th>';
            $html .= '</tr></thead><tbody>';
            
            foreach ($stock_data as $stock_row) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($stock_row[0]) . '</td>';
                $html .= '<td style="text-align:right;">' . htmlspecialchars($stock_row[1]) . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
        }
        
        $pdf->writeHTML($html, true, false, true, false, '');
        
        $filename = strtolower(str_replace(' ', '_', $title)) . '_' . (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Ymd_His') . '.pdf';
        $pdf->Output($filename, 'D');
    } else {
        // Fallback: Generate print-friendly HTML that can be saved as PDF
        // Clear output buffer
        if (ob_get_length()) {
            ob_end_clean();
        }
        
        header('Content-Type: text/html; charset=utf-8');
        
        $filename = strtolower(str_replace(' ', '_', $title)) . '_' . (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Ymd_His');
        
        // Get base URL for logo
        $logo_path = '../image/CHMSUWebLOGO.png';
        $logo_base64 = '';
        if (file_exists($logo_path)) {
            $logo_data = file_get_contents($logo_path);
            $logo_base64 = 'data:image/png;base64,' . base64_encode($logo_data);
        }
        
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        @media print {
            body { margin: 0; padding: 20px; }
            .no-print { display: none !important; }
            @page { margin: 1cm; }
        }
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .report-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            color: white;
            padding: 20px;
            margin: -20px -20px 30px -20px;
            text-align: center;
            border-top: 4px solid #fbbf24;
        }
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-bottom: 15px;
        }
        .logo-container img {
            height: 80px;
            width: auto;
        }
        .report-header h1 {
            margin: 10px 0 5px 0;
            font-size: 24px;
            color: white;
            font-weight: bold;
        }
        .report-header p {
            margin: 5px 0;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.95);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            background: white;
            padding: 20px;
        }
        .header h2 {
            margin: 0;
            font-size: 20px;
            color: #1a1a1a;
            font-weight: bold;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 12px;
            color: #666;
        }
        .info {
            margin-bottom: 20px;
            font-size: 12px;
            color: #555;
            padding: 10px;
            background: #f9fafb;
            border-left: 4px solid #1e3a8a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 11px;
        }
        th {
            background-color: #f3f4f6;
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #333;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .back-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #1e3a8a;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        .back-button:hover {
            background-color: #1e40af;
        }
        @media print {
            .back-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <button class="back-button no-print" onclick="window.history.back()">
        <i class="fas fa-arrow-left"></i> Back
    </button>
    <div class="report-header">
        <div class="logo-container">';
        if ($logo_base64) {
            echo '<img src="' . $logo_base64 . '" alt="CHMSU Logo">';
        }
        echo '</div>
        <h1>BUSINESS AFFAIRS OFFICE REPORTS</h1>
        <p>CITY OF TALISAY, Province of Negros Occidental</p>
        <p>CHMSU - Carlos Hilado Memorial State University</p>
    </div>
    
    <div class="header">
        <h2>' . htmlspecialchars($title) . '</h2>
        <p>Generated on: ' . (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('F j, Y, g:i a') . '</p>
    </div>';
        
        // Add filter info if available
        if (!empty($start_date) || !empty($end_date) || !empty($status)) {
            echo '<div class="info">';
            if (!empty($start_date)) echo '<strong>Start Date:</strong> ' . date('F j, Y', strtotime($start_date)) . ' | ';
            if (!empty($end_date)) echo '<strong>End Date:</strong> ' . date('F j, Y', strtotime($end_date)) . ' | ';
            if (!empty($status)) echo '<strong>Status:</strong> ' . ucfirst($status);
            echo '</div>';
        }
        
        echo '<table>
        <thead>
            <tr>';
        foreach ($headers as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr>
        </thead>
        <tbody>';
        
        foreach ($data as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . htmlspecialchars($cell) . '</td>';
            }
            echo '</tr>';
        }
        
        // Add total row for bus reports
        if ($report_type === 'bus' && isset($total_amount)) {
            echo '<tr style="background-color:#f3f4f6; font-weight:bold;">';
            echo '<td colspan="' . (count($headers) - 1) . '" style="text-align:right;">Total Amount:</td>';
            echo '<td style="text-align:right;">₱' . number_format($total_amount, 2) . '</td>';
            echo '</tr>';
        }
        
        // Add total row for inventory reports
        if ($report_type === 'inventory' && isset($total_revenue)) {
            echo '<tr style="background-color:#f3f4f6; font-weight:bold;">';
            echo '<td colspan="' . (count($headers) - 1) . '" style="text-align:right;">Total Revenue:</td>';
            echo '<td style="text-align:right;">₱' . number_format($total_revenue, 2) . '</td>';
            echo '</tr>';
        }
        
        // Add total row for summary reports
        if ($report_type === 'summary' && isset($total_requests) && isset($total_approved) && isset($total_collected)) {
            echo '<tr style="background-color:#f3f4f6; font-weight:bold;">';
            echo '<td style="text-align:right;">Total:</td>';
            echo '<td style="text-align:right;">' . number_format($total_requests) . '</td>';
            echo '<td style="text-align:right;">' . number_format($total_approved) . '</td>';
            echo '<td style="text-align:right;">₱' . number_format($total_collected, 2) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>
    </table>';
        
        // Add Remaining Stock section for inventory reports
        if ($report_type === 'inventory' && isset($stock_data) && !empty($stock_data)) {
            echo '<br><br><h2 style="font-size:16px; margin-bottom:15px; border-bottom:2px solid #333; padding-bottom:5px;">Remaining Stock</h2>';
            echo '<table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Remaining</th>
                </tr>
            </thead>
            <tbody>';
            
            foreach ($stock_data as $stock_row) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($stock_row[0]) . '</td>';
                echo '<td style="text-align:right;">' . htmlspecialchars($stock_row[1]) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>
        </table>';
        }
        
        echo '
    
    <div class="footer">
        <p><strong>City of Talisay Business Affairs Office</strong></p>
        <p>This report was generated automatically on ' . (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('F j, Y') . ' at ' . (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('g:i A') . '</p>
        <p>For inquiries, please contact the Business Affairs Office</p>
    </div>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        // Auto-trigger print dialog after page loads
        window.onload = function() {
            // Small delay to ensure page is fully rendered
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>';
        exit;
    }
    
} else { // Excel format
    // Clear output buffer
    if (ob_get_length()) {
        ob_end_clean();
    }
    
    // Check if PhpSpreadsheet is available
    $phpspreadsheet_available = false;
    if (file_exists('../vendor/autoload.php')) {
        require_once '../vendor/autoload.php';
        if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            $phpspreadsheet_available = true;
        }
    }
    
    if ($phpspreadsheet_available) {
        // Use PhpSpreadsheet for proper Excel formatting with borders
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $currentRow = 1;
        
        // Professional Report Header
        $sheet->setCellValue('A' . $currentRow, 'BUSINESS AFFAIRS OFFICE REPORTS');
        $sheet->mergeCells('A' . $currentRow . ':' . chr(64 + count($headers)) . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $currentRow++;
        
        $sheet->setCellValue('A' . $currentRow, 'CITY OF TALISAY, Province of Negros Occidental');
        $sheet->mergeCells('A' . $currentRow . ':' . chr(64 + count($headers)) . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $currentRow++;
        
        $sheet->setCellValue('A' . $currentRow, 'CHMSU - Carlos Hilado Memorial State University');
        $sheet->mergeCells('A' . $currentRow . ':' . chr(64 + count($headers)) . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getFont()->setSize(12);
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $currentRow += 2;
        
        // Generation date and time (generate fresh each time using DateTime for accuracy)
        $now_dt = new DateTime('now', new DateTimeZone('Asia/Manila'));
        $generated_date = $now_dt->format('F j, Y');
        $generated_time = $now_dt->format('g:i A');
        $sheet->setCellValue('A' . $currentRow, 'Generated on:');
        $sheet->setCellValue('B' . $currentRow, $generated_date . ' at ' . $generated_time);
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
        $currentRow += 2;
        
        // Report title
        $sheet->setCellValue('A' . $currentRow, $title);
        $sheet->mergeCells('A' . $currentRow . ':' . chr(64 + count($headers)) . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $currentRow += 2;
        
        // Covered period/filters
        $period_info = [];
        
        // Handle mode-based filters (for summary reports)
        if (isset($mode)) {
            if ($mode === 'daily') {
                $period_info[] = 'Date: ' . date('F j, Y', strtotime($date));
            } elseif ($mode === 'monthly') {
                $period_info[] = 'Month: ' . date('F Y', strtotime($month . '-01'));
            } elseif ($mode === 'yearly') {
                $period_info[] = 'Year: ' . $year;
            }
        }
        
        // Handle date range filters
        if (!empty($start_date) && !empty($end_date)) {
            if ($start_date === $end_date) {
                $period_info[] = 'Date: ' . date('F j, Y', strtotime($start_date));
            } else {
                $period_info[] = 'Period: ' . date('F j, Y', strtotime($start_date)) . ' - ' . date('F j, Y', strtotime($end_date));
            }
        } elseif (!empty($start_date)) {
            $period_info[] = 'From: ' . date('F j, Y', strtotime($start_date));
        } elseif (!empty($end_date)) {
            $period_info[] = 'To: ' . date('F j, Y', strtotime($end_date));
        }
        
        // Handle other filters
        if (!empty($status)) {
            $period_info[] = 'Status: ' . ucfirst($status);
        }
        if (!empty($role)) {
            $period_info[] = 'Role: ' . ucfirst($role);
        }
        if (!empty($department)) {
            $period_info[] = 'Department: ' . htmlspecialchars($department);
        }
        if (!empty($service)) {
            $period_info[] = 'Service: ' . ucfirst($service);
        }
        if (!empty($report_type_param)) {
            $period_info[] = 'Report Type: ' . ucfirst($report_type_param);
        }
        
        if (!empty($period_info)) {
            $col = 'A';
            foreach ($period_info as $info) {
                $sheet->setCellValue($col . $currentRow, $info);
                $col++;
            }
            $currentRow += 2;
        }
        
        // Table headers with borders and styling
        $headerRow = $currentRow;
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $currentRow, $header);
            $sheet->getStyle($col . $currentRow)->getFont()->setBold(true);
            $sheet->getStyle($col . $currentRow)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D3D3D3');
            $sheet->getStyle($col . $currentRow)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $col++;
        }
        $currentRow++;
        
        // Write data with borders
        foreach ($data as $row) {
            $col = 'A';
            foreach ($row as $cell) {
                $sheet->setCellValue($col . $currentRow, $cell);
                $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $col++;
            }
            $currentRow++;
        }
        
        // Add total row for bus reports
        if ($report_type === 'bus' && isset($total_amount)) {
            $col = 'A';
            $lastCol = chr(64 + count($headers) - 1);
            $amountCol = chr(64 + count($headers));
            
            // Merge cells for "Total Amount:" label
            $sheet->setCellValue($col . $currentRow, 'Total Amount:');
            $sheet->mergeCells($col . $currentRow . ':' . $lastCol . $currentRow);
            $sheet->getStyle($col . $currentRow)->getFont()->setBold(true);
            $sheet->getStyle($col . $currentRow)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D3D3D3');
            $sheet->getStyle($col . $currentRow)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            // Total amount value
            $sheet->setCellValue($amountCol . $currentRow, '₱' . number_format($total_amount, 2));
            $sheet->getStyle($amountCol . $currentRow)->getFont()->setBold(true);
            $sheet->getStyle($amountCol . $currentRow)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D3D3D3');
            $sheet->getStyle($amountCol . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            $currentRow++;
        }
        
        // Add total row for inventory reports
        if ($report_type === 'inventory' && isset($total_revenue)) {
            $col = 'A';
            $lastCol = chr(64 + count($headers) - 1);
            $revenueCol = chr(64 + count($headers));
            
            // Merge cells for "Total Revenue:" label
            $sheet->setCellValue($col . $currentRow, 'Total Revenue:');
            $sheet->mergeCells($col . $currentRow . ':' . $lastCol . $currentRow);
            $sheet->getStyle($col . $currentRow)->getFont()->setBold(true);
            $sheet->getStyle($col . $currentRow)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D3D3D3');
            $sheet->getStyle($col . $currentRow)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            // Total revenue value
            $sheet->setCellValue($revenueCol . $currentRow, '₱' . number_format($total_revenue, 2));
            $sheet->getStyle($revenueCol . $currentRow)->getFont()->setBold(true);
            $sheet->getStyle($revenueCol . $currentRow)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D3D3D3');
            $sheet->getStyle($revenueCol . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            $currentRow++;
        }
        
        // Add Remaining Stock section for inventory reports
        if ($report_type === 'inventory' && isset($stock_data) && !empty($stock_data)) {
            $currentRow += 2; // Add spacing
            
            // Section header
            $sheet->setCellValue('A' . $currentRow, 'Remaining Stock');
            $sheet->mergeCells('A' . $currentRow . ':' . chr(64 + count($headers)) . $currentRow);
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $currentRow++;
            
            // Stock table headers
            $sheet->setCellValue('A' . $currentRow, 'Item');
            $sheet->setCellValue('B' . $currentRow, 'Remaining');
            $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)->getFont()->setBold(true);
            $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D3D3D3');
            $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $currentRow++;
            
            // Stock data rows
            foreach ($stock_data as $stock_row) {
                $sheet->setCellValue('A' . $currentRow, $stock_row[0]);
                $sheet->setCellValue('B' . $currentRow, $stock_row[1]);
                $sheet->getStyle('A' . $currentRow . ':B' . $currentRow)->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->getStyle('B' . $currentRow)->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $currentRow++;
            }
        }
        
        // Add total row for summary reports
        if ($report_type === 'summary' && isset($total_requests) && isset($total_approved) && isset($total_collected)) {
            $serviceCol = 'A';
            $requestsCol = 'B';
            $approvedCol = 'C';
            $collectedCol = 'D';
            
            // Total label
            $sheet->setCellValue($serviceCol . $currentRow, 'Total:');
            $sheet->getStyle($serviceCol . $currentRow)->getFont()->setBold(true);
            $sheet->getStyle($serviceCol . $currentRow)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D3D3D3');
            $sheet->getStyle($serviceCol . $currentRow)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle($serviceCol . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            // Total requests
            $sheet->setCellValue($requestsCol . $currentRow, number_format($total_requests));
            $sheet->getStyle($requestsCol . $currentRow)->getFont()->setBold(true);
            $sheet->getStyle($requestsCol . $currentRow)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D3D3D3');
            $sheet->getStyle($requestsCol . $currentRow)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle($requestsCol . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            // Total approved
            $sheet->setCellValue($approvedCol . $currentRow, number_format($total_approved));
            $sheet->getStyle($approvedCol . $currentRow)->getFont()->setBold(true);
            $sheet->getStyle($approvedCol . $currentRow)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D3D3D3');
            $sheet->getStyle($approvedCol . $currentRow)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle($approvedCol . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            // Total collected
            $sheet->setCellValue($collectedCol . $currentRow, '₱' . number_format($total_collected, 2));
            $sheet->getStyle($collectedCol . $currentRow)->getFont()->setBold(true);
            $sheet->getStyle($collectedCol . $currentRow)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D3D3D3');
            $sheet->getStyle($collectedCol . $currentRow)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle($collectedCol . $currentRow)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            $currentRow++;
        }
        
        // Auto-size columns
        foreach (range('A', chr(64 + count($headers))) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        // Also auto-size column B for stock section
        if ($report_type === 'inventory' && isset($stock_data) && !empty($stock_data)) {
            $sheet->getColumnDimension('B')->setAutoSize(true);
        }
        
        // Add footer
        $currentRow += 2;
        $sheet->setCellValue('A' . $currentRow, 'City of Talisay Business Affairs Office');
        $sheet->mergeCells('A' . $currentRow . ':' . chr(64 + count($headers)) . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $currentRow++;
        
        $sheet->setCellValue('A' . $currentRow, 'This report was generated automatically on ' . $generated_date . ' at ' . $generated_time);
        $sheet->mergeCells('A' . $currentRow . ':' . chr(64 + count($headers)) . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getFont()->setSize(10);
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $currentRow++;
        
        $sheet->setCellValue('A' . $currentRow, 'For inquiries, please contact the Business Affairs Office');
        $sheet->mergeCells('A' . $currentRow . ':' . chr(64 + count($headers)) . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getFont()->setSize(10);
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Set filename
        $filename = strtolower(str_replace(' ', '_', $title)) . '_' . (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Ymd_His') . '.xlsx';
        
        // Output file
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
        
    } else {
        // Fallback to CSV format if PhpSpreadsheet is not available
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . strtolower(str_replace(' ', '_', $title)) . '_' . (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Ymd_His') . '.csv"');
        
        // Add BOM for UTF-8
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        
        // Professional Report Header (like the image)
        fputcsv($output, []); // Empty row
        fputcsv($output, ['BUSINESS AFFAIRS OFFICE REPORTS']);
        fputcsv($output, ['CITY OF TALISAY, Province of Negros Occidental']);
        fputcsv($output, ['CHMSU - Carlos Hilado Memorial State University']);
        fputcsv($output, []); // Empty row
        
        // Generation date and time (generate fresh each time using DateTime for accuracy)
        $now_dt = new DateTime('now', new DateTimeZone('Asia/Manila'));
        $generated_date = $now_dt->format('F j, Y');
        $generated_time = $now_dt->format('g:i A');
        fputcsv($output, ['Generated on:', $generated_date . ' at ' . $generated_time]);
        fputcsv($output, []); // Empty row
        
        // Report title
        fputcsv($output, [$title]);
        fputcsv($output, []); // Empty row
        
        // Covered period/filters
        $period_info = [];
        
        // Handle mode-based filters (for summary reports)
        if (isset($mode)) {
            if ($mode === 'daily') {
                $period_info[] = 'Date: ' . date('F j, Y', strtotime($date));
            } elseif ($mode === 'monthly') {
                $period_info[] = 'Month: ' . date('F Y', strtotime($month . '-01'));
            } elseif ($mode === 'yearly') {
                $period_info[] = 'Year: ' . $year;
            }
        }
        
        // Handle date range filters
        if (!empty($start_date) && !empty($end_date)) {
            if ($start_date === $end_date) {
                $period_info[] = 'Date: ' . date('F j, Y', strtotime($start_date));
            } else {
                $period_info[] = 'Period: ' . date('F j, Y', strtotime($start_date)) . ' - ' . date('F j, Y', strtotime($end_date));
            }
        } elseif (!empty($start_date)) {
            $period_info[] = 'From: ' . date('F j, Y', strtotime($start_date));
        } elseif (!empty($end_date)) {
            $period_info[] = 'To: ' . date('F j, Y', strtotime($end_date));
        }
        
        // Handle other filters
        if (!empty($status)) {
            $period_info[] = 'Status: ' . ucfirst($status);
        }
        if (!empty($role)) {
            $period_info[] = 'Role: ' . ucfirst($role);
        }
        if (!empty($department)) {
            $period_info[] = 'Department: ' . htmlspecialchars($department);
        }
        if (!empty($service)) {
            $period_info[] = 'Service: ' . ucfirst($service);
        }
        if (!empty($report_type_param)) {
            $period_info[] = 'Report Type: ' . ucfirst($report_type_param);
        }
        
        if (!empty($period_info)) {
            fputcsv($output, $period_info);
            fputcsv($output, []); // Empty row
        }
        
        // Table headers
        fputcsv($output, $headers);
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        // Add total row for bus reports
        if ($report_type === 'bus' && isset($total_amount)) {
            $total_row = array_fill(0, count($headers) - 1, '');
            $total_row[count($headers) - 2] = 'Total Amount:';
            $total_row[count($headers) - 1] = '₱' . number_format($total_amount, 2);
            fputcsv($output, $total_row);
        }
        
        // Add total row for inventory reports
        if ($report_type === 'inventory' && isset($total_revenue)) {
            $total_row = array_fill(0, count($headers) - 1, '');
            $total_row[count($headers) - 2] = 'Total Revenue:';
            $total_row[count($headers) - 1] = '₱' . number_format($total_revenue, 2);
            fputcsv($output, $total_row);
        }
        
        // Add total row for summary reports
        if ($report_type === 'summary' && isset($total_requests) && isset($total_approved) && isset($total_collected)) {
            fputcsv($output, ['Total:', number_format($total_requests), number_format($total_approved), '₱' . number_format($total_collected, 2)]);
        }
        
        // Add Remaining Stock section for inventory reports
        if ($report_type === 'inventory' && isset($stock_data) && !empty($stock_data)) {
            fputcsv($output, []); // Empty row
            fputcsv($output, ['Remaining Stock']);
            fputcsv($output, ['Item', 'Remaining']);
            foreach ($stock_data as $stock_row) {
                fputcsv($output, $stock_row);
            }
        }
        
        // Footer
        fputcsv($output, []); // Empty row
        fputcsv($output, ['City of Talisay Business Affairs Office']);
        fputcsv($output, ['This report was generated automatically on ' . $generated_date . ' at ' . $generated_time]);
        fputcsv($output, ['For inquiries, please contact the Business Affairs Office']);
        
        fclose($output);
        exit;
    }
}
?>

