# Release Checklist

Use this checklist from the plugin root.

## Before Release

- [ ] Read `AGENTS.md`.
- [ ] Confirm the release branch only contains intended changes.
- [ ] Confirm WordPress minimum version and tested-up-to values.
- [ ] Confirm PHP minimum version and required PHP extensions, especially BCMath.
- [ ] Confirm required dependencies are installed with `composer install` and `npm install`.
- [ ] Synchronize version values in `wp-events-manager.php`, `readme.txt`, `WPEMS_VER`, and release notes.
- [ ] Update `CHANGELOG.md`.
- [ ] Update `readme.txt` changelog and stable tag if publishing to WordPress.org.

## Quality Checks

- [ ] Run `composer lint`.
- [ ] Run `vendor/bin/phpunit`.
- [ ] Run `npm run lint:js`.
- [ ] Run `npm run build`.
- [ ] Confirm no PHP errors are written to `WP_DEBUG_LOG` during smoke testing.
- [ ] Confirm no JavaScript console errors on event archive, single event, checkout, and admin pages.

## Smoke Test

- [ ] Activate the plugin on a clean WordPress site.
- [ ] Confirm custom tables are created.
- [ ] Confirm admin settings save successfully.
- [ ] Create an event with price, capacity, date, and location.
- [ ] Test free booking.
- [ ] Test one offline gateway.
- [ ] Test PayPal and Stripe in sandbox/test mode if enabled for the release.
- [ ] Test webhook endpoints after permalink flush.
- [ ] Test booking list, booking detail, coupon CRUD, and migration page.
- [ ] Test deactivation and reactivation.

## Packaging

- [ ] Run `npm run makepot`.
- [ ] Run `npm run build-makepot-zip` or the project-approved release command.
- [ ] Confirm the zip exists under `release/`.
- [ ] Inspect the zip contents for required PHP, templates, languages, vendor runtime files, and built assets.
- [ ] Confirm development-only folders are excluded from the zip.
- [ ] Confirm whether excluding Markdown files from the zip is intentional. The current gulp release copy excludes `README.md` and `changelog.md`.

## Compatibility

- [ ] Test on the supported minimum WordPress version if available.
- [ ] Test on the current WordPress version.
- [ ] Test on PHP 7.4 and the current target PHP version.
- [ ] Test with a default WordPress theme.
- [ ] Test with pretty permalinks enabled.
- [ ] Test multisite activation if multisite support is part of the release target.

## Rollback

- [ ] Keep the previous release zip available.
- [ ] Back up the database before running migrations.
- [ ] Record custom table schema version before release.
- [ ] Document whether the release performs destructive data changes. Current schema creation is additive, but migration and inventory rebuild tools mutate booking data.
- [ ] Verify deactivation unschedules plugin cron hooks.
- [ ] Prepare rollback instructions for restoring the previous plugin folder and database backup.
