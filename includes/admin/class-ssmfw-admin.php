<?php
/**
 * Main class for Admin
 *
 * @package     smart-subscription-manager-for-woocommerce/includes/admin
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Ssmfw_Admin' ) ) {

	/**
	 * Main class for Smart Subscription Manager For Woocommerce
	 */
	class Ssmfw_Admin {

		/**
		 * Variable to hold instance of this class
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Get single instance of this class
		 *
		 * @return Ssmfw_Admin
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
			add_action( 'admin_enqueue_scripts', array( $this, 'ssmfw_enqueue_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'ssmfw_enqueue_styles' ) );
			add_filter( 'woocommerce_register_shop_order_post_statuses', array( $this, 'ssmfw_register_renewal_order_status' ) );
			add_filter( 'wc_order_statuses', array( $this, 'ssmfw_woocommerce_add_renewal_order_status' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu_callback' ) );

		}

		/**
		 * Function to enqueue style
		 *
		 * @return void
		 */
		public function ssmfw_enqueue_styles() {
			wp_enqueue_style( 'ssmfw-admin', SSMFW_PLUGIN_URL . '/assets/css/ssmfw-admin.css', array(), SSMFW_PLUGIN_VERSION, true );

		}

		/**
		 * Function to enqueue script
		 *
		 * @return void
		 */
		public function ssmfw_enqueue_scripts() {
			wp_register_script( 'ssmfw-admin', SSMFW_PLUGIN_URL . '/assets/js/ssmfw-admin.js', array(), SSMFW_PLUGIN_VERSION, true );
			wp_enqueue_script( 'ssmfw-admin' );
		}

		/**
		 * Function to register order status
		 *
		 * @param array $status NA.
		 * @return array
		 */
		public function ssmfw_register_renewal_order_status( $status ) {
			$status['wc-smart_renewal'] = array(
				'label'                     => _x( 'Smart Renewal', 'Order status', 'smart-subscription-manager-for-woocommerce' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of orders */
				'label_count'               => _n_noop( 'Smart Renewal <span class="count">(%s)</span>', 'Smart Renewal <span class="count">(%s)</span>', 'smart-subscription-manager-for-woocommerce' ),
			);
			return $status;
		}


		/**
		 * Function to add order status
		 *
		 * @param array $status NA.
		 * @return array
		 */
		public function ssmfw_woocommerce_add_renewal_order_status( $status ) {
			$status['wc-smart_renewal'] = _x( 'Smart Renewal', 'Order status', 'smart-subscription-manager-for-woocommerce' );
			return $status;
		}


		/**
		 * Function to add menu
		 *
		 * @return void
		 */
		public function admin_menu_callback() {
			remove_menu_page( 'edit.php?post_type=smart_subscriptions' );
			remove_submenu_page( 'woocommerce', 'wc-orders--smart_subscriptions' );

			add_submenu_page(
				'woocommerce',
				'Smart Subscription Table',
				'Smart Subscription Table',
				'manage_options',
				'smart-subscription-table',
				'smart_subscription_table_callback'
			);

			/**
			 * Function for enqueue table page.
			 *
			 * @return void
			 */
			function smart_subscription_table_callback() {
				require_once SSMFW_PLUGIN_DIR_PATH . '/templates/admin/class-ssmfw-subscription-table.php';
			}

		}



	}
}

return Ssmfw_Admin::get_instance();
