-- MK Net ISP: performance + schema cleanup migration
-- Safe to run on the live DB. Run once via phpMyAdmin or:
--   mysql -u root -p mknet < migrations/2026_07_23_performance_and_cleanup.sql

-- 1) invoices: no indexes at all besides the primary key, even though
--    paybill.php / admin invoice list / callback all filter by user_id,
--    status, package_id, bundle_id. These speed those queries up as the
--    table grows (it already has 100+ rows from testing).
ALTER TABLE `invoices`
  ADD INDEX `idx_invoices_user_status` (`user_id`, `status`),
  ADD INDEX `idx_invoices_package` (`package_id`),
  ADD INDEX `idx_invoices_bundle` (`bundle_id`);

-- 2) packages / bundles: public pages filter WHERE visible = 1
ALTER TABLE `packages`
  ADD INDEX `idx_packages_visible` (`visible`);

ALTER TABLE `bundles`
  ADD INDEX `idx_bundles_visible` (`visible`);

-- 3) users: the dump currently has THREE identical unique keys on `email`
--    (email, email_2, email_3) - harmless but wastes space/slows writes
--    slightly. Keep just one.
ALTER TABLE `users`
  DROP INDEX `email_2`,
  DROP INDEX `email_3`;
