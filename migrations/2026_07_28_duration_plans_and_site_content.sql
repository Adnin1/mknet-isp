-- =====================================================================
-- MK Net — Custom-duration subscription plans + admin-controlled site
-- visuals (background/banner/carousel). Purely additive, idempotent.
-- =====================================================================

-- Package category (residential / business / enterprise) - "Business
-- Bronze/Silver" already existed by name only; this makes it a real,
-- filterable field, and lets an Enterprise tier be added properly.
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='packages' AND COLUMN_NAME='category');
SET @s := IF(@c=0,'ALTER TABLE packages ADD COLUMN category ENUM(\'residential\',\'business\',\'enterprise\') NOT NULL DEFAULT \'residential\'','SELECT 1'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

UPDATE packages SET category='business' WHERE name LIKE 'Business%';

-- Seed one Enterprise-tier package if none exists yet (only Residential
-- and Business tiers existed before this pass).
INSERT INTO packages (name, speed_mbps, price, price_bdt, description, category, visible, features)
SELECT 'Enterprise Dedicated', 200, 14999.00, 14999, 'Dedicated bandwidth, priority support, and an SLA-backed uptime guarantee for offices and businesses.', 'enterprise', 1, '["Dedicated (not shared) bandwidth","24/7 priority support line","99.9% uptime SLA","Static IP included","Same-day installation"]'
WHERE NOT EXISTS (SELECT 1 FROM packages WHERE category='enterprise');

-- ---------------------------------------------------------------------
-- DURATION PLANS: admin-configurable short/custom-duration top-up
-- options. Only ever offered to users who already have a package
-- assigned (existing subscribers) - new registrations still pick a
-- fixed package via the normal register.php flow, enforced in code,
-- not just by hiding UI.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS duration_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(50) NOT NULL,
  days INT NULL COMMENT 'NULL means this row IS the "custom duration" option',
  is_custom TINYINT(1) NOT NULL DEFAULT 0,
  min_custom_days INT NOT NULL DEFAULT 1,
  max_custom_days INT NOT NULL DEFAULT 365,
  discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO duration_plans (label, days, is_custom, discount_percent, sort_order)
SELECT * FROM (SELECT '1 Day' AS a, 1 AS b, 0 AS c, 0.00 AS d, 1 AS e) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM duration_plans WHERE label='1 Day');
INSERT INTO duration_plans (label, days, is_custom, discount_percent, sort_order)
SELECT * FROM (SELECT '3 Days' AS a, 3 AS b, 0 AS c, 0.00 AS d, 2 AS e) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM duration_plans WHERE label='3 Days');
INSERT INTO duration_plans (label, days, is_custom, discount_percent, sort_order)
SELECT * FROM (SELECT '7 Days' AS a, 7 AS b, 0 AS c, 2.00 AS d, 3 AS e) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM duration_plans WHERE label='7 Days');
INSERT INTO duration_plans (label, days, is_custom, discount_percent, sort_order)
SELECT * FROM (SELECT '15 Days' AS a, 15 AS b, 0 AS c, 3.00 AS d, 4 AS e) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM duration_plans WHERE label='15 Days');
INSERT INTO duration_plans (label, days, is_custom, discount_percent, sort_order)
SELECT * FROM (SELECT '30 Days' AS a, 30 AS b, 0 AS c, 5.00 AS d, 5 AS e) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM duration_plans WHERE label='30 Days');
INSERT INTO duration_plans (label, days, is_custom, discount_percent, sort_order)
SELECT * FROM (SELECT '60 Days' AS a, 60 AS b, 0 AS c, 7.00 AS d, 6 AS e) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM duration_plans WHERE label='60 Days');
INSERT INTO duration_plans (label, days, is_custom, discount_percent, sort_order)
SELECT * FROM (SELECT '90 Days' AS a, 90 AS b, 0 AS c, 10.00 AS d, 7 AS e) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM duration_plans WHERE label='90 Days');
INSERT INTO duration_plans (label, days, is_custom, min_custom_days, max_custom_days, discount_percent, sort_order)
SELECT * FROM (SELECT 'Custom' AS a, NULL AS b, 1 AS c, 1 AS d, 365 AS e, 0.00 AS f, 8 AS g) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM duration_plans WHERE label='Custom');

-- ---------------------------------------------------------------------
-- SITE CONTENT: admin-controlled homepage/page backgrounds, banners,
-- and carousel slides - the concrete, real version of "admin controls
-- the visuals" (see chat response for what this covers vs. doesn't).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS site_content (
  id INT AUTO_INCREMENT PRIMARY KEY,
  content_key VARCHAR(100) NOT NULL COMMENT 'e.g. homepage_background, dashboard_background',
  image_path VARCHAR(255) NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY idx_sc_key (content_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS carousel_slides (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NULL,
  subtitle VARCHAR(255) NULL,
  image_path VARCHAR(255) NOT NULL,
  link_url VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_banners (
  id INT AUTO_INCREMENT PRIMARY KEY,
  message VARCHAR(255) NOT NULL,
  type ENUM('offer','maintenance','info','urgent') NOT NULL DEFAULT 'info',
  link_url VARCHAR(255) NULL,
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Messenger/Telegram quick-connect links, alongside the existing
-- support_phone (used for the WhatsApp deep link).
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='settings' AND COLUMN_NAME='messenger_link');
SET @s := IF(@c=0,'ALTER TABLE settings ADD COLUMN messenger_link VARCHAR(255) NULL','SELECT 1'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='settings' AND COLUMN_NAME='telegram_link');
SET @s := IF(@c=0,'ALTER TABLE settings ADD COLUMN telegram_link VARCHAR(255) NULL','SELECT 1'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- Pay Bill now works as a real "show my outstanding bills" hub page again
-- (previously redirected to Packages as a workaround since paybill.php
-- used to require a specific item id - see chat response for detail).
UPDATE site_menu SET link='/mknet/pages/paybill.php' WHERE title='Pay Bill' AND link='/mknet/pages/packages.php';

-- Self Care: the site's nav linked to a completely separate, older,
-- insecure parallel login system (pages/selfcare_login.php - different
-- session key, no CSRF/2FA, and a literally doubled-up broken "Ads"
-- section referencing missing images) instead of the real, tested
-- login system this whole project builds on. That old file now just
-- redirects to pages/login.php; this points the nav there directly.
UPDATE site_menu SET link='/mknet/pages/login.php' WHERE title='Self Care' AND link='/mknet/pages/selfcare_login.php';

SELECT 'Duration plans + site content migration applied successfully.' AS result;
