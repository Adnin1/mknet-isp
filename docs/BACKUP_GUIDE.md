# Backup Guide

## Database backups
A ready-to-use script is included: `scripts/backup_db.sh`.
```bash
bash scripts/backup_db.sh
```
- Saves a timestamped `.sql` dump to `storage/backups/`.
- Automatically keeps only the last 14 backups (older ones are deleted so
  you don't quietly fill up your disk).
- Fill in `DB_PASS` inside the script if your database has a password (it's
  blank by default to match a typical local/XAMPP setup).

**Set this up as a daily cron job** once you're live:
```bash
# crontab -e, run every day at 3 AM:
0 3 * * * /usr/bin/bash /path/to/mknet/scripts/backup_db.sh
```

### What's actually in the database now that's worth protecting
Beyond your original customer/billing data, this round added: every user's
2FA secret and recovery codes (`user_2fa`), password reset/email verification
tokens (short-lived, low risk if leaked), coupon and referral data, and the
full payment transaction ledger (`payment_transactions`). None of this
changes your backup *strategy* — it's all in the same database — but it's
worth knowing the dump now contains more sensitive data than before, so
backup file storage security matters more than it used to (encrypt backups
at rest if they leave your server, e.g. cloud storage).

## File backups
Before any future deployment, zip the entire `mknet/` folder as it exists on
your live server — that's your rollback point. Keep at least the last 3
deployment snapshots, not just the most recent one.

```bash
# On your server, before deploying an update:
zip -r mknet_backup_$(date +%Y-%m-%d).zip mknet/
```

## What NOT to back up / commit to version control
- `config/services.php` (once it has real credentials) — back it up
  somewhere secure and separate from your code repository, not in the same
  place as your public-facing files or a public git repo.
- `storage/logs/*.log` — useful for debugging, not something you need
  long-term retention on; they rotate automatically once they hit ~2MB
  (see `app_log()` in `config.php`).
