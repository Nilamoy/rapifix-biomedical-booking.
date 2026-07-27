<?php
/**
 * Database Connection Handler
 * RapiFix - Biomedical Engineer Booking System
 * 
 * Supports native MySQL PDO with automatic SQLite fallback for instant zero-config testing.
 */

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'biomed_booking_db');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dbEngine = 'mysql';

    try {
        // Try MySQL Connection first
        $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        // Ensure Database Exists in MySQL
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `" . DB_NAME . "`");
        
    } catch (PDOException $e) {
        // Fallback to SQLite if MySQL server is not running on host
        $dbEngine = 'sqlite';
        $sqliteFile = __DIR__ . '/../database.sqlite';
        $pdo = new PDO("sqlite:" . $sqliteFile, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        // Enable foreign keys in SQLite
        $pdo->exec("PRAGMA foreign_keys = ON;");
    }

    // Ensure Tables Exist
    ensureTablesExist($pdo, $dbEngine);

    return $pdo;
}

function ensureTablesExist($pdo, $engine = 'mysql') {
    // Check if main table exists
    try {
        if ($engine === 'mysql') {
            $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
            $exists = $stmt->fetch();
        } else {
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
            $exists = $stmt->fetch();
        }

        if (!$exists) {
            createSchemaAndSeed($pdo, $engine);
        } else {
            // Auto-migrate columns if missing in pre-existing database
            try {
                if ($engine === 'mysql') {
                    $pdo->exec("ALTER TABLE `users` ADD COLUMN `approval_status` ENUM('approved', 'pending', 'rejected') NOT NULL DEFAULT 'approved'");
                } else {
                    $pdo->exec("ALTER TABLE users ADD COLUMN approval_status TEXT DEFAULT 'approved'");
                }
            } catch (Exception $e) {}

            try {
                if ($engine === 'mysql') {
                    $pdo->exec("ALTER TABLE `service_reports` ADD COLUMN `hospital_designation` VARCHAR(100) DEFAULT 'Department Head'");
                    $pdo->exec("ALTER TABLE `service_reports` ADD COLUMN `authorisation_code` VARCHAR(50) DEFAULT 'AUTH-VERIFIED'");
                } else {
                    $pdo->exec("ALTER TABLE service_reports ADD COLUMN hospital_designation TEXT DEFAULT 'Department Head'");
                    $pdo->exec("ALTER TABLE service_reports ADD COLUMN authorisation_code TEXT DEFAULT 'AUTH-VERIFIED'");
                }
            } catch (Exception $e) {}
        }
    } catch (Exception $ex) {
        createSchemaAndSeed($pdo, $engine);
    }
}

function createSchemaAndSeed($pdo, $engine) {
    if ($engine === 'sqlite') {
        $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          full_name TEXT NOT NULL,
          email TEXT NOT NULL UNIQUE,
          password TEXT NOT NULL,
          role TEXT NOT NULL DEFAULT 'hospital',
          approval_status TEXT DEFAULT 'approved',
          phone TEXT,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS hospitals (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          user_id INTEGER NOT NULL,
          hospital_name TEXT NOT NULL,
          facility_type TEXT DEFAULT 'General Hospital',
          address TEXT NOT NULL,
          city TEXT NOT NULL,
          contact_person TEXT NOT NULL,
          emergency_contact TEXT NOT NULL,
          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS engineers (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          user_id INTEGER NOT NULL,
          specialization TEXT NOT NULL DEFAULT 'General Medical Equipment',
          certification TEXT DEFAULT 'Certified Biomedical Equipment Technician (CBET)',
          years_experience INTEGER DEFAULT 3,
          availability_status TEXT DEFAULT 'available',
          city TEXT NOT NULL,
          rating REAL DEFAULT 4.90,
          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS equipment (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          hospital_id INTEGER NOT NULL,
          equipment_name TEXT NOT NULL,
          category TEXT NOT NULL,
          brand_model TEXT NOT NULL,
          serial_number TEXT NOT NULL,
          department TEXT NOT NULL,
          installation_year INTEGER DEFAULT 2021,
          warranty_status TEXT DEFAULT 'under_warranty',
          status TEXT DEFAULT 'operational',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS service_tickets (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          ticket_code TEXT NOT NULL UNIQUE,
          hospital_id INTEGER NOT NULL,
          equipment_id INTEGER NOT NULL,
          engineer_id INTEGER DEFAULT NULL,
          service_type TEXT NOT NULL,
          urgency TEXT NOT NULL DEFAULT 'high',
          fault_description TEXT NOT NULL,
          error_code TEXT,
          preferred_date DATE NOT NULL,
          status TEXT DEFAULT 'pending',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          completed_at DATETIME,
          FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
          FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
          FOREIGN KEY (engineer_id) REFERENCES engineers(id) ON DELETE SET NULL
        );

        CREATE TABLE IF NOT EXISTS ticket_updates (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          ticket_id INTEGER NOT NULL,
          author_name TEXT NOT NULL,
          author_role TEXT NOT NULL,
          status_note TEXT NOT NULL,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (ticket_id) REFERENCES service_tickets(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS service_reports (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          ticket_id INTEGER NOT NULL UNIQUE,
          engineer_id INTEGER NOT NULL,
          electrical_safety_status TEXT DEFAULT 'pass',
          ground_resistance_ohms REAL DEFAULT 0.045,
          leakage_current_ua REAL DEFAULT 12.50,
          calibration_status TEXT DEFAULT 'calibrated',
          work_performed TEXT NOT NULL,
          parts_replaced TEXT,
          recommendations TEXT,
          hospital_signoff_by TEXT,
          hospital_designation TEXT DEFAULT 'Department Head',
          authorisation_code TEXT DEFAULT 'AUTH-VERIFIED',
          signed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (ticket_id) REFERENCES service_tickets(id) ON DELETE CASCADE,
          FOREIGN KEY (engineer_id) REFERENCES engineers(id) ON DELETE CASCADE
        );
        ");
    } else {
        $sql = file_get_contents(__DIR__ . '/schema.sql');
        $pdo->exec($sql);
    }

    seedInitialData($pdo);
}

function seedInitialData($pdo) {
    // Check if users exist already
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM users");
    $res = $stmt->fetch();
    if ($res['cnt'] > 0) return;

    $hashedPassword = password_hash('password123', PASSWORD_BCRYPT);

    // 1. Insert Admin
    $pdo->prepare("INSERT INTO users (full_name, email, password, role, phone) VALUES (?, ?, ?, 'admin', ?)")
        ->execute(['System Admin', 'admin@rapifix.com', $hashedPassword, '+91 7908892778']);
    $pdo->prepare("INSERT INTO users (full_name, email, password, role, phone) VALUES (?, ?, ?, 'admin', ?)")
        ->execute(['System Admin Backup', 'admin@biomedcare.com', $hashedPassword, '+91 7908892778']);

    // 2. Insert Hospitals (Users + Hospital profiles)
    $pdo->prepare("INSERT INTO users (full_name, email, password, role, phone) VALUES (?, ?, ?, 'hospital', ?)")
        ->execute(['Dr. Aris Thorne (Metropolitan General)', 'metro@hospital.org', $hashedPassword, '+91 7908892778']);
    $hospUser1 = $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO hospitals (user_id, hospital_name, facility_type, address, city, contact_person, emergency_contact) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([$hospUser1, 'Metropolitan General Hospital', 'Tertiary Care Specialist Center', '742 Evergreen Terrace, West Wing', 'New York', 'Dr. Aris Thorne', '+91 7908892778']);
    $hospId1 = $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO users (full_name, email, password, role, phone) VALUES (?, ?, ?, 'hospital', ?)")
        ->execute(['Sarah Jenkins (St. Jude Heart Institute)', 'stjude@cardiohealth.org', $hashedPassword, '+1-555-0456']);
    $hospUser2 = $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO hospitals (user_id, hospital_name, facility_type, address, city, contact_person, emergency_contact) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([$hospUser2, 'St. Jude Heart & Cardiac Institute', 'Super-Specialty Cardiac Center', '108 Ocean Boulevard, Floor 4', 'Chicago', 'Sarah Jenkins, RN', '+1-555-0488']);
    $hospId2 = $pdo->lastInsertId();

    // 3. Insert Engineers (Users + Engineer profiles)
    $pdo->prepare("INSERT INTO users (full_name, email, password, role, phone) VALUES (?, ?, ?, 'engineer', ?)")
        ->execute(['Eng. Marcus Vance', 'marcus.vance@bme-pros.com', $hashedPassword, '+1-555-9001']);
    $engUser1 = $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO engineers (user_id, specialization, certification, years_experience, availability_status, city, rating) VALUES (?, ?, ?, ?, 'available', ?, 4.95)")
        ->execute([$engUser1, 'ICU Ventilators & Life Support Systems', 'Certified Biomedical Equipment Technician (CBET), Draeger Specialist', 8, 'New York']);
    $engId1 = $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO users (full_name, email, password, role, phone) VALUES (?, ?, ?, 'engineer', ?)")
        ->execute(['Eng. Elena Rostova', 'elena.rostova@medtech-services.com', $hashedPassword, '+1-555-9002']);
    $engUser2 = $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO engineers (user_id, specialization, certification, years_experience, availability_status, city, rating) VALUES (?, ?, ?, ?, 'on_site', ?, 4.98)")
        ->execute([$engUser2, 'Diagnostic Imaging (MRI, CT, X-Ray)', 'Siemens & GE Healthcare Senior Field Engineer', 12, 'New York']);
    $engId2 = $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO users (full_name, email, password, role, phone) VALUES (?, ?, ?, 'engineer', ?)")
        ->execute(['Eng. David Chen', 'david.chen@biomed-ops.org', $hashedPassword, '+1-555-9003']);
    $engUser3 = $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO engineers (user_id, specialization, certification, years_experience, availability_status, city, rating) VALUES (?, ?, ?, ?, 'available', ?, 4.88)")
        ->execute([$engUser3, 'Surgical Lasers & Anesthesia Workstations', 'AAMI Certified Healthcare Technology Manager (CHTM)', 6, 'Chicago']);
    $engId3 = $pdo->lastInsertId();

    // 4. Insert Equipment
    $equipStmt = $pdo->prepare("INSERT INTO equipment (hospital_id, equipment_name, category, brand_model, serial_number, department, installation_year, warranty_status, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $equipStmt->execute([$hospId1, 'ICU Mechanical Ventilator', 'Life Support', 'Evita V800 Draeger', 'SN-DRG-88401', 'Intensive Care Unit (ICU)', 2022, 'under_warranty', 'faulty']);
    $eq1 = $pdo->lastInsertId();

    $equipStmt->execute([$hospId1, '3.0T High-Field MRI Scanner', 'Diagnostic Imaging', 'Siemens MAGNETOM Vida', 'SN-SIE-30911', 'Radiology & Imaging', 2020, 'amc_covered', 'operational']);
    $eq2 = $pdo->lastInsertId();

    $equipStmt->execute([$hospId1, 'Anesthesia Delivery Station', 'Operating Theatre', 'Mindray A9 Anesthesia', 'SN-MND-44109', 'OT 3 - Surgical Suite', 2023, 'under_warranty', 'maintenance_due']);
    $eq3 = $pdo->lastInsertId();

    $equipStmt->execute([$hospId2, 'Digital Cardiac Catheterization Lab', 'Cardiology', 'Philips Azurion 7', 'SN-PHL-77210', 'Cath Lab 1', 2021, 'amc_covered', 'operational']);
    $eq4 = $pdo->lastInsertId();

    $equipStmt->execute([$hospId2, 'Biphasic Defibrillator Monitor', 'Emergency Care', 'Zoll R Series ALS', 'SN-ZOL-99204', 'Emergency Department (ED)', 2023, 'under_warranty', 'faulty']);
    $eq5 = $pdo->lastInsertId();

    // 5. Insert Service Tickets
    $t1Code = 'TICK-' . rand(10000, 99999);
    $pdo->prepare("INSERT INTO service_tickets (ticket_code, hospital_id, equipment_id, engineer_id, service_type, urgency, fault_description, error_code, preferred_date, status) VALUES (?, ?, ?, ?, 'breakdown_repair', 'critical', 'Ventilator pressure sensor alarm triggering continuously during high-PEAP cycles. Screen showing Err-304.', 'ERR-304-PRESS', ?, 'en_route')")
        ->execute([$t1Code, $hospId1, $eq1, $engId1, date('Y-m-d')]);
    $t1Id = $pdo->lastInsertId();

    $t2Code = 'TICK-' . rand(10000, 99999);
    $pdo->prepare("INSERT INTO service_tickets (ticket_code, hospital_id, equipment_id, engineer_id, service_type, urgency, fault_description, error_code, preferred_date, status) VALUES (?, ?, ?, ?, 'safety_calibration', 'high', 'Defibrillator joule energy discharge test yielding 8% deviation below target specs.', 'CAL-DEV-08', ?, 'pending')")
        ->execute([$t2Code, $hospId2, $eq5, null, date('Y-m-d', strtotime('+1 day'))]);

    // 6. Insert Ticket Updates / Timeline
    $pdo->prepare("INSERT INTO ticket_updates (ticket_id, author_name, author_role, status_note) VALUES (?, ?, ?, ?)")
        ->execute([$t1Id, 'Dr. Aris Thorne', 'Hospital Admin', 'Ticket submitted. Emergency breakdown in ICU Bed 4. Critical urgency requested.']);

    $pdo->prepare("INSERT INTO ticket_updates (ticket_id, author_name, author_role, status_note) VALUES (?, ?, ?, ?)")
        ->execute([$t1Id, 'System Dispatcher', 'Admin', 'Ticket assigned to Eng. Marcus Vance (Specialist in ICU Ventilators). Estimated arrival: 45 mins.']);

    $pdo->prepare("INSERT INTO ticket_updates (ticket_id, author_name, author_role, status_note) VALUES (?, ?, ?, ?)")
        ->execute([$t1Id, 'Eng. Marcus Vance', 'Biomedical Engineer', 'En route to Metropolitan General Hospital with calibrated pressure transducers and replacement O2 cell kit.']);
}
