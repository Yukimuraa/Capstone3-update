# Student Bus Request Status Display - FIXED ✅

## Problem

When an admin rejected a bus request, the student couldn't see that their request was rejected. The student page showed:
- All requests had a "Submitted" badge (hardcoded)
- No visual indication of rejected status
- Statistics showed 0 for rejected requests

## Root Cause

### Issue 1: Hardcoded "Submitted" Badge
**Location:** `student/bus.php` Line 720-722

```php
// OLD CODE (BROKEN)
<span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
    Submitted
</span>
```

The badge was hardcoded to always show "Submitted" regardless of the actual status from the database!

### Issue 2: Incorrect Statistics Query
**Location:** `student/bus.php` Line 569-576

```php
// OLD CODE (BROKEN)
$stats_query = "SELECT 
    COUNT(*) as total_requests,
    0 as approved_requests,    ← Hardcoded 0!
    0 as pending_requests,     ← Hardcoded 0!
    0 as rejected_requests     ← Hardcoded 0!
    FROM bus_schedules";
```

Statistics were hardcoded to 0 instead of counting actual statuses!

## Solution Implemented

### Fix 1: Dynamic Status Badges (Lines 720-756)

Added a switch statement to display the correct status badge based on the actual database status:

```php
// NEW CODE (FIXED)
switch($schedule['status']) {
    case 'pending':
        $status_class = 'bg-yellow-100 text-yellow-800';
        $status_text = 'Pending';
        $status_icon = 'fa-clock';
        break;
    case 'approved':
        $status_class = 'bg-green-100 text-green-800';
        $status_text = 'Approved';
        $status_icon = 'fa-check-circle';
        break;
    case 'rejected':
        $status_class = 'bg-red-100 text-red-800';
        $status_text = 'Rejected';
        $status_icon = 'fa-times-circle';
        break;
    case 'completed':
        $status_class = 'bg-blue-100 text-blue-800';
        $status_text = 'Completed';
        $status_icon = 'fa-flag-checkered';
        break;
}
```

### Fix 2: Accurate Statistics Query (Lines 568-580)

Updated query to count actual statuses for the current user:

```php
// NEW CODE (FIXED)
$stats_query = "SELECT 
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests
    FROM bus_schedules 
    WHERE user_id = ?";
```

## What Changed

### File Modified: `student/bus.php`

#### Change 1: Status Badge Display (Lines 720-756)
- ✅ Removed hardcoded "Submitted" text
- ✅ Added switch statement for dynamic status
- ✅ Added icons for each status
- ✅ Added color coding:
  - 🟡 **Yellow** for Pending
  - 🟢 **Green** for Approved  
  - 🔴 **Red** for Rejected
  - 🔵 **Blue** for Completed

#### Change 2: Statistics Query (Lines 568-580)
- ✅ Changed from hardcoded 0s to actual COUNT queries
- ✅ Added user_id filter to show only current user's stats
- ✅ Used SUM(CASE WHEN...) to count by status

## Visual Improvements

### Before (Broken)
```
┌─────────────────────────────────────┐
│ Destination          [Submitted]    │  ← Always showed "Submitted"
├─────────────────────────────────────┤
│ Statistics:                         │
│ Total: 3 | Approved: 0 | Rejected: 0│  ← Wrong counts
└─────────────────────────────────────┘
```

### After (Fixed)
```
┌─────────────────────────────────────┐
│ Destination  ⏰ [Pending]           │  ← Shows actual status!
│ Destination  ✅ [Approved]          │
│ Destination  ❌ [Rejected]          │  ← Now visible!
├─────────────────────────────────────┤
│ Statistics:                         │
│ Total: 3 | Approved: 1 | Rejected: 1│  ← Correct counts!
└─────────────────────────────────────┘
```

## Status Badge Colors & Icons

| Status | Color | Icon | Badge |
|--------|-------|------|-------|
| Pending | Yellow | 🕐 Clock | ![Yellow Badge](https://via.placeholder.com/80x20/FEF3C7/92400E?text=Pending) |
| Approved | Green | ✅ Check | ![Green Badge](https://via.placeholder.com/80x20/D1FAE5/065F46?text=Approved) |
| Rejected | Red | ❌ Times | ![Red Badge](https://via.placeholder.com/80x20/FEE2E2/991B1B?text=Rejected) |
| Completed | Blue | 🏁 Flag | ![Blue Badge](https://via.placeholder.com/80x20/DBEAFE/1E40AF?text=Completed) |

## Testing

### Test Case 1: Rejected Request

1. **As Admin:**
   - Go to `admin/bus.php`
   - Find a pending request
   - Click "Reject"

2. **As Student:**
   - Go to `student/bus.php`
   - Should see: 🔴 **Rejected** badge (red)
   - Statistics should show: Rejected: 1

### Test Case 2: Multiple Statuses

1. **Create 3 requests as student**
2. **As admin:** Approve one, reject one, leave one pending
3. **As student:**
   - Should see:
     - Request 1: 🟢 **Approved** (green)
     - Request 2: 🔴 **Rejected** (red)
     - Request 3: 🟡 **Pending** (yellow)
   - Statistics should show:
     - Total: 3
     - Approved: 1
     - Pending: 1
     - Rejected: 1

## Benefits

✅ **Students can now see rejected requests** - Clear visual feedback  
✅ **Color-coded statuses** - Easy to distinguish at a glance  
✅ **Icons for clarity** - Visual indicators for each status  
✅ **Accurate statistics** - Real counts instead of hardcoded 0s  
✅ **User-specific stats** - Shows only the current student's requests  

## Common Scenarios

### Scenario 1: Request Rejected
**What student sees:**
```
📍 Bacolod
❌ Rejected
📅 Nov 15, 2025 • Bus #1
```

### Scenario 2: Request Approved
**What student sees:**
```
📍 Silay  
✅ Approved
📅 Nov 20, 2025 • Bus #2
💰 ₱1,500.00
```

### Scenario 3: Request Pending
**What student sees:**
```
📍 Talisay
⏰ Pending
📅 Nov 25, 2025 • Bus #3
```

## Summary

The student bus page now **correctly displays the actual status** of each request:

- **Pending**: Yellow badge with clock icon ⏰
- **Approved**: Green badge with check icon ✅
- **Rejected**: Red badge with X icon ❌
- **Completed**: Blue badge with flag icon 🏁

Statistics are now accurate and show real counts for each status!

🎉 **Students can now see when their requests are rejected!**



















































