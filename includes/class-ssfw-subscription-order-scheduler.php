<?php
/**
 * Main class for Cart
 *
 * @package     smart-subscription-for-woocommerce/includes/
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'Ssfw_Subscription_Order_Scheduler' ) ) {
	/**
	 * Main class for Smart Subscription For Woocommerce
	 */
	class Ssfw_Subscription_Order_Scheduler {

		/**
		 * Variable to hold instance of this class
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Get single instance of this class
		 *
		 * @return Ssfw_Subscription_Order_Scheduler
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
		public function __construct() {
			add_action( 'init', array( $this, 'create_scheduler' ) );
			add_action( 'ssfw_schedule_renewal_order', array( $this, 'ssfw_schedule_renewal_order_callack' ) );
			add_action( 'ssfw_schedule_expired_renewal_order', array( $this, 'ssfw_schedule_expired_renewal_order_callack' ) );

		}

		/**
		 * Create scheduler
		 */
		public function create_scheduler() {
			if ( ! class_exists( 'ActionScheduler' ) ) {
				return false;
			}

			if ( function_exists( 'wp_next_scheduled' ) && false === wp_next_scheduled( 'ssfw_schedule_renewal_order' ) ) {
				wp_schedule_event( time(), 'hourly', 'ssfw_schedule_renewal_order' );
			}
			if ( function_exists( 'wp_next_scheduled' ) && false === wp_next_scheduled( 'ssfw_schedule_expired_renewal_order' ) ) {
				wp_schedule_event( time(), 'hourly', 'ssfw_schedule_expired_renewal_order' );
			}
		}

		/**
		 * Renewal callback
		 */
		public function ssfw_schedule_renewal_order_callack() {

			$subscription_orders = wc_get_orders(
				array(
					'type'       => 'smart_subscription',
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'key'   => 'smart_subscription_status',
							'value' => 'active',
						),
						array(
							'relation' => 'AND',
							array(
								'key'     => 'smart_subscription_parent_order_id',
								'compare' => 'EXISTS',
							),
							array(
								'key'     => 'smart_subscription_next_payment_date',
								'value'   => time(),
								'compare' => '<',
							),
						),
					),
					'return'     => 'ids',
				)
			);

			if ( empty( $subscription_orders ) || ! is_array( $subscription_orders ) ) {
				return false;
			}
			foreach ( $subscription_orders as $index => $subscription_id ) {
				$subscription = new Smart_Subscription_Order( $subscription_id );

				if ( empty( $subscription ) || ! is_object( $subscription ) ) {
					continue;
				}
				$parent_order_id = $subscription->get_meta( 'smart_subscription_parent_order_id' );
				$parent_order    = new WC_Order( $parent_order_id );
				if ( empty( $parent_order ) || ! is_object( $subscription ) ) {
					continue;
				}
				$payment_method                     = $parent_order->get_payment_method();
				$smart_subscription_for_woocommerce = Smart_Subscription_For_Woocommerce::get_instance();
				$order_id                           = is_callable( array( $smart_subscription_for_woocommerce, 'create_subscription_order' ) ) ? $smart_subscription_for_woocommerce->create_subscription_order( $subscription, true, false ) : false;
				if ( $order_id ) {

					$renewal_order = wc_get_order( $order_id );

					do_action( 'ssfw_renewal_order_payment_process', $renewal_order, $order_id, $payment_method );
				}
			}

		}

		/**
		 * Expired renewal callback
		 */
		public function ssfw_schedule_expired_renewal_order_callack() {
			$subscription_orders = wc_get_orders(
				array(
					'type'       => 'smart_subscription',
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'key'   => 'smart_subscription_status',
							'value' => array( 'active', 'pending' ),
						),
						array(
							'relation' => 'AND',
							array(
								'key'     => 'smart_subscription_parent_order_id',
								'compare' => 'EXISTS',
							),
							array(
								'relation' => 'AND',
								array(
									'key'     => 'smart_subscription_expiry',
									'value'   => time(),
									'compare' => '<',
								),
								array(
									'key'     => 'smart_subscription_expiry',
									'value'   => 'never',
									'compare' => '!=',
								),
							),
						),
					),
					'return'     => 'ids',
				)
			);

			if ( empty( $subscription_orders ) || ! is_array( $subscription_orders ) ) {
				return false;
			}
			foreach ( $subscription_orders as $index => $subscription_id ) {
				$subscription = new Smart_Subscription_Order( $subscription_id );
				if ( ! is_object( $subscription ) ) {
					continue;
				}
				if ( $subscription->is_subscription_expired() ) {

					$subscription->set_subscription_status( 'expired' );
					$subscription->update_meta_data( 'smart_subscription_next_payment_date', '' );
					$subscription->save();
				}
			}
		}
	}

}

return Ssfw_Subscription_Order_Scheduler::get_instance();
