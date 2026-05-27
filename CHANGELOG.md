# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.6.2] - 2026-05-26

### Added

- Initial public release of the Real3D Flipbook WooCommerce Addon.
- `R3D_Woo` singleton class encapsulating all plugin logic.
- `[product_flipbook]` shortcode with `product_id` and `mode` attributes for embedding purchase-gated flipbooks.
- **Product meta box** ("Real3D Flipbook") on the WooCommerce product edit screen for assigning a full flipbook (`r3d_flipbook_id`) and an optional preview flipbook (`r3d_preview_flipbook_id`).
- **Purchase verification** via `check_user_bought_variation_with_flipbook()`, matching by both customer ID and billing email. Supports both legacy post-based orders and WooCommerce High-Performance Order Storage (HPOS).
- **Per-variation flipbook control** — a checkbox on each product variation (`_flipbook` meta) marks whether purchasing that variation grants flipbook access.
- **Subscription and membership access** via `has_active_subscription()`, with built-in support for WooCommerce Subscriptions (`wcs_user_has_subscription`) and Paid Memberships Pro (`pmpro_hasMembershipLevel`).
- `r3d_has_active_subscription` filter for integrating third-party membership or subscription plugins.
- `r3d_woo_purchased_or_subscription` filter for overriding the combined purchase/subscription access check.
- `r3d_auto_inject_product_flipbook` filter (opt-in) to automatically inject the flipbook after the single product summary via `woocommerce_after_single_product_summary`.
- **Thank-you page flipbook display** controlled by the `r3d_woo_show_thankyou_flipbook` global option and a per-product three-state override (`r3d_show_thankyou_flipbook`: `yes` / `no` / empty for default).
- **My Account "Purchased Flipbooks" tab** listing all flipbooks the customer has access to, using a `flipbooks` rewrite endpoint.
- Thumbnail picker UI in the product meta box with live search and multi-select support.
- Plugin Update Checker v5.5 for automatic updates delivered via GitHub release assets.
- HPOS compatibility declaration via `\Automattic\WooCommerce\Utilities\FeaturesUtil`.
- PHPUnit test suite covering singleton behavior, meta box save logic, nonce enforcement, and access-check guards.
- GitHub Actions CI matrix: PHPCS on PHP 8.2, PHPUnit on PHP 7.4 / 8.0 / 8.2 / 8.4 against WordPress latest and 6.5.
- GitHub Actions release workflow packaging a distributable ZIP on version tags.
- `uninstall.php` cleaning up all plugin meta keys, options, and transients on removal.
- GPL-2.0-or-later license.

[1.6.2]: https://github.com/chrisfromthelc/real3d-flipbook-woocommerce-addon/releases/tag/v1.6.2
