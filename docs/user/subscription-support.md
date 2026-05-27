# Subscription and Membership Support

## Overview

The addon checks subscription and membership status as a fallback when the direct purchase check fails. If the current user did not purchase the product outright but holds an active subscription or membership, they are granted access to the full flipbook.

## WooCommerce Subscriptions

When [WooCommerce Subscriptions](https://woocommerce.com/products/woocommerce-subscriptions/) is active, the addon calls:

```php
wcs_user_has_subscription( $user_id, $product_id, 'active' );
```

This checks whether the user has an active subscription specifically scoped to the product being viewed. A subscription in any other status (cancelled, expired, on-hold, etc.) does not grant access.

## Paid Memberships Pro

When [Paid Memberships Pro](https://www.paidmembershipspro.com/) is active and WooCommerce Subscriptions is not, the addon calls:

```php
pmpro_hasMembershipLevel( null, $user_id );
```

The `null` level argument means any active membership level grants access. There is no product-scoped check — any active PMPro membership passes.

## Check Priority

The access logic in `has_active_subscription()` runs in this order:

1. Return `false` immediately if the user is not logged in.
2. Apply the `r3d_has_active_subscription` filter. If the filter returns a non-null value, that value is used and no further checks run.
3. If WooCommerce Subscriptions is active (`wcs_user_has_subscription` exists), use it.
4. Else if Paid Memberships Pro is active (`pmpro_hasMembershipLevel` exists), use it.
5. Return `false` if neither plugin is present.

Only one of steps 3 or 4 runs per request. WooCommerce Subscriptions takes precedence when both plugins are active.

## `r3d_has_active_subscription` Filter

This filter allows any third-party membership or subscription plugin to integrate with the access check without modifying plugin files.

**Hook name:** `r3d_has_active_subscription`

**Type:** filter

**Parameters:**

| Position | Type   | Description                                                                    |
| -------- | ------ | ------------------------------------------------------------------------------ |
| 1        | `null` | Always `null` on entry. Return a boolean to short-circuit the built-in checks. |
| 2        | `int`  | The current user's ID.                                                         |
| 3        | `int`  | The product ID being checked (may be `0` if called without a product context). |

**Return value:** Return `true` to grant access, `false` to deny, or `null` to fall through to the built-in checks.

### Example: custom membership plugin integration

```php
add_filter( 'r3d_has_active_subscription', function ( $result, $user_id, $product_id ) {
    // my_plugin_user_has_access() returns true/false
    return my_plugin_user_has_access( $user_id, $product_id );
}, 10, 3 );
```

### Example: grant access to all logged-in users regardless of purchase

```php
add_filter( 'r3d_has_active_subscription', function ( $result, $user_id, $product_id ) {
    return $user_id > 0;
}, 10, 3 );
```

### Example: deny subscription access entirely (purchase required)

```php
add_filter( 'r3d_has_active_subscription', function ( $result, $user_id, $product_id ) {
    return false;
}, 10, 3 );
```

## Interaction with Purchase Check

The subscription check is always secondary to the purchase check. The full access decision evaluated in `render_product_flipbook()` is:

```
show full flipbook = purchased OR has_active_subscription
```

The `r3d_woo_purchased_or_subscription` filter wraps both checks and can override the combined result. See [hooks-reference.md](../developer/hooks-reference.md) for details.
