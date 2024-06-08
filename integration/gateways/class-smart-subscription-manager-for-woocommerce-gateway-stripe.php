<?php
/**
 * Stripe Payment Integration.
 *
 * @package     Smart Subscription Manager For Woocommerce
 * @subpackage  Smart Subscription Manager For Woocommerce/integration/gateways
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'Smart_Subscription_Manager_For_Woocommerce_Gateway_Stripe' ) ) {
	/**
	 * Smart Subscription Gateway Stripe.
	 */
	class Smart_Subscription_Manager_For_Woocommerce_Gateway_Stripe extends WC_Gateway_Stripe {

		/**
		 * Stripe gateway id
		 *
		 * @var   string ID of specific gateway
		 */
		public static $gateway_id = 'stripe';

		/**
		 * Get single instance of this class
		 *
		 * @return Smart_Subscription_Manager_For_Woocommerce_Gateway_Stripe
		 */
		public static function get_instance() {

			// Check if instance is already exists or not.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor for Smart Subscription Gateway Stripe.
		 */
		public function __construct() {
			parent::__construct();

			$this->supports = array(
				'products',
				'refunds',
				'tokenization',
			);
		}

		/**
		 * Function to process the payment.
		 *
		 * @param int  $_order_id NA.
		 * @param bool $retry NA.
		 * @param bool $forcefully_save_source NA.
		 * @param bool $prev_error NA.
		 * @param bool $use_order_source NA.
		 */
		public function process_payment( $_order_id, $retry = true, $forcefully_save_source = false, $prev_error = false, $use_order_source = false ) {
			$is_subscription = function_exists( 'ssmfw_check_if_subscription_product_exist' ) ? ssmfw_check_if_subscription_product_exist( $order_id ) : false;

			if ( $is_subscription ) {
				return parent::process_payment( $_order_id, $retry, true, $prev_error );
			} else {
				return parent::process_payment( $_order_id, $retry, $forcefully_save_source, $prev_error );
			}
		}

		/**
		 * Function to process renewal payment.
		 *
		 * @param int $order_id NA.
		 */
		public function get_upe_enabled_at_checkout_payment_method_ids( $order_id = null ) {
			$is_automatic_capture_enabled = $this->is_automatic_capture_enabled();
			$payment_method_ids           = array();
			$class_array                  = array( WC_Stripe_UPE_Payment_Method_CC::class );
			foreach ( $class_array as $index => $method_class ) {

				$payment_method                                     = new $method_class();
				$this->payment_methods[ $payment_method->get_id() ] = $payment_method;
			}
			$upe_checkout_experience_accepted_payments = $this->get_option( 'upe_checkout_experience_accepted_payments', array( 'card' ) );
			foreach ( $upe_checkout_experience_accepted_payments as $key => $payment_method_id ) {
				if ( ! isset( $this->payment_methods[ $payment_method_id ] ) ) {
					continue;
				}

				$method = $this->payment_methods[ $payment_method_id ];
				if ( $method->is_enabled_at_checkout( $order_id ) === false ) {
					continue;
				}

				if ( ! $is_automatic_capture_enabled && $method->requires_automatic_capture() ) {
					continue;
				}

				$payment_method_ids[] = $payment_method_id;
			}

			return $payment_method_ids;
		}

		/**
		 * Function to process renewal payment.
		 *
		 * @param object $renewal_order NA.
		 * @param int    $subscription_id NA.
		 * @param int    $payment_method NA.
		 *
		 * @throws WC_Stripe_Exception As handling expception.
		 */
		public function ssmfw_process_stripe_renewal_payment( $renewal_order, $subscription_id, $payment_method ) {

			if ( ! is_object( $renewal_order ) ) {
				return false;
			}
			$subscription_id = $renewal_order->get_meta( 'smart_subscriptions_id' );
			$is_renewal      = $renewal_order->get_meta( 'is_smart_subscription_renewal_order' );
			if ( ! $subscription_id || 'yes' !== $is_renewal ) {

				return false;
			}
			$order_id       = $renewal_order->get_id();
			$previous_error = false;
			$renewal_order->update_status( 'pending' );
			$parent_id    = $renewal_order->get_meta( 'smart_subscriptions_parent_order_id' );
			$parent_order = wc_get_order( $parent_id );
			if ( 'stripe' !== $payment_method ) {

				return false;
			}
			$amount = $renewal_order->get_total();
			if ( $amount <= 0 ) {

				$renewal_order->payment_complete();
				return true;
			}

			try {

				if ( $amount * 100 < WC_Stripe_Helper::get_minimum_amount() ) {
					/* translators: minimum amount */
					$message = sprintf( __( 'Sorry, the minimum allowed order total is %1$s to use this payment method.', 'woocommerce-gateway-stripe' ), wc_price( WC_Stripe_Helper::get_minimum_amount() / 100 ) );

					return new WP_Error( 'stripe_error', $message );
				}

				// Get source from order.
				$prepared_source = $this->prepare_order_source( $parent_order );

				if ( ! $prepared_source ) {
					throw new WC_Stripe_Exception( WC_Stripe_Helper::get_localized_messages()['missing'] );
				}

				$source_object = $prepared_source->source_object;

				if ( ! $prepared_source->customer ) {
					throw new WC_Stripe_Exception(
						'Failed to process renewal for order ' . $renewal_order->get_id() . '. Stripe customer id is missing in the order',
						__( 'Customer not found', 'woocommerce-gateway-stripe' )
					);
				}

				WC_Stripe_Logger::log( "Info: Begin processing subscription payment for order {$order_id} for the amount of {$amount}" );

				/*
					* If we're doing a retry and source is chargeable, we need to pass
					* a different idempotency key and retry for success.
					*/
				if ( is_object( $source_object ) && empty( $source_object->error ) && $this->need_update_idempotency_key( $source_object, $previous_error ) ) {
					add_filter( 'wc_stripe_idempotency_key', array( $this, 'change_idempotency_key' ), 10, 2 );
				}

				if ( ( $this->is_no_such_source_error( $previous_error ) || $this->is_no_linked_source_error( $previous_error ) ) && apply_filters( 'wc_stripe_use_default_customer_source', true ) ) {
					// Passing empty source will charge customer default.
					$prepared_source->source = '';
				}

				if ( $this->lock_order_payment( $renewal_order ) ) {
					return false;
				}

				$response                   = $this->create_and_confirm_intent_for_off_session( $renewal_order, $prepared_source, $amount );
				$is_authentication_required = $this->is_authentication_required_for_payment( $response );

				if ( ! empty( $response->error ) && ! $is_authentication_required ) {
					$localized_message = __( 'Sorry, we are unable to process your payment at this time. Please retry later.', 'woocommerce-gateway-stripe' );
					$renewal_order->add_order_note( $localized_message );
					throw new WC_Stripe_Exception( print_r( $response, true ), $localized_message );
				}

				if ( $is_authentication_required ) {

					do_action( 'wc_gateway_stripe_process_payment_authentication_required', $renewal_order, $response );

					$error_message = __( 'This transaction requires authentication.', 'woocommerce-gateway-stripe' );
					$renewal_order->add_order_note( $error_message );

					$charge = end( $response->error->payment_intent->charges->data );
					$id     = $charge->id;
					$renewal_order->set_transaction_id( $id );
					/* translators: %s is the charge Id */
					$renewal_order->update_status( 'failed', sprintf( __( 'Stripe charge awaiting authentication by user: %s.', 'woocommerce-gateway-stripe' ), $id ) );
					$renewal_order->save();
				} else {
					do_action( 'wc_gateway_stripe_process_payment', $response, $renewal_order );

					// Use the last charge within the intent or the full response body in case of SEPA.
					$this->process_response( isset( $response->charges ) ? end( $response->charges->data ) : $response, $renewal_order );
				}
			} catch ( WC_Stripe_Exception $e ) {
				WC_Stripe_Logger::log( 'Error: ' . $e->getMessage() );
				do_action( 'wc_gateway_stripe_process_payment_error', $e, $renewal_order );
			}

		}
	}
}

