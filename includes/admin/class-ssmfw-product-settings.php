<?php
/**
 * Main class for product
 *
 * @package     smart-subscription-manager-for-woocommerce/includes/admin
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Ssmfw_Product_Settings' ) ) {

	/**
	 * Main class for Smart Subscription Manager For Woocommerce
	 */
	class Ssmfw_Product_Settings {

		/**
		 * Variable to hold instance of this class
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Get single instance of this class
		 *
		 * @return Ssmfw_Product_Settings
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
			add_filter( 'product_type_options', array( $this, 'ssmfw_add_subscription_type_options' ) );
			add_filter( 'woocommerce_product_data_tabs', array( $this, 'ssmfw_create_subscription_tab' ) );
			add_filter( 'woocommerce_product_data_panels', array( $this, 'ssmfw_subscription_settings_html' ) );
			add_action( 'woocommerce_process_product_meta', array( $this, 'ssmfw_save_subscription_setting_fields' ) );

		}


		/**
		 * Function to add option.
		 *
		 * @param array $product_types NA.
		 * @return array
		 */
		public function ssmfw_add_subscription_type_options( $product_types ) {
			$product_types['ssmfw_subscription_product'] = array(
				'id'            => 'ssmfw_subscription_product',
				'wrapper_class' => 'show_if_simple',
				'label'         => esc_html__( 'Subscription', 'smart-subscription-manager-for-woocommerce' ),
				'description'   => esc_html__( 'Enable this to make product subscription type', 'smart-subscription-manager-for-woocommerce' ),
				'default'       => 'no',
			);

			return $product_types;
		}


		/**
		 * Function to create subscription tab.
		 *
		 * @param array $tabs NA.
		 * @return array
		 */
		public function ssmfw_create_subscription_tab( $tabs ) {
			$tabs['ssmfw_subscription_tab'] = array(
				'label'    => __( 'Subscription Settings', 'smart-subscription-manager-for-woocommerce' ),
				'target'   => 'ssmfw_subscription_product_data',
				'class'    => array( 'show_if_simple' ),
				'priority' => 10,
			);

			return $tabs;

		}


		/**
		 * Function to create setting html
		 *
		 * @return void
		 */
		public function ssmfw_subscription_settings_html() {
			global $post;
			$product_id = $post->ID;
			$args       = array(
				'smart_subscriptions_recurring_input'  => get_post_meta( $product_id, 'smart_subscriptions_recurring_input', true ),
				'smart_subscriptions_recurring_period' => get_post_meta( $product_id, 'smart_subscriptions_recurring_period', true ),
				'smart_subscriptions_recurring_expiry' => get_post_meta( $product_id, 'smart_subscriptions_recurring_expiry', true ),
				'smart_subscriptions_recurring_expiry_time' => get_post_meta( $product_id, 'smart_subscriptions_recurring_expiry_time', true ),
			);

			wc_get_template( 'admin/subscription-product-html.php', $args, '', SSMFW_PLUGIN_DIR_PATH . '/templates/' );
		}

		/**
		 * Function to save settings.
		 *
		 * @param int $product_id NA.
		 * @return void
		 */
		public function ssmfw_save_subscription_setting_fields( $product_id ) {
			if ( ! isset( $_POST['ssmfw_subscription_product_security'] ) || ! wp_verify_nonce( wc_clean( wp_unslash( $_POST['ssmfw_subscription_product_security'] ) ), 'ssmfw_subscription_product_security' ) ) { // phpcs:ignore
				return;
			}
			$product_meta               = array(
				'smart_subscriptions_recurring_input',
				'smart_subscriptions_recurring_period',
				'smart_subscriptions_recurring_expiry',
				'smart_subscriptions_recurring_expiry_time',
			);
			$product                    = wc_get_product( $product_id );
			$ssmfw_subscription_product = isset( $_POST['ssmfw_subscription_product'] ) ? 'yes' : 'no';
			$product->update_meta_data( '_ssmfw_subscription_product', $ssmfw_subscription_product );
			if ( empty( $ssmfw_subscription_product ) ) {
				return;
			}
			foreach ( $product_meta as $meta_field ) {
				if ( isset( $_POST[ $meta_field ] ) ) {
					$product->update_meta_data( $meta_field, wc_clean( wp_unslash( $_POST[ $meta_field ] ) ) ); // phpcs:ignore
				}
			}
			$product->save();
		}


	}
}

return Ssmfw_Product_Settings::get_instance();
