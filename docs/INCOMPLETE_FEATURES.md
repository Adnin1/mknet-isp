# Features That Need Your Credentials to Finish

Everything in this document is **fully coded and tested in dev-mode** — the
logic, database, and UI all work today. What's missing in every case is a
credential only you (as the business owner) can obtain, because it requires
your legal business identity, a bank account, or a phone number/domain you
control. This is not something that could be worked around — every one of
these providers requires proof of business ownership before issuing API
access, by design (fraud prevention).

---

## 1. Email sending (SMTP)
**What works now:** every email (verification, password reset, checkout OTP,
payment confirmation) is generated correctly and logged in full to
`storage/logs/mail.log` — you can read the exact content that would have
been sent.

**What's missing:** real SMTP server credentials.

**Why it couldn't be done automatically:** SMTP requires an account with a
mail provider (or your own mail server) tied to your domain — there's no
generic "test" SMTP server that would deliver to real inboxes.

**How to complete it:**
1. Pick a provider: your hosting company's SMTP (often included free with
   cPanel hosting), Gmail/Google Workspace, SendGrid, or Mailgun.
2. Get the host, port, username, and password they give you.
3. Open `config/services.php`, fill in the `mail` section.
4. Send yourself a test registration to confirm delivery.

---

## 2. SMS / Phone OTP
**What works now:** OTP generation, hashing, rate-limiting, and verification
are fully functional — codes are logged to `storage/logs/sms.log` in dev
mode so you can test the whole flow today.

**What's missing:** a contracted SMS gateway account.

**Why it couldn't be done automatically:** Bangladeshi SMS gateways (Alpha
SMS/BulkSMSBD, SSL Wireless, REVE) all require a signed merchant agreement
and typically a registered Sender ID (your business name showing as the SMS
sender) approved by the telecom regulator — this is a multi-day business
process, not an API call.

**How to complete it:**
1. Sign up with a provider (Alpha SMS and SSL Wireless are the two most
   commonly used by Bangladeshi businesses for this purpose).
2. Get your API key, sender ID, and their API endpoint URL.
3. Fill in the `sms` section of `config/services.php`.
4. If their request format differs from the generic implementation in
   `includes/lib/Sms.php`, adjust `sendViaHttp()` there to match their
   documented format (most Bangladeshi providers use a simple GET/POST
   with `api_key`, `senderid`, `number`, `message` — already what's coded).

---

## 3. SSLCommerz (recommended starting point for payments)
**What works now:** the full Session API + Validation API integration is
coded exactly to their published spec, and the checkout flow calls it
correctly. In dev mode, checkout uses a "Simulate Successful Payment"
substitute so you can test the whole purchase flow today.

**What's missing:** a store_id and store_passwd.

**Why it couldn't be done automatically:** even their free sandbox requires
signing up with your email/phone — I have no way to create an account on
your behalf, and this sandbox environment has no network access to
sslcommerz.com to test against even if I did.

**How to complete it:**
1. Sign up free at https://developer.sslcommerz.com — sandbox credentials
   are issued instantly, no business verification needed for testing.
2. Put the sandbox `store_id`/`store_passwd` into `config/services.php`,
   `sandbox => true`.
3. Test a full purchase — you should be redirected to SSLCommerz's real
   sandbox checkout page (not the simulate page).
4. When ready for real money, apply for a live merchant account (this part
   *does* require business documents — trade license, bank account) and
   swap in the live credentials with `sandbox => false`.

**Why this is the recommended starting point:** one integration here gives
you bKash, Nagad, Rocket, Upay, Visa, and Mastercard all at once through
their hosted checkout page, instead of separately integrating and
maintaining 5+ individual gateway relationships.

---

## 4. bKash / Nagad direct integration
**What works now:** both adapters are coded to each provider's published API
contract (bKash's Tokenized Checkout token-grant/create/execute flow; Nagad's
RSA-signed initialize/complete flow).

**What's missing:** merchant credentials from each provider directly.

**Why it couldn't be done automatically:** same reason as SSLCommerz —
business-identity-gated signup, and no network access from this environment
to verify against their live sandboxes.

**How to complete it:** only pursue this if you specifically want a direct
relationship instead of going through SSLCommerz (e.g. a negotiated lower
transaction fee at higher volume).
- bKash: apply at https://developer.bka.sh
- Nagad: apply via your bank or nagad.com.bd's business portal
Fill in the respective sections of `config/services.php`.

**Important**: because these were coded without the ability to test against
a live sandbox, **test thoroughly against each provider's own sandbox before
processing real transactions** — the request/response shapes are correct
per their documentation, but documentation and live behavior occasionally
drift, and this is the one category of bug that costs real money if wrong.

---

## 5. Rocket / Upay / Visa / Mastercard direct integration
**What works now:** these route through the SSLCommerz adapter (item 3
above) by default, which is the standard way small/medium merchants accept
these payment types in Bangladesh anyway.

**What's missing:** nothing, if you're fine going through SSLCommerz (most
businesses are). Direct integration with Rocket (DBBL) or Upay (UCB)
requires a bilateral bank agreement, which is a relationship-based business
process, not something with a self-serve API.

---

## 6. Stripe and PayPal — different category of limitation, verified
These two are in Phase 1's payment list, so they're covered here for
completeness, but they're fundamentally different from every gateway
above: **this isn't a "get an API key" gap — it's a country-eligibility
block I verified directly against Stripe's and PayPal's own current
documentation** while building this.

- **PayPal**: does not support BDT as a payment currency at all (checked
  PayPal Developer's official supported-currency list — ~25 currencies,
  BDT isn't one). Separately, Bangladesh is not a supported country for
  opening a standard PayPal *Business* account — this is a country-level
  restriction, not something resolved by signing up correctly.
- **Stripe**: Bangladesh is not among Stripe's ~46 fully-supported
  countries for opening a merchant account at all (verified against
  Stripe's current supported-countries documentation). Unlike PayPal,
  Stripe's systems do technically support BDT as a currency — but that's
  moot if the account itself can't be opened.

**The common workaround both companies' own documentation points to**:
incorporate a business entity in a supported country (US, UK, an EU
country, etc.) and open the account under that entity. This is a real
option some Bangladeshi businesses use, but it's a legal/business
decision — company formation, foreign banking, tax implications — not
something to decide inside a codebase.

**What I built anyway**: both `StripeGateway.php` and `PaypalGateway.php`
are fully coded to each provider's real API contract (Stripe Checkout
Sessions; PayPal Orders API v2 with OAuth2), wired into checkout the same
as every other gateway, and tested in dev-mode simulate flow. If you ever
do set up a foreign-entity account, they're ready to configure. Until then,
treat these two as **not realistically available for a Bangladesh-registered
MK Net**, and lean on SSLCommerz/bKash/Nagad for domestic customers — the
international customer case (if you ever have one) is really the only
scenario where chasing a foreign entity for Stripe/PayPal would be worth it.

---

## Summary table

| Feature | Code status | What you need to provide | Where to enter it |
|---|---|---|---|
| Email verification / reset / notifications | ✅ Tested, dev-mode logs the content | SMTP host/user/pass | `config/services.php` → `mail` |
| Phone OTP | ✅ Tested, dev-mode logs the code | SMS gateway API key | `config/services.php` → `sms` |
| SSLCommerz payments | ✅ Coded to spec, untestable here (no network access) | store_id / store_passwd | `config/services.php` → `sslcommerz` |
| bKash direct | ✅ Coded to spec, untestable here | app_key/secret, username/password | `config/services.php` → `bkash` |
| Nagad direct | ✅ Coded to spec, untestable here | merchant_id + RSA keys | `config/services.php` → `nagad` |
| Rocket / Upay / Cards | ✅ Works via SSLCommerz automatically | (same as SSLCommerz) | — |
| Stripe | ✅ Coded to spec | ⚠️ Needs a foreign-entity business — not just an API key | `config/services.php` → `stripe` |
| PayPal | ✅ Coded to spec | ⚠️ Needs a foreign-entity business — not just an API key | `config/services.php` → `paypal` |
