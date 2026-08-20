# Rollback Guide

## If something breaks right after deploying

### Files: instant rollback
Since you kept a pre-deployment zip (per DEPLOYMENT_GUIDE.md step 1), just
re-upload it, overwriting the new files. There's no build step, no compiled
assets, nothing to "undo" beyond restoring the files — PHP runs directly
from source.

### Database: this is additive-only, so full rollback usually isn't necessary
Every migration in this project only *adds* tables and columns — nothing was
dropped or renamed. This means:
- **Old code will keep working even with the new schema in place.** If you
  roll back the *files* to the old version but leave the new database
  columns/tables in place, nothing breaks — the old code simply never
  queries the new columns.
- You almost never need to reverse a migration. If you do want to fully
  undo it anyway (e.g. you're certain you'll never use these features):

```sql
-- Reverses 2026_07_25_feature_upgrade.sql. Run only if you're certain
-- you want the new tables/columns gone - this deletes their data.
DROP TABLE IF EXISTS payment_transactions, user_addons, notifications,
  referrals, coupons, user_2fa, login_history, user_sessions, otp_codes,
  email_verifications;

ALTER TABLE users DROP COLUMN email_verified_at, DROP COLUMN phone_verified_at,
  DROP COLUMN account_status, DROP COLUMN paused_at, DROP COLUMN package_id,
  DROP COLUMN package_expires_at, DROP COLUMN referral_code,
  DROP COLUMN referred_by_user_id, DROP COLUMN failed_login_attempts,
  DROP COLUMN locked_until;

ALTER TABLE packages DROP COLUMN badge, DROP COLUMN offer_ends_at,
  DROP COLUMN cashback_percent, DROP COLUMN emi_months,
  DROP COLUMN video_url, DROP COLUMN faq;
```

### Full disaster-recovery rollback
If you need to go back to exactly where you were before this entire
engagement:
1. Restore your pre-engagement database backup (the one from before any of
   these migrations ran).
2. Restore your pre-engagement file backup.
3. That's it — there's no other system (no external services were ever
   *required* to be configured, since everything defaults to dev-mode
   fallbacks).

## If a specific new feature is causing a problem, but the rest is fine
You don't have to roll back everything. Each feature is reasonably isolated:
- **2FA causing lockouts?** `DELETE FROM user_2fa WHERE user_id = X;` for the
  affected user — they'll log in with just their password again.
- **A payment gateway integration misbehaving?** In
  `config/services.php`, clear that gateway's credentials — it falls back
  to the dev-mode simulate flow automatically, or just don't select it in
  the checkout payment-method list (remove it from
  `PaymentGatewayFactory::GATEWAYS` temporarily).
- **Checkout flow itself causing issues?** The old direct
  `paybill.php?package_id=X` link (used by the package page's "Subscribe"
  button before this round) still exists and still works if you need an
  emergency fallback while debugging checkout.
