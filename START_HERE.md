# 🚀 START HERE - Fix Database Error

## Your Error
```
Fatal error: Table 'chmsu_bao.user_accounts' doesn't exist
```

## Quick Fix (3 Steps)

### 1️⃣ Start XAMPP
Open XAMPP Control Panel and start:
- ✅ Apache
- ✅ MySQL

### 2️⃣ Run Database Setup
Open your browser and go to **ONE** of these URLs:

**Option A (Recommended):**
```
http://localhost/Capstone-3/database/index.php
```
- Visual interface
- Shows what's missing
- One-click setup

**Option B (Direct):**
```
http://localhost/Capstone-3/setup_database.php
```
- Quick redirect to setup

**Option C (Complete Setup):**
```
http://localhost/Capstone-3/database/setup_complete.php
```
- Detailed setup with logs

### 3️⃣ Test & Login
```
http://localhost/Capstone-3/login.php
```

**Test Account:**
- Email: `admin@chmsu.edu.ph`
- Password: `admin123`

---

## What Was Fixed

I've created a complete database setup system for your project:

### ✅ New Files Created

**Setup Scripts:**
- `database/setup_complete.php` - Main setup script
- `database/index.php` - Visual setup interface
- `setup_database.php` - Quick access from root
- `test_database.php` - Test your database connection

**SQL Files:**
- `database/complete_database_setup.sql` - All tables
- `database/user_accounts_tables.sql` - User accounts only

**Documentation:**
- `FIX_SUMMARY.md` - Detailed fix explanation
- `DATABASE_SETUP_INSTRUCTIONS.md` - Full instructions
- `QUICK_START.txt` - Quick reference
- `START_HERE.md` - This file

### ✅ Files Updated

**Fixed table references from "users" to "user_accounts":**
- `register.php` - Added organization field support
- `reset-password.php` - Fixed table name
- `external/profile.php` - Fixed table name
- `student/profile.php` - Fixed table name
- `admin/dashboard.php` - Fixed JOIN statements
- `database/create_new_users.php` - Added organization field
- `database/bus_tables.sql` - Added bus_schedules table
- `database/complete_database_setup.sql` - Added password_resets table

### ✅ Tables Created (10 total)

1. **user_accounts** - User authentication (with organization & profile_pic fields)
2. **password_resets** - Password reset tokens
3. **inventory** - Product inventory
4. **orders** - Order management
5. **buses** - Bus fleet (3 buses)
6. **bus_schedules** - Bus booking schedules
7. **bus_bookings** - Active bus bookings
8. **billing_statements** - Billing and receipts
9. **facilities** - Facilities (gym, pool, courts)
10. **bookings** - Facility reservations
11. **requests** - General user requests

---

## Default Login Accounts

All use password: `admin123`

| Type | Email | Access |
|------|-------|--------|
| Admin | admin@chmsu.edu.ph | Full system admin |
| Staff | staff@chmsu.edu.ph | BAO staff access |
| Student | student@chmsu.edu.ph | Student portal |
| External | external@example.com | External user portal |

---

## Useful Tools

### Test Database Connection
```
http://localhost/Capstone-3/test_database.php
```
- Check connection status
- Verify all tables exist
- See sample users

### Fix Table References (if needed)
```
http://localhost/Capstone-3/database/fix_table_references.php
```
- Automatically updates files using "users" to use "user_accounts"
- Fixes JOIN, FROM, UPDATE, INTO statements

### Migrate Existing Data (if you had a "users" table)
```
http://localhost/Capstone-3/database/migrate_users_table.php
```
- Copies data from old "users" table to "user_accounts"
- Preserves existing data

---

## Troubleshooting

### "Connection failed"
**Problem:** Can't connect to database

**Fix:**
1. Check MySQL is running in XAMPP (should be green)
2. Check `config/database.php`:
   - Database: `chmsu_bao`
   - User: `root`
   - Password: (empty)

### "Table still doesn't exist"
**Problem:** Error persists after setup

**Fix:**
1. Run `test_database.php` to check status
2. Make sure setup completed without errors
3. Refresh the page and clear browser cache

### "Page not found"
**Problem:** Setup page won't load

**Fix:**
1. Make sure Apache is running in XAMPP
2. Check the folder is at: `C:\xampp\htdocs\Capstone-3\`
3. Try accessing from root: `http://localhost/Capstone-3/`

### "Duplicate entry" warnings
**Not a problem!** This means data already exists. The setup script is smart and won't duplicate data.

---

## After Setup

### Change Default Passwords
⚠️ **IMPORTANT:** Change `admin123` passwords after first login!

### Test Features
- ✅ Login/Logout
- ✅ Registration (with OTP)
- ✅ Password Reset
- ✅ User Dashboards
- ✅ Bus Booking
- ✅ Facility Booking
- ✅ Inventory/Orders

### Clean Up (Optional)
Once everything works, you can delete:
- `setup_database.php`
- `test_database.php`
- `START_HERE.md` (this file)
- `QUICK_START.txt`
- `FIX_SUMMARY.md`

Keep the `database/` folder for future reference!

---

## Need More Help?

### Check These Files:
1. `FIX_SUMMARY.md` - Detailed explanation of the fix
2. `DATABASE_SETUP_INSTRUCTIONS.md` - Full setup guide
3. `database/README.md` - Database documentation

### Common Issues:
- XAMPP not running → Start Apache & MySQL
- Wrong database name → Check `config/database.php`
- Permission errors → Run XAMPP as Administrator
- Still seeing errors → Run `test_database.php` to diagnose

---

## System Requirements

✅ XAMPP (or equivalent)
- Apache 2.4+
- MySQL 5.7+ / MariaDB 10.2+
- PHP 7.4+

✅ PHP Extensions:
- mysqli
- pdo_mysql
- mbstring

---

## What's Next?

1. ✅ Run the setup
2. ✅ Test login with sample accounts
3. ✅ Change default passwords
4. ✅ Explore the system
5. ✅ Customize as needed

---

**🎉 Your system should now be fully functional!**

If you need any help, all the documentation is in the files listed above.

Good luck with your CHMSU BAO System! 🚀








































