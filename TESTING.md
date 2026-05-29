# Testing

Run automated commands from the plugin root.

## Automated Checks

```bash
composer lint
vendor/bin/phpunit
npm run lint:js
npm run build
```

Additional release build commands:

```bash
npm run makepot
npm run build-makepot-zip
```

## Environment Setup

- [ ] Use WordPress `6.0+`.
- [ ] Use PHP `7.4+`.
- [ ] Confirm the BCMath extension is enabled.
- [ ] Run `composer install`.
- [ ] Run `npm install`.
- [ ] Enable `WP_DEBUG`, `WP_DEBUG_LOG`, and `SCRIPT_DEBUG` during local testing.
- [ ] Confirm built assets exist under `assets/dist/js` and `assets/dist/css` after `npm run build`.

## Activation and Deactivation

- [ ] Activate the plugin from wp-admin and confirm no fatal errors.
- [ ] Confirm the Events Manager admin menu appears.
- [ ] Confirm custom tables are created and `wpems_schema_version` is set.
- [ ] Confirm generated pages for register, login, forgot password, reset password, and account exist or are assigned.
- [ ] Confirm webhook rewrite endpoints work after activation or permalink flush.
- [ ] Confirm WP-Cron events are scheduled for payment sync and hold expiry.
- [ ] Deactivate the plugin and confirm cron events are unscheduled.
- [ ] Reactivate the plugin and confirm existing settings and data remain intact.

## Admin UI

- [ ] Create and edit a `tp_event`.
- [ ] Save quantity, price, start/end date, registration end, schedule toggle, location, and iframe fields.
- [ ] Confirm metabox nonce and capability failures block unauthorized saves.
- [ ] Save General, Pages, Emails, and Checkout settings tabs.
- [ ] Confirm invalid settings input is sanitized or rejected.
- [ ] View the Users page.
- [ ] View the Bookings page, filter/search bookings, open booking detail, run status actions, and export CSV.
- [ ] Create, edit, disable, delete, and bulk-manage coupons.
- [ ] Open the migration page and test count, batch run, verify, and rebuild inventory actions with a backup database.

## Frontend

- [ ] Visit the event archive and test search, location filter, category filter, price filter, date filter, sorting, grid view, and list view.
- [ ] Visit a single event and confirm event details, price, date, location, map area, share links, navigation, and registration area render correctly.
- [ ] Test `[wp_event_list_event]`.
- [ ] Test `[wp_event_register]`.
- [ ] Test `[wp_event_login]`.
- [ ] Test `[wp_event_forgot_password]`.
- [ ] Test `[wp_event_reset_password]`.
- [ ] Test `[wp_event_account]`.
- [ ] Test `[wp_event_countdown]`.
- [ ] Test `[wpems_my_bookings]` as guest and logged-in user.
- [ ] Test the countdown widget if widgets are enabled in the active theme.

## AJAX

- [ ] Confirm legacy `load_form_register` rejects missing or invalid nonce.
- [ ] Confirm legacy `event_auth_register` rejects invalid nonce, invalid event, and invalid quantity.
- [ ] Confirm `event_login_action` handles valid and invalid login attempts.
- [ ] Confirm checkout quote AJAX rejects invalid nonce and missing event ID.
- [ ] Confirm coupon validation handles valid, invalid, expired, inactive, and event-scoped coupons.
- [ ] Confirm checkout submit rejects invalid nonce and short idempotency keys.
- [ ] Confirm repeated checkout submit with the same idempotency key does not create duplicate bookings.

## Payments

- [ ] Test free event checkout.
- [ ] Test manual gateway checkout.
- [ ] Test check gateway checkout.
- [ ] Test PayPal Standard/IPN in sandbox.
- [ ] Test PayPal REST checkout, cancel, capture, webhook, and sync in sandbox.
- [ ] Test Stripe Checkout success, cancel, webhook, refund, and sync in test mode.
- [ ] Verify browser return URLs using `wpems_gateway` for each enabled redirect gateway.

## Cron and CLI

- [ ] Run due payment sync through WP-Cron.
- [ ] Run hold expiry through WP-Cron.
- [ ] Run `wp cron event list | grep wpems` in a local WP-CLI environment.
- [ ] Run `wp cron event run wpems_sync_pending_gateway_payments`.
- [ ] Run `wp cron event run wpems_expire_booking_holds`.
- [ ] Run `wp wpems bookings migrate --dry-run`.
- [ ] Run `wp wpems bookings verify_migration`.
- [ ] Run `wp wpems bookings rebuild_inventory`.
- [ ] Run `wp wpems bookings sync_payments`.
- [ ] Run `wp wpems bookings expire_holds`.

## Database and Migration

- [ ] Back up the database before migration tests.
- [ ] Confirm all nine custom tables exist.
- [ ] Confirm legacy bookings map to new booking rows with `legacy_post_id`.
- [ ] Confirm migration is idempotent when a batch is repeated.
- [ ] Confirm failed migration rows are reported and do not stop the whole batch.
- [ ] Confirm inventory counters match migrated booking totals.
- [ ] Confirm coupons, coupon usage, payment transactions, and sync queue records are created as expected.

## WP_DEBUG Review

- [ ] Browse admin and frontend screens with `WP_DEBUG_LOG` enabled.
- [ ] Confirm there are no PHP notices, warnings, deprecations, or fatal errors.
- [ ] Confirm AJAX failures return JSON and do not emit PHP warnings before JSON.
- [ ] Confirm webhook failures return the expected HTTP status without exposing secrets.
