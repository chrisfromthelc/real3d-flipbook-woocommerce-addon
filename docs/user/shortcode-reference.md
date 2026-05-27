# Shortcode Reference

## `[product_flipbook]`

Renders a purchase-gated flipbook for a WooCommerce product. Buyers (and subscribers) see the full flipbook; everyone else sees the preview flipbook. If neither condition applies and no preview is assigned, the shortcode outputs nothing.

### Attributes

| Attribute    | Type   | Default      | Description                                                                                                                                                            |
| ------------ | ------ | ------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `product_id` | int    | _(auto)_     | The WooCommerce product ID whose flipbooks should be rendered. If omitted, the shortcode uses the current `$product` global or `get_the_ID()`.                         |
| `mode`       | string | `"lightbox"` | The display mode passed to the underlying `[real3dflipbook]` shortcode. Accepts any mode supported by the main Real3D Flipbook plugin (e.g. `"normal"`, `"lightbox"`). |

### Behavior

1. The shortcode resolves `product_id` — from the attribute, the global `$product` object, or the current post ID, in that order.
2. It reads `r3d_flipbook_id` and `r3d_preview_flipbook_id` from the product's post meta.
3. It performs the purchase/subscription check (see [product-setup.md](product-setup.md)).
4. It calls `[real3dflipbook id="..." mode="..."]` with the resolved flipbook ID and wraps the output in `<div class="r3d-product-flipbook-wrap">`.
5. If both flipbook IDs are empty, or if the resolved flipbook shortcode produces no output, the shortcode returns an empty string.

When `r3d_flipbook_id` contains a semicolon-separated list of IDs, only the **first** ID in the list is used by the shortcode. The full list is used by the thank-you page display (all IDs rendered).

### Output HTML

```html
<div class="r3d-product-flipbook-wrap">
  <!-- [real3dflipbook] output here -->
  <div class="r3d-product-flipbook-play" aria-hidden="true">
    <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
      <circle cx="32" cy="32" r="32" fill="rgba(0,0,0,0.55)" />
      <polygon points="26,20 26,44 46,32" fill="#fff" />
    </svg>
  </div>
</div>
```

The play-button SVG overlay is purely decorative (`aria-hidden="true"`).

### Usage Examples

**Minimal — inside a product description (auto-detects current product):**

```
[product_flipbook]
```

**Explicit product ID:**

```
[product_flipbook product_id="42"]
```

**Normal (inline) display mode instead of lightbox:**

```
[product_flipbook mode="normal"]
```

**Explicit product ID with normal mode:**

```
[product_flipbook product_id="42" mode="normal"]
```

**In a PHP template:**

```php
echo do_shortcode( '[product_flipbook product_id="' . get_the_ID() . '"]' );
```

### Notes

- The shortcode is registered on `plugins_loaded` and is available site-wide, not only on product pages. When used outside a product page context, `product_id` must be supplied explicitly.
- The frontend stylesheet (`r3d-product-flipbook`) is enqueued only on singular `product` post type pages. If you use the shortcode on a non-product page, you may need to enqueue `css/product-flipbook.css` manually.
- The shortcode does not handle multiple flipbooks in a semicolon-separated list; it always renders only the first ID. For displaying multiple flipbooks, place multiple shortcodes with explicit `product_id` attributes.
