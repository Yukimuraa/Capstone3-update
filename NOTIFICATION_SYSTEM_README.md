# Notification System Implementation

## Overview
A comprehensive notification system has been added to the CHMSU BAO system, allowing administrators to send notifications to all users or specific users, and all users can view and manage their notifications through a notification bell icon.

## Features

### For All Users
- **Notification Bell**: A bell icon appears in the header of all dashboard pages
- **Unread Count Badge**: Shows the number of unread notifications
- **Notification Dropdown**: Click the bell to view recent notifications
- **Mark as Read**: Click on a notification to mark it as read
- **Mark All as Read**: Button to mark all notifications as read at once
- **Delete Notifications**: Delete individual notifications
- **Auto-refresh**: Notification count updates every 30 seconds
- **Clickable Links**: Notifications can include links to redirect users to specific pages

### For Administrators
- **Send Notifications Page**: Accessible from admin sidebar menu
- **Send to All Users**: Broadcast notifications to all active users
- **Send to Specific User**: Send notifications to individual users
- **Notification Types**: Support for different notification types (info, success, warning, error, booking, order, request, system)
- **Optional Links**: Add clickable links to notifications

## Files Created

### Database
- `database/setup_notifications.php` - Standalone setup script for notifications table
- Updated `database/complete_database_setup.sql` - Added notifications table to complete setup

### Core Functions
- `includes/notification_functions.php` - All notification-related PHP functions:
  - `create_notification()` - Create notification for a user
  - `create_notification_for_all()` - Create notification for all users
  - `get_unread_notification_count()` - Get count of unread notifications
  - `get_user_notifications()` - Get user's notifications
  - `mark_notification_read()` - Mark notification as read
  - `mark_all_notifications_read()` - Mark all notifications as read
  - `delete_notification()` - Delete a notification

### API
- `api/get_notifications.php` - RESTful API endpoint for:
  - Getting notification count
  - Listing notifications
  - Marking notifications as read
  - Marking all as read
  - Deleting notifications

### UI Components
- `includes/notification_bell.php` - Reusable notification bell component with dropdown

### Admin Pages
- `admin/send_notification.php` - Admin interface to send notifications

## Database Schema

```sql
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error', 'booking', 'order', 'request', 'system') DEFAULT 'info',
    link VARCHAR(255) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_accounts(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
)
```

## Setup Instructions

### 1. Create the Database Table

**Option A: Run the standalone setup script**
```
http://localhost/Capstone-3/database/setup_notifications.php
```

**Option B: Run the complete database setup** (includes notifications table)
```
http://localhost/Capstone-3/database/setup_complete.php
```

### 2. Integration

The notification bell has been automatically integrated into:
- ✅ Admin dashboard
- ✅ Student dashboard
- ✅ Staff dashboard
- ✅ External dashboard
- ✅ Admin inventory page
- ✅ Student inventory page
- ✅ Staff inventory page

## Usage Examples

### Send Notification from Code

```php
require_once '../includes/notification_functions.php';

// Send to a specific user
create_notification($user_id, "Booking Approved", "Your gym booking has been approved!", "success", "student/gym_reservation.php");

// Send to all users
create_notification_for_all("System Maintenance", "The system will be under maintenance tonight from 10 PM to 2 AM.", "warning");
```

### Send Notification via Admin Interface

1. Log in as admin
2. Go to "Send Notification" in the sidebar
3. Select target (All Users or specific user)
4. Choose notification type
5. Enter title and message
6. Optionally add a link
7. Click "Send Notification"

## Notification Types

- **info** - General information (blue)
- **success** - Success messages (green)
- **warning** - Warning messages (yellow)
- **error** - Error messages (red)
- **booking** - Booking-related notifications
- **order** - Order-related notifications
- **request** - Request-related notifications
- **system** - System notifications

## API Endpoints

All endpoints are in `api/get_notifications.php`:

- `GET ?action=count` - Get unread notification count
- `GET ?action=list&limit=10` - Get list of notifications
- `GET ?action=mark_read&id=123` - Mark notification as read
- `GET ?action=mark_all_read` - Mark all notifications as read
- `GET ?action=delete&id=123` - Delete a notification

## UI Features

### Notification Bell
- Shows unread count badge (red circle with number)
- Dropdown shows up to 10 most recent notifications
- Unread notifications have blue background
- Time display: "Just now", "X minutes ago", "X hours ago", or date

### Notification Dropdown
- Header with "Mark all read" button (if unread notifications exist)
- Close button (X)
- Scrollable list of notifications
- Delete button for each notification
- Click notification to mark as read and navigate (if link provided)

## Integration Points

The notification system can be integrated into any page by adding:

```php
<?php require_once '../includes/notification_bell.php'; ?>
```

Place it in the header section where you want the notification bell to appear.

## Future Enhancements

Potential improvements:
- Email notifications
- Push notifications
- Notification preferences per user
- Notification categories/filtering
- Rich text notifications
- Notification templates
- Scheduled notifications

## Troubleshooting

### Notifications not showing
1. Check if the `notifications` table exists in the database
2. Verify user is logged in (session has `user_id`)
3. Check browser console for JavaScript errors
4. Verify API endpoint is accessible: `api/get_notifications.php`

### Notification bell not appearing
1. Ensure `includes/notification_bell.php` is included in the page
2. Check that user is logged in
3. Verify `base_url` variable is set correctly

### Cannot send notifications
1. Verify admin is logged in
2. Check database connection
3. Ensure `notifications` table exists
4. Check PHP error logs

## Support

For issues or questions, check:
- Database setup: `database/setup_notifications.php`
- Function definitions: `includes/notification_functions.php`
- API endpoint: `api/get_notifications.php`

