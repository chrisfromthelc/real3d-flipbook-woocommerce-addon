# Real3D Flipbook WooCommerce Addon

[![CI](https://github.com/chrisfromthelc/real3d-flipbook-woocommerce-addon/actions/workflows/ci.yml/badge.svg)](https://github.com/chrisfromthelc/real3d-flipbook-woocommerce-addon/actions/workflows/ci.yml)

WooCommerce addon for Real3D Flipbook that enables purchase-gated flipbook access on product pages. Assign a full flipbook and an optional preview flipbook to any WooCommerce product. Logged-in customers who have purchased the product (or hold an active subscription) see the full flipbook; everyone else sees the preview.

## Requirements

| Dependency             | Minimum version |
| ---------------------- | --------------- |
| PHP                    | 7.4             |
| WordPress              | 6.0             |
| WooCommerce            | 5.0             |
| Real3D Flipbook plugin | 3.17.1          |

## Installation

1. Download `real3d-flipbook-woocommerce-addon.zip` from the [latest GitHub release](https://github.com/chrisfromthelc/real3d-flipbook-woocommerce-addon/releases/latest).
2. In WordPress admin, go to **Plugins > Add New > Upload Plugin**.
3. Upload the ZIP and click **Install Now**, then **Activate**.

## Quick Start

1. Install and activate the main **Real3D Flipbook** plugin first, then activate this addon.
2. Open any WooCommerce product in the editor (**Products > All Products > Edit**).
3. Find the **Real3D Flipbook** meta box in the product editor.
4. Select a flipbook under **Select flipbook for purchased product**.
5. Optionally select a second flipbook under **Select preview flipbook for non-purchased product**.
6. Save the product.
7. Place the `[product_flipbook]` shortcode in the product description, or use the filter to auto-inject it (see [Hooks Reference](docs/developer/hooks-reference.md)).

## Features

- **Purchase verification** — checks WooCommerce order history to determine whether the current user has bought the product.
- **Preview vs. full flipbook** — assign separate flipbooks for buyers and non-buyers on the same product.
- **Per-variation flipbooks** — a checkbox on each product variation controls whether that variation grants flipbook access.
- **Thank-you page display** — optionally embed the flipbook on the WooCommerce order confirmation page, configurable globally and per product.
- **WooCommerce Subscriptions support** — users with an active subscription to the product see the full flipbook without a direct purchase.
- **Paid Memberships Pro support** — users with any active membership level pass the subscription check.
- **My Account tab** — a "Purchased Flipbooks" tab appears in the customer's account area listing all flipbooks they have access to.
- **HPOS compatible** — purchase queries support both legacy post-based orders and WooCommerce High-Performance Order Storage.
- **Auto-updates** — the plugin checks the GitHub repository for new releases and surfaces update notices in the WordPress admin.

## Development

```bash
# Install PHP dependencies (including test tools and PHPCS)
composer install

# Run the unit test suite
vendor/bin/phpunit

# Run coding standards checks
vendor/bin/phpcs
```

See [docs/developer/contributing.md](docs/developer/contributing.md) for the full development setup.

## Auto-Updates

This plugin includes [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) v5. It polls the GitHub repository for new tagged releases and displays a standard WordPress update notice when one is available. No API key is required. Updates are delivered as release asset ZIPs attached to GitHub releases.

To update: click **Update now** in **Plugins > Installed Plugins** when a notice appears, exactly as you would for any WordPress.org plugin.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
