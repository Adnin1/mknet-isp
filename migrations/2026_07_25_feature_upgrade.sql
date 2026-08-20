-- =====================================================================
-- MK Net ISP — Feature upgrade migration (Auth, Dashboard, Checkout,
-- Payments). Purely ADDITIVE: no existing table is renamed, no existing
-- column is dropped, no existing data is touched. Safe to run on the
-- live DB; every statement is guarded so re-running it is harmless.
-- =====================================================================

-- ---------------------------------------------------------------------
-- USERS: new optional columns for verification, account status,
-- referral, and login-security state. All nullable / defaulted so
-- existing rows and existing code that SELECT * FROM users keep working.
-- ---------------------------------------------------------------------
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='email_verified_at');
SET @s := IF(@c=0,'ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER email','SELECT 1'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='phone_verified_at');
SET @s := IF(@c=0,'ALTER TABLE users ADD COLUMN phone_verified_at DATETIME NULL AFTER phone','SELECT 1'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='account_status');
SET @s := IF(@c=0,'ALTER TABLE users ADD COLUMN account_status ENUM(\'active\',\'paused\',\'suspended\') NOT NULL DEFAULT \'active\'','SELECT 1'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='paused_at');
SET @s := IF(@c=0,'ALTER TABLE users ADD COLUMN paused_at DATETIME NULL','SELECT 1'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='package_id');
SET @s := IF(@c=0,'ALTER TABLE users ADD COLUMN package_id INT NULL AFTER package_speed_mbps','SELECT 1'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='package_expires_at');
SET @s := IF(@c=0,'ALTER TABLE users ADD COLUMN package_expires_at DATE NULL','SELECT 1'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='referral_code');
SET @s := IF(@c=0,'ALTER TABLE users ADD COLUMN referral_code VARCHAR(30) NULL','SELECT 1'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='referred_by_user_id');
SET @s := IF(@c=0,'ALTER TABLE users ADD COLUMN referred_by_user_id INT NULL','SELECT 1'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='failed_login_attempts');
SET @s := IF(@c=0,'ALTER TABLE users ADD COLUMN failed_login_attempts INT NOT NULL DEFAULT 0','SELECT 1'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='locked_until');
SET @s := IF(@c=0,'ALTER TABLE users ADD COLUMN locked_until DATETIME NULL','SELECT 1'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- Unique index for referral_code (only once column exists and is populated per-user later by app code)
SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND INDEX_NAME='idx_users_referral_code');
SET @s := IF(@c=0,'ALTER TABLE users ADD UNIQUE INDEX idx_users_referral_code (referral_code)','SELECT 1'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------------------------------------------------------------------
-- PACKAGES: merchandising fields for the upgraded package page.
-- ---------------------------------------------------------------------
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='packages' AND COLUMN_NAME='badge');
SET @s := IF(@c=0,'ALTER TABLE packages ADD COLUMN badge ENUM(\'none\',\'popular\',\'new\',\'limited\') NOT NULL DEFAULT \'none\'','SELECT 1'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='packages' AND COLUMN_NAME='offer_ends_at');
SET @s := IF(@c=0,'ALTER TABLE packages ADD COLUMN offer_ends_at DATETIME NULL','SELECT 1'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='packages' AND COLUMN_NAME='cashback_percent');
SET @s := IF(@c=0,'ALTER TABLE packages ADD COLUMN cashback_percent DECIMAL(5,2) NOT NULL DEFAULT 0','SELECT 1'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='packages' AND COLUMN_NAME='emi_months');
SET @s := IF(@c=0,'ALTER TABLE packages ADD COLUMN emi_months INT NOT NULL DEFAULT 0','SELECT 1'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='packages' AND COLUMN_NAME='video_url');
SET @s := IF(@c=0,'ALTER TABLE packages ADD COLUMN video_url VARCHAR(255) NULL','SELECT 1'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='packages' AND COLUMN_NAME='faq');
SET @s := IF(@c=0,'ALTER TABLE packages ADD COLUMN faq LONGTEXT NULL CHECK (faq IS NULL OR JSON_VALID(faq))','SELECT 1'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------------------------------------------------------------------
-- EMAIL VERIFICATION
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS email_verifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  verified_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ev_user (user_id),
  CONSTRAINT fk_ev_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- OTP CODES (phone verification, checkout confirmation, 2FA-by-email
-- fallback). Codes are stored hashed, never in plain text.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS otp_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  channel ENUM('email','phone') NOT NULL,
  purpose VARCHAR(50) NOT NULL,
  destination VARCHAR(150) NOT NULL,
  code_hash VARCHAR(255) NOT NULL,
  attempts INT NOT NULL DEFAULT 0,
  max_attempts INT NOT NULL DEFAULT 5,
  expires_at DATETIME NOT NULL,
  consumed_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_otp_user (user_id),
  KEY idx_otp_purpose (purpose, destination)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- SESSIONS: backs "Remember Me", "Active Sessions" and "Device
-- Management". One row per browser/device that has logged in.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  selector VARCHAR(64) NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  php_session_id VARCHAR(128) NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  device_label VARCHAR(150) NULL,
  is_remember_me TINYINT(1) NOT NULL DEFAULT 0,
  last_active_at DATETIME NOT NULL,
  expires_at DATETIME NOT NULL,
  revoked_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY idx_sessions_selector (selector),
  KEY idx_sessions_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- LOGIN HISTORY: audit trail of every login attempt (success/fail).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  username_attempted VARCHAR(150) NULL,
  status ENUM('success','failed','locked') NOT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_lh_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 2FA (TOTP - Google Authenticator compatible, works with no external
-- service - fully self-contained RFC 6238 implementation).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_2fa (
  user_id INT PRIMARY KEY,
  secret VARCHAR(64) NOT NULL,
  enabled_at DATETIME NULL,
  recovery_codes LONGTEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_2fa_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- COUPONS / PROMO CODES
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS coupons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL,
  type ENUM('percent','flat') NOT NULL DEFAULT 'percent',
  value DECIMAL(10,2) NOT NULL,
  min_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  max_uses INT NULL,
  used_count INT NOT NULL DEFAULT 0,
  starts_at DATETIME NULL,
  expires_at DATETIME NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY idx_coupons_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- REFERRALS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS referrals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  referrer_user_id INT NOT NULL,
  referred_user_id INT NULL,
  code VARCHAR(30) NOT NULL,
  reward_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  status ENUM('pending','completed') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME NULL,
  KEY idx_ref_referrer (referrer_user_id),
  KEY idx_ref_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- NOTIFICATIONS (customer dashboard notification center)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  message TEXT NULL,
  type VARCHAR(30) NOT NULL DEFAULT 'info',
  link VARCHAR(255) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notif_user (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- USER ADDONS (which add-ons from `addons` a customer currently has)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_addons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  addon_id INT NOT NULL,
  status ENUM('active','removed') NOT NULL DEFAULT 'active',
  added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  removed_at DATETIME NULL,
  KEY idx_ua_user (user_id),
  CONSTRAINT fk_ua_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ua_addon FOREIGN KEY (addon_id) REFERENCES addons(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- PAYMENT TRANSACTIONS: gateway-agnostic ledger for every payment
-- attempt across SSLCommerz / bKash / Nagad / Rocket / Upay / Card /
-- manual, independent of the older ad-hoc `payments` table.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS payment_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT NULL,
  user_id INT NULL,
  gateway VARCHAR(30) NOT NULL,
  gateway_txn_id VARCHAR(150) NULL,
  amount DECIMAL(10,2) NOT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'BDT',
  status ENUM('initiated','pending','success','failed','cancelled') NOT NULL DEFAULT 'initiated',
  raw_response LONGTEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_pt_invoice (invoice_id),
  KEY idx_pt_user (user_id),
  KEY idx_pt_gwtxn (gateway_txn_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT 'Feature upgrade migration applied successfully.' AS result;
