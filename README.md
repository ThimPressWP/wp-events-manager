# WP Events Manager

WP Events Manager is a WordPress plugin from ThimPress for publishing events, managing registrations, and selling event tickets. The current codebase contains the legacy event/booking implementation and a newer PSR-4, table-backed booking system under the `WPEMS\` namespace.

## Main Features

- Event custom post type `tp_event` with event category, tag, and type taxonomies.
- Legacy booking custom post type `event_auth_book` with booking statuses such as `ea-pending`, `ea-processing`, `ea-completed`, and `ea-cancelled`.
- Event metaboxes for ticket quantity, price, dates, registration cutoff, location, Google Map iframe, schedule visibility, and countdown shortcode.
- Public event archive and single-event templates, search/filter UI, registration area, countdown, Google Map, carousel, and modal assets.
- Shortcodes for event lists, event registration, login, forgot/reset password, account, countdown, and `[wpems_my_bookings]`.
- Admin menu under Events Manager with settings, users, bookings, coupons, and booking migration tools.
- Table-backed booking runtime with repositories, services, inventory holds, coupons, tax calculation, payment sync queue, and migration support.
- Payment gateway registry with manual, check, PayPal, and Stripe gateway classes.
- Payment webhook rewrite endpoints for PayPal REST, PayPal IPN, and Stripe.
- AJAX endpoints for legacy registration/login flows and the newer checkout quote, coupon validation, and checkout submit flow.
- WP-Cron jobs for payment sync and booking hold expiry, plus legacy cancel-payment scheduling.
- WP-CLI command group `wpems bookings` for migration, verification, inventory rebuild, payment sync, hold expiry, and marking bookings paid.
- Email, account, event count, and GDPR exporter/eraser integrations.
- PHPUnit and Brain Monkey based unit tests.

## Requirements

- WordPress: `6.0+` from the plugin header.
- PHP: `7.4+` from the plugin header.
- Tested up to: `6.8` from `readme.txt`.
- PHP extensions: BCMath is used by checkout, coupon, tax, refund, and gateway code. Treat it as required unless fallbacks are added.
- Composer runtime dependency: `stripe/stripe-php`.
- Composer development dependencies: PHP_CodeSniffer, WordPress Coding Standards, PHPUnit 9.6, Brain Monkey, and Mockery.
- Node.js: `>=24.0.0 <25.0.0` from `package.json`.
- JavaScript dependencies: `toastify-js` and `tom-select`; build tooling uses `@wordpress/scripts`, webpack, gulp, Sass, and ESLint.
- Required plugins: Unknown / not detected. The code does show an admin notice if legacy Thim Events or Thim Event Authentication plugin files are present.

## Installation

1. Place this directory at `wp-content/plugins/wp-events-manager`.
2. From the plugin directory, install PHP dependencies:

   ```bash
   composer install
   ```

3. Install JavaScript dependencies and build assets:

   ```bash
   npm install
   npm run build
   ```

4. Activate WP Events Manager from the WordPress admin Plugins screen.
5. Visit Settings > Permalinks or reactivate the plugin if webhook rewrite endpoints do not resolve.

## Local Development

Run commands from the plugin root:

```bash
composer lint
vendor/bin/phpunit
npm run lint:js
npm run build
```

Useful local checks:

- Confirm `assets/dist/js` and `assets/dist/css` exist after building.
- Activate the plugin with `WP_DEBUG` and `WP_DEBUG_LOG` enabled.
- Create a test `tp_event`, configure checkout settings, and test free and paid booking flows.
- Run migration tests only against backed-up legacy booking data.

## Important Files and Folders

- `wp-events-manager.php`: main plugin file and bootstrap.
- `inc/`: PHP classes, legacy globals, namespaced classes, repositories, services, gateways, admin, cron, CLI, migrations, and table schema.
- `templates/`: public, admin, email, notice, loop, and shortcode templates.
- `assets/src/`: source JavaScript and SCSS.
- `assets/dist/`: generated JavaScript and CSS build output.
- `tests/`: PHPUnit unit tests and test bootstrap.
- `languages/`: translation files.
- `release/`: generated release package output.

## Notes for Maintainers

- The codebase is mid-migration from legacy global `WPEMS_*` classes to namespaced `WPEMS\` classes.
- Composer maps `WPEMS\` to `inc/`.
- Custom tables must use `WPEMS\Tables\TableNames`; table names should not be hardcoded elsewhere.
- The plugin header, `readme.txt` stable tag, and `WPEMS_VER` are aligned at `2.2.4`.
