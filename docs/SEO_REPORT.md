# SEO Report

## What's in place
- Every page renders through the shared `includes/header.php`, which sets
  `<html lang="en">`, a mobile viewport meta tag, and a `<title>` driven by a
  `$pageTitle` variable.
- **Fixed this round**: `login.php`, `checkout.php`, `dashboard.php`,
  `forgot_password.php`, `reset_password.php`, `verify_email.php`,
  `verify_phone.php`, `account_security.php`, and `profile.php` were all
  silently falling back to the generic "MK Net" title — each now sets a
  proper descriptive title (e.g. "Checkout — MK Net").
- `pages/packages.php` emits `JSON-LD` structured data (schema.org
  `Service`/`Offer`) listing every visible package with its price — this
  was already in place and is genuinely valuable for rich snippets in
  Google search results.
- Package images use real `alt` text (the package name).

## Gaps — none of this was in scope for this round, listed so it's tracked
1. **No per-page meta descriptions.** `$pageTitle` is wired up; there's no
   equivalent `$pageDescription` for the `<meta name="description">` tag.
   Cheap to add — same pattern as the title fix above.
2. **Authenticated pages (dashboard, checkout, account settings) shouldn't
   be indexed** but there's no `<meta name="robots" content="noindex">` on
   them, nor a `robots.txt` disallowing `/pages/dashboard.php` etc. Low risk
   (search engines rarely index behind-login pages by accident) but worth
   adding explicitly.
3. **No `sitemap.xml`** was generated for the new dynamic pages
   (`packages.php` package list, `bundles.php`).
4. **No canonical URLs** (`<link rel="canonical">`) — matters if
   `packages.php?sort=price_asc` and `packages.php` could otherwise be seen
   as duplicate content.
5. **Checkout/account pages correctly have no JSON-LD** (they shouldn't —
   that's expected, not a gap).

## Recommendation
Items 1–4 above are all small, mechanical additions (a few lines each) that
would make a good focused follow-up task — none of them required touching
business logic, so they were deprioritized behind the functional
Authentication/Dashboard/Checkout/Payments work this round was scoped for.
