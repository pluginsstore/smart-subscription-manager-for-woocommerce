<?php
/**
 * Include functions related to admin settings
 *
 * @package     smart-subscription-manager-for-woocommerce/templates/admin
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'Ssmfw_Stripe_Integration' ) ) {

	/**
	 * Class for stripe integration
	 */
	class Ssmfw_Stripe_Integration {




		/**
		 * Constructor of this class.
		 */
		public function __construct() {

			add_action( 'ssmfw_renewal_order_payment_process', array( $this, 'ssmfw_renewal_order_payment_process_callback' ), 10, 3 );
			add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( $this, 'ssmfw_add_renewal_status' ), 10, 2 );

			$woocommerce_stripe_settings = get_option( 'woocommerce_stripe_settings' );
			$checkout_experience         = isset( $woocommerce_stripe_settings['upe_checkout_experience_enabled'] ) ? $woocommerce_stripe_settings['upe_checkout_experience_enabled'] : '';

			( 'disabled' !== $checkout_experience ) ? add_filter( 'wc_stripe_display_save_payment_method_checkbox', array( $this, 'ssmfw_wc_stripe_force_save1' ), 10, 1 ) : add_filter( 'wc_stripe_force_save_source', array( $this, 'ssmfw_wc_stripe_force_save2' ), 10, 2 );

		}

		/**
		 * Constructor of this class.
		 *
		 * @param string $status NA.
		 * @param object $order NA.
		 */
		public function ssmfw_add_renewal_status( $status, $order ) {
			if ( $order ) {
				$payment_method = $order->get_payment_method();
				$is_renewal     = $order->get_meta( 'is_smart_subscription_renewal_order' );
				if ( 'stripe' === $payment_method && 'yes' === $is_renewal ) {
					$status[] = 'smart_renewal';
				}
			}
			return $status;
		}

		/**
		 * Constructor of this class.
		 *
		 * @param bool $force_save Whether to force save.
		 */
		public function ssmfw_wc_stripe_force_save1( $force_save ) {
			$subscription_id = Ssmfw_Cart::check_if_cart_has_subscription();
			if ( $subscription_id ) {
				return false;
			}
			return $force_save;
		}

		/**
		 * Constructor of this class.
		 *
		 * @param bool  $force_save Whether to force save.
		 * @param mixed $customer   The customer data.
		 */
		public function ssmfw_wc_stripe_force_save2( $force_save, $customer ) {
			$subscription_id = Ssmfw_Cart::check_if_cart_has_subscription();
			if ( $subscription_id ) {
				return true;
			}
			return $force_save;
		}

		/**
		 * Constructor of this class.
		 *
		 * @param object $subscription_order Whether to force save.
		 * @param int    $subscription_id   The customer data.
		 * @param mixed  $payment_method   The customer data.
		 */
		public function ssmfw_renewal_order_payment_process_callback( $subscription_order, $subscription_id, $payment_method ) {

			if ( class_exists( 'Smart_Subscription_Manager_For_Woocommerce_Gateway_Stripe' ) ) {
				$smart_subscription_manager_for_woocommerce_gateway_stripe = new Smart_Subscription_Manager_For_Woocommerce_Gateway_Stripe();
				if ( is_callable( array( $smart_subscription_manager_for_woocommerce_gateway_stripe, 'ssmfw_process_stripe_renewal_payment' ) ) ) {

					$smart_subscription_manager_for_woocommerce_gateway_stripe->ssmfw_process_stripe_renewal_payment( $subscription_order, $subscription_id, $payment_method );
				}
			}
		}

	}
}


new Ssmfw_Stripe_Integration();















