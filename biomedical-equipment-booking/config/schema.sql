-- Biomedical Engineer Booking & Equipment Maintenance Database Schema (MySQL)
-- Created for BioMedCare Systems

CREATE DATABASE IF NOT EXISTS `biomed_booking_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `biomed_booking_db`;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('hospital', 'engineer', 'admin') NOT NULL DEFAULT 'hospital',
  `approval_status` ENUM('approved', 'pending', 'rejected') NOT NULL DEFAULT 'approved',
  `phone` VARCHAR(30) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: hospitals
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `hospitals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `hospital_name` VARCHAR(150) NOT NULL,
  `facility_type` VARCHAR(80) DEFAULT 'General Hospital',
  `address` TEXT NOT NULL,
  `city` VARCHAR(80) NOT NULL,
  `contact_person` VARCHAR(100) NOT NULL,
  `emergency_contact` VARCHAR(30) NOT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: engineers
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `engineers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `specialization` VARCHAR(100) NOT NULL DEFAULT 'General Medical Equipment',
  `certification` VARCHAR(150) DEFAULT 'Certified Biomedical Equipment Technician (CBET)',
  `years_experience` INT DEFAULT 3,
  `availability_status` ENUM('available', 'on_site', 'busy', 'offline') DEFAULT 'available',
  `city` VARCHAR(80) NOT NULL,
  `rating` DECIMAL(3,2) DEFAULT 4.90,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: equipment
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `equipment` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `hospital_id` INT NOT NULL,
  `equipment_name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `brand_model` VARCHAR(120) NOT NULL,
  `serial_number` VARCHAR(100) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `installation_year` INT DEFAULT 2021,
  `warranty_status` ENUM('under_warranty', 'amc_covered', 'expired', 'out_of_contract') DEFAULT 'under_warranty',
  `status` ENUM('operational', 'faulty', 'maintenance_due', 'decommissioned') DEFAULT 'operational',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`hospital_id`) REFERENCES `hospitals`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: service_tickets
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `service_tickets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_code` VARCHAR(20) NOT NULL UNIQUE,
  `hospital_id` INT NOT NULL,
  `equipment_id` INT NOT NULL,
  `engineer_id` INT DEFAULT NULL,
  `service_type` ENUM('breakdown_repair', 'preventive_maintenance', 'safety_calibration', 'installation') NOT NULL,
  `urgency` ENUM('critical', 'high', 'medium', 'low') NOT NULL DEFAULT 'high',
  `fault_description` TEXT NOT NULL,
  `error_code` VARCHAR(50) DEFAULT NULL,
  `preferred_date` DATE NOT NULL,
  `status` ENUM('pending', 'assigned', 'en_route', 'diagnosing', 'waiting_parts', 'completed', 'cancelled') DEFAULT 'pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `completed_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`hospital_id`) REFERENCES `hospitals`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`equipment_id`) REFERENCES `equipment`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`engineer_id`) REFERENCES `engineers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: ticket_updates (Timeline & Notes)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ticket_updates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `author_name` VARCHAR(100) NOT NULL,
  `author_role` VARCHAR(50) NOT NULL,
  `status_note` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `service_tickets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: service_reports (Digital Job Sheet)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `service_reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL UNIQUE,
  `engineer_id` INT NOT NULL,
  `electrical_safety_status` ENUM('pass', 'fail', 'na') DEFAULT 'pass',
  `ground_resistance_ohms` DECIMAL(5,3) DEFAULT 0.045,
  `leakage_current_ua` DECIMAL(6,2) DEFAULT 12.50,
  `calibration_status` ENUM('calibrated', 'requires_factory_recalibration', 'passed') DEFAULT 'calibrated',
  `work_performed` TEXT NOT NULL,
  `parts_replaced` TEXT DEFAULT NULL,
  `recommendations` TEXT DEFAULT NULL,
  `hospital_signoff_by` VARCHAR(100) DEFAULT NULL,
  `hospital_designation` VARCHAR(100) DEFAULT 'Department Supervisor',
  `authorisation_code` VARCHAR(50) DEFAULT 'AUTH-VERIFIED',
  `signed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `service_tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`engineer_id`) REFERENCES `engineers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
