<?php
/**
 * Include functions related to admin settings
 *
 * @package     smart-subscription-for-woocommerce/includes/admin/
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SSFW_Admin_Settings' ) ) {

	/**
	 * Main class for Admin Settings
	 */
	class SSFW_Admin_Settings {

		/**
		 * Tab name for woocommerce setting.
		 *
		 * @var $slug
		 *
		 * @since 1.0.0
		 */
		public $slug = 'smart-subscription-for-woocommerce-settings';

		/**
		 *  Constructor
		 */
		public function __construct() {

			// Hoooks for admin settings.
			add_filter( 'woocommerce_settings_tabs_array', array( $this, 'ssfw_add_admin_settings' ), 50 );
			add_action( 'woocommerce_settings_' . $this->slug, array( $this, 'ssfw_display_admin_settings' ) );
			add_action( 'woocommerce_update_options_' . $this->slug, array( $this, 'ssfw_save_admin_settings' ) );

		}

		/**
		 * Function to add main tab and title name.
		 *
		 * @param array $tabs are existing tabs.
		 * @return array $tabs merged tabs with existing one.
		 */
		public function ssfw_add_admin_settings( $tabs = array() ) {

			$tabs[ $this->slug ] = __( 'Smart Subscription For Woocommerce', 'smart-subscription-for-woocommerce' );

			return $tabs;
		}

		/**
		 * Function to display settings
		 */
		public function ssfw_display_admin_settings() {

			$ssfw_admin_settings = $this->get_settings();
			if ( ! is_array( $ssfw_admin_settings ) || empty( $ssfw_admin_settings ) ) {
				return;
			}

			woocommerce_admin_fields( $ssfw_admin_settings );
			wp_nonce_field( 'ssfw_admin_settings_nonce', 'ssfw_admin_settings_nonce', false );
		}

		/**
		 * Function to get settings.
		 *
		 * @return array $ssfw_settings Smart Subscription For Woocommerce settings.
		 */
		public function get_settings() {

			$ssfw_settings = array(
				array(
					'title' => __( 'Smart Woocommerce Subscription Settings', 'smart-subscription-for-woocommerce' ),
					'type'  => 'title',
					'id'    => 'ssfw_admin_settings',
				),
				array(
					'name'     => __( 'Enable settings', 'smart-subscription-for-woocommerce' ),
					'type'     => 'checkbox',
					'desc'     => __( 'Enable this to enable all plugin functionality.', 'smart-subscription-for-woocommerce' ),
					'default'  => 'yes',
					'id'       => 'ssfw_enable_settings',
					'autoload' => false,
				),
				array(
					'name'        => __( 'Change Add to Cart label on product page', 'smart-subscription-for-woocommerce' ),
					'desc'        => __( 'Replace the add to cart button text on single product page.', 'smart-subscription-for-woocommerce' ),
					'id'          => 'ssfw_add_to_cart_label',
					'type'        => 'text',
					'placeholder' => __( 'Subscribe', 'smart-subscription-for-woocommerce' ),
					'autoload'    => false,
					'desc_tip'    => false,
				),
				array(
					'name'        => __( 'Change Place Order label on checkout page', 'smart-subscription-for-woocommerce' ),
					'desc'        => __( 'Replace the place order button text on single product page.', 'smart-subscription-for-woocommerce' ),
					'id'          => 'ssfw_place_order_label',
					'type'        => 'text',
					'placeholder' => __( 'Sign Up', 'smart-subscription-for-woocommerce' ),
					'autoload'    => false,
					'desc_tip'    => false,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'afwc_admin_settings',
				),
			);
			return apply_filters( 'ssfw_admin_settings', $ssfw_settings );
		}

		/**
		 * Function for saving settings for Smart Subscription For Woocommerce
		 *
		 * @return void
		 */
		public function ssfw_save_admin_settings() {
			if ( ! isset( $_POST['ssfw_admin_settings_nonce'] ) || ! wp_verify_nonce( wc_clean( wp_unslash( $_POST['ssfw_admin_settings_nonce'] ) ), 'ssfw_admin_settings_nonce' )  ) { // phpcs:ignore
				return;
			}

			$ssfw_settings = $this->get_settings();
			if ( ! is_array( $ssfw_settings ) || empty( $ssfw_settings ) ) {
				return;
			}
			woocommerce_update_options( $ssfw_settings );
		}

	}

}

return new SSFW_Admin_Settings();
