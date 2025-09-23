-- Create facilities table
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

-- Create bookings table
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

-- Insert sample facilities with gym type
INSERT INTO facilities (name, description, capacity, type) VALUES
('Gymnasium', 'Main gymnasium for sports and events', 500, 'gym'),
('Swimming Pool', 'Olympic-sized swimming pool', 100, 'gym'),
('Tennis Court', 'Outdoor tennis court', 4, 'gym'),
('Basketball Court', 'Indoor basketball court', 50, 'gym'),
('Conference Room', 'Meeting and conference room', 30, 'other');

-- Create indexes after tables are created
CREATE INDEX idx_booking_date ON bookings(booking_date);
CREATE INDEX idx_facility_date ON bookings(facility_id, booking_date);
CREATE INDEX idx_user_type ON bookings(user_type); 