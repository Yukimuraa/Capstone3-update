<?php
session_start();
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
        $headers = ['Reservation ID', 'Requester/Dept', 'Destination', 'Date', 'Vehicles', 'Status', 'Total Amount', 'Payment'];
        
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
        
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                $row['id'],
                $row['client'],
                $row['destination'],
                date('M d, Y', strtotime($row['date_covered'])),
                $row['no_of_vehicles'],
                ucfirst($row['status']),
                '₱' . number_format((float)($row['total_amount'] ?? 0), 2),
                isset($row['payment_status']) ? ucfirst($row['payment_status']) : '—'
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
        
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                $row['item_name'],
                number_format($row['qty_issued']),
                '₱' . number_format($row['revenue'], 2)
            ];
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
        
        $count_bookings = (int)$conn->query("SELECT COUNT(*) as c FROM bookings WHERE DATE(created_at) BETWEEN '".$conn->real_escape_string($start)."' AND '".$conn->real_escape_string($end)."'")->fetch_assoc()['c'] ?? 0;
        $count_bus = (int)$conn->query("SELECT COUNT(*) as c FROM bus_schedules WHERE DATE(created_at) BETWEEN '".$conn->real_escape_string($start)."' AND '".$conn->real_escape_string($end)."'")->fetch_assoc()['c'] ?? 0;
        $approved_bookings = (int)$conn->query("SELECT COUNT(*) as c FROM bookings WHERE status='approved' AND DATE(updated_at) BETWEEN '".$conn->real_escape_string($start)."' AND '".$conn->real_escape_string($end)."'")->fetch_assoc()['c'] ?? 0;
        $approved_bus = (int)$conn->query("SELECT COUNT(*) as c FROM bus_schedules WHERE status='approved' AND DATE(updated_at) BETWEEN '".$conn->real_escape_string($start)."' AND '".$conn->real_escape_string($end)."'")->fetch_assoc()['c'] ?? 0;
        $col_orders = (float)($conn->query("SELECT SUM(total_price) as s FROM orders WHERE status='completed' AND DATE(updated_at) BETWEEN '".$conn->real_escape_string($start)."' AND '".$conn->real_escape_string($end)."'")->fetch_assoc()['s'] ?? 0);
        $col_bus = (float)($conn->query("SELECT SUM(total_amount) as s FROM billing_statements WHERE payment_status='paid' AND DATE(payment_date) BETWEEN '".$conn->real_escape_string($start)."' AND '".$conn->real_escape_string($end)."'")->fetch_assoc()['s'] ?? 0);
        
        $data[] = ['Gym', number_format($count_bookings), number_format($approved_bookings), '₱0.00'];
        $data[] = ['Bus', number_format($count_bus), number_format($approved_bus), '₱' . number_format($col_bus, 2)];
        $data[] = ['Item Sales', '—', '—', '₱' . number_format($col_orders, 2)];
        break;
        
    case 'gym':
        // Gym reports have different sub-types
        if ($report_type_param === 'usage') {
            $title = 'Gym Usage Report';
            $headers = ['Date', 'Facility', 'Booking Count'];
            
            $start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
            $end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : date('Y-m-d');
            
            $query = "SELECT b.date as booking_date, 'Gymnasium' as facility_name, COUNT(*) as booking_count 
                      FROM bookings b 
                      WHERE b.facility_type = 'gym' AND b.date BETWEEN ? AND ? 
                      GROUP BY b.date 
                      ORDER BY b.date ASC";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    date('F j, Y', strtotime($row['booking_date'])),
                    $row['facility_name'],
                    $row['booking_count']
                ];
            }
        } elseif ($report_type_param === 'utilization') {
            $title = 'Facility Utilization Report';
            $headers = ['Facility', 'Capacity', 'Total Bookings', 'Approved', 'Rejected', 'Cancelled', 'Utilization Rate'];
            
            $month = isset($_GET['month']) ? sanitize_input($_GET['month']) : date('Y-m');
            $start_date = $month . '-01';
            $end_date = date('Y-m-t', strtotime($start_date));
            
            $facilities_query = "SELECT id, name as facility_name, capacity FROM gym_facilities ORDER BY name ASC";
            $facility_stmt = $conn->prepare($facilities_query);
            $facility_stmt->execute();
            $facilities_result = $facility_stmt->get_result();
            
            $stats_query = "SELECT 
                      COUNT(*) as booking_count,
                      COUNT(CASE WHEN status = 'confirmed' OR status = 'approved' THEN 1 ELSE NULL END) as approved_count,
                      COUNT(CASE WHEN status = 'rejected' THEN 1 ELSE NULL END) as rejected_count,
                      COUNT(CASE WHEN status = 'cancelled' THEN 1 ELSE NULL END) as cancelled_count
                      FROM bookings 
                      WHERE facility_type = 'gym' AND date BETWEEN ? AND ?";
            
            $stats_stmt = $conn->prepare($stats_query);
            $stats_stmt->bind_param("ss", $start_date, $end_date);
            $stats_stmt->execute();
            $stats_result = $stats_stmt->get_result();
            $stats = $stats_result->fetch_assoc();
            
            while ($facility = $facilities_result->fetch_assoc()) {
                $days_in_month = date('t', strtotime($start_date));
                $max_possible_bookings = $days_in_month;
                $utilization_rate = $max_possible_bookings > 0 ? (($stats['approved_count'] / $max_possible_bookings) * 100) : 0;
                
                $data[] = [
                    $facility['facility_name'],
                    $facility['capacity'],
                    $stats['booking_count'] ?? 0,
                    $stats['approved_count'] ?? 0,
                    $stats['rejected_count'] ?? 0,
                    $stats['cancelled_count'] ?? 0,
                    number_format($utilization_rate, 2) . '%'
                ];
            }
        } elseif ($report_type_param === 'status') {
            $title = 'Booking Status Report';
            $headers = ['Status', 'Facility', 'Booking Count'];
            
            $period = isset($_GET['period']) ? sanitize_input($_GET['period']) : 'month';
            $end_date = date('Y-m-d');
            switch ($period) {
                case 'week': $start_date = date('Y-m-d', strtotime('-1 week')); break;
                case 'month': $start_date = date('Y-m-d', strtotime('-1 month')); break;
                case 'quarter': $start_date = date('Y-m-d', strtotime('-3 months')); break;
                case 'year': $start_date = date('Y-m-d', strtotime('-1 year')); break;
                default: $start_date = date('Y-m-d', strtotime('-1 month'));
            }
            
            $query = "SELECT b.status, 'Gymnasium' as facility_name, COUNT(*) as booking_count 
                      FROM bookings b 
                      WHERE b.facility_type = 'gym' AND b.date BETWEEN ? AND ?";
            
            $params = [$start_date, $end_date];
            $types = "ss";
            
            if (!empty($status)) {
                if ($status === 'approved') {
                    $query .= " AND (b.status = 'approved' OR b.status = 'confirmed')";
                } else {
                    $query .= " AND b.status = ?";
                    $params[] = $status;
                    $types .= "s";
                }
            }
            
            $query .= " GROUP BY b.status ORDER BY b.status";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    ucfirst($row['status']),
                    $row['facility_name'],
                    $row['booking_count']
                ];
            }
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
        
        // Header
        $html = '<h1 style="text-align:center; font-size:18px; margin-bottom:10px;">' . htmlspecialchars($title) . '</h1>';
        $html .= '<p style="text-align:center; font-size:10px; color:#666; margin-bottom:15px;">Generated on: ' . date('F j, Y, g:i a') . '</p>';
        
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
        
        $html .= '</tbody></table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        
        $filename = strtolower(str_replace(' ', '_', $title)) . '_' . date('Ymd_His') . '.pdf';
        $pdf->Output($filename, 'D');
    } else {
        // Fallback: Generate print-friendly HTML that can be saved as PDF
        // Clear output buffer
        if (ob_get_length()) {
            ob_end_clean();
        }
        
        header('Content-Type: text/html; charset=utf-8');
        
        $filename = strtolower(str_replace(' ', '_', $title)) . '_' . date('Ymd_His');
        
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
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #1a1a1a;
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
        .instructions {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .instructions h3 {
            margin-top: 0;
            color: #856404;
        }
        .instructions ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .instructions li {
            margin: 5px 0;
        }
        .btn-print {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .btn-print:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="no-print instructions">
        <h3>📄 How to Save as PDF:</h3>
        <ol>
            <li>Click the "Print" button below, or press <strong>Ctrl+P</strong> (Windows) / <strong>Cmd+P</strong> (Mac)</li>
            <li>In the print dialog, select "Save as PDF" or "Microsoft Print to PDF" as the destination</li>
            <li>Click "Save" and choose where to save the file</li>
        </ol>
        <p><strong>Note:</strong> For automatic PDF generation, please install TCPDF by running: <code>composer require tecnickcom/tcpdf</code></p>
        <button class="btn-print" onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>
    
    <div class="header">
        <h1>' . htmlspecialchars($title) . '</h1>
        <p>Generated on: ' . date('F j, Y, g:i a') . '</p>
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
        
        echo '</tbody>
    </table>
    
    <script>
        // Auto-trigger print dialog after page loads
        window.onload = function() {
            // Small delay to ensure page is fully rendered
            setTimeout(function() {
                // Uncomment the line below to auto-open print dialog
                // window.print();
            }, 500);
        };
    </script>
</body>
</html>';
        exit;
    }
    
} else { // Excel (CSV format)
    // Clear output buffer
    if (ob_get_length()) {
        ob_end_clean();
    }
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . strtolower(str_replace(' ', '_', $title)) . '_' . date('Ymd_His') . '.csv"');
    
    // Add BOM for UTF-8
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}
?>

