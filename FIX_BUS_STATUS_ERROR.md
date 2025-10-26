# Fix: Unknown column 'status' in bus_schedules

## The Error
```
Fatal error: Uncaught mysqli_sql_exception: Unknown column 'status' in 'field list' 
in C:\xampp\htdocs\Capstone-3\admin\bus.php:134
```

## The Problem
The `bus_schedules` table is missing the `status` column that the code is trying to use.

## Quick Fix (3 Minutes)

### Step 1: Open the Fix Page
Go to your browser and open:
```
http://localhost/Capstone-3/fix_bus_error.html
```

### Step 2: Click "Fix Bus Schedules Table"
This will automatically add the missing column.

### Step 3: Test
Go to:
```
http://localhost/Capstone-3/admin/bus.php
```
The error should be gone!

## What Will Be Added

The fix script will add these columns to `bus_schedules` table:

| Column | Type | Default | Description |
|--------|------|---------|-------------|
| **status** | ENUM | 'pending' | Request status (pending/approved/rejected/completed) |
| user_id | INT | NULL | User who made the request |
| user_type | ENUM | 'student' | Type of user |
| updated_at | TIMESTAMP | NOW | Last update time |

## Alternative Methods

### Method 1: Use Automated Script
```
http://localhost/Capstone-3/fix_bus_schedules_table.php
```
- ✅ Checks existing structure
- ✅ Adds missing columns
- ✅ Shows detailed results
- ✅ Verifies the fix

### Method 2: Fix All Tables at Once
```
http://localhost/Capstone-3/fix_all_missing_columns.php
```
- Checks ALL tables
- Adds missing columns to:
  - bus_schedules
  - user_accounts
  - buses
  - facilities
  - bookings

### Method 3: Manual SQL (phpMyAdmin)
1. Open: `http://localhost/phpmyadmin`
2. Select database: `chmsu_bao`
3. Click on `bus_schedules` table
4. Click "SQL" tab
5. Run this query:

```sql
ALTER TABLE bus_schedules 
ADD COLUMN status ENUM('pending', 'approved', 'rejected', 'completed') 
DEFAULT 'pending';
```

## Why This Happened

The `bus_schedules` table was created without the `status` column, but the PHP code in `admin/bus.php` expects it to exist.

The SQL file `database/bus_tables.sql` includes the status column, but it seems the table was created before this was added to the SQL file, or was created manually.

## Verify the Fix

After running the fix, you can verify:

1. **Check in phpMyAdmin:**
   - Go to `bus_schedules` table
   - Click "Structure"
   - Look for the `status` column

2. **Check with our script:**
   ```
   http://localhost/Capstone-3/check_database.php
   ```
   This will show all columns in all tables.

3. **Test the page:**
   ```
   http://localhost/Capstone-3/admin/bus.php
   ```
   Should load without errors.

## Related Files

### Files Fixed:
- `fix_bus_schedules_table.php` - Adds missing columns to bus_schedules
- `fix_all_missing_columns.php` - Fixes all tables
- `fix_bus_error.html` - Visual guide

### Files That Use bus_schedules.status:
- `admin/bus.php` (line 134) - Counts pending/approved/rejected
- `admin/bookings.php` - Filters by status
- `student/bus.php` - Checks if approved

## Prevent Future Issues

To avoid similar issues, always run:
```
http://localhost/Capstone-3/database/setup_complete.php
```

This creates all tables with proper structure from the start.

## Still Having Issues?

### Issue: "Column already exists" error
**Solution:** The column was already added! Try accessing admin/bus.php again.

### Issue: "Can't DROP 'status'" error
**Solution:** The column exists but might have different definition. Run:
```sql
ALTER TABLE bus_schedules 
MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'completed') 
DEFAULT 'pending';
```

### Issue: Different error about other columns
**Solution:** Run the complete fix:
```
http://localhost/Capstone-3/fix_all_missing_columns.php
```

## Summary

**Problem:** Missing `status` column in `bus_schedules` table

**Solution:** Run `fix_bus_schedules_table.php` to add it

**Time:** ~30 seconds

**Result:** admin/bus.php will work properly

---

**Quick Link:** [http://localhost/Capstone-3/fix_bus_error.html](http://localhost/Capstone-3/fix_bus_error.html)







