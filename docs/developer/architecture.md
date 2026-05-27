# Architecture

## File Map

```
real3d-flipbook-woocommerce-addon/
├── real3d-flipbook-woocommerce-addon.php   # Plugin entry point: constants, singleton bootstrap, update checker, HPOS declaration
├── includes/
│   └── main.php                            # R3D_Woo class — all plugin logic
├── js/
│   └── admin.js                            # Admin UI script (flipbook picker interactions)
├── css/
│   ├── admin.css                           # Styles for the product meta box
│   └── product-flipbook.css               # Frontend wrapper styles for the flipbook on product pages
├── lib/
│   └── plugin-update-checker/             # Bundled Plugin Update Checker v5.5 (YahnisElsts)
├── languages/                             # Translation files (.po/.mo), text domain: real3d-flipbook-woocommerce-addon
├── tests/
│   ├── bootstrap.php                      # PHPUnit bootstrap — stubs REAL3D_FLIPBOOK_VERSION, loads plugin
│   ├── bin/
│   │   └── install-wp-tests.sh            # Installs WordPress test suite and database
│   └── php/
│       ├── WooAddonTest.php               # Singleton, constants, shortcode method presence
│       ├── ThankYouPageTest.php           # Access check guards (not-logged-in, filter pass-through)
│       └── ProductFlipbookTest.php        # Meta box save: nonce, field persistence, deletion
├── uninstall.php                          # Cleanup: removes all plugin meta keys, options, transients
├── composer.json                          # Dev dependencies: PHPUnit, WPCS, WP test libs
├── phpcs.xml                              # PHPCS ruleset (WordPress standard with project-specific exclusions)
└── phpunit.xml.dist                       # PHPUnit configuration
```

## The R3D_Woo Singleton

All plugin logic lives in the `R3D_Woo` class in `includes/main.php`. It is instantiated once via `R3D_Woo::get_instance()` from the entry point file.

```
real3d-flipbook-woocommerce-addon.php
  └─ R3D_Woo::get_instance()
       └─ new R3D_Woo()
            ├─ add_action( 'plugins_loaded', [$this, 'plugins_loaded'] )
            └─ add_action( 'init', [$this, 'init'] )
```

The constructor registers only two hooks. All WooCommerce-dependent hooks are registered inside `plugins_loaded()` after dependency checks pass. This ensures no hooks fire if Real3D Flipbook or WooCommerce is missing.

## Dependency Checks

`plugins_loaded()` performs three checks in order:

1. `REAL3D_FLIPBOOK_VERSION` constant is defined (Real3D Flipbook is active).
2. `REAL3D_FLIPBOOK_VERSION >= 3.17.1` (minimum version met).
3. `function_exists('WC')` (WooCommerce is active).

If any check fails, an `admin_notices` action is registered to display a dismissible warning and the method returns early. No other hooks are registered.

## Hook Registration

When all dependencies are satisfied, `plugins_loaded()` registers:

```
WordPress / WooCommerce hooks  →  R3D_Woo method
──────────────────────────────────────────────────────────────────
add_meta_boxes                 →  register_meta_boxes()
admin_enqueue_scripts          →  admin_scripts()
admin_enqueue_scripts          →  enqueue_product_admin_assets()
save_post                      →  save_meta_box()
woocommerce_after_single_product_summary (conditional, priority 25)
                               →  add_flipbook_shortcode_single_product_page()
[shortcode] product_flipbook   →  product_flipbook_shortcode()
wp_enqueue_scripts             →  enqueue_frontend_styles()
woocommerce_thankyou_order_received_text (priority 20)
                               →  change_thankyou_sub_title()
woocommerce_account_menu_items →  add_link_my_account()
woocommerce_account_flipbooks_endpoint
                               →  add_tab_content()
init                           →  add_endpoint()
woocommerce_variation_options (priority 10, 3 args)
                               →  add_flipbook_checkbox_next_to_manage_stock()
woocommerce_save_product_variation (priority 10, 2 args)
                               →  save_flipbook_checkbox_value()
[filter] r3d_woo_purchased_or_subscription (priority 10)
                               →  filter_purchased_or_subscription()
```

The `woocommerce_after_single_product_summary` hook is registered only when the `r3d_auto_inject_product_flipbook` filter returns `true`. It is off by default.

## Component Diagram

```
┌─────────────────────────────────────────────────────────┐
│                  WordPress / WooCommerce                │
│  save_post  add_meta_boxes  woocommerce_thankyou  init  │
└───────────────────────┬─────────────────────────────────┘
                        │ hooks
                        ▼
┌───────────────────────────────────────────────────────────┐
│                      R3D_Woo (singleton)                  │
│                                                           │
│  Admin                                                    │
│  ├─ register_meta_boxes()      ← add_meta_boxes           │
│  ├─ r3d_meta_box()             ← meta box render callback │
│  ├─ save_meta_box()            ← save_post                │
│  ├─ admin_scripts()            ← admin_enqueue_scripts    │
│  └─ enqueue_product_admin_assets()                        │
│                                                           │
│  Frontend                                                 │
│  ├─ product_flipbook_shortcode()  ← [product_flipbook]    │
│  ├─ render_product_flipbook()     ← internal              │
│  └─ enqueue_frontend_styles()  ← wp_enqueue_scripts       │
│                                                           │
│  Access Control                                           │
│  ├─ check_user_bought_variation_with_flipbook()           │
│  ├─ get_purchased_variation_ids()  ← direct DB (HPOS-aware)│
│  ├─ has_active_subscription()   ← WCS / PMPro / filter    │
│  └─ filter_purchased_or_subscription()                    │
│                                                           │
│  Thank-You Page                                           │
│  └─ change_thankyou_sub_title() ← woocommerce_thankyou…  │
│                                                           │
│  My Account                                               │
│  ├─ add_endpoint()             ← init                     │
│  ├─ add_link_my_account()      ← woocommerce_account_menu │
│  ├─ add_tab_content()          ← …_flipbooks_endpoint     │
│  └─ get_purchased_flipbooks()  ← internal (transient-cached)│
│                                                           │
│  Variations                                               │
│  ├─ add_flipbook_checkbox_next_to_manage_stock()          │
│  └─ save_flipbook_checkbox_value()                        │
└───────────────────────┬───────────────────────────────────┘
                        │ calls
                        ▼
┌──────────────────────────────────┐
│  Real3D Flipbook (main plugin)   │
│  r3d_get_flipbook( $post_id )    │
│  [real3dflipbook id="..." ]      │
└──────────────────────────────────┘
```

## Dependency on the Main Plugin

The addon calls two things from Real3D Flipbook:

- `r3d_get_flipbook( $post_id )` — retrieves flipbook configuration data (used to get `lightboxThumbnailUrl` for the admin picker thumbnails).
- The `[real3dflipbook]` shortcode — used to render the actual flipbook output on the frontend and on the thank-you page.

The addon does not modify the main plugin's settings, data, or hooks. It only consumes the `r3d` post type, the `r3d_get_flipbook()` function, and the shortcode.

## Update Checker

Plugin Update Checker v5.5 (bundled in `lib/plugin-update-checker/`) is initialized in the entry point file. It polls `https://github.com/chrisfromthelc/real3d-flipbook-woocommerce-addon/` for new tagged releases, with release assets enabled. Updates are distributed as ZIP files attached to GitHub releases and installed through the standard WordPress updater UI.

## HPOS Compatibility

The entry point declares compatibility with WooCommerce Custom Order Tables (HPOS) via `\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility()` on the `before_woocommerce_init` action. The `get_purchased_variation_ids()` method contains separate query branches for HPOS (`wc_orders` table) and legacy post-based orders (`posts` + `postmeta`), selected at runtime via `\Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()`.
