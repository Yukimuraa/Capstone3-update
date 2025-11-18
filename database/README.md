# Database Setup Guide

## Quick Start

### Option 1: Web Interface (Easiest)
1. Make sure XAMPP is running (Apache + MySQL)
2. Open: `http://localhost/Capstone-3/database/index.php`
3. Click "Run Complete Setup"
4. Wait for completion
5. Click "Go to Login"

### Option 2: Direct Setup
1. Open: `http://localhost/Capstone-3/database/setup_complete.php`
2. Or run: `http://localhost/Capstone-3/setup_database.php`

### Option 3: phpMyAdmin Import
1. Open: `http://localhost/phpmyadmin`
2. Select database: `chmsu_bao`
3. Import file: `complete_database_setup.sql`

## Files in this Directory

### Setup Scripts (PHP)
- **`setup_complete.php`** - ⭐ Main setup script (creates all tables)
- **`setup_user_accounts.php`** - Creates user_accounts table only
- **`setup_bus.php`** - Creates bus management tables
- **`setup_calendar.php`** - Creates facility booking tables
- **`index.php`** - Visual setup interface with status checking

### SQL Files
- **`complete_database_setup.sql`** - ⭐ Complete database schema (all tables)
- **`user_accounts_tables.sql`** - User accounts schema
- **`bus_tables.sql`** - Bus management schema
- **`calendar_tables.sql`** - Facility booking schema

### Other Files
- **`create_new_users.php`** - Legacy user creation script
- **`update_passwords.php`** - Password update utility

## Tables Created

The complete setup creates these tables:

### Core System
1. **user_accounts** - User authentication and profiles
2. **requests** - General user requests

### Inventory Management
3. **inventory** - Product/item inventory
4. **orders** - Order management

### Bus Management
5. **buses** - Bus fleet information
6. **bus_schedules** - Bus booking schedules
7. **bus_bookings** - Active bus bookings
8. **billing_statements** - Billing and receipts

### Facility Management
9. **facilities** - Facility information (gym, courts, etc.)
10. **bookings** - Facility bookings

## Default Login Credentials

Password for all accounts: `admin123`

| User Type | Email | Access Level |
|-----------|-------|--------------|
| Admin | admin@chmsu.edu.ph | Full system access |
| Staff | staff@chmsu.edu.ph | Staff/admin access |
| Student | student@chmsu.edu.ph | Student portal |
| External | external@example.com | External user portal |

## Troubleshooting

### "Table doesn't exist" Error
**Solution:** Run `setup_complete.php` to create all tables

### "Connection failed" Error
**Check:**
- MySQL service is running in XAMPP
- Database name in `config/database.php` is `chmsu_bao`
- MySQL credentials: user=`root`, password=`(empty)`

### "Duplicate entry" Warning
**This is normal** - It means the data already exists. The setup uses:
- `CREATE TABLE IF NOT EXISTS` - Won't fail if table exists
- `INSERT ... ON DUPLICATE KEY UPDATE` - Won't duplicate data

### "Permission denied" Error
**Solution:**
- Run XAMPP as Administrator
- Check MySQL user has CREATE, INSERT, ALTER permissions

### Tables Created but Login Still Fails
**Check:**
1. Database connection in `config/database.php`
2. Table name is exactly `user_accounts` (case-sensitive on Linux)
3. Run `SELECT * FROM user_accounts` in phpMyAdmin to verify data

## Manual Database Creation

If automated setup fails, you can manually create the database:

```sql
CREATE DATABASE IF NOT EXISTS chmsu_bao 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
```

Then import `complete_database_setup.sql` via phpMyAdmin.

## Updating Existing Database

If you need to add missing columns to existing tables:

```sql
-- Add organization column if missing
ALTER TABLE user_accounts 
ADD COLUMN IF NOT EXISTS organization VARCHAR(255) NULL 
AFTER user_type;
```

## Sample Data

The setup includes sample data:
- 4 user accounts (admin, staff, student, external)
- 3 buses (Bus #1, #2, #3)
- 5 facilities (Gymnasium, Pool, Tennis Court, Basketball Court, Conference Room)
- 5 inventory items (T-Shirt, Mug, Notebook, Pen Set, USB Drive)

## Security Notes

⚠️ **IMPORTANT:** After setup:
1. Change default passwords immediately
2. Remove or restrict access to setup scripts in production
3. Update sample user emails to real ones
4. Review and adjust user permissions

## Need Help?

If you're still having issues:
1. Check XAMPP Control Panel - MySQL should show "Running"
2. Check `config/database.php` has correct settings
3. Look at error messages in the setup script
4. Check MySQL error log in XAMPP

## Support Files

- Main instructions: `../DATABASE_SETUP_INSTRUCTIONS.md`
- Database config: `../config/database.php`
- Test connection: `../test.php` (if exists)


































