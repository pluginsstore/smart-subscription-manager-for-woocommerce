<?php
/**
 * Main class for Smart Subscription For Woocommerce
 *
 * @package     smart-subscription-for-woocommerce/includes/
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Smart_Subscription_For_Woocommerce' ) ) {

	/**
	 * Main class for Smart Subscription For Woocommerce
	 */
	final class Smart_Subscription_For_Woocommerce {

		/**
		 * Variable to hold instance of this class
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Get single instance of this class
		 *
		 * @return Smart_Subscription_For_Woocommerce
		 */
		public static function get_instance() {

			// Check if instance is already exists or not.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor of this class.
		 */
		private function __construct() {

			$this->constants();
			add_action( 'init', array( $this, 'ssfw_init_function' ) );
			$this->include_files();
			if ( 'yes' === get_option( 'ssfw_enable_settings' ) ) {

				add_filter( 'woocommerce_get_price_html', array( $this, 'change_price_html_for_subscription_product' ), 10, 2 );
				add_action( 'woocommerce_order_status_changed', array( $this, 'order_status_change_callback' ), 999, 3 );
			}

		}


		/**
		 * Function to define constants
		 */
		public function constants() {

			if ( ! defined( 'SSFW_PLUGIN_DIRPATH' ) ) {
				define( 'SSFW_PLUGIN_DIRPATH', __DIR__ );
			}
			if ( ! defined( 'SSFW_PLUGIN_BASENAME' ) ) {
				define( 'SSFW_PLUGIN_BASENAME', plugin_basename( dirname( SSFW_PLUGIN_FILE ) ) );
			}
			if ( ! defined( 'SSFW_PLUGIN_DIR' ) ) {
				define( 'SSFW_PLUGIN_DIR', dirname( plugin_basename( SSFW_PLUGIN_FILE ) ) );
			}
			if ( ! defined( 'SSFW_PLUGIN_URL' ) ) {
				define( 'SSFW_PLUGIN_URL', plugins_url( SSFW_PLUGIN_DIR ) );
			}
			if ( ! defined( 'SSFW_PLUGIN_DIR_PATH' ) ) {
				define( 'SSFW_PLUGIN_DIR_PATH', plugin_dir_path( SSFW_PLUGIN_FILE ) );
			}
			if ( ! defined( 'SSFW_PLUGIN_VERSION' ) ) {
				define( 'SSFW_PLUGIN_VERSION', '1.0.0' );
			}

			if ( ! defined( 'SSFW_TIMEZONE_STR' ) ) {
				$offset       = get_option( 'gmt_offset' );
				$timezone_str = sprintf( '%+02d:%02d', (int) $offset, ( $offset - floor( $offset ) ) * 60 );
				define( 'SSFW_TIMEZONE_STR', $timezone_str );
			}

		}

		/**
		 * Smart Subscription For Woocommerce functions on site initialization.
		 */
		public function ssfw_init_function() {
			$this->load_plugin_textdomain();
			$this->ssfw_register_smart_subscription_type();
		}


		/**
		 * Function to load  the plugin text domain so that translation strings can be used in internationalization.
		 * Load the plugin text domain for translation.
		 */
		public function load_plugin_textdomain() {
			$locale = apply_filters( 'plugin_locale', determine_locale(), 'smart-subscription-for-woocommerce' );

			unload_textdomain( 'smart-subscription-for-woocommerce' );
			load_textdomain( 'smart-subscription-for-woocommerce', WP_LANG_DIR . '/smart-subscription-for-woocommerce/smart-subscription-for-woocommerce-' . $locale . '.mo' );
			load_plugin_textdomain( 'smart-subscription-for-woocommerce', false, SSFW_PLUGIN_BASENAME . '/languages' );
		}


		/**
		 * Include files fo the plugin.
		 */
		public function include_files() {
			if ( is_admin() ) {

				include_once 'admin/class-ssfw-admin-settings.php';
				include_once 'admin/class-ssfw-admin.php';
			}
			if ( 'yes' === get_option( 'ssfw_enable_settings' ) ) {

				include_once 'smart-subscription-for-woocommerce-global-functions.php';
				if ( is_admin() ) {
					include_once 'admin/class-ssfw-product-settings.php';
				}

				include_once 'class-smart-subscription-order.php';
				include_once 'class-ssfw-cart.php';
				include_once 'class-ssfw-subscription-order-scheduler.php';

			}

		}

		/**
		 * Register Subscription type
		 */
		public function ssfw_register_smart_subscription_type() {
			if ( ! function_exists( 'wc_register_order_type' ) ) {
				return;
			}
			wc_register_order_type(
				'smart_subscription',
				array(
					'labels'                           => array(
						'name'               => __( 'Smart Subscription', 'smart-subscription-for-woocommerce' ),
						'singular_name'      => __( 'Smart Subscription', 'smart-subscription-for-woocommerce' ),
						'add_new'            => __( 'Add Smart Subscription', 'smart-subscription-for-woocommerce' ),
						'add_new_item'       => __( 'Add New Smart Subscription', 'smart-subscription-for-woocommerce' ),
						'edit'               => __( 'Edit', 'smart-subscription-for-woocommerce' ),
						'edit_item'          => __( 'Edit Smart Subscription', 'smart-subscription-for-woocommerce' ),
						'new_item'           => __( 'New Smart Subscription', 'smart-subscription-for-woocommerce' ),
						'view'               => __( 'View Smart Subscription', 'smart-subscription-for-woocommerce' ),
						'view_item'          => __( 'View Smart Subscription', 'smart-subscription-for-woocommerce' ),
						'search_items'       => __( 'Search Smart Subscription', 'smart-subscription-for-woocommerce' ),
						'not_found'          => __( 'Not Found', 'smart-subscription-for-woocommerce' ),
						'not_found_in_trash' => __( 'No Smart Subscription found in the trash', 'smart-subscription-for-woocommerce' ),
						'parent'             => __( 'Parent Smart Subscription', 'smart-subscription-for-woocommerce' ),
						'menu_name'          => __( 'Smart Subscription', 'smart-subscription-for-woocommerce' ),
					),
					'description'                      => __( 'Smart subscription.', 'smart-subscription-for-woocommerce' ),
					'capability_type'                  => 'shop_order',
					'class_name'                       => 'Smart_Subscription_Order',
					'public'                           => false,
					'show_ui'                          => true,
					'map_meta_cap'                     => true,
					'publicly_queryable'               => false,
					'exclude_from_search'              => true,
					'show_in_menu'                     => true,
					'hierarchical'                     => false,
					'show_in_nav_menus'                => true,
					'exclude_from_orders_screen'       => true,
					'add_order_meta_boxes'             => true,
					'exclude_from_order_count'         => true,
					'exclude_from_order_views'         => true,
					'exclude_from_order_webhooks'      => true,
					'exclude_from_order_reports'       => true,
					'exclude_from_order_sales_reports' => true,
					'rewrite'                          => false,
					'query_var'                        => false,
					'supports'                         => array( 'title', 'comments', 'custom-fields' ),
					'has_archive'                      => false,
				)
			);

		}

		/**
		 * Check if subscription
		 *
		 * @param object $product NA.
		 */
		public function check_if_subscription( $product ) {
			if ( is_numeric( $product ) ) {
				$product = wc_get_product( $product );
			}

			if ( ! $product instanceof WC_Product ) {
				return false;
			}

			$is_subscription_product = $product->get_meta( '_ssfw_subscription_product' );
			$recurring_period        = $product->get_meta( 'smart_subscription_recurring_input' );

			return apply_filters( 'ssfw_check_if_subscription_product', ( 'yes' === $is_subscription_product && '' !== $recurring_period ), $product->get_id() );
		}


		/**
		 * Change price html
		 *
		 * @param string $price_html NA.
		 * @param object $product NA.
		 */
		public function change_price_html_for_subscription_product( $price_html, $product ) {

			if ( ! $this->check_if_subscription( $product->get_id() ) ) {
				return $price_html;
			}

			$price_html = ssfw_change_subscription_price_html( $price_html, $product );
			return $price_html;
		}

		/**
		 * Create Subscription Order
		 *
		 * @param object $_order NA.
		 * @param bool   $renewal NA.
		 * @param array  $cart_item NA.
		 */
		public function create_subscription_order( $_order, $renewal = false, $cart_item = false ) {

			if ( empty( $_order ) ) {
				return;
			}
			$order_id = $_order->get_id();
			if ( $renewal ) {

				$subscription_order = wc_create_order();
			} else {
				$subscription_order = new Smart_Subscription_Order();

			}

			$subscription_order->set_customer_id( $_order->get_user_id() );
			$subscription_order->set_date_created( gmdate( 'Y-m-d H:i:s' ) );
			$subscription_order->set_created_via( '' );
			$subscription_order->save();
			$subscription_order->update_status( 'wc-smart_renewal' );
			$subscription_order->set_currency( $_order->get_currency() );
			$subscription_id = $subscription_order->get_id();

			if ( ! $renewal && $cart_item && is_array( $cart_item ) ) {

				$subscription_order->add_product(
					$cart_item['data'],
					$cart_item['quantity'],
					array(
						'totals' => array(
							'subtotal_tax' => $cart_item['line_subtotal_tax'],
							'tax'          => $cart_item['line_tax'],
							'tax_data'     => array(
								'subtotal' => array( $cart_item['line_subtotal_tax'] ),
								'total'    => array( $cart_item['line_tax'] ),
							),
							'subtotal'     => $cart_item['line_subtotal'],
							'total'        => $cart_item['line_total'],
						),
					)
				);
				$subscription_order->update_meta_data( 'line_subtotal_tax', $cart_item['line_subtotal_tax'] );
				$subscription_order->update_meta_data( 'line_tax', $cart_item['line_tax'] );
				$subscription_order->update_meta_data( 'line_subtotal', $cart_item['line_subtotal'] );
				$subscription_order->update_meta_data( 'line_total', $cart_item['line_total'] );
				$subscription_order->set_subscription_start_date( time() );
				$subscription_order->set_address( $_order->get_address( 'billing' ), 'billing' );
				$subscription_order->set_address( $_order->get_address( 'shipping' ), 'shipping' );
				$subscription_order->set_payment_method( $_order->get_payment_method() );
				$subscription_order->set_payment_method_title( $_order->get_payment_method_title() );
				$product_id = $cart_item['data']->get_id();
				$subscription_order->update_meta_data( 'smart_subscription_parent_order_id', $order_id );
				$subscription_order->update_meta_data( 'smart_subscription_product_id', $product_id );
				$subscription_order->update_meta_data( 'smart_subscription_product_qty', $cart_item['quantity'] );
				$subscription_order->set_subscription_status( 'pending' );
				$_order->update_meta_data( 'smart_subscription_id', $subscription_id );
				$_order->save();
				$subscription_order->set_as_subscription();
				$subscription_order->set_subscription_start_date( time() );
				$smart_subscription_recurring_input       = get_post_meta( $product_id, 'smart_subscription_recurring_input', true );
				$smart_subscription_recurring_period      = get_post_meta( $product_id, 'smart_subscription_recurring_period', true );
				$smart_subscription_recurring_expiry      = get_post_meta( $product_id, 'smart_subscription_recurring_expiry', true );
				$smart_subscription_recurring_expiry_time = get_post_meta( $product_id, 'smart_subscription_recurring_expiry_time', true );
				if ( 1 === (int) $smart_subscription_recurring_input ) {
					$smart_subscription_recurring_period = str_replace( 's', '', $smart_subscription_recurring_period );
				}
				$subscription_order->set_subscription_interval( "{$smart_subscription_recurring_input} {$smart_subscription_recurring_period}" );
				$subscription_order->set_subscription_next_payment_date();
				if ( 'never' === $smart_subscription_recurring_expiry ) {
					$subscription_order->set_subscription_expiry( 'never' );
				} else {
					$subscription_order->set_subscription_expiry( "{$smart_subscription_recurring_expiry_time} {$smart_subscription_recurring_period}" );
				}
			} else {

				$parent_order_id = $_order->get_meta( 'smart_subscription_parent_order_id' );
				$parent_order    = wc_get_order( $parent_order_id );
				$subscription_order->set_address( $parent_order->get_address( 'billing' ), 'billing' );
				$subscription_order->set_address( $parent_order->get_address( 'shipping' ), 'shipping' );
				$subscription_order->set_payment_method( $parent_order->get_payment_method() );
				$subscription_order->set_payment_method_title( $parent_order->get_payment_method_title() );
				$subscription_order->add_product(
					wc_get_product( $_order->get_meta( 'smart_subscription_product_id' ) ),
					$_order->get_meta( 'smart_subscription_product_qty' ),
					array(
						'totals' => array(
							'subtotal_tax' => $_order->get_meta( 'line_subtotal_tax' ),
							'tax'          => $_order->get_meta( 'line_tax' ),
							'tax_data'     => array(
								'subtotal' => array( $_order->get_meta( 'line_subtotal_tax' ) ),
								'total'    => array( $_order->get_meta( 'line_tax' ) ),
							),
							'subtotal'     => $_order->get_meta( 'line_subtotal' ),
							'total'        => $_order->get_meta( 'line_total' ),
						),
					)
				);
				$product_id                        = $_order->get_meta( 'smart_subscription_product_id' );
				$smart_subscription_renewal_orders = $_order->get_meta( 'smart_subscription_renewal_orders' );
				if ( empty( $smart_subscription_renewal_orders ) ) {
					$smart_subscription_renewal_orders = array( $order_id );

				} else {
					$smart_subscription_renewal_orders[] = $order_id;

				}
				$renewal_order_count = $_order->get_meta( 'smart_subscription_renewal_order_count' );
				if ( empty( $renewal_order_count ) ) {
					$renewal_order_count = 1;
				} else {
					$renewal_order_count = intval( $renewal_order_count ) + 1;
				}

				$subscription_order->update_meta_data( 'smart_subscription_parent_order_id', $parent_order_id );
				$subscription_order->update_meta_data( 'smart_subscription_id', $order_id );
				$subscription_order->update_meta_data( 'is_smart_subscription_renewal_order', 'yes' );
				$_order->update_meta_data( 'smart_subscription_renewal_order_count', $renewal_order_count );
				$_order->update_meta_data( 'smart_subscription_renewal_orders', $smart_subscription_renewal_orders );
				$_order->update_meta_data( 'smart_subscription_last_renewal_order_id', $subscription_id );
				$_order->set_subscription_start_date( time() );
				$_order->set_subscription_next_payment_date();
				$_order->save();
			}

			$subscription_order->update_taxes();
			$subscription_order->calculate_totals();
			$subscription_order->save();
			return $subscription_id;

		}

		/**
		 * Order status change callback
		 *
		 * @param int    $order_id NA.
		 * @param string $old_status NA.
		 * @param string $new_status NA.
		 */
		public function order_status_change_callback( $order_id, $old_status, $new_status ) {

			if ( 'completed' !== $new_status && 'processing' !== $new_status ) {
				return;
			}
			if ( ! $order_id ) {
				return;
			}
			$order = wc_get_order( $order_id );
			if ( ! $order->meta_exists( 'smart_subscription_id' ) ) {
				return;
			}
			$subscription_id    = $order->get_meta( 'smart_subscription_id' );
			$subscription_order = new Smart_Subscription_Order( $subscription_id );

			if ( ! $subscription_order && ! is_object( $subscription_order ) ) {
				return;
			}

			$subscription_order->set_subscription_status( 'active' );
			$subscription_order->set_subscription_next_payment_date();
			$subscription_order->update_status( 'completed' );

		}

	}
}
