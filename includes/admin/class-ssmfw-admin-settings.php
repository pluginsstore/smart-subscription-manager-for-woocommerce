<?php
/**
 * Include functions related to admin settings
 *
 * @package     smart-subscription-manager-for-woocommerce/includes/admin/
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SSMFW_Admin_Settings' ) ) {

	/**
	 * Main class for Admin Settings
	 */
	class SSMFW_Admin_Settings {

		/**
		 * Tab name for woocommerce setting.
		 *
		 * @var $slug
		 *
		 * @since 1.0.0
		 */
		public $slug = 'smart-subscription-manager-for-woocommerce-settings';

		/**
		 *  Constructor
		 */
		public function __construct() {

			// Hoooks for admin settings.
			add_filter( 'woocommerce_settings_tabs_array', array( $this, 'ssmfw_add_admin_settings' ), 50 );
			add_action( 'woocommerce_settings_' . $this->slug, array( $this, 'ssmfw_display_admin_settings' ) );
			add_action( 'woocommerce_update_options_' . $this->slug, array( $this, 'ssmfw_save_admin_settings' ) );

		}

		/**
		 * Function to add main tab and title name.
		 *
		 * @param array $tabs are existing tabs.
		 * @return array $tabs merged tabs with existing one.
		 */
		public function ssmfw_add_admin_settings( $tabs = array() ) {

			$tabs[ $this->slug ] = __( 'Smart Subscription Manager For Woocommerce', 'smart-subscription-manager-for-woocommerce' );

			return $tabs;
		}

		/**
		 * Function to display settings
		 */
		public function ssmfw_display_admin_settings() {

			$ssmfw_admin_settings = $this->get_settings();
			if ( ! is_array( $ssmfw_admin_settings ) || empty( $ssmfw_admin_settings ) ) {
				return;
			}

			woocommerce_admin_fields( $ssmfw_admin_settings );
			wp_nonce_field( 'ssmfw_admin_settings_nonce', 'ssmfw_admin_settings_nonce', false );
		}

		/**
		 * Function to get settings.
		 *
		 * @return array $ssmfw_settings Smart Subscription Manager For Woocommerce settings.
		 */
		public function get_settings() {

			$ssmfw_settings = array(
				array(
					'title' => __( 'Smart Woocommerce Subscription Settings', 'smart-subscription-manager-for-woocommerce' ),
					'type'  => 'title',
					'id'    => 'ssmfw_admin_settings',
				),
				array(
					'name'     => __( 'Enable settings', 'smart-subscription-manager-for-woocommerce' ),
					'type'     => 'checkbox',
					'desc'     => __( 'Enable this to enable all plugin functionality.', 'smart-subscription-manager-for-woocommerce' ),
					'default'  => 'no',
					'id'       => 'ssmfw_enable_settings',
					'autoload' => false,
				),
				array(
					'name'        => __( 'Change Add to Cart label on product page', 'smart-subscription-manager-for-woocommerce' ),
					'desc'        => __( 'Replace the add to cart button text on single product page.', 'smart-subscription-manager-for-woocommerce' ),
					'id'          => 'ssmfw_add_to_cart_label',
					'type'        => 'text',
					'placeholder' => __( 'Subscribe', 'smart-subscription-manager-for-woocommerce' ),
					'autoload'    => false,
					'desc_tip'    => false,
				),
				array(
					'name'        => __( 'Change Place Order label on checkout page', 'smart-subscription-manager-for-woocommerce' ),
					'desc'        => __( 'Replace the place order button text on single product page.', 'smart-subscription-manager-for-woocommerce' ),
					'id'          => 'ssmfw_place_order_label',
					'type'        => 'text',
					'placeholder' => __( 'Sign Up', 'smart-subscription-manager-for-woocommerce' ),
					'autoload'    => false,
					'desc_tip'    => false,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'afwc_admin_settings',
				),
			);
			return apply_filters( 'ssmfw_admin_settings', $ssmfw_settings );
		}

		/**
		 * Function for saving settings for Smart Subscription Manager For Woocommerce
		 *
		 * @return void
		 */
		public function ssmfw_save_admin_settings() {
			if ( ! isset( $_POST['ssmfw_admin_settings_nonce'] ) || ! wp_verify_nonce( wc_clean( wp_unslash( $_POST['ssmfw_admin_settings_nonce'] ) ), 'ssmfw_admin_settings_nonce' )  ) { // phpcs:ignore
				return;
			}

			$ssmfw_settings = $this->get_settings();
			if ( ! is_array( $ssmfw_settings ) || empty( $ssmfw_settings ) ) {
				return;
			}
			woocommerce_update_options( $ssmfw_settings );
		}

	}

}

return new SSMFW_Admin_Settings();
