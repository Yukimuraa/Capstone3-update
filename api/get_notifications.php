<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/notification_functions.php';

header('Content-Type: application/json');

// Get user_id from session - handle both old and new session structure
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} elseif (isset($_SESSION['active_user_type']) && isset($_SESSION['user_sessions'][$_SESSION['active_user_type']]['user_id'])) {
    $user_id = $_SESSION['user_sessions'][$_SESSION['active_user_type']]['user_id'];
} else {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'count':
        $count = get_unread_notification_count($user_id);
        echo json_encode(['count' => $count]);
        break;
        
    case 'list':
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
        $notifications = get_user_notifications($user_id, $limit, $unread_only);
        echo json_encode(['notifications' => $notifications]);
        break;
        
    case 'mark_read':
        $notification_id = intval($_GET['id'] ?? 0);
        if ($notification_id > 0) {
            $success = mark_notification_read($notification_id, $user_id);
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['error' => 'Invalid notification ID']);
        }
        break;
        
    case 'mark_all_read':
        $success = mark_all_notifications_read($user_id);
        echo json_encode(['success' => $success]);
        break;
        
    case 'delete':
        $notification_id = intval($_GET['id'] ?? 0);
        if ($notification_id > 0) {
            $success = delete_notification($notification_id, $user_id);
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['error' => 'Invalid notification ID']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>

