# Contributing

## Development Setup

### Requirements

- PHP 7.4+
- WordPress 6.0+
- WooCommerce 5.0+
- Real3D Flipbook plugin (installed and active)
- Composer
- MySQL or MariaDB

### Local Environment

1. Set up a WordPress development site (Local, wp-env, DDEV, etc.).
2. Install and activate WooCommerce.
3. Install and activate the Real3D Flipbook plugin.
4. Clone this repository into `wp-content/plugins/`:

```bash
cd /path/to/wordpress/wp-content/plugins
git clone git@github.com:chrisfromthelc/real3d-flipbook-woocommerce-addon.git
cd real3d-flipbook-woocommerce-addon
composer install
```

5. Activate the plugin in WordPress admin.

### Running Tests

```bash
# Install WP test framework (first time only)
bash tests/bin/install-wp-tests.sh wordpress_test root '' localhost latest

# Run PHPUnit
vendor/bin/phpunit

# Run PHPCS
vendor/bin/phpcs
```

The test bootstrap stubs `REAL3D_FLIPBOOK_VERSION` so tests can run without the main plugin loaded.

## Coding Standards

This project follows WordPress Coding Standards enforced via PHPCS. The configuration is in `phpcs.xml`.

```bash
# Check standards
vendor/bin/phpcs

# Auto-fix what's fixable
vendor/bin/phpcbf
```

## Pull Request Process

1. Create a feature branch from `main`.
2. Make your changes.
3. Ensure PHPCS passes: `vendor/bin/phpcs`
4. Ensure PHPUnit passes: `vendor/bin/phpunit`
5. Open a PR against `main` using the PR template.

### PR Requirements

All PRs must pass CI before merge:

- **phpcs** — WordPress Coding Standards compliance
- **phpunit** — Tests pass across PHP 7.4, 8.0, 8.2, 8.4 with WP latest and 6.5

### Commit Messages

Use conventional-style messages:

- `fix:` for bug fixes
- `feat:` for new features
- `chore:` for maintenance tasks
- `docs:` for documentation changes

## Testing with WooCommerce

To test purchase verification and product flipbooks:

1. Create a flipbook in Real3D Flipbook admin.
2. Create a WooCommerce product and assign the flipbook in the "Real3D Flipbook" meta box.
3. Optionally assign a preview flipbook.
4. Place a test order (use WooCommerce's cash on delivery gateway for convenience).
5. Verify the product page shows the preview flipbook for non-purchasers and the full flipbook for purchasers.
6. Check the thank-you page and My Account > Flipbooks tab.

## Release Process

Releases are automated via GitHub Actions. To create a new release:

1. Update the version number in:
   - `real3d-flipbook-woocommerce-addon.php` (plugin header `Version:` field)
   - `real3d-flipbook-woocommerce-addon.php` (`R3D_WOO_VERSION` constant)
2. Commit the version bump.
3. Tag and push:

```bash
git tag v1.7.0
git push origin v1.7.0
```

The release workflow packages the plugin into a ZIP and creates a GitHub release with the ZIP attached. Sites with the plugin installed will detect the new version automatically via the Plugin Update Checker.
