-- Bus Management System Database Schema

-- Create bus_schedules table (enhanced version)
-- First, check if table exists and add missing columns
ALTER TABLE bus_schedules 
ADD COLUMN IF NOT EXISTS status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS user_id INT,
ADD COLUMN IF NOT EXISTS user_type ENUM('student', 'admin', 'staff', 'external') DEFAULT 'student',
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Create buses table for availability tracking
CREATE TABLE IF NOT EXISTS buses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bus_number VARCHAR(20) UNIQUE NOT NULL,
    vehicle_type VARCHAR(50) NOT NULL,
    capacity INT NOT NULL,
    status ENUM('available', 'booked', 'maintenance', 'out_of_service') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create bus_bookings table for detailed booking tracking
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

-- Create billing_statements table for receipt generation
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

-- Insert default buses (3 buses as specified)
INSERT IGNORE INTO buses (bus_number, vehicle_type, capacity, status) VALUES
('1', 'Bus', 50, 'available'),
('2', 'Bus', 50, 'available'),
('3', 'Bus', 50, 'available');

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_bus_schedules_date ON bus_schedules(date_covered);
CREATE INDEX IF NOT EXISTS idx_bus_schedules_status ON bus_schedules(status);
CREATE INDEX IF NOT EXISTS idx_bus_bookings_date ON bus_bookings(booking_date);
CREATE INDEX IF NOT EXISTS idx_bus_bookings_bus ON bus_bookings(bus_id);
CREATE INDEX IF NOT EXISTS idx_billing_statements_schedule ON billing_statements(schedule_id);
