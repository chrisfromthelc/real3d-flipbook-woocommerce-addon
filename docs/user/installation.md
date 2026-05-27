# Installation

## Requirements

The following must be installed and active before activating this addon:

| Dependency             | Minimum version | Notes                                       |
| ---------------------- | --------------- | ------------------------------------------- |
| PHP                    | 7.4             |                                             |
| WordPress              | 6.0             |                                             |
| WooCommerce            | 5.0             | Must be active, not just installed          |
| Real3D Flipbook plugin | 3.17.1          | The core flipbook plugin this addon extends |

The addon will not load if either Real3D Flipbook or WooCommerce is missing. An admin notice is displayed in each case.

## Install Steps

1. Download `real3d-flipbook-woocommerce-addon.zip` from the [latest GitHub release](https://github.com/chrisfromthelc/real3d-flipbook-woocommerce-addon/releases/latest).
2. In WordPress admin, go to **Plugins > Add New > Upload Plugin**.
3. Click **Choose File**, select the downloaded ZIP, then click **Install Now**.
4. Click **Activate Plugin**.

Alternatively, unzip the archive and upload the `real3d-flipbook-woocommerce-addon` folder to `wp-content/plugins/` via FTP, then activate from **Plugins > Installed Plugins**.

## Activation

On first activation the plugin registers a `flipbooks` rewrite endpoint (used for the My Account tab) and flushes rewrite rules. No database tables are created; all data is stored in standard WordPress post meta and options.

If Real3D Flipbook is not active when you activate this addon, the addon loads but immediately shows an admin notice and skips registering all hooks. Activating Real3D Flipbook afterward resolves this without needing to reactivate the addon.

## Verifying the Setup

After activation, confirm the following:

1. Go to any WooCommerce product edit screen. A **Real3D Flipbook** meta box should appear in the normal (center) column, below the product description.
2. If no flipbooks exist yet, the meta box displays a link to create one. If flipbooks exist, thumbnail pickers are shown for the full and preview flipbooks.
3. Go to **WooCommerce > Settings** and confirm WooCommerce is operational.
4. Visit the frontend of a product page and place the `[product_flipbook]` shortcode in the product description to verify rendering.

If the meta box does not appear, check:

- Both Real3D Flipbook and WooCommerce are active (check **Plugins > Installed Plugins**).
- The current user has the `edit_posts` capability.
- No JavaScript errors in the browser console on the product edit screen.
