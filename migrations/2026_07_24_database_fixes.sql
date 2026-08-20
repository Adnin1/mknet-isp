-- =====================================================================
-- MK Net ISP — Database fix script
-- Run this once against your LIVE database (phpMyAdmin > Import, or:
--   mysql -u root -p mknet < 2026_07_24_database_fixes.sql
-- Safe to run even if some parts were already applied - uses
-- IF EXISTS / conditional checks where possible.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) BROKEN ADMIN LOGINS (this is almost certainly why the "super admin"
--    panel has been giving you trouble). Two of the three seeded admin
--    accounts had unusable password hashes:
--      - 'admin'  had a literal placeholder hash ("$2y$10$XXXXXXX...")
--        that can never match any real password.
--      - 'admin2' had a bare MD5 hash instead of a proper bcrypt hash,
--        which password_verify() can never match either.
--    Only 'superadmin' had a real bcrypt hash (unknown password).
--
--    This resets ALL THREE accounts to one known-good password so you
--    can get back into the admin panel immediately:
--
--        username: admin        password: MkNetAdmin@2026
--        username: admin2       password: MkNetAdmin@2026
--        username: superadmin   password: MkNetAdmin@2026
--
--    >>> Log in once, then change these from a real admin "change
--        password" feature if the site has one, or ask me to add one. <<<
UPDATE `admins`
SET `password` = '$2y$10$FRTCD56UoopfDM9oQlEQwOVcfSH.XlVKlA/ld827Uqfhl6ywqQY7O'
WHERE `username` IN ('admin', 'admin2', 'superadmin');

-- ---------------------------------------------------------------------
-- 2) Broken "Pay Bill" navigation link. The site's top menu is stored
--    in this table (not hardcoded in PHP), and the "Pay Bill" row
--    pointed straight at paybill.php with no package/bundle selected -
--    guaranteed "No package or bundle selected" error on every click.
--    This is the exact "payment error" you're seeing site-wide.
UPDATE `site_menu`
SET `link` = '/mknet/pages/packages.php'
WHERE `title` = 'Pay Bill' AND `link` = '/mknet/pages/paybill.php';

-- ---------------------------------------------------------------------
-- 3) Remove exact duplicate seed rows (same setup script was clearly
--    run more than once). Keeps the lowest id of each duplicate set.
DELETE e1 FROM `employees` e1
INNER JOIN `employees` e2
WHERE e1.id > e2.id
  AND e1.name = e2.name AND e1.role = e2.role AND e1.email = e2.email;

DELETE t1 FROM `team` t1
INNER JOIN `team` t2
WHERE t1.id > t2.id
  AND t1.name = t2.name AND t1.role = t2.role
  AND (t1.email <=> t2.email) AND (t1.join_date <=> t2.join_date);

-- ---------------------------------------------------------------------
-- 4) `packages.features` is NULL for every single package right now.
--    The code already tolerates NULL safely, but an empty JSON array is
--    cleaner and lets the "Features" list render consistently instead
--    of silently staying blank.
UPDATE `packages` SET `features` = '[]' WHERE `features` IS NULL;

-- ---------------------------------------------------------------------
-- 5) Performance indexes (safe no-ops if you already ran the previous
--    migration file — MariaDB will just error harmlessly on duplicates,
--    so this section is wrapped so it won't stop the rest of the script
--    from running if you re-import it).
SET @idx_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='invoices' AND INDEX_NAME='idx_invoices_user_status');
SET @sql := IF(@idx_exists = 0,
  'ALTER TABLE `invoices` ADD INDEX `idx_invoices_user_status` (`user_id`,`status`), ADD INDEX `idx_invoices_package` (`package_id`), ADD INDEX `idx_invoices_bundle` (`bundle_id`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='packages' AND INDEX_NAME='idx_packages_visible');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE `packages` ADD INDEX `idx_packages_visible` (`visible`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='bundles' AND INDEX_NAME='idx_bundles_visible');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE `bundles` ADD INDEX `idx_bundles_visible` (`visible`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- 6) `users` table has 3 identical unique keys on `email` (email,
--    email_2, email_3) from repeated migrations - harmless but wastes
--    space. Drop the redundant two, keep one.
SET @idx_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='users' AND INDEX_NAME='email_2');
SET @sql := IF(@idx_exists > 0, 'ALTER TABLE `users` DROP INDEX `email_2`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='users' AND INDEX_NAME='email_3');
SET @sql := IF(@idx_exists > 0, 'ALTER TABLE `users` DROP INDEX `email_3`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Done.
SELECT 'Database fixes applied successfully.' AS result;
