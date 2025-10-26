-- Complete Database Schema for CHMSU BAO System
-- This file creates all necessary tables for the system

-- ============================================
-- USER ACCOUNTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS user_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'staff', 'student', 'external') NOT NULL,
    organization VARCHAR(255) NULL,
    profile_pic VARCHAR(255) NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email)
);

-- ============================================
-- INVENTORY AND ORDERS TABLES
-- ============================================
CREATE TABLE IF NOT EXISTS inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    category VARCHAR(100),
    in_stock BOOLEAN DEFAULT TRUE,
    stock_quantity INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    inventory_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE
);

-- ============================================
-- BUS MANAGEMENT TABLES
-- ============================================
CREATE TABLE IF NOT EXISTS bus_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client VARCHAR(255) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    purpose VARCHAR(255) NOT NULL,
    date_covered DATE NOT NULL,
    vehicle VARCHAR(50) NOT NULL,
    bus_no VARCHAR(20) NOT NULL,
    no_of_days INT NOT NULL DEFAULT 1,
    no_of_vehicles INT NOT NULL DEFAULT 1,
    user_id INT,
    user_type ENUM('student', 'admin', 'staff', 'external') DEFAULT 'student',
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS buses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bus_number VARCHAR(20) UNIQUE NOT NULL,
    vehicle_type VARCHAR(50) NOT NULL,
    capacity INT NOT NULL,
    status ENUM('available', 'booked', 'maintenance', 'out_of_service') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bus_bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    schedule_id INT NOT NULL,
    bus_id INT NOT NULL,
    booking_date DATE NOT NULL,
    status ENUM('active', 'cancelled', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES bus_schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS billing_statements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    schedule_id INT NOT NULL,
    client VARCHAR(255) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    purpose VARCHAR(255) NOT NULL,
    date_covered DATE NOT NULL,
    no_of_days INT NOT NULL,
    vehicle VARCHAR(50) NOT NULL,
    bus_no VARCHAR(20) NOT NULL,
    no_of_vehicles INT NOT NULL,
    
    -- Itinerary details
    from_location VARCHAR(255) NOT NULL,
    to_location VARCHAR(255) NOT NULL,
    distance_km DECIMAL(10,2) NOT NULL,
    total_distance_km DECIMAL(10,2) NOT NULL,
    
    -- Cost calculations
    fuel_rate DECIMAL(10,2) NOT NULL DEFAULT 70.00,
    computed_distance DECIMAL(10,2) NOT NULL,
    runtime_liters DECIMAL(10,2) NOT NULL,
    
    -- Cost breakdown per vehicle
    fuel_cost DECIMAL(10,2) NOT NULL,
    runtime_cost DECIMAL(10,2) NOT NULL,
    maintenance_cost DECIMAL(10,2) NOT NULL,
    standby_cost DECIMAL(10,2) NOT NULL,
    additive_cost DECIMAL(10,2) NOT NULL,
    rate_per_bus DECIMAL(10,2) NOT NULL,
    subtotal_per_vehicle DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    
    -- Payment status
    payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    payment_date TIMESTAMP NULL,
    
    -- Approval fields
    prepared_by VARCHAR(255),
    recommending_approval VARCHAR(255),
    approved_by VARCHAR(255),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES bus_schedules(id) ON DELETE CASCADE
);

-- ============================================
-- FACILITY AND BOOKING TABLES
-- ============================================
CREATE TABLE IF NOT EXISTS facilities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    capacity INT,
    type ENUM('gym', 'other') DEFAULT 'other',
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    facility_id INT NOT NULL,
    user_id INT NOT NULL,
    user_type ENUM('student', 'admin', 'staff', 'external') NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled', 'unavailable') DEFAULT 'pending',
    is_buffer BOOLEAN DEFAULT FALSE,
    purpose VARCHAR(255),
    participants INT DEFAULT 1,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE
);

-- ============================================
-- REQUESTS TABLE (for general requests)
-- ============================================
CREATE TABLE IF NOT EXISTS requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    user_type ENUM('student', 'admin', 'staff', 'external') NOT NULL,
    request_type VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_accounts(id) ON DELETE CASCADE
);

-- ============================================
-- CREATE INDEXES FOR BETTER PERFORMANCE
-- ============================================
CREATE INDEX IF NOT EXISTS idx_user_email ON user_accounts(email);
CREATE INDEX IF NOT EXISTS idx_user_type ON user_accounts(user_type);
CREATE INDEX IF NOT EXISTS idx_user_status ON user_accounts(status);

CREATE INDEX IF NOT EXISTS idx_inventory_stock ON inventory(in_stock);
CREATE INDEX IF NOT EXISTS idx_inventory_category ON inventory(category);

CREATE INDEX IF NOT EXISTS idx_orders_user ON orders(user_id);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_orders_order_id ON orders(order_id);

CREATE INDEX IF NOT EXISTS idx_bus_schedules_date ON bus_schedules(date_covered);
CREATE INDEX IF NOT EXISTS idx_bus_schedules_status ON bus_schedules(status);
CREATE INDEX IF NOT EXISTS idx_bus_bookings_date ON bus_bookings(booking_date);
CREATE INDEX IF NOT EXISTS idx_bus_bookings_bus ON bus_bookings(bus_id);
CREATE INDEX IF NOT EXISTS idx_billing_statements_schedule ON billing_statements(schedule_id);

CREATE INDEX IF NOT EXISTS idx_booking_date ON bookings(booking_date);
CREATE INDEX IF NOT EXISTS idx_facility_date ON bookings(facility_id, booking_date);
CREATE INDEX IF NOT EXISTS idx_bookings_user_type ON bookings(user_type);

CREATE INDEX IF NOT EXISTS idx_requests_user ON requests(user_id);
CREATE INDEX IF NOT EXISTS idx_requests_status ON requests(status);

-- ============================================
-- INSERT SAMPLE DATA
-- ============================================

-- Insert sample users (password for all: admin123)
INSERT INTO user_accounts (name, email, password, user_type, organization, status) VALUES
('System Administrator', 'admin@chmsu.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, 'active'),
('BAO Staff', 'staff@chmsu.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, 'active'),
('Test Student', 'student@chmsu.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', NULL, 'active'),
('External User', 'external@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'external', 'Sample Organization', 'active')
ON DUPLICATE KEY UPDATE name=name;

-- Insert default buses (3 buses as specified)
INSERT IGNORE INTO buses (bus_number, vehicle_type, capacity, status) VALUES
('1', 'Bus', 50, 'available'),
('2', 'Bus', 50, 'available'),
('3', 'Bus', 50, 'available');

-- Insert sample facilities with gym type
INSERT INTO facilities (name, description, capacity, type) VALUES
('Gymnasium', 'Main gymnasium for sports and events', 500, 'gym'),
('Swimming Pool', 'Olympic-sized swimming pool', 100, 'gym'),
('Tennis Court', 'Outdoor tennis court', 4, 'gym'),
('Basketball Court', 'Indoor basketball court', 50, 'gym'),
('Conference Room', 'Meeting and conference room', 30, 'other')
ON DUPLICATE KEY UPDATE name=name;

-- Insert sample inventory items
INSERT INTO inventory (name, description, price, category, in_stock, stock_quantity) VALUES
('CHMSU T-Shirt', 'Official CHMSU t-shirt in various sizes', 250.00, 'Apparel', TRUE, 100),
('CHMSU Mug', 'Ceramic mug with CHMSU logo', 150.00, 'Merchandise', TRUE, 50),
('Notebook', 'CHMSU branded notebook', 75.00, 'Stationery', TRUE, 200),
('Pen Set', 'Set of 3 ballpoint pens', 50.00, 'Stationery', TRUE, 150),
('USB Flash Drive', '16GB USB drive with CHMSU logo', 300.00, 'Electronics', TRUE, 30)
ON DUPLICATE KEY UPDATE name=name;

