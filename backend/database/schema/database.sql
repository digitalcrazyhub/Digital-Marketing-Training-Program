-- ============================================================================
-- DIGITAL CRAZY HUB - ₹49 AI-Integrated Digital Marketing Paid Workshop
-- Database Schema for MySQL / MariaDB (Hostinger, cPanel, XAMPP compatible)
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Main Enrollments Table
CREATE TABLE IF NOT EXISTS `enrollments` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `enrollment_id` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `location` VARCHAR(100) NOT NULL,
  `current_profile` VARCHAR(100) NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL DEFAULT 49.00,
  `amount_paid` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `payment_id` VARCHAR(100) DEFAULT NULL,
  `order_id` VARCHAR(100) DEFAULT NULL,
  `amount_paid_status` ENUM('UNPAID', 'PAID') NOT NULL DEFAULT 'UNPAID',
  `payment_status` ENUM('UNPAID', 'PAID', 'NOT PAID') NOT NULL DEFAULT 'UNPAID',
  `workshop_date` VARCHAR(50) NOT NULL DEFAULT 'November 24, 2026',
  `workshop_time` VARCHAR(50) NOT NULL DEFAULT '7:00 PM IST',
  `invoice_number` VARCHAR(100) DEFAULT NULL,
  `invoice_url` VARCHAR(255) DEFAULT NULL,
  
  -- Technical Payment & Sync Metadata
  `signature` VARCHAR(255) DEFAULT NULL,
  `verification_token` VARCHAR(64) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `google_sheets_synced` TINYINT(1) NOT NULL DEFAULT 0,
  `google_sheets_row` INT UNSIGNED DEFAULT NULL,
  
  -- Timestamps
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `paid_at` DATETIME DEFAULT NULL,
  
  INDEX `idx_enrollment_id` (`enrollment_id`),
  INDEX `idx_amount_paid_status` (`amount_paid_status`),
  INDEX `idx_payment_status` (`payment_status`),
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_payment_id` (`payment_id`),
  INDEX `idx_phone` (`phone`),
  INDEX `idx_email` (`email`),
  INDEX `idx_google_sheets_synced` (`google_sheets_synced`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Payment & Audit Event Logs
CREATE TABLE IF NOT EXISTS `payment_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `enrollment_id` VARCHAR(50) DEFAULT NULL,
  `order_id` VARCHAR(100) DEFAULT NULL,
  `payment_id` VARCHAR(100) DEFAULT NULL,
  `event_type` VARCHAR(80) NOT NULL,
  `payload` LONGTEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_event_order` (`order_id`),
  INDEX `idx_event_enrollment` (`enrollment_id`),
  INDEX `idx_event_type` (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Google Sheets Synchronization Retry Queue
CREATE TABLE IF NOT EXISTS `sync_queue` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `enrollment_id` VARCHAR(50) NOT NULL,
  `action` ENUM('INSERT', 'UPDATE') NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `status` ENUM('PENDING', 'SUCCESS', 'FAILED') NOT NULL DEFAULT 'PENDING',
  `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_error` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_sync_status` (`status`),
  INDEX `idx_sync_enrollment` (`enrollment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Safe Upgrade Migrations for Existing Live Tables
-- ALTER TABLE `enrollments` ADD COLUMN IF NOT EXISTS `amount` DECIMAL(10,2) NOT NULL DEFAULT 49.00 AFTER `current_profile`;
-- ALTER TABLE `enrollments` ADD COLUMN IF NOT EXISTS `amount_paid_status` ENUM('UNPAID', 'PAID') NOT NULL DEFAULT 'UNPAID' AFTER `order_id`;
-- ALTER TABLE `enrollments` ADD COLUMN IF NOT EXISTS `invoice_number` VARCHAR(100) DEFAULT NULL AFTER `workshop_time`;
-- ALTER TABLE `enrollments` ADD COLUMN IF NOT EXISTS `invoice_url` VARCHAR(255) DEFAULT NULL AFTER `invoice_number`;
-- ALTER TABLE `enrollments` ADD COLUMN IF NOT EXISTS `ip_address` VARCHAR(45) DEFAULT NULL AFTER `verification_token`;

SET FOREIGN_KEY_CHECKS = 1;
