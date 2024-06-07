<?php
/**
 * Plugin Name: Smart Subscription For Woocommerce
 * Plugin URI: #
 * Description:
 * Version: 1.0.0
 * Author: PluginsStore
 * Author URI: https://pluginsstore.org/
 * Developer: PluginsStore
 * Developer URI: https://pluginsstore.org/
 * Requires at least: 5.0.0
 * Tested up to: 6.4.3
 * WC requires at least: 4.0.0
 * WC tested up to: 8.9.1
 * Text Domain: smart-subscription-for-woocommerce
 * Domain Path: /languages/
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package smart-subscription-for-woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



/**
 * Check woocommerce activation.
 */
function load_smart_subscription_for_woocommerce() {
	define( 'SSFW_PLUGIN_FILE', __FILE__ );
	$ssfw_new_admin_email = get_option( 'new_admin_email', '' );
	$ssfw_admin_email     = empty( $ssfw_new_admin_email ) ? get_option( 'admin_email', '' ) : $ssfw_new_admin_email;
	add_option( 'ssfw_admin_email_id', $ssfw_admin_email, '', 'no' );

	$active_plugins = (array) get_option( 'active_plugins', array() );
	if ( is_multisite() ) {
		$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
	}

	if ( ( in_array( 'woocommerce/woocommerce.php', $active_plugins, true ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins ) ) ) {
		include_once 'includes/class-smart-subscription-for-woocommerce.php';
		$GLOBALS['smart_subscription_for_woocommerce'] = Smart_Subscription_For_Woocommerce::get_instance();

	} elseif ( is_admin() ) {
		?>
			<div class="notice notice-error">
				<p><?php echo esc_html__( 'Smart Subscription For Woocommerce requires WooCommerce to be activated.', 'smart-subscription-for-woocommerce' ); ?></p>
			</div>
			<?php

	}
}
add_action( 'plugins_loaded', 'load_smart_subscription_for_woocommerce' );

register_activation_hook( __FILE__, 'smart_subscription_for_woocommerce_activate' );

/**
 * Function to activate the subscription  plugin.
 */
function smart_subscription_for_woocommerce_activate() {
	include_once 'includes/class-ssfw-install.php';

}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'add_plugin_settings_link' );

/**
 * Function to add setting link.
 *
 * @param array $links is link.
 */
function add_plugin_settings_link( $links ) {
				$settings_link = add_query_arg(
					array(
						'page' => 'wc-settings',
						'tab'  => 'smart-subscription-for-woocommerce-settings',
					),
					admin_url( 'admin.php' )
				);

		$ssfw_links = array(
			'settings' => '<a href="' . esc_url( $settings_link ) . '">' . esc_html( __( 'Settings', 'smart-subscription-for-woocommerce' ) ) . '</a>',
		);

		return array_merge( $ssfw_links, $links );
}


add_action(
	'init',
	function() {
		if ( class_exists( '\WC_Gateway_Stripe' ) ) {

			include_once 'integration/gateways/class-smart-subscription-for-woocommerce-gateway-stripe.php';
			include_once 'integration/gateways/class-ssfw-stripe-integration.php';
		}
	}
);

// Declare WooCommerce custom order tables i.e HPOS compatibility.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

// Declare WooCommerce cart checkout blocks compatibility.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);


