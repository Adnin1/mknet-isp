# Security Report

## What's hardened now

| Area | Before | After |
|---|---|---|
| Passwords | Mixed — some plaintext comparisons, some bcrypt | `password_hash()`/`password_verify()` everywhere; 2 of 3 admin accounts had unusable/broken hashes, now fixed |
| SQL queries | Several raw string-interpolated queries (real injection risk in `manage_billing.php`) | Parameterized (`PDO::prepare`) throughout every file this project touched |
| CSRF | None anywhere | `csrf_token()`/`csrf_field()`/`csrf_verify()` on every state-changing form this project added or touched (registration, login, checkout, password changes, 2FA, coupon admin) |
| Session cookies | Defaults | `httponly`, `samesite=Lax` set globally in `config.php` |
| Session fixation | Not addressed | `session_regenerate_id(true)` on every login and password reset |
| Brute force | Unlimited login attempts | 5 attempts → 15-minute lockout, all attempts logged to `login_history` |
| OTP/2FA codes | N/A (didn't exist) | Stored as `password_hash()` hashes, never plaintext; single-use; rate-limited |
| Remember-me | N/A | Selector/validator split-token pattern — a stolen cookie alone isn't enough, and the DB never stores the raw validator |
| Debug/setup scripts | `test_db.php`, `test_hash.php`, `mknet_setup.php`, `admin/hash.php`, `admin/insert_demo.php` (no auth check!) all publicly reachable | Quarantined into `_DEV_TOOLS_DO_NOT_UPLOAD/`, excluded from the deployable package |
| Error disclosure | Raw DB error messages shown to visitors on connection failure | `APP_DEBUG` flag in `config.php` — set `false` in production and visitors see a generic message while the real error still gets logged |
| Payment callbacks | N/A | SSLCommerz adapter re-validates every callback server-side via their Validation API rather than trusting the redirect query string (which SSLCommerz's own docs warn can be spoofed) |

## Verified, not just written
Every claim above was exercised against a real MySQL + PHP server in this
session, not just read-through — including a full registration → email
verify → 2FA enrol → logout → 2FA-challenge login loop, and a full coupon →
referral → OTP → payment-gateway checkout loop. The TOTP implementation was
checked against the official RFC 6238 Appendix B test vector byte-for-byte.

## Known gaps / recommended next steps
1. **CSRF coverage is not exhaustive.** It's on every form this engagement
   added or edited. Several pre-existing admin forms (e.g. `manage_packages.php`,
   `manage_team.php`) still don't have it. Lower risk (single trusted admin
   operator, not public-facing) but worth a follow-up pass — pattern to copy
   is already in `admin/manage_coupons.php`.
2. **Rate limiting is per-feature, not global.** OTP issuance and login
   attempts are rate-limited; there's no IP-level rate limiter in front of
   the whole site. Consider this at the web-server (Nginx/Apache) or
   Cloudflare level rather than in PHP.
3. **`config/services.php` will contain real secrets once you fill it in.**
   Make sure your `.gitignore` excludes it (a starter one is included) — only
   `config/services.example.php` should ever be committed.
4. **HTTPS is assumed but not enforced in code.** Once your SSL certificate
   is live, uncomment the `session.cookie_secure` line in `config.php` and
   add an HTTP→HTTPS redirect at the web-server level.
5. **File upload handling** (e.g. profile photos, if you add them later)
   wasn't in scope for this round — if you add upload features, they need
   their own validation (type/size/re-encoding) before going live.
6. **No Web Application Firewall / intrusion detection** is configured — this
   is a hosting-level concern, not something PHP code can provide.
