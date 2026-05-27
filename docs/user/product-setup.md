# Product Setup

## The Real3D Flipbook Meta Box

Every WooCommerce product edit screen contains a **Real3D Flipbook** meta box in the normal (center) column. It has three sections:

1. Flipbook picker for purchased products
2. Flipbook picker for non-purchased products (preview)
3. Order Confirmation Page override

If no flipbooks exist in the system yet, the meta box shows a link to create one. Once flipbooks exist, each section displays a scrollable thumbnail grid.

## Assigning a Flipbook to a Product

### Full (purchased) flipbook

In the **Select flipbook for purchased product** section, click a thumbnail to select it. The selected thumbnail is highlighted. This flipbook is stored in the `r3d_flipbook_id` post meta key on the product.

Customers who have purchased the product (or hold an active subscription) will see this flipbook.

### Preview flipbook

In the **Select preview flipbook for non-purchased product** section, click a thumbnail to select it. This flipbook is stored in the `r3d_preview_flipbook_id` post meta key.

Visitors who are not logged in, or who are logged in but have not purchased the product, will see this flipbook instead.

If no preview flipbook is assigned, non-buyers see nothing.

### Searching flipbooks

A search box above each thumbnail grid filters the visible flipbooks by name in real time. The count label below the grid updates to show how many flipbooks match the current query.

### Saving

Click the standard WordPress **Update** or **Publish** button. The meta box saves alongside the rest of the product. If either flipbook field is cleared (nothing selected), the corresponding meta key is deleted from the product.

## Purchase Verification

When the `[product_flipbook]` shortcode renders (or when the auto-inject filter is enabled), the addon checks whether the current user has purchased the product:

1. If the user is not logged in, they are treated as a non-buyer.
2. For simple products, `wc_customer_bought_product()` is called directly.
3. For variable products, a direct database query finds which variation IDs the customer has purchased (matched by both customer ID and billing email, across all paid order statuses). If any purchased variation has the `_flipbook` checkbox set to a value other than `no`, access is granted.
4. If the purchase check fails, `has_active_subscription()` is called. If WooCommerce Subscriptions is active, it checks for an active subscription to the product. If Paid Memberships Pro is active, it checks for any active membership level. Either passes the check.

If access is granted the full flipbook (`r3d_flipbook_id`) is shown; otherwise the preview flipbook (`r3d_preview_flipbook_id`) is shown.

The combined check can be overridden via the `r3d_woo_purchased_or_subscription` filter. See [hooks-reference.md](../developer/hooks-reference.md) for details.

## Per-Variation Flipbook Assignment

For variable products, each variation has a **Flipbook** checkbox in the variation options panel (next to the Manage Stock checkbox).

- **Checked** — purchasing this variation grants access to the full flipbook assigned to the parent product.
- **Unchecked** — purchasing this variation does not grant flipbook access, even if the parent product has a flipbook assigned.

By default (no meta value saved) a variation is treated as granting access. The checkbox is only meaningful when the parent product has a full flipbook assigned.

The checkbox value is stored as `_flipbook` post meta on the variation post (`yes` or `no`).

## Flipbook Display on the Product Page

The flipbook is not automatically injected on product pages unless the `r3d_auto_inject_product_flipbook` filter returns `true`. The standard method is to place the `[product_flipbook]` shortcode in the product's description or short description.

The shortcode wraps the rendered flipbook in a `<div class="r3d-product-flipbook-wrap">` container with an SVG play-button overlay. The frontend stylesheet `css/product-flipbook.css` is enqueued automatically on single product pages.

See [shortcode-reference.md](shortcode-reference.md) for shortcode attributes.
