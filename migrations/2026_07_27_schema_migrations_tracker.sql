-- Tracks which migration files have been applied, so the admin "System
-- Updates" page can safely show what's pending and apply only vetted,
-- already-shipped SQL files - never arbitrary uploaded content.
CREATE TABLE IF NOT EXISTS schema_migrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  filename VARCHAR(255) NOT NULL,
  applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY idx_sm_filename (filename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Backfill: mark every migration that ships in THIS release as already
-- applied, since if you're running this file, you've already run the
-- others manually per DEPLOYMENT_GUIDE.md.
INSERT IGNORE INTO schema_migrations (filename) VALUES
  ('2026_07_23_performance_and_cleanup.sql'),
  ('2026_07_24_database_fixes.sql'),
  ('2026_07_25_feature_upgrade.sql'),
  ('2026_07_26_tickets_and_billing.sql'),
  ('2026_07_27_schema_migrations_tracker.sql');

SELECT 'Migration tracker installed.' AS result;
