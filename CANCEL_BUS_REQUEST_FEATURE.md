# Cancel Bus Request Feature - Added ✅

## Overview

Students can now **cancel their pending bus requests** directly from the student bus page. This gives students control over their requests before they're approved.

## Features Added

### 1. Cancel Button
- ❌ Red cancel button appears next to pending requests
- 🔒 Only visible for **pending requests** (not approved/rejected/completed)
- ⚠️ Confirmation dialog before cancellation
- ✅ Instant feedback with success/error messages

### 2. Cancelled Status Badge
- 🚫 Grey badge with ban icon for cancelled requests
- 📊 Tracked in statistics
- 📝 Clearly distinguishable from other statuses

### 3. Security Features
- ✅ Verifies user owns the request
- ✅ Verifies request is pending (not approved/rejected)
- ✅ Prevents unauthorized cancellations
- ✅ Database-level validation

## Changes Made

### File Modified: `student/bus.php`

#### 1. Backend - Cancel Handler (Lines 448-477)
```php
if ($_POST['action'] === 'cancel_request') {
    $schedule_id = intval($_POST['schedule_id']);
    
    // Verify the schedule belongs to the current user and is pending
    $verify_stmt = $conn->prepare("SELECT status FROM bus_schedules WHERE id = ? AND user_id = ?");
    $verify_stmt->bind_param("ii", $schedule_id, $_SESSION['user_id']);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        $error = 'Request not found or you do not have permission to cancel it.';
    } else {
        $schedule_data = $verify_result->fetch_assoc();
        
        if ($schedule_data['status'] !== 'pending') {
            $error = 'Only pending requests can be cancelled.';
        } else {
            // Update status to cancelled
            $cancel_stmt = $conn->prepare("UPDATE bus_schedules SET status = 'cancelled' WHERE id = ?");
            $cancel_stmt->bind_param("i", $schedule_id);
            
            if ($cancel_stmt->execute()) {
                $success = 'Bus request cancelled successfully!';
            } else {
                $error = 'Error cancelling request: ' . $conn->error;
            }
        }
    }
}
```

#### 2. Status Badge - Added Cancelled (Lines 776-780)
```php
case 'cancelled':
    $status_class = 'bg-gray-100 text-gray-800';
    $status_text = 'Cancelled';
    $status_icon = 'fa-ban';
    break;
```

#### 3. UI - Cancel Button (Lines 826-834)
```php
<?php if ($schedule['status'] === 'pending'): ?>
    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to cancel this request?')">
        <input type="hidden" name="action" value="cancel_request">
        <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
        <button type="submit" class="text-red-600 hover:text-red-900" title="Cancel Request">
            <i class="fas fa-times-circle"></i>
        </button>
    </form>
<?php endif; ?>
```

#### 4. Statistics - Track Cancelled (Lines 604)
```php
SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_requests
```

## Status Badge Colors

| Status | Color | Icon | When Visible |
|--------|-------|------|--------------|
| ⏰ Pending | Yellow | Clock | Request submitted, awaiting approval |
| ✅ Approved | Green | Check | Request approved by admin |
| ❌ Rejected | Red | X | Request rejected by admin |
| 🚫 Cancelled | Grey | Ban | Request cancelled by student |
| 🏁 Completed | Blue | Flag | Request fulfilled/done |

## Action Buttons

For each request, students see different buttons based on status:

### Pending Request
- 👁️ **View** (blue) - View request details
- ❌ **Cancel** (red) - Cancel the request ← **NEW!**

### Approved Request  
- 👁️ **View** (blue) - View request details
- 🖨️ **Print** (green) - Print receipt/billing

### Rejected/Cancelled Request
- 👁️ **View** (blue) - View request details only

## User Flow

### Scenario 1: Student Cancels Pending Request

1. **Student submits** bus request
   - Status: ⏰ Pending
   - Cancel button appears

2. **Student changes mind** and clicks cancel button
   - Confirmation dialog: "Are you sure you want to cancel this request?"
   - Student clicks "OK"

3. **Request cancelled**
   - Success message: "Bus request cancelled successfully!"
   - Status changes to: 🚫 Cancelled
   - Cancel button disappears
   - Request appears in list with grey badge

### Scenario 2: Try to Cancel Non-Pending Request

1. **Admin approves** request
   - Status changes to: ✅ Approved
   - Cancel button disappears

2. **Student tries to cancel** (button not visible)
   - Cannot cancel - button only shows for pending

### Scenario 3: Security Check

1. **Malicious user** tries to cancel someone else's request
   - Error: "Request not found or you do not have permission to cancel it."
   - Request status unchanged

## Visual Example

**Before Cancellation:**
```
┌─────────────────────────────────────────────────┐
│ 📍 Bacolod  ⏰ [Pending]                        │
│ 📅 Nov 15, 2025 • Bus #1                        │
│ Purpose: Educational Field Trip                  │
│                                                   │
│ Actions: 👁️ View  ❌ Cancel  ← Cancel available  │
└─────────────────────────────────────────────────┘
```

**After Cancellation:**
```
┌─────────────────────────────────────────────────┐
│ 📍 Bacolod  🚫 [Cancelled]                      │
│ 📅 Nov 15, 2025 • Bus #1                        │
│ Purpose: Educational Field Trip                  │
│                                                   │
│ Actions: 👁️ View  ← Only view button now         │
└─────────────────────────────────────────────────┘
```

## Testing

### Test Case 1: Cancel Pending Request

1. **Login as student**
2. **Go to** `student/bus.php`
3. **Submit a new** bus request
4. **Wait for page refresh** - should show ⏰ Pending status
5. **Click the red cancel button** (❌)
6. **Confirm** in the dialog
7. **Result:**
   - ✅ Success message appears
   - ✅ Status changes to 🚫 Cancelled
   - ✅ Cancel button disappears
   - ✅ Only view button remains

### Test Case 2: Cannot Cancel Approved Request

1. **Admin approves** a pending request
2. **Student refreshes** page
3. **Check the request:**
   - ✅ Status shows ✅ Approved
   - ✅ No cancel button visible
   - ✅ Print button appears instead

### Test Case 3: Cannot Cancel Other User's Request

1. **Student A** submits request
2. **Student B** tries to manipulate POST data to cancel Student A's request
3. **Result:**
   - ❌ Error: "Request not found or you do not have permission"
   - ✅ Request remains unchanged

## Error Messages

| Error | When It Occurs |
|-------|----------------|
| "Request not found or you do not have permission to cancel it." | Trying to cancel someone else's request or non-existent request |
| "Only pending requests can be cancelled." | Trying to cancel approved/rejected/completed request |
| "Error cancelling request: [error]" | Database error during cancellation |

## Benefits

✅ **Student Control** - Students can cancel their own mistakes  
✅ **Reduces Admin Work** - Fewer unnecessary pending requests  
✅ **Clear Status** - Cancelled requests clearly marked  
✅ **Secure** - Only owners can cancel their pending requests  
✅ **User-Friendly** - Simple one-click cancellation with confirmation  
✅ **Audit Trail** - Cancelled requests remain in database for records  

## Database Impact

### Status Values in `bus_schedules` Table

Before:
- `pending`
- `approved`
- `rejected`
- `completed`

After (NEW):
- `pending`
- `approved`
- `rejected`
- **`cancelled`** ← New status
- `completed`

**Note:** The database doesn't need schema changes - the status column already supports any ENUM value. The 'cancelled' status is just a new value we're using.

## Admin Side

Admins will also see cancelled requests in `admin/bus.php` with:
- 🚫 Grey "Cancelled" badge
- No action buttons (already cancelled by student)
- Counted in statistics if you add a cancelled filter

## Future Enhancements (Optional)

1. **Email Notification**: Send email when student cancels
2. **Cancel Reason**: Ask why student is cancelling
3. **Time Limit**: Only allow cancellation within X hours of submission
4. **Re-request**: Add "Submit Similar Request" button on cancelled requests
5. **Cancel Statistics Card**: Add 5th statistics card for cancelled count

## Summary

Students can now **cancel their pending bus requests** with:
- ❌ One-click cancel button
- ⚠️ Confirmation dialog
- 🚫 Clear cancelled status badge
- 🔒 Secure validation
- ✅ Instant feedback

The feature is fully functional and ready to use! 🎉















































