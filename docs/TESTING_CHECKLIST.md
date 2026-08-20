# Testing Checklist

Everything below was executed at least once against a real MySQL + PHP
server during development (not just read through). Re-run this list on
your actual live/staging server before announcing the new features, since
hosting environments differ (PHP version, extensions installed, mail
transport).

## Authentication
- [ ] Register a new account → verification email arrives → link verifies
      the account
- [ ] Try registering with an email that already exists → clear error, no
      duplicate account created
- [ ] Log in with correct credentials → lands on dashboard
- [ ] Log in with wrong password 5 times → account locks for 15 minutes,
      6th attempt shows the lockout message even with the correct password
- [ ] "Remember me" checked → close browser, reopen → still logged in
- [ ] Enable 2FA → scan QR with an actual authenticator app → confirm code
      accepted → log out → log back in → 2FA challenge appears → correct
      code from the app logs you in
- [ ] 2FA: use a recovery code instead of the app code → works once, then
      that specific code is rejected on reuse
- [ ] Forgot password → email arrives → reset link works → new password
      logs in → old password no longer works → you're logged out of all
      previously active sessions
- [ ] Account Security page: see all active sessions/devices → "sign out"
      one → that device is actually logged out on its next request
- [ ] Verify phone via OTP → correct code verifies → wrong code shows error
      without verifying

## Customer Dashboard
- [ ] Current package, expiry date, and remaining days show correctly for a
      user with a package assigned
- [ ] A user with no usage_history data doesn't see a broken page (this was
      a real bug that was fixed — confirm it stays fixed)
- [ ] Pause connection → status updates → Resume connection → status reverts
- [ ] Referral code and shareable link display, and "Copy Link" actually
      copies
- [ ] Notification list shows and "mark read" works

## Package Page
- [ ] Search box filters the visible cards live, without a page reload
- [ ] Price slider and speed filter narrow the results
- [ ] Compare button adds a package to the comparison modal
- [ ] A package with `offer_ends_at` in the future shows a live countdown
- [ ] Submitting a review redirects back to the package page with a
      confirmation, and the review appears in `admin/manage_reviews.php` as
      **pending** (not auto-published)

## Checkout
- [ ] Selecting a package from the package page lands on checkout with the
      right item and price
- [ ] Applying a valid coupon reduces the total correctly; an expired/invalid
      one shows an error and doesn't change the total
- [ ] Applying your own referral code is rejected; a different user's valid
      code applies a discount
- [ ] Tax is calculated on (subtotal − discounts), not on the raw subtotal
- [ ] Unchecking the terms checkbox blocks progress with a clear message
- [ ] OTP email arrives, correct code advances to payment method selection,
      wrong code doesn't
- [ ] Placing an order creates exactly one invoice — reload/resubmit
      shouldn't create duplicates for the identical item within the same
      session

## Payments
- [ ] With `config/services.php` unconfigured, every gateway option
      completes via the dev-mode "simulate" flow and correctly marks the
      invoice paid
- [ ] Once you configure real SSLCommerz sandbox credentials, checkout
      actually redirects to SSLCommerz's real hosted page (not the simulate
      page) — confirms your credentials are being picked up
- [ ] A failed/cancelled payment doesn't mark the invoice as paid

## Admin Panel
- [ ] Log in as each of the 3 seeded admin accounts with the reset password
- [ ] Manage Users: add, edit, search, pause, delete all work
- [ ] Manage Coupons: create a coupon, deactivate it, confirm it's rejected
      at checkout once inactive
- [ ] Manage Billing: create a bill for a user, mark it paid, confirm the
      user sees it in their dashboard

## Regression (confirm nothing pre-existing broke)
- [ ] Every public page still loads (run `php scripts/smoke_test.php`
      against a locally running copy)
- [ ] Every admin page still loads for a logged-in admin
- [ ] `php -l` reports no syntax errors on any `.php` file (a one-liner:
      `find . -name "*.php" | xargs -n1 php -l | grep -v "No syntax errors"`
      should print nothing)
