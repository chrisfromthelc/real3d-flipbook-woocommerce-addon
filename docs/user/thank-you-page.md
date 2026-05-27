# Thank-You Page Flipbook Display

## Overview

After a customer completes checkout, WooCommerce shows an order confirmation page (the "thank-you page"). This addon can append a flipbook to the subtitle text on that page for each product in the order that has a flipbook assigned.

## How It Works

The addon hooks into `woocommerce_thankyou_order_received_text` (priority 20) to append flipbook output to the subtitle string. For each line item in the order:

1. The per-product override (`r3d_show_thankyou_flipbook`) is read.
2. If the override is `"no"`, that product is skipped.
3. If the override is not `"yes"`, the global setting (`r3d_woo_show_thankyou_flipbook` option) is checked. If the global setting is disabled, the product is skipped.
4. The product's `r3d_flipbook_id` and `r3d_preview_flipbook_id` are read.
5. If a full flipbook is assigned and the customer has purchased the product (or holds an active subscription), all flipbook IDs in the `r3d_flipbook_id` semicolon-separated list are rendered.
6. Otherwise, if a preview flipbook is assigned, all IDs in `r3d_preview_flipbook_id` are rendered.

Unlike the `[product_flipbook]` shortcode, the thank-you page display renders **all** IDs in a semicolon-separated list, not just the first.

## Global Setting

The global on/off switch is stored in the WordPress option `r3d_woo_show_thankyou_flipbook` (boolean). This option is removed when the plugin is uninstalled.

When the global setting is disabled (the default), no flipbooks are shown on thank-you pages unless a product overrides it to `"yes"`.

## Per-Product Override

Each product has a three-state select in the **Real3D Flipbook** meta box under the **Order Confirmation Page** heading.

| Select value | Label                        | Behavior                                                                                     |
| ------------ | ---------------------------- | -------------------------------------------------------------------------------------------- |
| _(empty)_    | Default (use global setting) | The global `r3d_woo_show_thankyou_flipbook` option determines whether the flipbook is shown. |
| `yes`        | Enable (always show)         | The flipbook is shown on the thank-you page regardless of the global setting.                |
| `no`         | Disable (never show)         | The flipbook is never shown on the thank-you page, even if the global setting is enabled.    |

The override is stored in `r3d_show_thankyou_flipbook` post meta on the product. When the select is set back to the empty/default option, the meta key is deleted.

## Decision Logic Summary

```
for each line item in order:
    if r3d_show_thankyou_flipbook == "no"  → skip
    if r3d_show_thankyou_flipbook != "yes" AND global setting is off  → skip
    if r3d_flipbook_id set AND (purchased OR subscribed)  → show full flipbook(s)
    else if r3d_preview_flipbook_id set  → show preview flipbook(s)
```

## Configuration Steps

1. To enable globally, set the `r3d_woo_show_thankyou_flipbook` option to `true`. This can be done with WP-CLI:

   ```bash
   wp option update r3d_woo_show_thankyou_flipbook 1
   ```

2. To override per product, open the product in the editor, find the **Order Confirmation Page** section in the **Real3D Flipbook** meta box, and choose **Enable (always show)** or **Disable (never show)** from the select. Save the product.

## Notes

- The thank-you page check uses the same `check_user_bought_variation_with_flipbook()` and `has_active_subscription()` methods as the product page shortcode, so subscription and membership access rules apply here too.
- The flipbooks are appended to the subtitle text string returned by `woocommerce_thankyou_order_received_text`, not inserted into a separate page section.
- If an order contains multiple products, each product with an eligible flipbook appends its flipbook(s) to the same subtitle string.
