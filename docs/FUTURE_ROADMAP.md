# Future Roadmap

Ordered roughly by impact-to-effort ratio, not strict priority — pick based
on your actual business needs.

## On the "premium redesign" and "AI as the heart of the site" requests
These come up a lot in the project brief, so they deserve a direct, honest
answer rather than being buried in a bullet list.

**Full visual redesign (glassmorphism, 3D, particle effects, cinematic
motion design):** technically buildable, but it directly conflicts with
this same project's own stated rule ("NEVER change the UI unless
necessary," "keep design unchanged"). It also touches literally every page
at once, which is the highest-risk kind of change to make without a
deliberate, explicit go-ahead — a rushed redesign is exactly how a stable,
tested site gets broken. Recommendation: treat this as its own dedicated
project with a clear before/after mockup review step, not a bullet point
folded into a backend feature round.

**AI assistant that understands natural language and takes actions**
("what's my bill," "upgrade my package"): the architecture for this is
straightforward — a chat widget that POSTs to a PHP endpoint, which calls
a real LLM API (Claude or OpenAI) with the user's real account context and
a defined set of safe actions it's allowed to take (view bill, view
package, create a ticket — not "delete account" or "issue a refund"
without human review). What it needs that can't be substituted is a real
API key and a per-request cost you'd be accepting — this is not something
that can be faked with simple keyword-matching and still honestly be
called "AI support that feels like a real engineer." A keyword-matching
chatbot pretending to be that would be worse than not having one — it
would frustrate customers with wrong answers delivered confidently.

**Voice assistant (Bangla + English):** the most genuinely achievable of
the "premium" asks — the browser's built-in Speech Recognition and Speech
Synthesis APIs are free and require no API key, and this project's
existing `pages/voice.php` already shows there was earlier work in this
direction. Bangla support quality varies by the visitor's browser/OS
(strong on Android Chrome, weaker on some desktop browsers), which is a
platform limitation, not a code limitation. This is a reasonable next
feature to build properly, on its own, rather than as an add-on to an
already-large round.

## On "firmware updates" for the website
A website doesn't have firmware in the router/IoT sense — there's no
separate hardware layer to flash. The actual equivalent, which this
project already does and will keep doing every round, is: **numbered,
idempotent SQL migrations + a file changelog**, so an update can be applied
to a live site without re-deploying everything from scratch and without
risk of double-applying a change. See `migrations/` and
`docs/CHANGELOG.md` — that folder growing over time, one dated file per
release, is the website equivalent of a firmware update history.

## A note on scope, from one developer to another
This project has grown, round over round, from "fix my bugs" to "build a
platform to rival Cloudflare/Starlink/Apple." That's a good ambition to
have for the business — but it's worth saying plainly: what's been built
so far (Phases up through this round) is already a genuinely substantial,
tested, production-capable ISP customer platform. The remaining wishlist
(CRM, live network monitoring, GIS drawing tools, a real AI agent, a full
cinematic redesign) is realistically several more focused engagements, not
one more turn of "finish everything." If you want to keep expanding this
codebase over many sessions with full version control, your own test runs,
and no practical turn-length limits, **Claude Code** (or Claude Cowork for
the non-engineering parts, like content/reports) is genuinely a better fit
for that ongoing work than a single chat thread — this isn't a brush-off,
it's the right tool for a project at this scale.

## High value, low effort

1. **Accessible form labels.** Replace placeholder-only inputs with real
   `<label>` elements across the new forms (see ACCESSIBILITY_REPORT.md).
   A focused half-day task, meaningfully improves usability for everyone,
   not just screen-reader users.
2. **Per-page meta descriptions + robots.txt rules** for authenticated pages
   (see SEO_REPORT.md).
3. **CSRF on the remaining pre-existing admin forms** that predate this
   engagement (`manage_packages.php`, `manage_team.php`, etc.) — the pattern
   to copy is already in `admin/manage_coupons.php`.
4. **A real "resend verification email" button** on the dashboard for users
   who registered but missed/lost the original email, instead of them
   needing to contact support.

## Medium effort, real business value
5. **Admin coupon/referral analytics** — right now you can see coupon usage
   counts in `admin/manage_coupons.php`, but there's no dashboard showing
   "which coupons actually drove revenue" or "top referrers." The data is
   all there (`payment_transactions`, `referrals`, `coupons.used_count`) —
   this is a reporting/UI task, not a data-modeling one.
6. **Automated data retention/cleanup** for `otp_codes` (delete after 7
   days), `login_history` (archive after 1 year), and `payment_transactions`
   `raw_response` blobs (these can get large — consider archiving old raw
   gateway responses to cold storage after the transaction is settled).
7. **Package upgrade/downgrade proration.** Right now, changing package via
   `package_action.php` and checkout creates a fresh invoice at full price;
   a mid-cycle prorated credit for the unused portion of the old package
   would feel more polished for customers switching mid-month.
8. **Real network-level pause/resume.** "Pause Connection" currently only
   flips a status flag in the database — it doesn't (and structurally can't,
   without more information about your infrastructure) actually cut off a
   customer's internet at the router/RADIUS level. If your ISP uses
   FreeRADIUS, Mikrotik, or a similar system, this needs an integration
   between `users.account_status` and that system — worth scoping as its
   own project once you tell me what your network side runs.

## Larger initiatives
9. **Automated testing suite.** Everything in this engagement was verified
   by hand against a real server during development, which is thorough but
   not repeatable by your team going forward. A PHPUnit suite covering the
   auth flows, checkout math, and payment callback handling would catch
   regressions automatically on future changes.
10. **Admin 2FA.** Customer accounts now have full 2FA; admin accounts
    (which have far more destructive power — billing, user data, coupons)
    still only have password + the existing session system. Worth
    prioritizing given admin accounts are the highest-value target.
11. **A proper email templating system.** Right now `Mailer::send()` takes
    raw HTML strings built inline in each calling page. Fine at the current
    scale; if you add many more transactional emails, a shared template
    (header/footer/branding) will keep them consistent and easier to edit.
12. **Multi-admin roles.** The `admins.role` column already distinguishes
    `admin` vs `super`, but no code currently checks it — every admin can
    do everything. If you want e.g. billing staff who can't delete users,
    that permission logic needs to be built.
