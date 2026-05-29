# Security Notes

This is a documentation-only audit of the inspected codebase. No code has been changed.

## Current Posture

### Direct File Access

Most inspected PHP files include `defined( 'ABSPATH' ) || exit;` or an equivalent direct-access guard.

### Nonces and Capabilities

Detected protections:

- Admin settings use nonce action `tp-event-settings` and capability `manage_options`.
- Event and legacy booking metabox saves verify nonces and `edit_post`.
- Admin migration AJAX uses nonce action `wpems_migration` and capability `manage_options`.
- Admin booking actions and CSV export use nonces and `manage_options`.
- Admin coupon save, delete, toggle, and bulk actions use nonces and `manage_options`.
- Legacy booking/register AJAX uses WordPress AJAX nonces.
- Checkout AJAX uses nonce action `wpems_checkout`.

### Sanitization and Validation

The code commonly uses `sanitize_text_field()`, `sanitize_key()`, `sanitize_email()`, `absint()`, `wp_unslash()`, allowlisted order fields, and settings sanitize callbacks. Settings fields are normalized and saved through `WPEMS\Admin\SettingsManager`.

### Escaping

Most inspected admin and frontend templates use `esc_html()`, `esc_attr()`, `esc_url()`, or `wp_kses_post()`. Some older templates still use raw translation helpers such as `_e()` and `the_title()` output in public markup, so a full escaping pass is still recommended.

### SQL Safety

Repositories and data services usually use `$wpdb->prepare()` and WordPress insert/update/delete helpers. Custom table names come from `WPEMS\Tables\TableNames`.

Risk: `phpcs.xml` disables `WordPress.DB.PreparedSQL.NotPrepared` and `WordPress.DB.PreparedSQL.InterpolatedNotPrepared`, so PHPCS will not catch every SQL issue. Dynamic SQL with trusted table and column names still needs manual review.

### AJAX Security

Public AJAX endpoints exist for login, registration, checkout quote, coupon validation, and checkout submit. These use nonces and sanitization, but no rate limiting or abuse throttling was detected.

Admin AJAX endpoints for migration are capability and nonce protected.

### REST API

REST routes: Unknown / not detected.

### Webhooks

Webhook endpoints are public by design and do not use WordPress nonces:

- `/wpems-webhook/paypal-rest/`
- `/wpems-webhook/paypal-ipn/`
- `/wpems-webhook/stripe/`

Stripe webhook handling verifies signatures through the gateway. PayPal IPN handling delegates verification to the PayPal gateway. Keep raw payload handling intact and test replay/idempotency behavior before release.

### Options and Metadata

Plugin settings are stored in WordPress options, mostly under `thimpress_events_*` and gateway-specific keys. Gateway secret keys and webhook secrets are regular WordPress options; this is common but sensitive. Limit admin access, mask secrets in UI, and avoid logging option values.

Event and legacy booking data are stored in post meta. New booking, coupon, inventory, transaction, and sync data are stored in custom tables.

## Concrete Risks Found

- BCMath functions are used directly in multiple booking, coupon, tax, and refund paths. If the extension is missing, checkout can fatal.
- Public AJAX endpoints do not appear to have rate limiting.
- Checkout accepts a free-form `meta` array after basic text sanitization. Expected keys should be documented and allowlisted.
- SQL PHPCS sniffs for prepared SQL are disabled, increasing reliance on manual SQL review.
- Older templates mix modern escaping with raw `_e()` and `the_title()` output.
- Gateway secrets are stored in options and should be treated as sensitive configuration.
- Admin migration and inventory rebuild tools can mutate large amounts of data. They are protected by capability and nonce checks, but should be used only after database backup.

## Recommendations

- Add an explicit environment check or fallback for BCMath.
- Add a payment return router that sanitizes request data and delegates to the registered gateway.
- Add rate limiting or temporary lockouts for public AJAX auth and checkout endpoints.
- Add security tests for missing nonce, invalid nonce, and insufficient capability across all AJAX and admin-post actions.
- Re-enable or selectively enforce SQL PHPCS checks after allowlisting trusted table-name interpolation.
- Audit public templates and replace raw translation output with escaped variants.
- Mask stored gateway secrets in admin fields and avoid including them in debug logs.
- Document Stripe and PayPal webhook setup steps for site administrators.
