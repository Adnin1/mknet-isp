# CHANGELOG — MK Net Production Upgrade

This covers every change made across the full engagement. Nothing below
removed existing functionality, renamed a table/column that other code
depended on, or changed a public URL.

## 🆕 Round 4: Custom-Duration Recharge, Pay Bill Fix, Admin Visuals, Dashboard Restructure

Also includes a cross-check against two other zips the user had from
separate, independent Claude conversations working on the same original
project — this surfaced a real corruption bug (`admin/manage_news.php` had
duplicated form-handling code sitting outside PHP tags, silently printing
as garbled text) and a CSRF-coverage gap across 6 admin content pages,
both fixed here.

- **Custom-duration recharge/renewal system** (`pages/recharge.php`,
  `admin/manage_duration_plans.php`, `duration_plans` table): existing
  subscribers can renew for 1/3/7/15/30/60/90 days or any custom range,
  with admin-configurable durations, discounts, and min/max custom range.
  Live JS price calculator (daily rate × days − discount + tax). New
  registrations still require a fixed package, enforced in code. Verified
  end-to-end including exact price-math and package-expiry extension on
  payment (renewing early correctly extends from the current expiry, not
  from today, so no paid days are lost).
- **Pay Bill fixed** (`pages/paybill.php`): previously required a specific
  package/bundle id and errored otherwise, which combined with the nav
  link pointing at Packages, produced exactly the "Pay Bill shows Packages"
  complaint. Now shows the customer's own outstanding invoices/bills with
  a direct Pay Now button when no specific item is given, while still
  supporting the "buy this specific new package" flow when arriving from
  a Subscribe button. Nav link (both `includes/footer.php` and the
  DB-stored `site_menu` row) now points back at the real page.
- **Admin-controlled site visuals** (`admin/manage_site_content.php`,
  `site_content`/`carousel_slides`/`admin_banners` tables): background
  image upload per page, a real homepage carousel (fixes the "repeats
  twice" complaint by construction — verified rendering exactly once),
  and site-wide banners (offers/maintenance notices) shown via
  `includes/header.php`. **[AI Enhancement]**: a subtle animated gradient
  sheen on the hero section for a "modern/animated" feel — see the chat
  response for why a literal 3D/Three.js background wasn't built.
- **Package categories**: `packages.category` (residential/business/
  enterprise) added; one Enterprise-tier package seeded.
- **Dashboard restructured** (`pages/dashboard.php`): new greeting header
  (time-of-day greeting, customer ID, real account status, last login),
  a Quick Actions grid (Recharge, Pay Bill, Support Ticket, Package
  Change, Referral, Store, Speed Test), and WhatsApp/Messenger/Telegram
  quick-connect buttons (configured in Admin → Site Settings). Every
  pre-existing working section (notifications, tickets, referral,
  addons, payment history, security) was kept, not replaced.
- **Bug fix**: `admin/manage_news.php` had a real corruption bug — a
  duplicated, broken "Add Event" form and its processing logic were
  sitting outside any PHP tag pair, so instead of running they printed as
  literal garbled text on the page. Removed; the working "Add News" form
  was untouched. Found via a purpose-built scanner that checks for
  PHP-code-shaped text living outside tag pairs.
- **CSRF hardening** extended to 6 admin content pages that had been
  missed in earlier rounds: `manage_ads.php`, `manage_announcements.php`,
  `manage_about.php`, `manage_chatbot_rules.php`, `manage_community.php`,
  `manage_settings.php`, plus `manage_news.php` above.
- **Fixed unescaped output** (XSS gap) in `admin/manage_settings.php` —
  every settings field was echoed without `h()`.
- Added `messenger_link`/`telegram_link` to `settings`, alongside the
  existing `support_phone` (now used for a real WhatsApp deep link).
- **Critical fix — a second, more serious source of the duplicate-content
  complaint.** While tracing the "content repeats twice" report, found
  that `includes/footer.php`'s "Self Care" nav link (and `logout.php`'s
  redirect) pointed at `pages/selfcare_login.php` — a completely separate,
  older login system with its own session key, no CSRF/2FA/session
  tracking, and a "Sponsored Ads" section **literally duplicated verbatim
  twice on the same page**, which is what customers were actually seeing.
  A second orphaned file, `pages/selfcare_dashboard.php`, had the same
  problem plus references to database columns that don't exist in this
  schema. Both now redirect to the real `pages/login.php`/`dashboard.php`
  system (URLs kept alive for anyone with an old bookmark); the nav link
  and the DB-stored `site_menu` row were updated to point there directly.
  Verified: visiting the old URLs now cleanly redirects, and the nav link
  loads the real, tested dashboard.

## What was deliberately not built this round, and why
- **Literal 3D/Three.js animated backgrounds** — replaced with a
  lightweight CSS gradient animation. A real 3D engine is a heavy,
  separate scope (performance budget, asset pipeline, mobile fallback)
  that deserves its own dedicated pass, not a bolt-on.
- **Live network telemetry** (connection uptime, latency, packet loss,
  fiber/ONU/router status) — building this as static or random UI would
  mean showing customers false information about their real connection,
  which is actively harmful if their internet is actually down. This
  needs real integration with your network monitoring/RADIUS system,
  which is outside what a website's database can know.
- **Real "AI Assistant"** — canned pattern-matching responses dressed up
  as AI would be misleading. A genuine assistant needs a real LLM API key
  under your control; happy to scaffold the integration in a future round.
- **Multi-connection accounts, static IP/IPv6 display, loyalty points,
  OTP-gated profile edits** — reasonable future features, added to
  `docs/FUTURE_ROADMAP.md` rather than rushed in.

---

## 🆕 Round 3: Support Tickets, PWA, Recurring Billing, Coverage Map

Scoped deliberately from the much larger "Phase 2-5" wishlist — see the
project owner's own stated priority order (🔴 Critical → 🟡 Important →
🟢 Premium). These four match the explicitly-stated "Important" tier and
were fully buildable without any external API keys or paid services.

- **Support Ticket System** (`pages/tickets.php`, `pages/ticket_view.php`,
  `admin/manage_tickets.php`, `admin/ticket_view.php`): department, priority,
  SLA target time, status workflow, customer replies, admin replies, and
  private internal notes (verified hidden from customers in live testing).
- **PWA**: `manifest.json`, `service-worker.js`, `offline.html`, generated
  proper 192×192/512×512 app icons from the existing logo, install-app
  button, offline fallback page. The site is now installable to a phone's
  home screen and keeps working (for previously-visited pages) with no
  connection.
- **Recurring Billing Automation** (`scripts/generate_recurring_invoices.php`):
  cron-ready script that generates each customer's next invoice automatically
  based on their `billing_cycles` record, and applies a 5% late fee to
  invoices still pending after a 7-day grace period. Verified idempotent
  (won't double-bill or double-charge a late fee on repeated runs) and
  verified against real math (1499 BDT package → exactly 1573.95 BDT after
  the 5% late fee).
- **Coverage Map upgrade** (`pages/coverage_map.php`): switched from plain
  markers to color-coded status markers + legend, illustrative service-radius
  circles around live POPs, and a free "Check My Coverage" address search
  (OpenStreetMap Nominatim geocoding — no API key, no billing, unlike Google
  Maps) that finds the nearest coverage area and its distance.
- Fixed an XSS gap in `pages/emergency.php` (admin-controlled fields were
  echoed without escaping).
- **System Updates page** (`admin/system_updates.php`): a safe, one-click
  way to apply future database updates — addresses the "make future updates
  a one-click firmware-style process" request in a way that doesn't open a
  file-upload/remote-code-execution hole. It only ever runs `.sql` files
  that already ship inside this codebase's `migrations/` folder (tracked
  via the new `schema_migrations` table), never an uploaded file or
  arbitrary input. Verified end-to-end against a simulated older install:
  correctly detected pending migrations and applied them safely.
- **Stripe and PayPal gateway adapters** added, closing a gap against the
  explicit Phase 1 payment list (they'd been missed in the original 7-gateway
  build). Both are fully coded to their real API contracts and wired into
  checkout identically to every other gateway. While researching their
  configuration requirements, verified directly against Stripe's and
  PayPal's own current documentation that **neither can actually be used by
  a Bangladesh-registered business** — this is a country-eligibility
  restriction on their end, not a missing-credentials gap like the other
  seven gateways. Full detail in `docs/INCOMPLETE_FEATURES.md`.

### Explicitly deferred this round (see FUTURE_ROADMAP.md)
CRM/sales pipeline, WhatsApp/Telegram notifications, live network
ping/traceroute monitoring, Ookla-grade speed testing, LLM-backed AI
assistant, bilingual voice assistant, and any visual/UI redesign — each
needs either external credentials, infrastructure this codebase can't
provide on its own, or a deliberate separate decision given it would
conflict with the project's own "keep UI unchanged" rule. Reasoning for
each is in `docs/FUTURE_ROADMAP.md`.



### Authentication
- **Registration** (`pages/register.php`): server-side validation, duplicate-email
  check, CSRF protection, generates each user a referral code, sends an email
  verification link.
- **Email verification** (`pages/verify_email.php`): token-based, 24h expiry,
  hashed token storage (`email_verifications` table).
- **Phone OTP verification** (`pages/verify_phone.php`, `includes/lib/Otp.php`):
  6-digit codes, hashed storage, 10-minute expiry, rate-limited (max 3 per 10 min),
  max 5 verification attempts per code.
- **Forgot / Reset password** (`pages/forgot_password.php`, `pages/reset_password.php`):
  doesn't reveal whether an email is registered, 1-hour token expiry, revokes all
  active sessions on successful reset.
- **Remember Me**: selector/validator cookie pattern (industry standard — never a
  single guessable cookie), 30-day expiry, stored hashed in `user_sessions`.
- **2FA** (`includes/lib/TwoFactor.php`, `pages/account_security.php`): full RFC 6238
  TOTP implementation (Google Authenticator / Authy compatible), QR-code setup,
  10 recovery codes, verified against the official RFC test vector.
- **Session timeout**: configurable idle timeout (`SessionManager::touch()`,
  default 30 minutes) — auto-logout on the server side, not just the client.
- **Login history** (`login_history` table): every attempt (success/failed/locked)
  logged with IP + device.
- **Active Sessions / Device Management** (`pages/account_security.php`): lists
  every device currently logged in, sign out individually or "sign out all others".
- **Account lockout**: 5 failed attempts locks the account for 15 minutes.

### Customer Dashboard (`pages/dashboard.php`)
Current package, expiry date + remaining days, current bill with a Pay Now link,
full payment history, invoice download, active add-ons, upgrade/downgrade package
(`pages/package_action.php`), pause/resume connection, change password & 2FA
(links to Account Security), update profile (`pages/profile.php`), notification
center, and a "Refer a Friend" panel with a copyable referral link.

### ISP Package Page (`pages/packages.php`)
Live client-side search, price-range slider, speed filter, server-side sort,
Compare-packages modal, Popular / New / Limited-Offer badges, live countdown
timers for time-boxed offers, cashback display, EMI monthly-breakdown display,
per-package FAQ accordion, per-package video embed, customer reviews with
average rating, and JSON-LD structured data for SEO.

### Checkout (`pages/checkout.php`)
Order summary → coupon/promo code → referral code → tax calculation → terms
acceptance → email OTP confirmation → payment method selection → order
placement, all as one guided flow against a single source of truth (the
invoice's `meta` JSON records exactly what was applied, for auditability).

### Payments (`includes/lib/PaymentGateways/`)
A `GatewayInterface` with seven adapters — **SSLCommerz, bKash, Nagad, Rocket,
Upay, Card (Visa/Mastercard), and Manual transfer** — all wired into checkout
and the admin billing tools. Every attempt is logged to a new gateway-agnostic
`payment_transactions` ledger. See **INCOMPLETE_FEATURES.md** for exactly what
each one needs to go live.

## 🔧 Bugs fixed in this round
- `admin/manage_coverage.php`: fatal `TypeError` from `json_decode()` on a NULL
  DB column — crashed the page on every visit.
- `admin/insert_demo.php`: had **no login check at all** — anyone with the URL
  could silently overwrite real users/packages with demo data. Quarantined.
- 4 admin pages used a mismatched CSS layout class, causing a ~40px content
  shift versus every other admin page ("glitchy" layout).
- Both seeded non-superadmin accounts had broken password hashes and could
  never log in — fixed via `migrations/2026_07_24_database_fixes.sql`.
- The site-wide top-nav "Pay Bill" link (stored in the `site_menu` DB table)
  pointed at `paybill.php` with no package selected — guaranteed error on
  every click. Fixed at the data level.
- `pages/dashboard.php`: empty `usage_history` data produced invalid inline
  JavaScript (`var problemsCount = ;`), breaking every script on the page for
  every user.
- `admin/submit_review.php` was a **public-facing** endpoint mistakenly placed
  under `/admin/` while `packages.php` linked to `/pages/submit_review.php` —
  guaranteed 404 on every review submission. Moved to the correct path.
- `admin/manage_users.php` had all its backend logic but was missing its HTML
  entirely — the page rendered blank. Built out the full add/edit/search/
  pause/delete UI.
- Duplicate `function e()`/`h()` declarations across 11 files causing sitewide
  fatal "Cannot redeclare" errors (from the earlier bug-fix round).
- SQL-injection-shaped string interpolation in `admin/manage_billing.php`,
  now fully parameterized.

## 🗄️ Database migrations (run in order — see DEPLOYMENT_GUIDE.md)
1. `2026_07_23_performance_and_cleanup.sql` — indexes, duplicate-key cleanup.
2. `2026_07_24_database_fixes.sql` — broken admin hashes, broken nav link,
   duplicate seed rows.
3. `2026_07_25_feature_upgrade.sql` — every new table/column the Auth/
   Dashboard/Checkout/Payments round needed (12 new tables, ~15 new columns
   on `users`/`packages`). 100% additive.
4. `2026_07_26_tickets_and_billing.sql` — `tickets`, `ticket_messages`,
   `billing_cycles` tables.
5. `2026_07_27_schema_migrations_tracker.sql` — the `schema_migrations`
   tracking table that powers `admin/system_updates.php`, backfilled with
   every migration up to this one.
6. `2026_07_28_duration_plans_and_site_content.sql` — `duration_plans`,
   `site_content`, `carousel_slides`, `admin_banners` tables, package
   categories, an Enterprise package seed, and the Pay Bill/Self Care nav
   link fixes.

All six are idempotent — safe to re-run if you're not sure which have
already been applied. Going forward, applying new ones is one click via
**Admin → System Updates** instead of manual SQL imports.
