# AGENTS.md

This file is the canonical operating guide for AI coding agents working in this
repository. Keep it current when architecture, build commands, or development
workflow changes.

## Project Identity

WP Events Manager is a WordPress plugin by ThimPress for event management,
ticket sales, bookings, checkout, payment gateways, and booking administration.

- Plugin bootstrap: `wp-events-manager.php`
- Plugin slug: `wp-events-manager`
- Text domain: `wp-events-manager`
- Main event post type: `tp_event`
- Legacy booking post type: `event_auth_book`
- WordPress requirement: `6.0+`
- PHP requirement: `7.4+`
- Local site: `http://lp.test`

The codebase is mid-migration from legacy global `WPEMS_*` classes to PSR-4
namespaced classes under `WPEMS\`. Follow the style of the file being edited.
For new PHP modules, prefer the namespaced layout under `inc/`.

## Repo Guardrails

- Do not overwrite or revert unrelated worktree changes.
- Read the local files before editing; prefer existing patterns over new
  abstractions.
- Keep changes scoped to the requested behavior.
- Do not edit generated release artifacts by hand.
- Do not hardcode custom table names. Use `WPEMS\Tables\TableNames`.
- PHPCS does not enforce prepared SQL in this repo. Manually verify SQL safety.
- Template files under `templates/` can be overridden by themes; keep public
  template contracts stable.
- Preserve backward compatibility with legacy class names, hooks, post types,
  settings, and add-on extension points unless the task explicitly changes them.

## Common Commands

PHP:

```bash
composer lint
composer phpcs
composer format
vendor/bin/phpunit
vendor/bin/phpunit --filter SomeTest tests/Unit/Path/To/SomeTest.php
```

JavaScript and CSS:

```bash
npm run start
npm run build
npm run lint:js
npm run makepot
npm run release
gulp
```

Notes:

- Node must satisfy `>=24.0.0 <25.0.0`.
- `npm run build` uses `@wordpress/scripts` and writes JS assets.
- `gulp` compiles SCSS, generates RTL and minified CSS, and creates release
  output.
- `vendor/bin/phpunit` runs pure PHP unit tests with Brain Monkey and Mockery;
  it does not load a full WordPress test suite.

## Bootstrap Flow

`wp-events-manager.php` is the main entry point.

1. Loads Composer autoload from `vendor/autoload.php`.
2. Boots `WPEMS\Payments\PaymentGatewayRegistry` and
   `WPEMS\Payments\PaymentWebhookRouter` if available.
3. Creates the `WPEMS` singleton.
4. Defines constants, includes files, and initializes hooks.
5. Registers the legacy `WPEMS_Autoloader` for global `WPEMS_*` classes.
6. On `init`, creates the session, fires `wpems_init`, and loads translations.
7. Boots admin classes in admin context and frontend classes in frontend context.
8. Fires `wpems-plugin-ready` for add-ons.

## PHP Architecture

- `inc/Models/`: domain entities and post-backed models.
- `inc/Repositories/`: data access for bookings, coupons, inventory, payments,
  and query objects.
- `inc/Services/`: orchestration such as checkout, booking status, coupons,
  taxes, and payment sync.
- `inc/Pricing/`: checkout quote value objects.
- `inc/Payments/`: gateway registry, webhook routing, payment results, and
  payment abstractions.
- `inc/Gateways/`: concrete gateway implementations.
- `inc/Tables/`: custom table names and schema management.
- `inc/Admin/`: admin menu, assets, metaboxes, settings, bookings, coupons, and
  tools.
- `inc/Frontend/`, `inc/ShortCodes/`, `inc/TemplateHooks/`: frontend rendering
  and template composition.
- `inc/Migrations/`: booking migration and inventory rebuild utilities.
- `inc/Upgrades/`: versioned upgrade scripts.
- `inc/Integration/`: GDPR and event count integrations.
- `inc/CLI/`: WP-CLI commands.
- `inc/Cron/`: scheduled task bootstrap.

## Custom Tables

`WPEMS\Tables\TableNames` is the single source of truth for custom table names.
Current tables include:

- `wpems_bookings`
- `wpems_booking_meta`
- `wpems_event_inventory`
- `wpems_coupons`
- `wpems_coupon_events`
- `wpems_coupon_usage`
- `wpems_payment_events`
- `wpems_payment_transactions`
- `wpems_payment_sync_queue`

Use repository classes for table access where possible. If direct SQL is needed,
sanitize request data, use `$wpdb->prepare()` for dynamic values, and document
why repository APIs were not sufficient.

## Frontend Assets

Source files live under:

- `assets/src/js/`
- `assets/src/scss/`

Build output lives under:

- `assets/dist/js/`
- `assets/dist/css/`

The webpack config auto-discovers JS entries under `assets/src/js/`.
The gulp pipeline compiles SCSS, creates RTL files, minifies CSS, and prepares
release assets.

The ESLint config bans global `jQuery` and `$`. Use explicit imports or
WordPress-enqueued dependencies following existing file patterns.

## Testing Guidance

Use focused tests for focused changes.

- PHP unit tests live under `tests/Unit`.
- `tests/bootstrap.php` stubs WordPress classes and functions.
- `tests/Unit/TestCase.php` provides the Brain Monkey base test case.
- Mock WordPress calls with `Brain\Monkey\Functions::when()` or `expect()`.
- Do not rely on a full WordPress runtime in unit tests.
- Add or update tests when changing services, repositories, payment flows,
  checkout behavior, migrations, schema logic, or public template contracts.

## Coding Standards

The project uses `WordPress-Core` via `phpcs.xml` with local relaxations:

- Short arrays are allowed.
- Loose comparisons are allowed.
- Yoda conditions are disabled.
- Several naming sniffs are disabled to support existing conventions.
- Prepared SQL sniffs are excluded.

Respect the surrounding file style. Keep comments brief and useful. Avoid broad
formatting churn unless a formatter is intentionally being run for the task.

## Long-Running Development Workflow

For multi-step work, maintain a clear local thread of what changed, why it
changed, and how it was verified.

Before editing:

- Check `git status --short`.
- Identify user changes already present.
- Read the owning classes, tests, and templates.
- Choose the smallest set of files needed for the task.

While editing:

- Prefer namespaced PHP for new modules.
- Prefer service and repository boundaries over direct procedural changes.
- Keep public hooks and filters compatible.
- Keep frontend behavior accessible and resilient to missing optional data.
- Update translation strings and POT generation only when needed.

Before finishing:

- Run the narrowest useful test or lint command.
- For PHP-only changes, prefer `php -l` on touched files and targeted PHPUnit.
- For JS changes, prefer `npm run lint:js` or a focused build when feasible.
- For SCSS/build changes, run the relevant build command if dependencies are
  available.
- Report any verification that could not be run.

## Release Notes

The release process produces a distributable tree and zip under `release/`.
Never patch release output directly. Change source files, then run the release
pipeline.

The release package excludes development files such as `node_modules/`,
`assets/src/`, tests, source maps/configs, and Composer/npm manifests according
to the current gulp and release scripts.
