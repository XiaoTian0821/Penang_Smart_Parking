<?php
/**
 * Database Setup Script - Creates database and seeds data
 */
$password = '123456';

try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);
    echo "Connected to MySQL.\n";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS penang_parking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE penang_parking");
    echo "Database ready.\n";
    
    // Generate password hash
    $hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
    echo "Password hash generated.\n";
    
    // Create tables
    $tables = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            ic_number VARCHAR(20),
            role ENUM('super_admin','admin','officer','customer') NOT NULL DEFAULT 'customer',
            status ENUM('active','suspended','deactivated') NOT NULL DEFAULT 'active',
            permissions JSON,
            last_login_at DATETIME,
            last_login_ip VARCHAR(45),
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_role (role),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS vehicles (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            owner_id INT UNSIGNED NOT NULL,
            plate VARCHAR(50) NOT NULL,
            normalized_plate VARCHAR(50) NOT NULL,
            vehicle_type ENUM('car','motorcycle','lorry','bus','van') NOT NULL DEFAULT 'car',
            color VARCHAR(50),
            make VARCHAR(100),
            model VARCHAR(100),
            year INT,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_normalized_plate (normalized_plate),
            INDEX idx_owner (owner_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS wallets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id INT UNSIGNED NOT NULL UNIQUE,
            balance DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_customer (customer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS wallet_transactions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            wallet_id INT UNSIGNED NOT NULL,
            customer_id INT UNSIGNED NOT NULL,
            transaction_type ENUM('RELOAD','PARKING_PAYMENT','COMPOUND_PAYMENT','REFUND','ADMIN_CREDIT','ADMIN_DEBIT','ADJUSTMENT') NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            balance_before DECIMAL(10, 2) NOT NULL,
            balance_after DECIMAL(10, 2) NOT NULL,
            reference_id VARCHAR(100),
            status ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (wallet_id) REFERENCES wallets(id),
            FOREIGN KEY (customer_id) REFERENCES users(id),
            INDEX idx_customer (customer_id),
            INDEX idx_reference (reference_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS parking_zones (
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
            status ENUM('active','inactive','maintenance') NOT NULL DEFAULT 'active',
            qr_token VARCHAR(64) UNIQUE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_code (code),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS parking_sessions (
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
            status ENUM('active','expired','completed','cancelled') NOT NULL DEFAULT 'active',
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS cameras (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            code VARCHAR(50) NOT NULL UNIQUE,
            camera_type ENUM('upload','webcam','ip','anpr','other') NOT NULL DEFAULT 'upload',
            zone_id INT UNSIGNED,
            latitude DECIMAL(10, 7),
            longitude DECIMAL(10, 7),
            status ENUM('active','inactive','maintenance') NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (zone_id) REFERENCES parking_zones(id) ON DELETE SET NULL,
            INDEX idx_code (code),
            INDEX idx_zone (zone_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS ai_detections (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            plate VARCHAR(50),
            confidence DECIMAL(5, 4),
            model_used VARCHAR(50),
            processing_time DECIMAL(8, 4),
            image_path VARCHAR(500),
            status ENUM('processed','review','violation','error') NOT NULL DEFAULT 'processed',
            enforcement_result VARCHAR(100),
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_plate (plate),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS compounds (
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
            status ENUM('pending_review','issued','paid','overdue','appealed','cancelled') NOT NULL DEFAULT 'pending_review',
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS appeals (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            compound_id INT UNSIGNED NOT NULL,
            customer_id INT UNSIGNED NOT NULL,
            reason TEXT NOT NULL,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS notifications (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS audit_logs (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
    
    foreach ($tables as $table) {
        try {
            $pdo->exec($table);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    echo "All tables created.\n";
    
    // Seed data
    // Clear existing data
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    $pdo->exec("TRUNCATE TABLE audit_logs");
    $pdo->exec("TRUNCATE TABLE notifications");
    $pdo->exec("TRUNCATE TABLE appeals");
    $pdo->exec("TRUNCATE TABLE compounds");
    $pdo->exec("TRUNCATE TABLE ai_detections");
    $pdo->exec("TRUNCATE TABLE cameras");
    $pdo->exec("TRUNCATE TABLE parking_sessions");
    $pdo->exec("TRUNCATE TABLE wallet_transactions");
    $pdo->exec("TRUNCATE TABLE wallets");
    $pdo->exec("TRUNCATE TABLE vehicles");
    $pdo->exec("TRUNCATE TABLE users");
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    echo "Tables cleared.\n";
    
    // Insert users
    $pdo->exec("INSERT INTO users (email, password, full_name, role, status) VALUES
        ('superadmin@penangparking.gov', '$hash', 'System Administrator', 'super_admin', 'active'),
        ('admin@penangparking.gov', '$hash', 'Admin User', 'admin', 'active'),
        ('officer1@penangparking.gov', '$hash', 'Enforcement Officer 1', 'officer', 'active'),
        ('officer2@penangparking.gov', '$hash', 'Enforcement Officer 2', 'officer', 'active'),
        ('customer1@test.com', '$hash', 'Ahmad bin Ali', 'customer', 'active'),
        ('customer2@test.com', '$hash', 'Tan Mei Ling', 'customer', 'active')
    ");
    echo "Users seeded.\n";
    
    // Insert wallets
    $pdo->exec("INSERT INTO wallets (customer_id, balance) VALUES (5, 100.00), (6, 50.00)");
    echo "Wallets seeded.\n";
    
    // Insert zones (ignore if exists)
    $pdo->exec("INSERT IGNORE INTO parking_zones (name, code, address, latitude, longitude, hourly_rate, max_duration, capacity, available_spaces, status, operating_hours, enforce_outside_hours) VALUES
        ('George Town Zone A', 'GTA', 'Victoria Street, George Town', 5.4141, 100.3288, 1.50, 120, 50, 48, 'active', '{\"monday\":{\"start\":\"07:00\",\"end\":\"22:00\"},\"tuesday\":{\"start\":\"07:00\",\"end\":\"22:00\"},\"wednesday\":{\"start\":\"07:00\",\"end\":\"22:00\"},\"thursday\":{\"start\":\"07:00\",\"end\":\"22:00\"},\"friday\":{\"start\":\"07:00\",\"end\":\"22:00\"},\"saturday\":{\"start\":\"08:00\",\"end\":\"20:00\"},\"sunday\":{\"start\":\"08:00\",\"end\":\"20:00\"}}', 1),
        ('George Town Zone B', 'GTB', 'Chulia Street, George Town', 5.4164, 100.3327, 2.00, 120, 30, 28, 'active', '{\"monday\":{\"start\":\"07:00\",\"end\":\"22:00\"},\"tuesday\":{\"start\":\"07:00\",\"end\":\"22:00\"},\"wednesday\":{\"start\":\"07:00\",\"end\":\"22:00\"},\"thursday\":{\"start\":\"07:00\",\"end\":\"22:00\"},\"friday\":{\"start\":\"07:00\",\"end\":\"22:00\"},\"saturday\":{\"start\":\"08:00\",\"end\":\"20:00\"},\"sunday\":{\"start\":\"08:00\",\"end\":\"20:00\"}}', 1),
        ('Batu Ferringhi Zone', 'BFZ', 'Batu Ferringhi Road', 5.4617, 100.2917, 1.00, 240, 80, 75, 'active', '{\"monday\":{\"start\":\"06:00\",\"end\":\"23:00\"},\"tuesday\":{\"start\":\"06:00\",\"end\":\"23:00\"},\"wednesday\":{\"start\":\"06:00\",\"end\":\"23:00\"},\"thursday\":{\"start\":\"06:00\",\"end\":\"23:00\"},\"friday\":{\"start\":\"06:00\",\"end\":\"23:00\"},\"saturday\":{\"start\":\"06:00\",\"end\":\"23:00\"},\"sunday\":{\"start\":\"06:00\",\"end\":\"23:00\"}}', 0),
        ('Bayan Lepas Zone', 'BLZ', 'Main Road, Bayan Lepas', 5.3050, 100.2450, 1.00, 180, 60, 55, 'active', '{\"monday\":{\"start\":\"07:00\",\"end\":\"21:00\"},\"tuesday\":{\"start\":\"07:00\",\"end\":\"21:00\"},\"wednesday\":{\"start\":\"07:00\",\"end\":\"21:00\"},\"thursday\":{\"start\":\"07:00\",\"end\":\"21:00\"},\"friday\":{\"start\":\"07:00\",\"end\":\"21:00\"},\"saturday\":{\"start\":\"08:00\",\"end\":\"20:00\"},\"sunday\":{\"start\":\"08:00\",\"end\":\"20:00\"}}', 1),
        ('Penang Bridge Zone', 'PBZ', 'Penang Bridge Toll', 5.2739, 100.1997, 2.00, 60, 20, 18, 'active', '{\"monday\":{\"start\":\"00:00\",\"end\":\"23:59\"},\"tuesday\":{\"start\":\"00:00\",\"end\":\"23:59\"},\"wednesday\":{\"start\":\"00:00\",\"end\":\"23:59\"},\"thursday\":{\"start\":\"00:00\",\"end\":\"23:59\"},\"friday\":{\"start\":\"00:00\",\"end\":\"23:59\"},\"saturday\":{\"start\":\"00:00\",\"end\":\"23:59\"},\"sunday\":{\"start\":\"00:00\",\"end\":\"23:59\"}}', 0)
    ");
    echo "Zones seeded.\n";
    
    // Insert vehicles (ignore if exists)
    $pdo->exec("INSERT IGNORE INTO vehicles (owner_id, plate, normalized_plate, vehicle_type, color, make, model, year) VALUES
        (5, 'WXY 1234', 'WXY1234', 'car', 'white', 'Toyota', 'Camry', 2022),
        (5, 'ABC 5678', 'ABC5678', 'motorcycle', 'red', 'Honda', 'PCX', 2023),
        (6, 'DEF 9012', 'DEF9012', 'car', 'black', 'BMW', '320i', 2021)
    ");
    echo "Vehicles seeded.\n";
    
    // Insert sample sessions (ignore if exists)
    $pdo->exec("INSERT IGNORE INTO parking_sessions (session_number, customer_id, vehicle_id, normalized_plate, zone_id, start_time, end_time, duration_minutes, fee, status) VALUES
        ('PS-20260101-ABC123', 5, 1, 'WXY1234', 1, NOW() - INTERVAL 30 MINUTE, NOW() + INTERVAL 30 MINUTE, 60, 1.50, 'active'),
        ('PS-20260101-DEF456', 6, 3, 'DEF9012', 2, NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 20 MINUTE, 100, 3.00, 'expired')
    ");
    echo "Sessions seeded.\n";
    
    // Insert sample compounds (ignore if exists)
    $pdo->exec("INSERT IGNORE INTO compounds (compound_number, violation_type, normalized_plate, zone_id, detection_time, amount, status, issued_by, due_date) VALUES
        ('CMP-20260101-XYZ789', 'PARKING_NOT_PAID', 'GHI3456', 1, NOW() - INTERVAL 1 HOUR, 10.00, 'issued', 3, NOW() + INTERVAL 30 DAY),
        ('CMP-20260101-UVW012', 'PARKING_EXPIRED', 'JKL7890', 2, NOW() - INTERVAL 30 MINUTE, 10.00, 'pending_review', NULL, NOW() + INTERVAL 30 DAY)
    ");
    echo "Compounds seeded.\n";
    
    // Verify
    echo "\n=== Verification ===\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    echo "Users: " . $stmt->fetchColumn() . "\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM vehicles");
    echo "Vehicles: " . $stmt->fetchColumn() . "\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM parking_zones");
    echo "Zones: " . $stmt->fetchColumn() . "\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM parking_sessions");
    echo "Sessions: " . $stmt->fetchColumn() . "\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM compounds");
    echo "Compounds: " . $stmt->fetchColumn() . "\n";
    
    // Verify password
    $stmt = $pdo->query("SELECT password FROM users WHERE email='admin@penangparking.gov'");
    $storedHash = $stmt->fetchColumn();
    echo "\nPassword verify: " . (password_verify('admin123', $storedHash) ? 'YES' : 'NO') . "\n";
    
    echo "\n✅ Database setup complete!\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}
