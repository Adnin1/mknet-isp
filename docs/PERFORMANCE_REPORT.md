# Performance Report

## Database
- Added indexes on every foreign-key-shaped column the new features query
  by: `invoices(user_id, status)`, `invoices(package_id)`, `invoices(bundle_id)`,
  `packages(visible)`, `bundles(visible)`, plus the earlier round's indexes on
  `invoices`, `billing`, `packages`. Verified with `SHOW INDEX` after migration.
- Removed 2 duplicate unique indexes on `users.email` (3 identical indexes →
  1) — pure waste, no functional change.
- All new tables (`user_sessions`, `otp_codes`, `login_history`, `notifications`,
  etc.) have indexes on their lookup columns (`user_id`, `selector`,
  `destination + purpose`) from the start, rather than needing a later
  "add indexes" pass.
- `otp_codes` and `login_history` will grow indefinitely with normal use.
  Nothing in this project deletes old rows automatically — see the
  **Future Roadmap** for a suggested cleanup cron job (e.g. delete OTPs older
  than 7 days, archive login history older than 1 year).

## Application
- Every new/rewritten page uses parameterized prepared statements — beyond
  the security benefit, PDO can reuse query plans for identical statement
  shapes, which is a minor but real win under load.
- The dashboard, package page, and checkout all issue single, targeted
  queries per data need rather than `SELECT *` fan-out — e.g. active add-ons
  is one JOIN, not N+1 queries per add-on.
- `SessionManager::touch()` does a lightweight `UPDATE ... last_active_at`
  on every authenticated page load — this is a lot of small writes at scale.
  For a single ISP's customer base this is fine; if you ever have thousands
  of concurrent logged-in users, consider batching this (e.g. only update if
  more than 60 seconds have passed since the last update) — noted in the
  Future Roadmap.

## What wasn't changed
- No caching layer (Redis/Memcached) was introduced — this project's traffic
  profile (an ISP's customer base, not a viral consumer app) doesn't need one
  yet. The Future Roadmap covers when to reconsider this.
- No CDN configuration — that's a hosting/DNS decision outside this codebase,
  covered in the Deployment Guide.
- No image optimization pass was done on `assets/images/` — several package
  images are referenced by convention (`{slug}.jpg`) with a fallback to
  `default.jpg` if missing; compressing/resizing the actual image files is a
  content task, not a code task.
