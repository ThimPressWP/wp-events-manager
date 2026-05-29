# Tasks

## Current Priority

- [x] Synchronize version metadata across `wp-events-manager.php`, `readme.txt`, and `WPEMS_VER`.
- [ ] Confirm BCMath is available in supported hosting environments or add guarded fallbacks for every direct `bc*()` call.
- [ ] Build assets and verify runtime paths, especially `assets/dist/js`, `assets/dist/css`, admin bookings assets, and admin coupons assets.
- [ ] Verify the table-backed checkout flow end to end for free, manual, check, PayPal, and Stripe bookings.
- [x] Add and verify a central browser-return dispatcher for `wpems_gateway` query args.
- [ ] Test the booking migration page and WP-CLI migration commands against a backed-up copy of real legacy `event_auth_book` data.

## Bugs or Risks

- [ ] `assets/dist` files were not detected in the inspected tree, but frontend runtime code references built files under `assets/dist`.
- [ ] Admin booking and coupon managers enqueue `assets/css/admin/...` and `assets/js/admin/...`; verify those paths exist or are generated in release builds.
- [ ] Registration nonce action uses the typo `auth-reigter-nonce` in both template and handler; it works as written but should be documented before any rename.
- [ ] Payment webhooks are public by design; verify Stripe signing secret, PayPal IPN validation, and idempotency behavior before release.
- [ ] SQL PHPCS prepared-SQL sniffs are disabled in `phpcs.xml`; custom SQL should get a manual review.

## Security Improvements

- [ ] Whitelist expected checkout `meta` keys instead of accepting arbitrary sanitized metadata.
- [ ] Add rate limiting or abuse protection for public AJAX checkout, quote, coupon, login, and registration endpoints.
- [ ] Review all public templates for raw translation output such as `_e()` and raw `the_title()` calls.
- [ ] Ensure gateway secrets are masked in admin screens and never logged.
- [ ] Add an explicit security test for unauthenticated access to admin-only AJAX and admin-post actions.
- [ ] Document webhook setup steps for Stripe and PayPal, including signature/IPN verification expectations.

## Refactor Ideas

- [ ] Add a dedicated payment return router to match the existing webhook router.
- [ ] Centralize checkout request validation into a request DTO or value object.
- [ ] Reduce duplication between legacy `WPEMS_Shortcodes` and namespaced `WPEMS\ShortCodes\EventShortCodes`.
- [ ] Standardize asset enqueue paths so source, built, and release assets follow one convention.
- [ ] Keep legacy class aliases but move new behavior into namespaced classes where possible.

## Testing Tasks

- [ ] Run `composer lint`.
- [ ] Run `vendor/bin/phpunit`.
- [ ] Run `npm run lint:js`.
- [ ] Run `npm run build`.
- [ ] Smoke test activation, deactivation, and reactivation on a clean WordPress site.
- [ ] Smoke test event archive, single event, booking form, account bookings, and shortcodes.
- [ ] Smoke test admin settings, event metaboxes, bookings page, coupons page, and migration tool.
- [ ] Test webhooks with Stripe CLI and PayPal sandbox/IPN test data.
- [ ] Test WP-Cron hooks and `wp wpems bookings` WP-CLI commands.

## Done

- [x] Main plugin bootstrap exists in `wp-events-manager.php`.
- [x] Composer PSR-4 autoload maps `WPEMS\` to `inc/`.
- [x] Custom table name registry and schema manager exist.
- [x] Repository, model, service, payment, migration, cron, CLI, admin booking, and admin coupon classes exist.
- [x] PHPUnit test suite exists under `tests/Unit`.
- [x] Booking migration admin page and AJAX endpoints exist.
- [x] Frontend checkout AJAX handlers exist.
- [x] Payment gateway registry and webhook router exist.
