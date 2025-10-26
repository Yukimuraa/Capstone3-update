# Check Your Database - Based on Screenshot

## What I See in Your Database

From your screenshot, you have these tables:
- ✅ billing_statements
- ✅ bookings
- ✅ buses
- ✅ bus_bookings
- ✅ bus_schedules
- ✅ facilities
- ✅ gym_bookings
- ✅ gym_facilities
- ✅ inventory
- ✅ orders
- ✅ password_resets
- ✅ requests
- ✅ request_comments
- ✅ **user_accounts** ← This is the table that was causing the error!

## Good News! 🎉

Your database already has the `user_accounts` table! This is the main table that was missing and causing the error.

## Next Steps

### Step 1: Verify Your Database
Run this script to check what's missing (if anything):

```
http://localhost/Capstone-3/check_database.php
```

This will:
- ✓ Show all your current tables
- ✓ Compare with required tables
- ✓ Check table structure
- ✓ Count records in each table

### Step 2: Add Any Missing Tables (if needed)
If the check shows missing tables, run:

```
http://localhost/Capstone-3/add_missing_tables.php
```

This will automatically add any missing tables.

### Step 3: Test Login
Go to your login page:

```
http://localhost/Capstone-3/login.php
```

**Try these test accounts:**
- Email: `admin@chmsu.edu.ph`
- Password: `admin123`

## If You Still Get "Table doesn't exist" Error

This might happen if:

### 1. Empty user_accounts table
**Solution:** Run this to add sample users:
```
http://localhost/Capstone-3/database/setup_complete.php
```

### 2. Missing columns in user_accounts
The table might exist but missing important columns like `organization` or `profile_pic`.

**Solution:** The `add_missing_tables.php` script will check and add missing columns automatically.

### 3. Wrong database name
Check your `config/database.php` file - make sure it says:
```php
$db_name = 'chmsu_bao';
```

## Additional Tables You Have

I noticed you have these extra tables that aren't in the core requirements:
- `gym_bookings` - Separate from main bookings (good for specific gym features)
- `gym_facilities` - Separate from main facilities (good for gym-specific data)
- `request_comments` - For commenting on requests (nice feature!)

These are perfectly fine and likely used for extended functionality in your system.

## Quick Diagnostic

### Check if user_accounts has data:
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select `chmsu_bao` database
3. Click on `user_accounts` table
4. Click "Browse"
5. Check if there are any users

If empty, run the setup to add sample users.

### Check table structure:
1. Click on `user_accounts` table
2. Click "Structure" tab
3. Make sure these columns exist:
   - ✓ id
   - ✓ name
   - ✓ email
   - ✓ password
   - ✓ user_type
   - ✓ organization (can be NULL)
   - ✓ profile_pic (can be NULL)
   - ✓ status
   - ✓ created_at
   - ✓ updated_at

## Common Issues After Seeing Your Database

### Issue 1: user_accounts exists but is empty
**Symptom:** Can't login, no users found

**Fix:**
```
http://localhost/Capstone-3/add_missing_tables.php
```
This will add sample users.

### Issue 2: Some files still reference "users" table
**Symptom:** Some features work, others show table errors

**Fix:**
```
http://localhost/Capstone-3/database/fix_table_references.php
```
This updates all PHP files to use `user_accounts` instead of `users`.

### Issue 3: Missing columns in user_accounts
**Symptom:** Error when trying to register as external user

**Fix:** Run `add_missing_tables.php` - it checks and adds missing columns.

## Your Database Looks Good!

Based on your screenshot, you have all the main tables. The most likely issues are:

1. ✓ **user_accounts table is empty** - Need to add users
2. ✓ **Missing some columns** - Need to add organization/profile_pic
3. ✓ **Some code still uses "users" table name** - Need to update references

All of these can be fixed by running:
1. `check_database.php` (to see what's needed)
2. `add_missing_tables.php` (to fix everything)

## Sample Users That Will Be Added

When you run `add_missing_tables.php`, these users will be added:

| Name | Email | Password | Type |
|------|-------|----------|------|
| System Administrator | admin@chmsu.edu.ph | admin123 | admin |
| BAO Staff | staff@chmsu.edu.ph | admin123 | admin |
| Test Student | student@chmsu.edu.ph | admin123 | student |
| External User | external@example.com | admin123 | external |

## Still Having Issues?

Run the comprehensive test:
```
http://localhost/Capstone-3/test_database.php
```

This will show you:
- ✓ Connection status
- ✓ All tables and record counts
- ✓ Critical tables check
- ✓ Sample users list
- ✓ Query test results

---

**TL;DR:**
1. Run `check_database.php` to see status
2. Run `add_missing_tables.php` to fix issues
3. Try login with `admin@chmsu.edu.ph` / `admin123`
4. If still issues, run `test_database.php` for detailed diagnostics





