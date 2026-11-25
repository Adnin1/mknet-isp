-- Additive migration for DB-backed persistent auth rate limiting (zero request-time DDL)
CREATE TABLE IF NOT EXISTS `auth_rate_limits` (
  `rate_key` VARCHAR(128) NOT NULL,
  `attempts` INT NOT NULL DEFAULT 0,
  `window_start` INT NOT NULL,
  `expires_at` INT NOT NULL,
  PRIMARY KEY (`rate_key`),
  KEY `idx_auth_rate_limits_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
