-- ============================================================================
-- DIGITAL CRAZY HUB - ₹49 AI-Integrated Digital Marketing Paid Workshop
-- Database Schema for MySQL / MariaDB (Hostinger, cPanel, XAMPP compatible)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `enrollments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `enrollment_uuid` VARCHAR(64) NOT NULL UNIQUE,
  `name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `city` VARCHAR(120) NOT NULL,
  `profession` VARCHAR(120) NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL DEFAULT 49.00,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
  `status` ENUM('PENDING', 'PAID', 'FAILED', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
  `razorpay_order_id` VARCHAR(100) DEFAULT NULL,
  `razorpay_payment_id` VARCHAR(100) DEFAULT NULL,
  `razorpay_signature` VARCHAR(255) DEFAULT NULL,
  `verification_token` VARCHAR(64) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `paid_at` DATETIME DEFAULT NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_order_id` (`razorpay_order_id`),
  INDEX `idx_phone` (`phone`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `enrollment_id` INT UNSIGNED DEFAULT NULL,
  `order_id` VARCHAR(100) DEFAULT NULL,
  `event_type` VARCHAR(60) NOT NULL,
  `payload` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

