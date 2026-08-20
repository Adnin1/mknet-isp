# Deployment Guide

## 1. Before you touch the live server
- [ ] Take a full backup of your current live database (see BACKUP_GUIDE.md).
- [ ] Take a full copy of your current live file tree (zip the whole
      `mknet/` folder as it exists today).
- [ ] Confirm you have FTP/SSH access and can restore both of the above if
      something goes wrong (see ROLLBACK_GUIDE.md).

## 2. Deploy the files
1. Upload the entire `mknet/` folder from this package, **replacing** your
   current live folder (this package's `mknet/` is a complete, self-contained
   copy — not a diff/patch).
2. **Do not upload `_DEV_TOOLS_DO_NOT_UPLOAD/`** — it's excluded from
   `mknet/` already, it sits alongside it in this package purely for your own
   reference (it contains the insecure debug scripts that were removed from
   the live site).
3. Confirm `config.php` still has your correct live DB credentials (host,
   db name, username, password) — this package didn't change your DB
   connection details, only added to the file, so check the top of
   `config.php` matches your server.

## 3. Configure real credentials (see INCOMPLETE_FEATURES.md for detail)
1. Copy `config/services.example.php` to `config/services.php` if it isn't
   already there (it ships pre-copied with `dev_mode => true` so the site
   works immediately, but you should fill in real values before launch).
2. Fill in SMTP details for real email sending (verification links,
   password resets, invoices).
3. Fill in SMS gateway details for real phone OTP delivery.
4. Fill in SSLCommerz (recommended first — covers the most payment methods
   in one integration) and/or bKash/Nagad credentials.
5. Once every section you need is filled in, set `'dev_mode' => false` at
   the top of `config/services.php`.

## 4. Run the database migrations
In order, either via phpMyAdmin's Import tab or the command line:
```bash
mysql -u your_db_user -p your_db_name < migrations/2026_07_23_performance_and_cleanup.sql
mysql -u your_db_user -p your_db_name < migrations/2026_07_24_database_fixes.sql
mysql -u your_db_user -p your_db_name < migrations/2026_07_25_feature_upgrade.sql
mysql -u your_db_user -p your_db_name < migrations/2026_07_26_tickets_and_billing.sql
```
All four are safe to re-run if you're ever unsure whether they already ran.

## 4b. Set up the recurring billing cron job
```bash
# crontab -e, run once a day at 6 AM:
0 6 * * *  php /path/to/mknet/scripts/generate_recurring_invoices.php >> /path/to/mknet/storage/logs/billing_cron_output.log 2>&1
```
This generates each customer's next invoice automatically based on their
package, and applies a late fee to invoices still unpaid after 7 days. See
`docs/CHANGELOG.md` for exactly how it was tested.

## 5. One-time admin password reset
`2026_07_24_database_fixes.sql` resets all admin accounts to a known
password (**MkNetAdmin@2026**) since two of the three were previously
unusable. **Log in and change this immediately** via a real "change admin
password" flow, or directly in the `admins` table with a fresh
`password_hash()` value, since this password is now written in this
document.

## 6. Backfill referral codes for existing users (optional but recommended)
Existing users get a referral code automatically the first time they load
their dashboard, but if you'd rather have every account ready immediately:
```sql
-- Run once; requires a PHP script since codes must be unique and
-- password_hash-style generation isn't available in raw SQL. See
-- scripts/backfill_referral_codes.php if you want this automated,
-- or simply let the dashboard generate them on first visit (default
-- behavior, no action needed).
```

## 7. Smoke test on the live server
Run through TESTING_CHECKLIST.md end to end, or at minimum:
- Register a new test account, confirm the verification email arrives.
- Log in, view the dashboard.
- Add a test coupon in `admin/manage_coupons.php`, run a test checkout.
- Confirm the payment gateway you configured actually redirects to the real
  provider (not the dev-mode simulate page — if you still see "Simulate
  Successful Payment", your credentials in `config/services.php` aren't
  being picked up).

## 8. Go live checklist
See PRODUCTION_CHECKLIST.md for the full pre-launch list (HTTPS, `APP_DEBUG`,
etc.).
