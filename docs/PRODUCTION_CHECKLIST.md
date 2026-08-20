# Production Checklist

Go through this in order before pointing your real domain at the new code.

## Configuration
- [ ] `config.php`: DB credentials match your live database
- [ ] `config.php`: `APP_DEBUG` set to `false` (stops raw DB/PHP errors from
      being shown to visitors)
- [ ] `config/services.php` exists (copied from `.example.php`) with real
      SMTP credentials filled in
- [ ] `config/services.php`: real SMS gateway credentials filled in (or a
      documented decision to launch without phone verification for now)
- [ ] `config/services.php`: at minimum SSLCommerz configured (fastest path
      to covering bKash/Nagad/Rocket/Upay/Cards); `dev_mode` set to `false`
- [ ] `config/services.php` is excluded from version control / public
      access (it will contain real secrets)

## Security
- [ ] Changed the default admin password (`MkNetAdmin@2026`) on all 3
      accounts — see DEPLOYMENT_GUIDE.md step 5
- [ ] HTTPS/SSL certificate is live on the domain
- [ ] Uncommented `session.cookie_secure` in `config.php` (only after
      HTTPS is confirmed working — it will break login over plain HTTP)
- [ ] `_DEV_TOOLS_DO_NOT_UPLOAD/` was NOT uploaded to the live server
- [ ] Confirmed `test_db.php`, `test_hash.php`, `mknet_setup.php`, and
      similar debug scripts return 404 on the live domain (they shouldn't
      exist there at all)

## Database
- [ ] All 3 migrations applied (`SHOW TABLES` should show 44 tables)
- [ ] Fresh backup taken immediately before and immediately after migration
- [ ] Daily backup cron job scheduled (`scripts/backup_db.sh`)

## Functional
- [ ] Full TESTING_CHECKLIST.md run against the live domain, not just
      locally
- [ ] A real test purchase completed end-to-end with a real (small) payment
      through your configured live gateway, not just the sandbox
- [ ] A real verification email received in an actual inbox (check spam
      folder placement too — first-time sending domains often land in spam;
      consider SPF/DKIM/DMARC records for your sending domain)

## Operational
- [ ] Someone on your team knows how to check `storage/logs/` (`mail.log`,
      `sms.log`, `payment_callback.log`, `checkout.log`, `db.log`) when a
      user reports a problem
- [ ] Someone knows where `docs/ROLLBACK_GUIDE.md` is if a deployment goes
      wrong
- [ ] Cron/scheduled task set up for backups (see BACKUP_GUIDE.md)

## Post-launch (first 48 hours)
- [ ] Monitor `storage/logs/mail.log` and `sms.log` for delivery failures
- [ ] Monitor `payment_transactions` table for a cluster of `failed` status
      rows, which would indicate a gateway credential problem
- [ ] Spot-check a handful of real customer registrations completed the
      email verification step (if many don't, suspect spam-folder delivery)
