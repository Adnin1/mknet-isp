-- MK Net ISP Master Comprehensive Database Schema
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS `mknet` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mknet`;

-- 1. Users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password` VARCHAR(255) NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `full_name` VARCHAR(100) NULL,
  `phone` VARCHAR(30) NULL,
  `address` VARCHAR(255) NULL,
  `account_status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `package_id` INT UNSIGNED NULL,
  `package_speed_mbps` INT UNSIGNED NULL DEFAULT 20,
  `package_expires_at` DATETIME NULL,
  `email_verified_at` DATETIME NULL,
  `phone_verified_at` DATETIME NULL,
  `referral_code` VARCHAR(30) NULL UNIQUE,
  `referred_by_user_id` INT NULL,
  `loyalty_points` INT NOT NULL DEFAULT 0,
  `is_regular` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Admins
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NULL,
  `email` VARCHAR(120) NULL,
  `role` VARCHAR(30) NOT NULL DEFAULT 'admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Admin Invitations
CREATE TABLE IF NOT EXISTS `admin_invitations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(120) NOT NULL,
  `role` VARCHAR(30) NOT NULL DEFAULT 'admin',
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `status` ENUM('pending','accepted','expired') NOT NULL DEFAULT 'pending',
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Packages
CREATE TABLE IF NOT EXISTS `packages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(100) NULL,
  `speed_mbps` INT UNSIGNED NOT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `price_bdt` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `data_cap` DECIMAL(10,2) NULL,
  `description` VARCHAR(255) NULL,
  `category` ENUM('residential','business','enterprise') NOT NULL DEFAULT 'residential',
  `badge` VARCHAR(30) NOT NULL DEFAULT 'none',
  `image` VARCHAR(255) NULL,
  `image_slug` VARCHAR(50) DEFAULT NULL,
  `region` VARCHAR(100) DEFAULT 'All Bangladesh',
  `billing_cycle` VARCHAR(50) DEFAULT 'Monthly',
  `trial_days` INT NULL,
  `visible` TINYINT(1) NOT NULL DEFAULT 1,
  `featured` TINYINT(1) NOT NULL DEFAULT 0,
  `order_index` INT DEFAULT 0,
  `features` LONGTEXT NULL,
  `promo_price` DECIMAL(10,2) NULL,
  `promo_start` DATETIME NULL,
  `promo_end` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Invoices
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `package_id` INT NULL,
  `bundle_id` INT NULL,
  `duration_days` INT NULL DEFAULT 30,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'BDT',
  `status` ENUM('pending','paid','cancelled','failed') NOT NULL DEFAULT 'pending',
  `meta` LONGTEXT NULL,
  `due_date` DATE NULL,
  `paid_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Payment Transactions
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` INT NOT NULL,
  `user_id` INT NULL,
  `gateway` VARCHAR(50) NOT NULL,
  `trx_id` VARCHAR(100) NULL,
  `sender_number` VARCHAR(30) NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(10) DEFAULT 'BDT',
  `status` VARCHAR(30) NOT NULL DEFAULT 'initiated',
  `gateway_txn_id` VARCHAR(100) NULL,
  `raw_response` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Contact Submissions
CREATE TABLE IF NOT EXISTS `contact_submissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `phone` VARCHAR(30) NULL,
  `subject` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('unread','read','replied') NOT NULL DEFAULT 'unread',
  `admin_notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Addons & User Addons
CREATE TABLE IF NOT EXISTS `addons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `description` VARCHAR(255) NULL,
  `icon` VARCHAR(50) DEFAULT 'box',
  `type` ENUM('recurring','one_time') NOT NULL DEFAULT 'recurring',
  `taxable` TINYINT(1) NOT NULL DEFAULT 0,
  `visible` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_addons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `addon_id` INT NOT NULL,
  `status` ENUM('active','cancelled') NOT NULL DEFAULT 'active',
  `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Admin Banners & Ads
CREATE TABLE IF NOT EXISTS `admin_banners` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `message` VARCHAR(255) NOT NULL,
  `link_url` VARCHAR(255) NULL,
  `type` VARCHAR(50) NOT NULL DEFAULT 'top_banner',
  `banner_type` VARCHAR(50) NOT NULL DEFAULT 'top_banner',
  `image_path` VARCHAR(255) NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `starts_at` DATETIME NULL,
  `ends_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `image` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Announcements & News
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `news` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `image` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `news_events` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(120) NULL,
  `title` VARCHAR(160) NOT NULL,
  `content` TEXT NOT NULL,
  `event_date` DATE NULL,
  `category` VARCHAR(50) DEFAULT 'General',
  `image` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Coverage Areas & Coverage
CREATE TABLE IF NOT EXISTS `coverage_areas` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `district` VARCHAR(80) NOT NULL,
  `upazila`  VARCHAR(80) NOT NULL,
  `status`   ENUM('Covered','Planned','Unavailable') NOT NULL DEFAULT 'Covered',
  `customers` INT NULL,
  `upcoming_targets` TEXT NULL,
  `sectors` INT NULL,
  `homes` INT NULL,
  `offices` INT NULL,
  `present_status` TEXT NULL,
  `updates` TEXT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `coverage` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `region` VARCHAR(100) NOT NULL,
  `postal_code` VARCHAR(30) NULL,
  `area_name` VARCHAR(100) NOT NULL,
  `lat` DECIMAL(10,8) NULL,
  `lng` DECIMAL(11,8) NULL,
  `packages` LONGTEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Reviews
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `package_id` INT NULL,
  `user_name` VARCHAR(100) NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `comment` TEXT NOT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Tickets & Messages
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `department` VARCHAR(50) NOT NULL DEFAULT 'general',
  `priority` VARCHAR(20) NOT NULL DEFAULT 'medium',
  `status` VARCHAR(30) NOT NULL DEFAULT 'open',
  `assigned_admin_id` INT NULL,
  `sla_due_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. Chatbot Rules & Messages
CREATE TABLE IF NOT EXISTS `chatbot_rules` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `keyword` VARCHAR(100) NULL,
  `trigger_keywords` VARCHAR(255) NULL,
  `reply` TEXT NULL,
  `bot_response` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `chatbot_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `user_message` TEXT NOT NULL,
  `bot_reply` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. Settings & Content
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `site_name` VARCHAR(100) NOT NULL DEFAULT 'MK Net ISP',
  `sitename` VARCHAR(100) DEFAULT 'MK Net ISP',
  `hotline` VARCHAR(50) DEFAULT '16335 / 09678-123123',
  `email` VARCHAR(120) DEFAULT 'support@mknet.com',
  `address` VARCHAR(255) DEFAULT 'Police Plaza Concord, Gulshan 1, Dhaka',
  `bkash_number` VARCHAR(30) DEFAULT '01700000000',
  `nagad_number` VARCHAR(30) DEFAULT '01700000000',
  `active_theme` VARCHAR(50) DEFAULT 'cyber_dark',
  `theme` VARCHAR(50) DEFAULT 'cyber_dark',
  `custom_css` TEXT NULL,
  `logo_path` VARCHAR(255) NULL,
  `favicon_path` VARCHAR(255) NULL,
  `bg_image_path` VARCHAR(255) NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Admins (username: admin / admin2 / superadmin, password: MkNetAdmin@2026)
INSERT INTO `admins` (`id`, `username`, `password`, `email`, `role`) VALUES
(1, 'admin', '$2y$10$FRTCD56UoopfDM9oQlEQwOVcfSH.XlVKlA/ld827Uqfhl6ywqQY7O', 'admin@mknet.com', 'admin'),
(2, 'admin2', '$2y$10$FRTCD56UoopfDM9oQlEQwOVcfSH.XlVKlA/ld827Uqfhl6ywqQY7O', 'admin2@mknet.com', 'admin'),
(3, 'superadmin', '$2y$10$FRTCD56UoopfDM9oQlEQwOVcfSH.XlVKlA/ld827Uqfhl6ywqQY7O', 'superadmin@mknet.com', 'superadmin')
ON DUPLICATE KEY UPDATE `password` = VALUES(`password`);

-- Seed Settings
INSERT INTO `settings` (`id`, `site_name`, `hotline`, `email`, `address`, `active_theme`) VALUES
(1, 'MK Net ISP', '16335 / 09678-123123', 'support@mknet.com', 'Police Plaza Concord, Gulshan 1, Dhaka', 'cyber_dark')
ON DUPLICATE KEY UPDATE `site_name` = VALUES(`site_name`);
