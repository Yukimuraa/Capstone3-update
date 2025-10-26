# 🚌 Bus and Fuel Rate Management System

## New Features Added

This guide explains the new bus management and fuel rate features added to the admin panel.

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [Setup Instructions](#setup-instructions)
4. [How to Use](#how-to-use)
5. [Technical Details](#technical-details)

---

## 🎯 Overview

The Bus Management System now includes:
- **Add New Buses** - Admins can add new buses to the fleet
- **Manage Bus Status** - Update bus availability status (available, maintenance, etc.)
- **Dynamic Fuel Rate** - Update fuel prices as they change in the market
- **Automatic Billing** - System uses the current fuel rate for all new bookings

---

## ✨ Features

### 1. Bus Management Tab
- **Add New Buses**: Add buses with custom bus numbers, types, and capacity
- **View All Buses**: See complete list of all buses in your fleet
- **Edit Bus Status**: Change bus status between:
  - Available
  - Booked
  - Maintenance
  - Out of Service
- **Delete Buses**: Remove buses (only if no active bookings exist)

### 2. Fuel Rate Tab
- **View Current Rate**: See the current fuel rate being used for calculations
- **Update Fuel Rate**: Change the fuel price per liter
- **Real-time Updates**: All new bookings automatically use the updated rate
- **Protected Existing Bookings**: Past bookings retain their original fuel rate

### 3. Bus Schedules Tab
- View and manage all bus schedule requests
- Approve or reject pending requests
- Filter by month or view all requests
- Print receipts for approved bookings

---

## 🔧 Setup Instructions

### Step 1: Initialize the Fuel Rate Settings

Run the setup script to create the settings table and initialize the default fuel rate:

```
http://localhost/Capstone-3/database/setup_fuel_rate.php
```

This will:
- Create the `bus_settings` table
- Set the default fuel rate to ₱70.00 per liter
- Verify the setup was successful

### Step 2: Access the Admin Panel

1. Log in as an admin
2. Navigate to **Bus Management** from the sidebar
3. You'll see three tabs at the top:
   - 📅 Bus Schedules
   - 🚌 Manage Buses
   - ⛽ Fuel Rate

---

## 📖 How to Use

### Adding a New Bus

1. Go to **Admin → Bus Management → Manage Buses**
2. Fill in the form on the left:
   - **Bus Number**: Enter a unique identifier (e.g., "4", "Bus-001")
   - **Vehicle Type**: Select from Bus, Coaster, Van, or Shuttle
   - **Capacity**: Enter the number of seats
   - **Status**: Select initial status
3. Click **Add Bus**

✅ Success! The bus will appear in the list on the right.

### Managing Bus Status

1. Go to **Manage Buses** tab
2. Find the bus in the list
3. Click the **Edit** icon (pencil)
4. Select the new status from the dropdown
5. Click **Update Status**

**Status Options:**
- **Available** - Ready for booking
- **Booked** - Currently reserved
- **Maintenance** - Under repair/service
- **Out of Service** - Not available for use

### Deleting a Bus

1. Go to **Manage Buses** tab
2. Find the bus you want to remove
3. Click the **Delete** icon (trash)
4. Confirm the deletion

⚠️ **Note**: You cannot delete buses with active bookings. Complete or cancel those bookings first.

### Updating Fuel Rate

1. Go to **Admin → Bus Management → Fuel Rate**
2. You'll see the current fuel rate displayed
3. Enter the new fuel rate in the form (e.g., 75.50)
4. Click **Update Fuel Rate**

✅ The system will:
- Save the new rate immediately
- Use it for all NEW bus bookings
- Keep existing bookings at their original rate

**Example:**
- Current Rate: ₱70.00/liter
- Student submits booking → Uses ₱70.00
- Admin updates to ₱75.00
- New student booking → Uses ₱75.00
- Previous booking → Still uses ₱70.00

---

## 🔬 Technical Details

### Database Changes

#### New Table: `bus_settings`
```sql
CREATE TABLE bus_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

#### Updated Logic
The system now dynamically fetches the fuel rate when calculating billing:

**Before:**
```php
$fuel_rate = 70.00; // Hardcoded
```

**After:**
```php
$fuel_rate_query = $conn->query("SELECT setting_value FROM bus_settings WHERE setting_key = 'fuel_rate'");
if ($fuel_rate_query && $fuel_rate_query->num_rows > 0) {
    $fuel_rate = floatval($fuel_rate_query->fetch_assoc()['setting_value']);
} else {
    $fuel_rate = 70.00; // Default fallback
}
```

### Files Modified

1. **admin/bus.php** - Added tabs and management features
2. **student/bus.php** - Updated fuel rate calculation
3. **database/setup_fuel_rate.php** - New setup script

### API Endpoints (POST Actions)

| Action | Description | Parameters |
|--------|-------------|------------|
| `add_bus` | Add new bus | bus_number, vehicle_type, capacity, status |
| `update_bus_status` | Change bus status | bus_id, new_status |
| `delete_bus` | Remove bus | bus_id |
| `update_fuel_rate` | Change fuel price | fuel_rate |

---

## 💡 Best Practices

### For Fuel Rate Management
1. **Check Local Prices**: Regularly monitor local fuel stations
2. **Keep Records**: Document when and why you update rates
3. **Add Buffer**: Consider adding 5-10% buffer for price fluctuations
4. **Update Timely**: Update before approving new bookings

### For Bus Management
1. **Use Clear Numbers**: Use simple, memorable bus numbers (1, 2, 3...)
2. **Regular Maintenance**: Mark buses as "Maintenance" when servicing
3. **Update Status Promptly**: Change status immediately when booking/completing
4. **Clean Data**: Delete only buses that are permanently retired

---

## 🆘 Troubleshooting

### Problem: Fuel rate not updating
**Solution**: Run `database/setup_fuel_rate.php` to initialize the settings table

### Problem: Cannot delete bus
**Solution**: Check for active bookings. Complete or cancel them first.

### Problem: Bus number already exists
**Solution**: Choose a different, unique bus number

### Problem: Old bookings showing new fuel rate
**Solution**: This shouldn't happen - existing bookings retain original rate. If it does, contact support.

---

## 📞 Support

If you encounter any issues:
1. Check the PHP error logs
2. Verify database connection
3. Ensure you're logged in as admin
4. Run the setup script again

---

## 🔄 Future Enhancements

Potential features for future versions:
- Fuel rate history tracking
- Automatic fuel price updates from API
- Email notifications on fuel rate changes
- Bulk bus import/export
- Bus maintenance scheduling
- Fuel consumption analytics

---

**Version**: 1.0  
**Last Updated**: October 26, 2025  
**Author**: Capstone-3 Development Team

