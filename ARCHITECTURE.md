# Architecture

## Entry Point

The main plugin file is `wp-events-manager.php`.

Bootstrap order:

1. Prevent direct access when `ABSPATH` is missing.
2. Load `vendor/autoload.php` when present.
3. Bootstrap `WPEMS\Payments\PaymentGatewayRegistry`.
4. Bootstrap `WPEMS\Payments\PaymentWebhookRouter`.
5. Register `WPEMS\BookingSystemBootstrap::init()` on `plugins_loaded`.
6. Register cron activation/deactivation wrappers if `WPEMS\Cron\CronBootstrap` exists.
7. Instantiate the legacy `WPEMS` singleton, define constants, include legacy files, and register hooks.
8. Store the singleton in `$GLOBALS['WPEMS']`.

The codebase intentionally mixes legacy global classes and newer namespaced classes. Composer autoloads namespaced classes from `inc/`.

## Folder Structure

- `inc/`: PHP runtime code.
- `inc/Admin/`: admin menu, settings, metaboxes, booking manager, coupon manager, and migration page.
- `inc/Cron/`: WP-Cron bootstrap.
- `inc/CLI/`: WP-CLI booking commands.
- `inc/Gateways/`: manual, check, PayPal, and Stripe gateway implementations.
- `inc/Payments/`: payment gateway contract, registry, results, checkout result, and webhook router.
- `inc/Repositories/`: custom table data access.
- `inc/Services/`: checkout, quote, tax, coupon, booking status, and payment sync services.
- `inc/Tables/`: custom table names and schema creation/upgrade.
- `inc/Migrations/`: legacy booking migration logic.
- `inc/Integration/`: GDPR, account, and event count integrations.
- `templates/`: frontend, admin, email, notice, loop, and shortcode templates.
- `assets/src/`: source JS and SCSS.
- `assets/dist/`: generated build output expected by runtime enqueue code.
- `tests/`: PHPUnit and Brain Monkey tests.

## WordPress Objects

### Post Types

- `tp_event`: public event post type.
- `event_auth_book`: legacy booking post type, visible in admin but not publicly queryable.

### Taxonomies

- `tp_event_category`
- `tp_event_tag`
- `tp_event_type`

### Booking Statuses

- `ea-cancelled`
- `ea-pending`
- `ea-processing`
- `ea-completed`

## Admin Surface

Main admin parent menu: `tp-event-setting` with label Events Manager.

Detected admin pages:

- Settings: `WPEMS\Admin\SettingsManager`.
- Users: `WPEMS\Admin\Users`.
- Bookings: `WPEMS\Admin\Bookings\AdminBookingManager`.
- Coupons: `WPEMS\Admin\Coupons\AdminCouponManager`.
- Migrate bookings: `WPEMS\Admin\Tools\BookingMigrationPage`.

Settings tabs are provided by:

- `WPEMS\Admin\Settings\General`
- `WPEMS\Admin\Settings\Pages`
- `WPEMS\Admin\Settings\Emails`
- `WPEMS\Admin\Settings\Checkout`

## Frontend Surface

Template loading is handled by `WPEMS_Template` through the `template_include` filter. Public templates are in `templates/`.

Detected shortcodes:

- `[wp_event_list_event]`
- `[wp_event_register]`
- `[wp_event_login]`
- `[wp_event_forgot_password]`
- `[wp_event_reset_password]`
- `[wp_event_account]`
- `[wp_event_countdown]`
- `[wpems_my_bookings]`

The countdown widget class is `WPEMS_Widget_Countdown`.

## AJAX Handlers

Legacy AJAX class `WPEMS_Ajax` registers:

- `event_remove_notice`
- `event_auth_register`
- `event_login_action`
- `load_form_register`

New checkout AJAX class `WPEMS\Frontend\CheckoutAjax` registers:

- `wpems_checkout_quote`
- `wpems_checkout_submit`
- `wpems_checkout_validate_coupon`

Migration admin page AJAX handlers:

- `wpems_migration_count`
- `wpems_migration_run_batch`
- `wpems_migration_verify`
- `wpems_migration_rebuild_inventory`

## REST Routes

REST routes: Unknown / not detected. No `register_rest_route()` calls were found in the inspected plugin PHP files.

## Payment Flow

Payment gateway classes extend `WPEMS\Payments\AbstractPaymentGateway` and are registered by `WPEMS\Payments\PaymentGatewayRegistry`.

Default gateway classes:

- `WPEMS\Gateways\ManualGateway`
- `WPEMS\Gateways\CheckGateway`
- `WPEMS\Gateways\PaypalGateway`
- `WPEMS\Gateways\StripeGateway`

Webhook routes are registered with rewrite rules by `WPEMS\Payments\PaymentWebhookRouter`:

- `/wpems-webhook/paypal-rest/`
- `/wpems-webhook/paypal-ipn/`
- `/wpems-webhook/stripe/`

Browser return URLs include `wpems_gateway` query args in gateway code. `WPEMS\Payments\PaymentReturnRouter` handles those return/cancel requests, delegates to the matching gateway, applies the payment result through `PaymentSyncService`, and redirects to the local order-received URL.

## Cron and CLI

`WPEMS\Cron\CronBootstrap` registers:

- `wpems_sync_pending_gateway_payments`
- `wpems_expire_booking_holds`
- custom schedule `wpems_5min`

Legacy code also schedules `tp_event_cancel_payment_booking` for payment cancellation.

When WP-CLI is active, `WPEMS\BookingSystemBootstrap` registers:

```bash
wp wpems bookings
```

Detected subcommands are implemented as methods on `WPEMS\CLI\BookingCommands`: `migrate`, `verify_migration`, `rebuild_inventory`, `sync_payments`, `expire_holds`, and `mark_paid`.

## Database Usage

Legacy storage uses WordPress posts, post meta, options, and taxonomies.

The table-backed booking system uses nine custom tables through `WPEMS\Tables\TableNames`:

- `wpems_bookings`
- `wpems_booking_meta`
- `wpems_event_inventory`
- `wpems_coupons`
- `wpems_coupon_events`
- `wpems_coupon_usage`
- `wpems_payment_events`
- `wpems_payment_transactions`
- `wpems_payment_sync_queue`

`WPEMS\Tables\SchemaManager` creates and upgrades these tables with `dbDelta()` and stores the schema version in `wpems_schema_version`.

## Data Flow

Admin event management:

1. An administrator creates or edits a `tp_event`.
2. Event metabox data is saved to post meta after nonce and capability checks.
3. Archive and single templates read post data and event meta for public display.

Frontend booking:

1. A visitor opens a single event and the plugin enqueues frontend assets.
2. The booking form requests quotes and coupon validation through AJAX.
3. Checkout submit calls `BookingCheckoutService`.
4. The service creates a table-backed booking, reserves inventory, records coupon usage, and starts gateway checkout when needed.
5. Payment webhooks or cron sync update booking and payment status.
6. Email, account, GDPR, and event count integrations read the updated booking data.

Migration:

1. Legacy `event_auth_book` posts are counted and imported by `BookingMigrator`.
2. The migration page runs batches through admin AJAX.
3. WP-CLI can run the same migration and verification services.
