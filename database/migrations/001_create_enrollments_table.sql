-- Migration: 001_create_enrollments_table.sql
-- Description: Create initial schema for Digital Crazy Hub Workshop System

CREATE TABLE IF NOT EXISTS `enrollments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `enrollment_id` VARCHAR(32) NOT NULL UNIQUE,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `location` VARCHAR(120) NOT NULL,
    `current_profile` VARCHAR(100) NOT NULL,
    `order_id` VARCHAR(64) NULL,
    `payment_id` VARCHAR(64) NULL,
    `workshop_date` VARCHAR(60) NOT NULL DEFAULT 'November 5, 2026',
    `workshop_time` VARCHAR(60) NOT NULL DEFAULT '7:00 PM IST',
    `amount_paid` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `payment_status` ENUM('NOT PAID', 'PAID') NOT NULL DEFAULT 'NOT PAID',
    `verification_token` VARCHAR(64) NOT NULL,
    `google_sheets_synced` TINYINT(1) NOT NULL DEFAULT 0,
    `google_sheets_row` INT UNSIGNED NULL,
    `paid_at` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_enrollment_id` (`enrollment_id`),
    INDEX `idx_email` (`email`),
    INDEX `idx_phone` (`phone`),
    INDEX `idx_payment_status` (`payment_status`),
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_payment_id` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `enrollment_id` VARCHAR(32) NULL,
    `order_id` VARCHAR(64) NULL,
    `payment_id` VARCHAR(64) NULL,
    `event_type` VARCHAR(64) NOT NULL,
    `payload` LONGTEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_log_enrollment` (`enrollment_id`),
    INDEX `idx_log_order` (`order_id`),
    INDEX `idx_log_event` (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sync_queue` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `enrollment_id` VARCHAR(32) NOT NULL,
    `action` ENUM('INSERT', 'UPDATE') NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `status` ENUM('PENDING', 'SUCCESS', 'FAILED') NOT NULL DEFAULT 'PENDING',
    `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `last_error` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_sync_status` (`status`),
    INDEX `idx_sync_enrollment` (`enrollment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

