# 🎓 CHMSU Business Affairs Office Management System

A comprehensive web-based management system for Carlos Hilado Memorial State University (CHMSU) Business Affairs Office to handle bus requests, inventory management, gym reservations, and administrative tasks.

## 📋 Features

### 👥 User Roles

- **BAO Admin** - Full system access including user management
- **BAO Secretary** - Administrative access without user management and inventory addition
- **Students/Faculty/Staff** - Request services (bus, gym, inventory)
- **External Users** - Limited access for external organizations

### 🚌 Bus Request Management

- Schedule bus rentals for official activities
- Automated distance calculation using local Negros Occidental database
- Dynamic fuel rate management
- Billing statement generation
- Request approval/rejection workflow
- Bus availability tracking

### 📦 Inventory Management

- Track office supplies and equipment
- Size/specification support for items
- Order processing and fulfillment
- Receipt generation
- Low stock notifications

### 🏋️ Gym Reservation System

- Real-time gym availability checking
- Calendar-based booking system
- Equipment request integration
- Reservation approval workflow

### 📊 Reports & Analytics

- Revenue tracking
- Usage statistics
- Booking history
- Inventory reports

## 🛠️ Technologies Used

- **Backend:** PHP 7.4+
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, JavaScript
- **UI Framework:** Tailwind CSS
- **Icons:** Font Awesome
- **Calendar:** FullCalendar
- **Testing:** Cypress

## 📥 Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (optional)

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/capstone-3.git
   cd capstone-3
   ```

2. **Configure Database**
   ```bash
   # Copy the example config file
   cp config/database.example.php config/database.php
   
   # Edit config/database.php with your database credentials
   ```

3. **Create Database**
   ```sql
   CREATE DATABASE capstone3;
   ```

4. **Import Database Schema**
   ```bash
   # Import the complete database setup
   mysql -u root -p capstone3 < database/complete_database_setup.sql
   ```

5. **Set File Permissions**
   ```bash
   # Make uploads directory writable
   chmod -R 755 uploads/
   ```

6. **Access the System**
   - Open browser: `http://localhost/capstone-3`
   - Default admin login:
     - Email: `admin@chmsu.edu.ph`
     - Password: `admin123`
     - User Type: BAO Admin

## 🔐 Default Accounts

| Role | Email | Password | User Type |
|------|-------|----------|-----------|
| Admin | admin@chmsu.edu.ph | admin123 | BAO Admin |
| Student | student@chmsu.edu.ph | student123 | Student |
| External | external@example.com | external123 | External User |

⚠️ **Important:** Change default passwords after first login!

## 📁 Project Structure

```
capstone-3/
├── admin/              # Admin panel pages
├── student/            # Student portal pages
├── external/           # External user pages
├── staff/              # Staff pages
├── config/             # Configuration files
├── includes/           # Reusable components
├── database/           # Database schemas and setup
├── assets/             # CSS, JS, images
├── uploads/            # User uploaded files
└── PHPMailer/          # Email functionality
```

## 🚀 Deployment

### Free Hosting Options

1. **InfinityFree** (Recommended for testing)
   - Unlimited bandwidth
   - MySQL database included
   - Free SSL

2. **000webhost**
   - 300 MB storage
   - PHP 7.4 support

See deployment guide in `/docs` folder.

## 🔧 Configuration

### Distance Calculation

The system uses a local database of Negros Occidental coordinates for distance calculation:
- File: `includes/negros_occidental_locations.php`
- Add new locations as needed
- Haversine formula for accuracy

### Fuel Rate Management

Admins can update fuel rates dynamically:
- Navigate to: Admin → Bus Management → Fuel Rate Settings
- Changes apply to new bookings only

## 🧪 Testing

```bash
# Install Cypress (if not already installed)
npm install

# Run tests
npm run cypress:open
```

## 📝 License

This project is developed as a capstone project for CHMSU.

## 👥 Contributors

- **Development Team** - CHMSU Students
- **Advisor** - [Faculty Name]

## 📞 Support

For issues and questions:
- Create an issue on GitHub
- Contact: [your-email@example.com]

## 🙏 Acknowledgments

- Carlos Hilado Memorial State University
- Business Affairs Office
- Faculty advisors and panel members

---

**Note:** This system is designed specifically for CHMSU Business Affairs Office operations and contains location-specific data for Negros Occidental.

