# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

WP Events Manager (`wp-events-manager.php`, plugin slug `wp-events-manager`, text domain `wp-events-manager`) is a WordPress plugin by ThimPress for event management, ticket sales, and bookings. Requires WordPress 6.0+ and PHP 7.4+; local dev environment is Laragon serving `http://lp.test` (see project-root `CLAUDE.md`).

Two post types drive the data model: `tp_event` (events) and `event_auth_book` (bookings — legacy slug retained for back-compat from the merged `tp-event`/`tp-event-auth` predecessors, which `WPEMS_Install::install()` deactivates on activation).

## Commands

PHP (Composer scripts):
- `composer lint` / `composer phpcs` — run PHP_CodeSniffer against `phpcs.xml` (scans `inc/`, `templates/`, `wp-events-manager.php`; excludes `inc/libraries/`, `vendor/`, `assets/`, `tests/`, `release/`).
- `composer format` — auto-fix with `phpcbf`.
- `vendor/bin/phpunit` — run the unit suite (`tests/Unit/**/*Test.php`, bootstrap `tests/bootstrap.php`). PHPUnit 9.6 with Brain Monkey + Mockery; tests do NOT load WordPress — they mock it. Single test: `vendor/bin/phpunit --filter SomeTest tests/Unit/Path/To/SomeTest.php`.

JS/CSS (npm — Node 24.x required per `engines`):
- `npm run start` — webpack dev build, writes to `assets/dist/js/`. Entries are auto-discovered by `webpack.config.js` walking `assets/src/js/` recursively.
- `npm run build` — production build (minified, `.min.js` suffix).
- `npm run lint:js` — ESLint over `assets/src/js/**/*.js`.
- `gulp` (default task) — full release pipeline: clears `release/` + `assets/dist/css`, compiles SCSS from `assets/src/scss/`, generates `-rtl` variants, minifies, copies plugin to `release/wp-events-manager/`, zips as `release/wp-events-manager_<version>.zip`.
- `npm run makepot` — regenerate `languages/wp-events-manager.pot` via WP-CLI.
- `npm run release` — runs `build-release.js`, which spawns `npm start` and `npm run build-makepot-zip` (composer no-dev + build + makepot + gulp) in parallel.

## Architecture

### Bootstrap flow (`wp-events-manager.php`)

1. Loads Composer autoload (`vendor/autoload.php`) — required, the PSR-4 namespace `WPEMS\` maps to `inc/`.
2. Boots `\WPEMS\Payments\PaymentGatewayRegistry` and `\WPEMS\Payments\PaymentWebhookRouter` immediately if the classes exist.
3. Constructs the `WPEMS` singleton, which calls `define_constants()` → `includes()` → `init_hooks()`.
4. `includes()` registers a **second** autoloader (`inc/class-wpems-autoloader.php`, `WPEMS_Autoloader`) for legacy `WPEMS_*` snake-case classes — it maps `wpems_foo_bar` → `inc/class-wpems-foo-bar.php`, with special prefixes for `wpems_payment_gateway_*` → `inc/gateways/...`, `wpems_abstract_*` → `inc/abstracts/`, `wpems_widget_*` → `inc/widgets/`. A small `$legacy_admin_classes` table redirects historical names into `inc/Admin/*`.
5. On `init`, `WPEMS::loaded()` creates `WPEMS_Session`, fires `wpems_init`, and the plugin loads its text domain. `included_files_when_plugins_loaded` (priority 20) defers `inc/class-wpems-payment-gateways.php` to avoid early translation loading.
6. Admin context bootstraps via `\WPEMS\Admin\Admin::init()` (Menu, Assets, Metaboxes, SettingsManager, BookingMigrationPage). Frontend context loads template, frontend assets, user process, and shortcodes.
7. Final action `wpems-plugin-ready` is reserved for add-ons.

### Dual-namespace coexistence

The codebase is mid-migration from legacy global `WPEMS_*` classes to PSR-4 `\WPEMS\…`. Both autoloaders are active simultaneously. New code lives under `inc/<Subnamespace>/ClassName.php` with `namespace WPEMS\<Subnamespace>;`. Legacy code still uses `inc/class-wpems-*.php` files referenced by the custom autoloader. When editing, follow the convention of the file you're in; when adding new modules, prefer the namespaced layout.

### Domain layout (`inc/`)

- `Models/` — domain entities (e.g. `EventPostModel`, `BookingPostModel`, `CouponModel`, `PaymentTransactionModel`, `BookingTableModel`). `PostModel.php` is a shared base for post-backed models.
- `Repositories/` — data-access boundary for custom tables (Booking, Coupon, CouponEvent, CouponUsage, EventInventory, PaymentEvent, PaymentTransaction, PaymentSyncQueue) plus a `BookingQuery` / `CouponQuery` query-builder pattern.
- `Databases/`, `Filters/` — DTO-style request → SQL helpers (currently `EventDB`, `EventFilter`).
- `Services/` — orchestration on top of repositories: `BookingCheckoutService`, `BookingStatusService`, `CheckoutQuoteService`, `CouponService`, `PaymentSyncService`, `TaxService`. Services return result objects (`CheckoutDispatchResult`, `CouponValidationResult`).
- `Pricing/` — `CheckoutQuote` value object.
- `Payments/` — payment gateway framework: `AbstractPaymentGateway`, `PaymentGatewayRegistry` (singleton, default gateways: `ManualGateway`, `CheckGateway`, `PaypalGateway`, `StripeGateway`), `PaymentWebhookRouter`, plus `CheckoutResult` / `PaymentResult`.
- `Gateways/` — concrete gateway implementations (PayPal SDK assets under `inc/Gateways/paypal/`). Stripe uses `stripe/stripe-php:^14.0` from Composer.
- `Tables/` — **`TableNames` is the single source of truth for the 9 custom tables** (`wpems_bookings`, `wpems_booking_meta`, `wpems_event_inventory`, `wpems_coupons`, `wpems_coupon_events`, `wpems_coupon_usage`, `wpems_payment_events`, `wpems_payment_transactions`, `wpems_payment_sync_queue`). Never hardcode `$wpdb->prefix . 'wpems_…'` — always go through `WPEMS\Tables\TableNames::*()`. `SchemaManager` owns DDL.
- `Admin/` — namespaced admin UI: `Admin`, `Menu`, `Assets`, `Metaboxes/` (event, booking), `Settings/` (`General`, `Pages`, `Checkout`, `Emails` — duplicated under both `Admin/Settings/*.php` PSR-4 files and legacy `class-wpems-admin-setting-*.php` during migration), `Bookings/` and `Coupons/` (list tables + admin actions + views/), `Tools/BookingMigrationPage.php`.
- `Frontend/`, `ShortCodes/`, `TemplateHooks/` — frontend rendering. Templates live in `templates/` and can be overridden by themes; `class-wpems-template.php` resolves them via `wpems_clean_relative_template_path()` for path-traversal safety.
- `Migrations/` — booking migration from legacy CPT to the `wpems_bookings` table (`BookingMigrator`, `BatchResult`, `InventoryRebuildReport`, `VerifyReport`). Triggered from the admin "Tools" page.
- `Upgrades/` — versioned upgrade scripts wired in `WPEMS_Install::init()` (`2.0`, `2.0.8`, `2.1.7.2`).
- `Cron/CronBootstrap.php`, `CLI/BookingCommands.php`, `Integration/` (EventCounts, GDPR exporter/eraser), `Exceptions/OutOfStockException.php`, `widgets/`, `emails/`, `abstracts/`, `libraries/` (vendored).

### Frontend assets (`assets/`)

- Source: `assets/src/js/` (entries auto-discovered) and `assets/src/scss/`.
- JS builds to `assets/dist/js/` via webpack (`@wordpress/scripts` defaults + `DependencyExtractionWebpackPlugin` to externalize `@wordpress/*` and produce `.asset.php` files; jQuery datetimepicker has its AMD parser disabled).
- CSS builds to `assets/dist/css/` via gulp+dart-sass, with auto-generated RTL (`*-rtl.css`) and minified (`*.min.css`) variants.
- `WP_NO_EXTERNALS=1` disables dependency externals; `WP_BUNDLE_ANALYZER=1` enables the bundle analyzer.
- ESLint config (`.eslintrc.json`) extends `@wordpress/eslint-plugin/recommended` and **bans the `jQuery` and `$` globals** — use enqueued WP scripts / explicit imports instead.

### Testing setup

`tests/bootstrap.php` runs WITHOUT loading WordPress — it stubs `WP_Post`, `WP_Error`, `WP_List_Table`, `WP_CLI`, and a `WPEMS_Shortcodes` double, then defines the `WPEMS_*` constants the production code expects. `tests/Unit/TestCase.php` is the Brain Monkey base class that pre-seeds `__`, `esc_*`, `wp_unslash`, `wp_json_encode`, etc. as identity/passthrough doubles. Treat any new test as a pure-PHP unit test mocking WordPress calls with `Brain\Monkey\Functions::when()` / `expect()`; don't reach for the WP test suite.

### Coding standards quirks (`phpcs.xml`)

WordPress-Core ruleset with several relaxations: short array syntax allowed, loose comparisons allowed, snake_case naming rules disabled for variables/properties/methods/hooks, Yoda conditions off, and `WordPress.DB.PreparedSQL.*` rules **excluded** — meaning PHPCS will NOT flag unprepared SQL. Manually verify SQL safety; don't trust the linter on that.

### Release artifact

`gulp` produces `release/wp-events-manager_<version>.zip` containing everything except `node_modules/`, `assets/src/`, dev/build configs, `tests/`, `.git/`, `package*.json`, `composer.*`. The `release/` folder is the distributable plugin tree — never edit it by hand.
