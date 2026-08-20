# Database Report

## Schema changes (all additive — see migrations/ folder)
- **12 new tables**: `email_verifications`, `otp_codes`, `user_sessions`,
  `login_history`, `user_2fa`, `coupons`, `referrals`, `notifications`,
  `user_addons`, `payment_transactions`, plus the earlier round's schema
  fixes. Every one has a primary key, appropriate indexes, and foreign keys
  to `users`/`addons` where the relationship is 1:many and ownership-based.
- **`users` table**: 11 new nullable/defaulted columns (`email_verified_at`,
  `phone_verified_at`, `account_status`, `paused_at`, `package_id`,
  `package_expires_at`, `referral_code`, `referred_by_user_id`,
  `failed_login_attempts`, `locked_until`). No existing column touched.
- **`packages` table**: 6 new columns for the merchandising features
  (`badge`, `offer_ends_at`, `cashback_percent`, `emi_months`, `video_url`,
  `faq`). No existing column touched.
- Zero tables renamed. Zero columns renamed. Zero columns dropped.

## Data integrity findings (from inspecting your actual data dump)
- `packages.features` was `NULL` for all 5 packages — cleaned up to `'[]'`
  by the fix migration so the Features list renders consistently instead of
  silently staying blank.
- 2 of 3 seeded admin accounts (`admin`, `admin2`) had password hashes that
  could never successfully authenticate (one was a literal placeholder
  string, one was raw MD5 instead of bcrypt) — fixed.
- `employees` and `team` tables had exact-duplicate rows from a setup script
  apparently being run more than once — de-duplicated (kept the lowest id of
  each duplicate set; rows that weren't byte-for-byte identical, like the
  `team` rows with different photos, were left alone since collapsing those
  would have been a guess, not a fact).
- `users.email` had 3 redundant identical unique indexes — down to 1.

## Referential integrity
Checked for orphaned foreign keys across `invoices`, `billing`: zero orphans
found (every `user_id` in both tables points at a row that actually exists
in `users`). The new tables all declare real `FOREIGN KEY ... ON DELETE
CASCADE` constraints where the child data is meaningless without the parent
(e.g. `user_2fa`, `user_addons`), so deleting a user cleanly cascades instead
of leaving orphaned rows.

## Growth / maintenance considerations
- `otp_codes`, `login_history`, and `payment_transactions` are append-only
  with no automatic pruning. For a mid-sized ISP this won't matter for
  years, but see the Future Roadmap for a suggested retention policy.
- `invoices.meta` and `packages.faq`/`features` are JSON stored in
  `LONGTEXT` columns with a `CHECK (JSON_VALID(...))` constraint where MySQL
  supports it — this validates shape without needing a stricter native JSON
  column type, keeping compatibility with older MySQL/MariaDB versions.

## What to verify on your own server before going live
Run this once after importing your data + migrations, to confirm your
specific database matches what was tested here:
```sql
SELECT id, username, LENGTH(password) AS pwd_len FROM admins;  -- expect 60 for all rows
SELECT COUNT(*) FROM users WHERE referral_code IS NULL;         -- expect 0 after first dashboard visit per user, or run the backfill in DEPLOYMENT_GUIDE.md
SHOW TABLES;                                                     -- expect 44 tables
```
