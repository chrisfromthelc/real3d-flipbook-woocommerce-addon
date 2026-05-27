# Hooks Reference

All actions and filters provided by the Real3D Flipbook WooCommerce Addon.

## Filters

### `r3d_auto_inject_product_flipbook`

Controls whether the flipbook is automatically injected after the product summary on single product pages.

| Property | Value               |
| -------- | ------------------- |
| Type     | `bool`              |
| Default  | `false`             |
| Location | `includes/main.php` |

**Parameters:**

| Parameter | Type   | Description                          |
| --------- | ------ | ------------------------------------ |
| `$inject` | `bool` | Whether to auto-inject the flipbook. |

**Usage:**

```php
// Automatically display flipbooks on all product pages.
add_filter( 'r3d_auto_inject_product_flipbook', '__return_true' );
```

When enabled, the flipbook renders via `woocommerce_after_single_product_summary` at priority 25.

---

### `r3d_has_active_subscription`

Short-circuits the subscription/membership check. Return a non-null boolean to override the built-in WooCommerce Subscriptions and Paid Memberships Pro checks.

| Property | Value                                            |
| -------- | ------------------------------------------------ |
| Type     | `bool\|null`                                     |
| Default  | `null` (fall through to built-in checks)         |
| Location | `includes/main.php`, `has_active_subscription()` |

**Parameters:**

| Parameter     | Type         | Description                                                                            |
| ------------- | ------------ | -------------------------------------------------------------------------------------- |
| `$result`     | `bool\|null` | Return `true` to grant access, `false` to deny, `null` to continue to built-in checks. |
| `$user_id`    | `int`        | Current user ID.                                                                       |
| `$product_id` | `int`        | WooCommerce product ID.                                                                |

**Usage:**

```php
// Integrate a custom membership plugin.
add_filter( 'r3d_has_active_subscription', function ( $result, $user_id, $product_id ) {
    if ( my_membership_plugin_user_has_access( $user_id, $product_id ) ) {
        return true;
    }
    return $result;
}, 10, 3 );
```

**Evaluation order:**

1. This filter runs first. If any callback returns a non-null value, that value is used immediately.
2. If the filter returns `null`, the addon checks `wcs_user_has_subscription()` (WooCommerce Subscriptions).
3. If that function doesn't exist, it checks `pmpro_hasMembershipLevel()` (Paid Memberships Pro).
4. If neither plugin is available, returns `false`.

---

### `r3d_woo_purchased_or_subscription`

Controls full flipbook access on product pages. The addon registers its own callback at priority 10 that checks purchase history and subscription status. Additional callbacks can grant or restrict access.

| Property | Value                                                     |
| -------- | --------------------------------------------------------- |
| Type     | `bool`                                                    |
| Default  | `false`                                                   |
| Location | `includes/main.php`, `filter_purchased_or_subscription()` |

**Parameters:**

| Parameter | Type   | Description                                       |
| --------- | ------ | ------------------------------------------------- |
| `$access` | `bool` | Whether the user has access to the full flipbook. |

**Usage:**

```php
// Grant access to all logged-in users (bypass purchase check).
add_filter( 'r3d_woo_purchased_or_subscription', function ( $access ) {
    return is_user_logged_in();
} );

// Grant access based on user role.
add_filter( 'r3d_woo_purchased_or_subscription', function ( $access ) {
    if ( $access ) {
        return true;
    }
    return current_user_can( 'manage_options' );
} );
```

**Note:** This filter is applied by the main Real3D Flipbook plugin, not by this addon. The addon registers its callback via `add_filter()` during initialization. The filter is only useful when the main plugin calls `apply_filters( 'r3d_woo_purchased_or_subscription', false )` — typically in contexts where the main plugin renders a flipbook that may be purchase-gated.

## Actions

This addon does not define custom actions. It hooks into the following WordPress and WooCommerce actions:

| Hook                                       | Priority | Callback                                       | Purpose                                                                                     |
| ------------------------------------------ | -------- | ---------------------------------------------- | ------------------------------------------------------------------------------------------- |
| `add_meta_boxes`                           | 10       | `register_meta_boxes()`                        | Registers the "Real3D Flipbook" meta box on product edit screens.                           |
| `save_post`                                | 10       | `save_meta_box()`                              | Saves flipbook meta fields when a product is saved.                                         |
| `admin_enqueue_scripts`                    | 10       | `admin_scripts()`                              | Loads admin JS.                                                                             |
| `admin_enqueue_scripts`                    | 10       | `enqueue_product_admin_assets()`               | Loads product admin CSS/JS.                                                                 |
| `wp_enqueue_scripts`                       | 10       | `enqueue_frontend_styles()`                    | Loads frontend CSS on product pages.                                                        |
| `woocommerce_after_single_product_summary` | 25       | `add_flipbook_shortcode_single_product_page()` | Auto-injects flipbook (only when `r3d_auto_inject_product_flipbook` filter returns `true`). |
| `woocommerce_thankyou_order_received_text` | 20       | `change_thankyou_sub_title()`                  | Displays flipbook on the order thank-you page.                                              |
| `woocommerce_account_flipbooks_endpoint`   | 10       | `add_tab_content()`                            | Renders purchased flipbooks in My Account.                                                  |
| `init`                                     | 10       | `add_endpoint()`                               | Registers the `flipbooks` rewrite endpoint.                                                 |
| `woocommerce_variation_options`            | 10       | `add_flipbook_checkbox_next_to_manage_stock()` | Adds flipbook checkbox to variation options.                                                |
| `woocommerce_save_product_variation`       | 10       | `save_flipbook_checkbox_value()`               | Saves the per-variation flipbook checkbox.                                                  |
| `woocommerce_account_menu_items`           | 10       | `add_link_my_account()`                        | Adds "Purchased Flipbooks" link to My Account menu.                                         |
