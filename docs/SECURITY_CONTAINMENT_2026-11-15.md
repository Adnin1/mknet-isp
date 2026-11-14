# MK Net ISP — Security Containment Note (2026-11-15)

## Scope and evidence

This note describes findings from the currently published GitHub `main` source as inspected on 2026-11-15. It does not claim that the local Antigravity V7 candidate, production server, or production database has been updated.

## Confirmed P0/P1 findings in the old public main source

1. `scripts/auto_sync_live.py` contained FTP credentials and hard-coded workstation paths. It uploaded files, staged the entire working directory, committed, and pushed directly to `main`. Upload exceptions were swallowed, while the final output could still claim synchronization succeeded.
2. `config.php` contained production database credentials in tracked source.
3. The request-time schema bootstrap reset the `admin` account password to a known default hash whenever that account existed.
4. The public payment callback accepted simulated `*_sim` callbacks and each of the bKash/Nagad/SSLCommerz adapters treated a caller-provided simulation status as success.
5. Direct Nagad callback handling trusted a status string and did not verify a provider response signature.

## Containment changes in this branch

- Production database profiles now come from ignored `config/database.php` or `MKNET_DB_*` environment variables. Production mode is explicit through `MKNET_ENV=production`; it does not infer production from the client-controlled Host header.
- Added `config/database.example.php` as a non-secret template and ignored `config/database.php`.
- Removed automatic default admin creation/password reset from ordinary web requests. Added CLI-only one-time provisioning using owner-provided environment variables.
- Replaced the old auto-sync script with a read-only PHP syntax auditor. It cannot FTP, stage files, commit, or push.
- Simulation callbacks now require explicit `APP_DEBUG === true` and a loopback client address.
- Direct Nagad callbacks now fail closed until cryptographic provider-response verification is implemented.

## Limitations — do not call release-ready

- This branch has not been merged, and no code has been deployed to InfinityFree.
- Secrets already committed to Git history remain compromised until the owner rotates them. Removing secrets from a later commit does not revoke old credentials.
- Owner must rotate hosting/cPanel/FTP and database credentials, inspect any reused password, and then configure fresh credentials in the hosting provider's private configuration.
- Direct Nagad payment is intentionally unavailable until an approved provider signature-validation implementation and sandbox tests exist.
- Existing payment settlement still requires a reviewed atomic/idempotent invoice + payment-attempt service, amount/currency/order binding and concurrency tests. This branch does not claim those fixes.
- A production database backup and isolated restore must be performed before any production migration or deployment.
- Local V7 release candidate and sanitized archive/bundle referenced in the owner handover are not present in this GitHub repository. They must be compared against this branch and against the actual live host before release.

## Required owner actions

1. Rotate exposed FTP/hosting and database credentials immediately; do not reuse old or shared credentials.
2. Create `config/database.php` on the host from `config/database.example.php` using the new credentials, with file permissions restricted to the hosting account.
3. Validate and merge this branch only after CI passes and an independent code review is complete.
4. Export a fresh production database backup and verify restore in an isolated database.
5. Complete the payment settlement remediation and sandbox certification.
6. Deploy only through a reviewed release package, then run authenticated admin/customer UAT.
7. Clean sensitive Git history only after credential rotation, archive preservation, and owner-approved coordinated rewrite. Do not force-push until all clones, forks, releases and collaborators are considered.

## Status vocabulary

- PASS: independently executed verification passed on a stated commit/environment.
- FAIL: a reproducible check failed.
- BLOCKED_EXTERNAL: requires owner account, provider, domain or financial institution action.
- NOT_VERIFIED: the target environment was not accessible for a test.
- DEFERRED: explicitly postponed.

Do not mark production sealed while any external commissioning gate remains open.
