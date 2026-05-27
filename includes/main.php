<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/*plugin class*/
class R3D_Woo {


	public $version;
	public $path;
	public $plugin_dir_path;
	public $plugin_dir_url;

	const MINIMUM_REAL3D_FLIPBOOK_VERSION = '3.17.1';

	// Singleton
	private static $instance = null;

	public static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	protected function __construct() {

		$this->version         = R3D_WOO_VERSION;
		$this->path            = R3D_WOO_FILE;
		$this->plugin_dir_path = plugin_dir_path( $this->path );
		$this->plugin_dir_url  = plugin_dir_url( $this->path );

		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'init', array( $this, 'init' ) );

		register_activation_hook( $this->path, array( $this, 'plugin_activated' ) );
		register_deactivation_hook( $this->path, array( $this, 'plugin_deactivated' ) );
	}


	public function plugin_activated() {

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {

			error_log( 'real3d flipbook woocommerce addon activated' );
		}
		$this->add_endpoint();
		flush_rewrite_rules();
	}

	public function plugin_deactivated() {
		flush_rewrite_rules();
	}


	public function init() {

		// Intentionally empty — load_plugin_textdomain moved to plugins_loaded per WordPress handbook.
	}

	/**
	 * Check requirements and add actions
	 */
	public function plugins_loaded() {

		load_plugin_textdomain( 'real3d-flipbook-woocommerce-addon', false, plugin_basename( dirname( R3D_WOO_FILE ) ) . '/languages' );

		// Check if Real3D Flipbook is installed
		if ( ! defined( 'REAL3D_FLIPBOOK_VERSION' ) ) {
			// Display notice that Real3D Flipbook is required
			add_action( 'admin_notices', array( $this, 'admin_notice_missing_real3d_flipbook' ) );
			return;
		}

		// Check for required Real3D Flipbook version
		if ( ! version_compare( REAL3D_FLIPBOOK_VERSION, self::MINIMUM_REAL3D_FLIPBOOK_VERSION, '>=' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_minimum_real3d_flipbook_version' ) );
			return;
		}

		if ( ! function_exists( 'WC' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_missing_woocommerce' ) );
			return;
		}

		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_product_admin_assets' ) );
		add_action( 'save_post', array( $this, 'save_meta_box' ) );

		if ( apply_filters( 'r3d_auto_inject_product_flipbook', false ) ) {
			add_action( 'woocommerce_after_single_product_summary', array( $this, 'add_flipbook_shortcode_single_product_page' ), 25 );
		}

		add_shortcode( 'product_flipbook', array( $this, 'product_flipbook_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_styles' ) );

		add_action( 'woocommerce_thankyou_order_received_text', array( $this, 'change_thankyou_sub_title' ), 20, 2 );

		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_link_my_account' ) );
		add_action( 'woocommerce_account_flipbooks_endpoint', array( $this, 'add_tab_content' ) );
		add_action( 'init', array( $this, 'add_endpoint' ) );

		add_action( 'woocommerce_variation_options', array( $this, 'add_flipbook_checkbox_next_to_manage_stock' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_flipbook_checkbox_value' ), 10, 2 );

		add_filter( 'r3d_woo_purchased_or_subscription', array( $this, 'filter_purchased_or_subscription' ), 10, 1 );
	}

	/**
	 * Include admin scripts on WooCommerce single product page
	 */
	public function admin_scripts() {
		global $wp_query, $post;

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$version   = $this->version;

		// Register scripts.
		wp_register_script( 'r3dwc-admin', $this->plugin_dir_url . 'js/admin.js', array(), $version );
		wp_register_style( 'r3dwc-admin', $this->plugin_dir_url . 'css/admin.css', array(), $version );

		// WooCommerce admin pages.
		if ( $screen_id == 'product' ) {
			wp_enqueue_script( 'r3dwc-admin' );
			wp_enqueue_style( 'r3dwc-admin' );
		}
	}

	/**
	 * Auto-inject flipbook on product page (legacy, opt-in via r3d_auto_inject_product_flipbook filter).
	 */
	public function add_flipbook_shortcode_single_product_page() {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$product_id = $product->get_id();
		echo $this->render_product_flipbook( $product_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped inside render_product_flipbook.
	}


	public function change_thankyou_sub_title( $thank_you_title, $order ) {
		$global_enabled = (bool) get_option( 'r3d_woo_show_thankyou_flipbook' );

		$title = $thank_you_title;
		$items = $order->get_items();

		foreach ( $items as $item ) {
			$product_id       = $item->get_product_id();
			$product_override = get_post_meta( $product_id, 'r3d_show_thankyou_flipbook', true );

			if ( 'no' === $product_override ) {
				continue;
			}
			if ( 'yes' !== $product_override && ! $global_enabled ) {
				continue;
			}

			$flipbook_id         = get_post_meta( $product_id, 'r3d_flipbook_id', true );
			$preview_flipbook_id = get_post_meta( $product_id, 'r3d_preview_flipbook_id', true );

			if ( ! empty( $flipbook_id ) && ( $this->check_user_bought_variation_with_flipbook( $product_id ) || $this->has_active_subscription( $product_id ) ) ) {
				$title .= self::process_flipbook_ids( $flipbook_id, true );
			} elseif ( ! empty( $preview_flipbook_id ) ) {
				$title .= self::process_flipbook_ids( $preview_flipbook_id, false );
			}
		}
		return $title;
	}


	public function add_flipbook_checkbox_next_to_manage_stock( $loop, $variation_data, $variation ) {
		// Define whether the checkbox is checked; get_post_meta() will return '' if no value is found
		$is_checked = get_post_meta( $variation->ID, '_flipbook', true ) === 'yes' ? true : false;
		?>
<label class="tips"
	data-tip="<?php esc_attr_e( 'Check this to hide the flipbook for this variation.', 'real3d-flipbook-woocommerce-addon' ); ?>">
	<input type="checkbox" class="checkbox" name="flipbook[<?php echo esc_attr( $loop ); ?>]" <?php checked( $is_checked ); ?> />
		<?php esc_html_e( 'Flipbook', 'real3d-flipbook-woocommerce-addon' ); ?>
</label>
		<?php
	}

	public function save_flipbook_checkbox_value( $variation_id, $loop ) {
		if ( ! current_user_can( 'edit_post', $variation_id ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified by WooCommerce's product save handler.
		$flipbook_value = isset( $_POST['flipbook'][ $loop ] ) ? 'yes' : 'no';
		update_post_meta( $variation_id, '_flipbook', $flipbook_value );
	}

	public function check_user_bought_variation_with_flipbook( $product_id = 0 ) {
		global $product;

		if ( ! is_user_logged_in() ) {
			return false;
		}

		$current_user = wp_get_current_user();

		if ( ! $product_id && isset( $product ) ) {
			$product_id = $product->get_id();
		}

		if ( ! $product_id ) {
			return false;
		}

		$the_product = wc_get_product( $product_id );

		if ( ! $the_product ) {
			return false;
		}

		if ( ! $the_product->is_type( 'variable' ) ) {
			return wc_customer_bought_product( $current_user->user_email, $current_user->ID, $product_id );
		}

		$variations = $the_product->get_children();
		if ( empty( $variations ) ) {
			return false;
		}

		// Single query: find which variation IDs the customer has purchased.
		$purchased_ids = $this->get_purchased_variation_ids( $current_user, $product_id, $variations );

		foreach ( $purchased_ids as $variation_id ) {
			if ( get_post_meta( $variation_id, '_flipbook', true ) === 'yes' ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get variation IDs from a product that the customer has purchased.
	 *
	 * @param WP_User $user          Current user.
	 * @param int     $product_id    Parent product ID.
	 * @param int[]   $variation_ids All variation IDs of the product.
	 * @return int[] Purchased variation IDs.
	 */
	private function get_purchased_variation_ids( $user, $product_id, $variation_ids ) {
		global $wpdb;

		$statuses        = array_map( 'esc_sql', wc_get_is_paid_statuses() );
		$statuses_string = "'" . implode( "','", $statuses ) . "'";

		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// HPOS-aware query.
			$variation_placeholders = implode( ',', array_fill( 0, count( $variation_ids ), '%d' ) );
			$query_args             = array_merge(
				array( $user->ID ),
				$variation_ids,
				array( $user->user_email ),
				$variation_ids
			);

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $statuses_string is escaped above, $variation_placeholders are format strings
			$results = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT oi_meta.meta_value FROM {$wpdb->prefix}woocommerce_order_items oi
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oi_meta ON oi.order_item_id = oi_meta.order_item_id
					INNER JOIN {$wpdb->prefix}wc_orders o ON oi.order_id = o.id
					WHERE o.status IN ({$statuses_string})
					AND o.customer_id = %d
					AND oi_meta.meta_key = '_variation_id'
					AND oi_meta.meta_value IN ({$variation_placeholders})
					UNION
					SELECT DISTINCT oi_meta.meta_value FROM {$wpdb->prefix}woocommerce_order_items oi
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oi_meta ON oi.order_item_id = oi_meta.order_item_id
					INNER JOIN {$wpdb->prefix}wc_orders o ON oi.order_id = o.id
					INNER JOIN {$wpdb->prefix}wc_order_addresses oa ON o.id = oa.order_id AND oa.address_type = 'billing'
					WHERE o.status IN ({$statuses_string})
					AND oa.email = %s
					AND oi_meta.meta_key = '_variation_id'
					AND oi_meta.meta_value IN ({$variation_placeholders})",
					$query_args
				)
			);
		} else {
			// Legacy post-based orders.
			$variation_placeholders = implode( ',', array_fill( 0, count( $variation_ids ), '%d' ) );
			$query_args             = array_merge(
				array( $user->ID ),
				$variation_ids,
				array( $user->user_email ),
				$variation_ids
			);

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $statuses_string is escaped above, $variation_placeholders are format strings
			$results = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT im.meta_value FROM {$wpdb->prefix}woocommerce_order_items oi
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta im ON oi.order_item_id = im.order_item_id
					INNER JOIN {$wpdb->posts} p ON oi.order_id = p.ID
					INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
					WHERE p.post_status IN ({$statuses_string})
					AND pm.meta_key = '_customer_user'
					AND pm.meta_value = %d
					AND im.meta_key = '_variation_id'
					AND im.meta_value IN ({$variation_placeholders})
					UNION
					SELECT DISTINCT im.meta_value FROM {$wpdb->prefix}woocommerce_order_items oi
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta im ON oi.order_item_id = im.order_item_id
					INNER JOIN {$wpdb->posts} p ON oi.order_id = p.ID
					INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
					WHERE p.post_status IN ({$statuses_string})
					AND pm.meta_key = '_billing_email'
					AND pm.meta_value = %s
					AND im.meta_key = '_variation_id'
					AND im.meta_value IN ({$variation_placeholders})",
					$query_args
				)
			);
		}

		return array_map( 'absint', $results );
	}


	/**
	 * If current user has active WooCommerce subscription.
	 *
	 * @param int $product_id Product ID to scope the subscription check.
	 */
	public function has_active_subscription( $product_id = 0 ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$user_id = get_current_user_id();
		if ( 0 === $user_id ) {
			return false;
		}
		$result = apply_filters( 'r3d_has_active_subscription', null, $user_id, $product_id );
		if ( null !== $result ) {
			return $result;
		}
		if ( function_exists( 'wcs_user_has_subscription' ) ) {
			return wcs_user_has_subscription( $user_id, $product_id, 'active' );
		} elseif ( function_exists( 'pmpro_hasMembershipLevel' ) ) {
			return pmpro_hasMembershipLevel( null, $user_id );
		}
		return false;
	}

	/**
	 * Filter for r3d_woo_purchased_or_subscription — grants full access if user purchased or has active subscription.
	 *
	 * @param bool $access Current access state.
	 * @return bool
	 */
	public function filter_purchased_or_subscription( $access ) {
		if ( $access ) {
			return true;
		}

		$product_id = get_the_ID();
		if ( ! $product_id ) {
			return false;
		}

		return $this->check_user_bought_variation_with_flipbook( $product_id )
			|| $this->has_active_subscription( $product_id );
	}

	/**
	 * Register meta box(es).
	 */
	public function register_meta_boxes() {

		add_meta_box(
			'fliobook-pdf',
			esc_html__( 'Real3D Flipbook', 'real3d-flipbook-woocommerce-addon' ),
			array(
				$this,
				'r3d_meta_box',
			),
			'product',
			'normal',
			'high'
		);
	}



	public function r3d_meta_box( $post ) {
		// Fetching flipbook IDs from post metadata
		$r3d_flipbook_id         = get_post_meta( $post->ID, 'r3d_flipbook_id', true );
		$r3d_preview_flipbook_id = get_post_meta( $post->ID, 'r3d_preview_flipbook_id', true );
		// Security field
		wp_nonce_field( 'r3d_save', 'r3d_nonce' );

		// Hidden inputs to store the selected flipbook IDs
		?>
<input type="hidden" id="r3d_flipbook_id" name="r3d_flipbook_id" value="<?php echo esc_attr( $r3d_flipbook_id ); ?>">
<input type="hidden" id="r3d_preview_flipbook_id" name="r3d_preview_flipbook_id"
	value="<?php echo esc_attr( $r3d_preview_flipbook_id ); ?>">

		<?php
		// Query for all published r3d posts, sorted alphabetically.
		$args         = array(
			'post_type'      => 'r3d',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'orderby'        => 'title',
			'order'          => 'ASC',
		);
		$r3d_post_ids = get_posts( $args );

		if ( ! empty( $r3d_post_ids ) ) :
			// Render flipbooks for purchased products
			self::render_flipbook_thumbnails( $r3d_post_ids, $r3d_flipbook_id, __( 'Select flipbook for purchased product', 'real3d-flipbook-woocommerce-addon' ), '' );

			// Render flipbooks for preview (non-purchased products)
			self::render_flipbook_thumbnails( $r3d_post_ids, $r3d_preview_flipbook_id, __( 'Select preview flipbook for non-purchased product', 'real3d-flipbook-woocommerce-addon' ), 'preview-flipbook' );

		else :
			echo "<p>No flipbooks found. <a href='" . esc_url( admin_url( 'post-new.php?post_type=r3d' ) ) . "'>" . esc_html__( 'Create new flipbook', 'real3d-flipbook-woocommerce-addon' ) . '</a></p>';
		endif;

		$product_override = get_post_meta( $post->ID, 'r3d_show_thankyou_flipbook', true );
		?>
		<hr style="margin: 20px 0;">
		<h4><?php esc_html_e( 'Order Confirmation Page', 'real3d-flipbook-woocommerce-addon' ); ?></h4>
		<p>
			<label for="r3d_show_thankyou_flipbook">
				<?php esc_html_e( 'Show flipbook on thank you page:', 'real3d-flipbook-woocommerce-addon' ); ?>
			</label>
			<select name="r3d_show_thankyou_flipbook" id="r3d_show_thankyou_flipbook">
				<option value="" <?php selected( $product_override, '' ); ?>><?php esc_html_e( 'Default (use global setting)', 'real3d-flipbook-woocommerce-addon' ); ?></option>
				<option value="yes" <?php selected( $product_override, 'yes' ); ?>><?php esc_html_e( 'Enable (always show)', 'real3d-flipbook-woocommerce-addon' ); ?></option>
				<option value="no" <?php selected( $product_override, 'no' ); ?>><?php esc_html_e( 'Disable (never show)', 'real3d-flipbook-woocommerce-addon' ); ?></option>
			</select>
		</p>
		<?php
	}





	/**
	 * Save meta box content.
	 *
	 * @param int $post_id Post ID
	 */
	public function save_meta_box( $post_id ) {

		// Check if nonce is valid.
		if ( ! isset( $_POST['r3d_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['r3d_nonce'] ) ), 'r3d_save' ) ) {
			return;
		}

		// Check if user has permissions to save data.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check if not an autosave.
		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Check if not a revision.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( isset( $_POST['post_type'] ) && sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) === 'product' ) {

			$meta_fields = array( 'r3d_flipbook_id', 'r3d_preview_flipbook_id' );
			foreach ( $meta_fields as $field ) {
				if ( ! isset( $_POST[ $field ] ) ) {
					continue;
				}
				$value = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
				if ( '' === $value ) {
					delete_post_meta( $post_id, $field );
				} else {
					update_post_meta( $post_id, $field, $value );
				}
			}

			// Per-product override (yes/no/empty).
			$thankyou_override = isset( $_POST['r3d_show_thankyou_flipbook'] ) ? sanitize_text_field( wp_unslash( $_POST['r3d_show_thankyou_flipbook'] ) ) : '';
			if ( in_array( $thankyou_override, array( 'yes', 'no' ), true ) ) {
				update_post_meta( $post_id, 'r3d_show_thankyou_flipbook', $thankyou_override );
			} else {
				delete_post_meta( $post_id, 'r3d_show_thankyou_flipbook' );
			}
		}
	}

	public function add_link_my_account( $items ) {
		$flipbooks = $this->get_purchased_flipbooks();
		if ( ! empty( $flipbooks ) ) {
			// Insert 'Purchased Flipbooks' before 'Logout'
			$logout = $items['customer-logout']; // Backup the logout item
			unset( $items['customer-logout'] ); // Remove the logout item from the array

			// Add the new 'Purchased Flipbooks' item
			$items['flipbooks'] = esc_html__( 'Purchased Flipbooks', 'real3d-flipbook-woocommerce-addon' );

			// Re-add the logout item
			$items['customer-logout'] = $logout;
		}
		return $items;
	}



	private function get_purchased_flipbooks() {
		$current_user = wp_get_current_user();
		if ( 0 === (int) $current_user->ID ) {
			return array();
		}

		$cache_key = 'r3d_flipbooks_' . $current_user->ID;
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$args            = array(
			'customer_id' => $current_user->ID,
			'limit'       => 100,
			'status'      => array( 'wc-completed', 'wc-processing' ),
			'orderby'     => 'date',
			'order'       => 'DESC',
		);
		$customer_orders = wc_get_orders( $args );
		$flipbook_ids    = array();

		foreach ( $customer_orders as $order ) {
			$items = $order->get_items();
			foreach ( $items as $item ) {
				$variation_id = $item->get_variation_id();
				$product_id   = $item->get_product_id();
				$id_to_check  = $variation_id ? $variation_id : $product_id;

				$flipbook_enabled = get_post_meta( $id_to_check, '_flipbook', true ) !== 'no';
				$flipbook_id      = get_post_meta( $id_to_check, 'r3d_flipbook_id', true );

				if ( empty( $flipbook_id ) && $variation_id ) {
					$flipbook_id = get_post_meta( $product_id, 'r3d_flipbook_id', true );
				}

				if ( $flipbook_enabled && ! empty( $flipbook_id ) ) {
					$flipbook_ids_array = explode( ';', $flipbook_id );
					foreach ( $flipbook_ids_array as $id ) {
						$id = trim( $id );
						if ( (int) $id > 0 && ! in_array( $id, $flipbook_ids, true ) ) {
							$flipbook_ids[] = $id;
						}
					}
				}
			}
		}

		set_transient( $cache_key, $flipbook_ids, HOUR_IN_SECONDS );

		return $flipbook_ids;
	}



	public function add_tab_content() {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return;
		}

		$flipbook_ids = $this->get_purchased_flipbooks();
		if ( empty( $flipbook_ids ) ) {
			return;
		}

		foreach ( $flipbook_ids as $flipbook_id ) {
			echo do_shortcode( '[real3dflipbook id="' . esc_attr( $flipbook_id ) . '" mode="lightbox" previewpages=""]' );
		}
	}



	public function add_endpoint() {
		add_rewrite_endpoint( 'flipbooks', EP_ROOT | EP_PAGES );
	}

	public function admin_notice_minimum_real3d_flipbook_version() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'real3d-flipbook-woocommerce-addon' ),
			'<strong>' . esc_html__( 'Real3D Flipbook WooCommerce Addon', 'real3d-flipbook-woocommerce-addon' ) . '</strong>',
			'<strong>' . esc_html__( 'Real3D Flipbook', 'real3d-flipbook-woocommerce-addon' ) . '</strong>',
			self::MINIMUM_REAL3D_FLIPBOOK_VERSION
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', wp_kses_post( $message ) );
	}

	public function admin_notice_missing_real3d_flipbook() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
		/* translators: 1: Plugin name 2: Elementor */
			esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'real3d-flipbook-woocommerce-addon' ),
			'<strong>' . esc_html__( 'Real3D Flipbook WooCommerce Addon', 'real3d-flipbook-woocommerce-addon' ) . '</strong>',
			'<strong>' . esc_html__( 'Real3D Flipbook', 'real3d-flipbook-woocommerce-addon' ) . '</strong>'
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', wp_kses_post( $message ) );
	}

	public function admin_notice_missing_woocommerce() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
		/* translators: 1: Plugin name 2: Elementor */
			esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'real3d-flipbook-woocommerce-addon' ),
			'<strong>' . esc_html__( 'Real3D Flipbook WooCommerce Addon', 'real3d-flipbook-woocommerce-addon' ) . '</strong>',
			'<strong>' . esc_html__( 'WooCommerce', 'real3d-flipbook-woocommerce-addon' ) . '</strong>'
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', wp_kses_post( $message ) );
	}

	/**
	 * [product_flipbook] shortcode handler.
	 *
	 * @param  array $atts Shortcode attributes: product_id, mode.
	 * @return string Rendered flipbook HTML or empty string.
	 */
	public function product_flipbook_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'product_id' => '',
				'mode'       => 'lightbox',
			),
			$atts,
			'product_flipbook'
		);

		$product_id = $atts['product_id'];

		if ( empty( $product_id ) ) {
			global $product;
			if ( $product instanceof \WC_Product ) {
				$product_id = $product->get_id();
			} else {
				$product_id = get_the_ID();
			}
		}

		$product_id = absint( $product_id );
		if ( ! $product_id ) {
			return '';
		}

		return $this->render_product_flipbook( $product_id, $atts['mode'] );
	}

	/**
	 * Render the flipbook for a product, choosing full or preview based on purchase status.
	 *
	 * @param  int    $product_id WooCommerce product ID.
	 * @param  string $mode       Flipbook display mode (default: lightbox).
	 * @return string Rendered HTML or empty string.
	 */
	private function render_product_flipbook( $product_id, $mode = 'lightbox' ) {
		$flipbook_id         = get_post_meta( $product_id, 'r3d_flipbook_id', true );
		$preview_flipbook_id = get_post_meta( $product_id, 'r3d_preview_flipbook_id', true );

		$first_flipbook_id         = ! empty( $flipbook_id ) ? self::get_first_flipbook_id( $flipbook_id ) : '';
		$first_preview_flipbook_id = ! empty( $preview_flipbook_id ) ? self::get_first_flipbook_id( $preview_flipbook_id ) : '';

		$show_full = false;
		if ( $first_flipbook_id && (int) $first_flipbook_id > 0 ) {
			$show_full = $this->check_user_bought_variation_with_flipbook( $product_id )
				|| $this->has_active_subscription( $product_id );
		}

		$shortcode_output = '';
		if ( $show_full && $first_flipbook_id ) {
			$shortcode_output = do_shortcode(
				'[real3dflipbook id="' . esc_attr( $first_flipbook_id ) . '" mode="' . esc_attr( $mode ) . '"]'
			);
		} elseif ( $first_preview_flipbook_id && (int) $first_preview_flipbook_id > 0 ) {
			$shortcode_output = do_shortcode(
				'[real3dflipbook id="' . esc_attr( $first_preview_flipbook_id ) . '" mode="' . esc_attr( $mode ) . '"]'
			);
		}

		if ( empty( $shortcode_output ) ) {
			return '';
		}

		return '<div class="r3d-product-flipbook-wrap">'
			. $shortcode_output
			. '<div class="r3d-product-flipbook-play" aria-hidden="true">'
			. '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">'
			. '<circle cx="32" cy="32" r="32" fill="rgba(0,0,0,0.55)"/>'
			. '<polygon points="26,20 26,44 46,32" fill="#fff"/>'
			. '</svg>'
			. '</div>'
			. '</div>';
	}

	/**
	 * Enqueue frontend CSS for the product flipbook wrapper on product pages.
	 */
	public function enqueue_frontend_styles() {
		if ( ! is_singular( 'product' ) ) {
			return;
		}

		wp_enqueue_style(
			'r3d-product-flipbook',
			$this->plugin_dir_url . 'css/product-flipbook.css',
			array(),
			$this->version
		);
	}

	/**
	 * Get the first flipbook ID from a semicolon-separated list.
	 *
	 * @param  string $flipbook_ids Semicolon-separated flipbook IDs.
	 * @return string The first ID, trimmed of whitespace.
	 */
	private static function get_first_flipbook_id( $flipbook_ids ) {
		$flipbook_ids_array = explode( ';', $flipbook_ids );
		return trim( $flipbook_ids_array[0] );
	}

	/**
	 * Build flipbook shortcode output for a semicolon-separated list of IDs.
	 *
	 * @param  string $flipbook_ids Semicolon-separated flipbook IDs.
	 * @param  bool   $is_purchased Whether the product has been purchased.
	 * @return string Rendered shortcode HTML for all valid IDs.
	 */
	private static function process_flipbook_ids( $flipbook_ids, $is_purchased ) {
		$title_addition     = '';
		$flipbook_ids_array = explode( ';', $flipbook_ids );

		foreach ( $flipbook_ids_array as $id ) {
			$id = trim( $id );
			if ( ! empty( $id ) && (int) $id > 0 ) {
				$shortcode       = '[real3dflipbook id="' . esc_attr( $id ) . '"]';
				$title_addition .= do_shortcode( $shortcode );
			}
		}

		return $title_addition;
	}

	/**
	 * Render thumbnail picker UI for a set of flipbooks.
	 *
	 * @param int[]  $r3d_post_ids          Array of r3d post IDs to display.
	 * @param string $selected_flipbook_ids Semicolon-separated currently-selected IDs.
	 * @param string $section_title         Heading text for the section.
	 * @param string $class                 Additional CSS class for the wrapper element.
	 */
	private static function render_flipbook_thumbnails( $r3d_post_ids, $selected_flipbook_ids, $section_title, $class ) {
		$selected_flipbook_ids_array = explode( ';', $selected_flipbook_ids );
		$total                       = count( $r3d_post_ids );
		$wrapper_id                  = 'r3d-wrapper-' . sanitize_html_class( $class ? $class : 'purchased' );

		echo '<h4>' . esc_html( $section_title ) . '</h4>';
		echo "<input type='search' class='r3d-pf-search' placeholder='" . esc_attr__( 'Search flipbooks...', 'real3d-flipbook-woocommerce-addon' ) . "' />";
		echo "<div id='" . esc_attr( $wrapper_id ) . "' class='r3d-thumbs-wrapper " . esc_attr( $class ) . "'>";
		echo "<div class='r3d-thumbs'>";
		foreach ( $r3d_post_ids as $post_id ) {
			$flipbook      = r3d_get_flipbook( $post_id );
			$name          = get_the_title( $post_id );
			$name          = empty( $name ) ? __( ' (no title)', 'real3d-flipbook-woocommerce-addon' ) : $name;
			$thumbnail_url = isset( $flipbook['lightboxThumbnailUrl'] ) ? $flipbook['lightboxThumbnailUrl'] : '';
			echo "<div class='r3d-thumb " . ( in_array( (string) $post_id, $selected_flipbook_ids_array ) ? 'r3d-thumb-selected' : '' ) . "' data-id='" . esc_attr( $post_id ) . "'>";
			echo "<div class='r3d-thumb-img' style='background-image: url(\"" . esc_url( $thumbnail_url ) . "\");'></div>";
			echo "<p class='r3d-thumb-name'>" . esc_html( $name ) . '</p>';
			echo '</div>';
		}
		echo '</div>';
		echo '</div>';
		echo "<span class='r3d-pf-count'>" . esc_html( $total . ' flipbook' . ( 1 !== $total ? 's' : '' ) ) . '</span>';
	}

	/**
	 * Enqueue admin scripts/styles for flipbook picker search on product screens.
	 */
	public function enqueue_product_admin_assets() {
		$screen = get_current_screen();
		if ( ! $screen || 'product' !== $screen->id ) {
			return;
		}

		wp_add_inline_script(
			'r3dwc-admin',
			'document.addEventListener("DOMContentLoaded",function(){' .
				'document.querySelectorAll(".r3d-pf-search").forEach(function(input){' .
					'var wrapper=input.nextElementSibling;' .
					'if(!wrapper||!wrapper.classList.contains("r3d-thumbs-wrapper"))return;' .
					'var count=wrapper.nextElementSibling;' .
					'var thumbs=wrapper.querySelectorAll(".r3d-thumb");' .
					'var total=thumbs.length;' .
					'input.addEventListener("input",function(){' .
						'var q=this.value.toLowerCase(),visible=0;' .
						'thumbs.forEach(function(thumb){' .
							'var name=thumb.querySelector(".r3d-thumb-name");' .
							'var text=name?name.textContent.toLowerCase():"";' .
							'var match=!q||text.indexOf(q)!==-1;' .
							'thumb.style.display=match?"":"none";' .
							'if(match)visible++;' .
						'});' .
						'if(count&&count.classList.contains("r3d-pf-count")){' .
							'count.textContent=q?(visible+" of "+total+" flipbook"+(total!==1?"s":"")):(total+" flipbook"+(total!==1?"s":""));' .
						'}' .
					'});' .
				'});' .
			'});'
		);
	}
}

