CREATE DATABASE penang_parking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE penang_parking;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    ic_number VARCHAR(20),
    role ENUM('super_admin', 'admin', 'officer', 'customer') NOT NULL DEFAULT 'customer',
    status ENUM('active', 'suspended', 'deactivated') NOT NULL DEFAULT 'active',
    permissions JSON,
    last_login_at DATETIME,
    last_login_ip VARCHAR(45),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vehicles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id INT UNSIGNED NOT NULL,
    plate VARCHAR(50) NOT NULL,
    normalized_plate VARCHAR(50) NOT NULL,
    vehicle_type ENUM('car', 'motorcycle', 'lorry', 'bus', 'van') NOT NULL DEFAULT 'car',
    color VARCHAR(50),
    make VARCHAR(100),
    model VARCHAR(100),
    year INT,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_normalized_plate (normalized_plate),
    INDEX idx_owner (owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wallets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL UNIQUE,
    balance DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wallet_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wallet_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    transaction_type ENUM('RELOAD', 'PARKING_PAYMENT', 'COMPOUND_PAYMENT', 'REFUND', 'ADMIN_CREDIT', 'ADMIN_DEBIT', 'ADJUSTMENT') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    balance_before DECIMAL(10, 2) NOT NULL,
    balance_after DECIMAL(10, 2) NOT NULL,
    reference_id VARCHAR(100),
    status ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id),
    INDEX idx_reference (reference_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE parking_zones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    address TEXT,
    latitude DECIMAL(10, 7),
    longitude DECIMAL(10, 7),
    hourly_rate DECIMAL(5, 2) NOT NULL DEFAULT 1.00,
    max_duration INT NOT NULL DEFAULT 120,
    capacity INT NOT NULL DEFAULT 100,
    available_spaces INT NOT NULL DEFAULT 100,
    operating_hours JSON,
    weekend_rules JSON,
    holiday_rules JSON,
    enforce_outside_hours TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive', 'maintenance') NOT NULL DEFAULT 'active',
    qr_token VARCHAR(64) UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE parking_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_number VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT UNSIGNED NOT NULL,
    vehicle_id INT UNSIGNED NOT NULL,
    normalized_plate VARCHAR(50) NOT NULL,
    plate_snapshot VARCHAR(255),
    zone_id INT UNSIGNED NOT NULL,
    gps_lat DECIMAL(10, 7),
    gps_lng DECIMAL(10, 7),
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    duration_minutes INT NOT NULL,
    fee DECIMAL(10, 2) NOT NULL,
    rate_snapshot JSON,
    payment_transaction_id VARCHAR(100),
    status ENUM('active', 'expired', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (zone_id) REFERENCES parking_zones(id),
    INDEX idx_session_number (session_number),
    INDEX idx_normalized_plate (normalized_plate),
    INDEX idx_customer (customer_id),
    INDEX idx_zone (zone_id),
    INDEX idx_status (status),
    INDEX idx_end_time (end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cameras (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    camera_type ENUM('upload', 'webcam', 'ip', 'anpr', 'other') NOT NULL DEFAULT 'upload',
    zone_id INT UNSIGNED,
    latitude DECIMAL(10, 7),
    longitude DECIMAL(10, 7),
    status ENUM('active', 'inactive', 'maintenance') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (zone_id) REFERENCES parking_zones(id) ON DELETE SET NULL,
    INDEX idx_code (code),
    INDEX idx_zone (zone_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ai_detections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    camera_id INT UNSIGNED,
    plate VARCHAR(50),
    confidence DECIMAL(5, 4),
    model_used VARCHAR(50),
    processing_time DECIMAL(8, 4),
    image_path VARCHAR(500),
    status ENUM('processed', 'review', 'violation', 'error') NOT NULL DEFAULT 'processed',
    enforcement_result VARCHAR(100),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (camera_id) REFERENCES cameras(id) ON DELETE SET NULL,
    INDEX idx_plate (plate),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE compounds (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    compound_number VARCHAR(50) NOT NULL UNIQUE,
    violation_type VARCHAR(100) NOT NULL,
    normalized_plate VARCHAR(50) NOT NULL,
    plate_snapshot VARCHAR(255),
    vehicle_id INT UNSIGNED,
    customer_id INT UNSIGNED,
    zone_id INT UNSIGNED,
    detection_time DATETIME NOT NULL,
    evidence_path VARCHAR(500),
    amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending_review', 'issued', 'paid', 'overdue', 'appealed', 'cancelled') NOT NULL DEFAULT 'pending_review',
    issued_by INT UNSIGNED,
    reviewed_by INT UNSIGNED,
    due_date DATETIME NOT NULL,
    paid_at DATETIME,
    review_remarks TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (zone_id) REFERENCES parking_zones(id) ON DELETE SET NULL,
    FOREIGN KEY (issued_by) REFERENCES users(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id),
    INDEX idx_compound_number (compound_number),
    INDEX idx_normalized_plate (normalized_plate),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE appeals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    compound_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    reviewed_by INT UNSIGNED,
    reviewed_at DATETIME,
    review_remarks TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (compound_id) REFERENCES compounds(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id),
    INDEX idx_compound (compound_id),
    INDEX idx_customer (customer_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_id INT UNSIGNED,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT UNSIGNED,
    old_value JSON,
    new_value JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (email, password, full_name, role, status, permissions) VALUES 
('superadmin@penangparking.gov', '$2y$12$XOYMzE5dBpNRmXKBSJ8K8uJ5VQZ1Fj5xL6NqGhB7wE8cD3fG5hI9K', 'System Administrator', 'super_admin', 'active', '[]'), 
('admin@penangparking.gov', '$2y$12$XOYMzE5dBpNRmXKBSJ8K8uJ5VQZ1Fj5xL6NqGhB7wE8cD3fG5hI9K', 'Admin User', 'admin', 'active', '["*"]'), 
('officer@penangparking.gov', '$2a$12$GCtURD.spOeZ4e6nVOoDKu.NzRtGYyUMEethrjIlYhMbPWJw86uIW', 'Enforcement Officer 1', 'officer', 'active', '["scan", "review_compounds", "view_evidence"]'), 
('officer2@penangparking.gov', '$2y$12$XOYMzE5dBpNRmXKBSJ8K8uJ5VQZ1Fj5xL6NqGhB7wE8cD3fG5hI9K', 'Enforcement Officer 2', 'officer', 'active', '["scan", "review_compounds", "view_evidence"]'), 
('customer@test.com', '$2a$12$4TKdB9t8albaZPmYzU/UhuPEzdqfXG9Lx2a1FQShTHAvUiwv05smW', 'Ahmad bin Ali', 'customer', 'active', '[]'), 
('customer2@test.com', '$2y$12$XOYMzE5dBpNRmXKBSJ8K8uJ5VQZ1Fj5xL6NqGhB7wE8cD3fG5hI9K', 'Tan Mei Ling', 'customer', 'active', '[]');

INSERT INTO wallets (customer_id, balance) VALUES
((SELECT id FROM users WHERE email = 'customer1@test.com'), 100.00),
((SELECT id FROM users WHERE email = 'customer2@test.com'), 50.00);

INSERT INTO parking_zones (name, code, address, latitude, longitude, hourly_rate, max_duration, capacity, available_spaces, status, operating_hours, enforce_outside_hours) VALUES
('George Town Zone A', 'GTA', 'Victoria Street, George Town', 5.4141000, 100.3288000, 1.50, 120, 50, 48, 'active', '{"monday":{"start":"07:00","end":"22:00"},"tuesday":{"start":"07:00","end":"22:00"},"wednesday":{"start":"07:00","end":"22:00"},"thursday":{"start":"07:00","end":"22:00"},"friday":{"start":"07:00","end":"22:00"},"saturday":{"start":"08:00","end":"20:00"},"sunday":{"start":"08:00","end":"20:00"}}', 1),
('George Town Zone B', 'GTB', 'Chulia Street, George Town', 5.4164000, 100.3327000, 2.00, 120, 30, 28, 'active', '{"monday":{"start":"07:00","end":"22:00"},"tuesday":{"start":"07:00","end":"22:00"},"wednesday":{"start":"07:00","end":"22:00"},"thursday":{"start":"07:00","end":"22:00"},"friday":{"start":"07:00","end":"22:00"},"saturday":{"start":"08:00","end":"20:00"},"sunday":{"start":"08:00","end":"20:00"}}', 1),
('Batu Ferringhi Zone', 'BFZ', 'Batu Ferringhi Road', 5.4617000, 100.2917000, 1.00, 240, 80, 75, 'active', '{"monday":{"start":"06:00","end":"23:00"},"tuesday":{"start":"06:00","end":"23:00"},"wednesday":{"start":"06:00","end":"23:00"},"thursday":{"start":"06:00","end":"23:00"},"friday":{"start":"06:00","end":"23:00"},"saturday":{"start":"06:00","end":"23:00"},"sunday":{"start":"06:00","end":"23:00"}}', 0),
('Bayan Lepas Zone', 'BLZ', 'Main Road, Bayan Lepas', 5.3050000, 100.2450000, 1.00, 180, 60, 55, 'active', '{"monday":{"start":"07:00","end":"21:00"},"tuesday":{"start":"07:00","end":"21:00"},"wednesday":{"start":"07:00","end":"21:00"},"thursday":{"start":"07:00","end":"21:00"},"friday":{"start":"07:00","end":"21:00"},"saturday":{"start":"08:00","end":"20:00"},"sunday":{"start":"08:00","end":"20:00"}}', 1),
('Penang Bridge Zone', 'PBZ', 'Penang Bridge Toll', 5.2739000, 100.1997000, 2.00, 60, 20, 18, 'active', '{"monday":{"start":"00:00","end":"23:59"},"tuesday":{"start":"00:00","end":"23:59"},"wednesday":{"start":"00:00","end":"23:59"},"thursday":{"start":"00:00","end":"23:59"},"friday":{"start":"00:00","end":"23:59"},"saturday":{"start":"00:00","end":"23:59"},"sunday":{"start":"00:00","end":"23:59"}}', 0);

INSERT INTO vehicles (owner_id, plate, normalized_plate, vehicle_type, color, make, model, year) VALUES
((SELECT id FROM users WHERE email = 'customer1@test.com'), 'WXY 1234', 'WXY1234', 'car', 'white', 'Toyota', 'Camry', 2022),
((SELECT id FROM users WHERE email = 'customer1@test.com'), 'ABC 5678', 'ABC5678', 'motorcycle', 'red', 'Honda', 'PCX', 2023),
((SELECT id FROM users WHERE email = 'customer2@test.com'), 'DEF 9012', 'DEF9012', 'car', 'black', 'BMW', '320i', 2021);

INSERT INTO cameras (name, code, camera_type, zone_id, latitude, longitude, status) VALUES
('Cam GTA North', 'CAM-GTA-01', 'anpr', (SELECT id FROM parking_zones WHERE code = 'GTA'), 5.4141000, 100.3288000, 'active'),
('Cam GTB South', 'CAM-GTB-01', 'anpr', (SELECT id FROM parking_zones WHERE code = 'GTB'), 5.4164000, 100.3327000, 'active');

INSERT INTO parking_sessions (session_number, customer_id, vehicle_id, normalized_plate, zone_id, start_time, end_time, duration_minutes, fee, status) VALUES
('PS-20260101-ABC123', 
 (SELECT id FROM users WHERE email = 'customer1@test.com'), 
 (SELECT id FROM vehicles WHERE normalized_plate = 'WXY1234'), 
 'WXY1234', 
 (SELECT id FROM parking_zones WHERE code = 'GTA'), 
 NOW() - INTERVAL 30 MINUTE, NOW() + INTERVAL 30 MINUTE, 60, 1.50, 'active'),
('PS-20260101-DEF456', 
 (SELECT id FROM users WHERE email = 'customer2@test.com'), 
 (SELECT id FROM vehicles WHERE normalized_plate = 'DEF9012'), 
 'DEF9012', 
 (SELECT id FROM parking_zones WHERE code = 'GTB'), 
 NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 20 MINUTE, 100, 3.00, 'expired');

INSERT INTO compounds (compound_number, violation_type, normalized_plate, zone_id, detection_time, amount, status, issued_by, due_date) VALUES
('CMP-20260101-XYZ789', 'PARKING_NOT_PAID', 'GHI3456', (SELECT id FROM parking_zones WHERE code = 'GTA'), NOW() - INTERVAL 1 HOUR, 10.00, 'issued', (SELECT id FROM users WHERE email = 'officer1@penangparking.gov'), NOW() + INTERVAL 30 DAY),
('CMP-20260101-UVW012', 'PARKING_EXPIRED', 'JKL7890', (SELECT id FROM parking_zones WHERE code = 'GTB'), NOW() - INTERVAL 30 MINUTE, 10.00, 'pending_review', NULL, NOW() + INTERVAL 30 DAY);

INSERT INTO notifications (user_id, type, title, message) VALUES
((SELECT id FROM users WHERE email = 'customer1@test.com'), 'parking_started', 'Parking Started', 'Your parking session in Zone GTA has started. Ends in 30 minutes.'),
((SELECT id FROM users WHERE email = 'customer1@test.com'), 'wallet_low', 'Low Balance Alert', 'Your wallet balance is below RM 5.00. Please reload.'),
((SELECT id FROM users WHERE email = 'customer2@test.com'), 'compound_issued', 'Parking Violation', 'A parking violation has been issued for plate DEF9012. Amount: RM 10.00.');