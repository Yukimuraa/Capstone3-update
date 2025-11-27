# Bus Request Not Showing - FIXED ✅

## Problem

You submitted a bus request from the student side (`student/bus.php`), but it wasn't showing up in the admin panel (`admin/bus.php`). The admin page showed:

```
Available Buses: /0
Total Requests: 0
Pending: 0
Approved: 0
No schedules found for this month.
```

## Root Cause

The admin page was **only showing bus requests for the current month** (October 2025). If your bus request was for a different month (like November, December, etc.), it wouldn't appear!

**Original Query (Line 141-145):**
```php
WHERE MONTH(bs.date_covered) = ? AND YEAR(bs.date_covered) = ?
```

This filtered requests to only show dates within the current month and year, hiding all other requests!

## Solution Implemented

I've updated the admin page to **show ALL bus requests by default**, with an option to filter by current month.

### Changes Made to `admin/bus.php`

#### 1. Added View Filter (Lines 127-171)
```php
// Check if filter is set (default to "all" to show all requests)
$view_filter = isset($_GET['view']) ? $_GET['view'] : 'all';

if ($view_filter === 'current_month') {
    // Show only current month
} else {
    // Show ALL requests (default)
}
```

#### 2. Added Filter Buttons (Lines 84-99)
Two buttons at the top:
- **"All Requests"** (default) - Shows ALL bus requests regardless of date
- **"Current Month"** - Shows only requests for October 2025

#### 3. Updated Page Title (Line 88)
```php
<?php echo $view_filter === 'current_month' ? '(October 2025)' : '(All Requests)'; ?>
```

#### 4. Better "No Results" Message (Lines 348-354)
```php
<?php if ($view_filter === 'current_month'): ?>
    No schedules found for this month. <a href="?view=all">View all requests</a>
<?php else: ?>
    No bus requests found in the system.
<?php endif; ?>
```

## Diagnostic Tool Created

I also created `check_bus_requests.php` to help diagnose bus request issues.

### Run this diagnostic tool:
```
http://localhost/Capstone-3/check_bus_requests.php
```

**This tool shows:**
- ✅ Total bus requests in the database
- ✅ Requests grouped by status (pending, approved, rejected)
- ✅ Requests grouped by month
- ✅ Complete list of all requests with dates
- ✅ Bus inventory
- ✅ Highlights which month is current

## How to Use the Fixed Admin Page

### Option 1: View ALL Requests (Recommended)
1. Go to `admin/bus.php`
2. Click the **"All Requests"** button (active by default)
3. You'll see ALL bus requests from any date

### Option 2: View Only Current Month
1. Go to `admin/bus.php`
2. Click the **"Current Month"** button
3. You'll see only requests for October 2025

## Example Scenarios

### Scenario 1: Student requests a bus for November 15
**Before Fix:**
- Student submits request ✓
- Admin page (October filter): Shows 0 requests ❌
- Request is "hidden" because it's for November

**After Fix:**
- Student submits request ✓
- Admin page (All Requests): Shows the request ✓
- Admin can see and approve it immediately

### Scenario 2: Multiple requests across different months
**Before Fix:**
```
October requests: 2 visible
November requests: 0 visible (hidden!)
December requests: 0 visible (hidden!)
```

**After Fix:**
```
All Requests view:
- October: 2 requests
- November: 5 requests  
- December: 3 requests
Total: 10 requests visible ✓
```

## Testing Steps

1. **Run the diagnostic tool:**
   ```
   http://localhost/Capstone-3/check_bus_requests.php
   ```
   
2. **Check if your request exists:**
   - Look at the "All Bus Requests" table
   - Find your request and note the "Date Covered"
   
3. **View in admin panel:**
   - Go to `admin/bus.php`
   - Should default to "All Requests" view
   - Your request should now be visible!

4. **Test filtering:**
   - Click "Current Month" - see only October requests
   - Click "All Requests" - see all requests again

## Files Modified

### 1. `admin/bus.php`
- Added `$view_filter` variable (line 128)
- Updated query logic (lines 131-171)  
- Added filter buttons (lines 91-98)
- Updated page title (line 88)
- Improved "no results" message (lines 348-354)

### 2. `check_bus_requests.php` (NEW)
- Diagnostic tool to view all bus requests
- Shows requests grouped by month and status
- Highlights current month
- Shows bus inventory

## Benefits

✅ **See ALL pending requests** - No more hidden requests!  
✅ **Better overview** - See requests across multiple months  
✅ **Quick filtering** - Easy toggle between "All" and "Current Month"  
✅ **Clear feedback** - Better messages when no requests found  
✅ **Diagnostic tool** - Easy troubleshooting with check_bus_requests.php  

## Common Issues & Solutions

### Issue: Still showing 0 requests

**Solution 1:** Run the diagnostic tool
```
http://localhost/Capstone-3/check_bus_requests.php
```
Check if requests actually exist in the database.

**Solution 2:** Check if buses exist
The diagnostic tool will also show if you have buses in the system. You need buses configured before you can accept requests.

**Solution 3:** Verify student request was submitted
Check the student's side - after submitting, they should see a success message with a schedule ID.

### Issue: Request exists but can't approve

**Solution:** Check if you have available buses
- Go to buses management
- Make sure buses exist with status "available"
- You need at least as many buses as requested

## Summary

The admin page now **defaults to showing ALL bus requests** instead of just the current month. This ensures no requests are accidentally hidden due to date filtering.

**Quick Test:**
1. Go to: `http://localhost/Capstone-3/admin/bus.php`
2. Should show "Bus Management System (All Requests)"
3. Should display your request if it exists!

🎉 **Problem solved!** Your bus requests are now visible!

















































