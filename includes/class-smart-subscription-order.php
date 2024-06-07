<?php
/**
 * Include functions related to admin settings
 *
 * @package     smart-subscription-for-woocommerce/templates/admin
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'Smart_Subscription_Order' ) && class_exists( 'WC_Order' ) ) :
	/**
	 * Subscription Class
	 */
	class Smart_Subscription_Order extends WC_Order {


		/**
		 * Set type.
		 *
		 * @public string type
		 * @var bool
		 */
		public $order_type = 'smart_subscription';

			/**
			 * Store the order data
			 *
			 * @private int Stores get_payment_count when used multiple times
			 * @var bool
			 */
		private $cached_payment_count = null;


		/**
		 * Store the order data
		 *
		 * Stores the $this->is_editable() returned value in memory
		 *
		 * @var bool
		 */
		private $editable;

		/**
		 * Store the order data
		 *
		 * @private array The set of valid date types that can be set on the subscription
		 * @var bool
		 */
		protected $valid_date_types = array();


		/**
		 * Constructor to initialize properties
		 *
		 * @param int $subscription NA.
		 * @return void
		 */
		public function __construct( $subscription = 0 ) {
			parent::__construct( $subscription );
			$this->order_type = 'smart_subscription';
		}


		/**
		 * Method to mark the order as a subscription
		 *
		 * @return void
		 */
		public function set_as_subscription() {

			$this->update_meta_data( 'is_smart_subscription', true );
			$this->save();
		}

		/**
		 * Method to get type
		 *
		 * @return string
		 */
		public function get_type() {
			return 'smart_subscription';
		}


		/**
		 * Method to check if the order is a subscription
		 *
		 * @return bool
		 */
		public function is_subscription() {

			return $this->get_meta( 'is_smart_subscription', false );
		}

		/**
		 * Method to set status.
		 *
		 * @param string $status NA.
		 * @return void
		 */
		public function set_subscription_status( $status ) {
			$this->update_meta_data( 'smart_subscription_status', $status );
			$this->save();
		}


		/**
		 * Method to get status.
		 *
		 * @return string
		 */
		public function get_subscription_status() {
			return $this->get_meta( 'smart_subscription_status', 'pending' );

		}



		/**
		 * Method to set subscription interval
		 *
		 * @param string $interval NA.
		 * @return void
		 */
		public function set_subscription_interval( $interval ) {

			$this->update_meta_data( 'smart_subscription_interval', $interval );
			$this->save();
		}



		/**
		 * Method to get subscription interval
		 *
		 * @return int
		 */
		public function get_subscription_interval() {

			return $this->get_meta( 'smart_subscription_interval', '1 day' );
		}


		/**
		 * Method to set subscription start date
		 *
		 * @param int $start_date NA.
		 * @return void
		 */
		public function set_subscription_start_date( $start_date ) {

			$this->update_meta_data( 'smart_subscription_start_date', $start_date );
			$this->save();
		}

		/**
		 * Method to get subscription start date
		 *
		 * @return int
		 */
		public function get_subscription_start_date() {

			return $this->get_meta( 'smart_subscription_start_date', time() );
		}


		/**
		 * Method to get subscription end date
		 *
		 * @return void
		 */
		public function set_subscription_next_payment_date() {

			$interval = strtolower( trim( $this->get_subscription_interval() ) );
			$end_date = strtotime( gmdate( 'Y-m-d H:i:s', strtotime( $interval, time() ) ) );
			$this->update_meta_data( 'smart_subscription_next_payment_date', $end_date );
			$this->save();

		}

		/**
		 * Method to get next payment date
		 *
		 * @return int
		 */
		public function get_subscription_next_payment_date() {

			return $this->get_meta( 'smart_subscription_next_payment_date' );

		}


		/**
		 *  Method to check if the subscription is active
		 *
		 * @return bool
		 */
		public function is_subscription_active() {

			$current_date = strtotime( gmdate( 'Y-m-d' ) );
			$start_date   = $this->get_subscription_start_date();
			$end_date     = $this->get_subscription_expiry();

			if ( 'never' === $end_date ) {
				return true;
			}

			return ( $current_date >= $start_date && $current_date <= $end_date );
		}

		public function is_subscription_expired() {

			$current_date = time();
			$end_date     = $this->get_subscription_expiry();

			if ( 'never' === $end_date ) {
				return false;
			}

			return ( $current_date >= $end_date );
		}

		/**
		 *  Method to set expiry
		 *
		 * @param mixed $expiry NA.
		 * @return void
		 */
		public function set_subscription_expiry( $expiry ) {
			if ( 'never' === $expiry || 'expired' === $expiry ) {
				$this->update_meta_data( 'smart_subscription_expiry', $expiry );
			} else {

				$expiry     = strtolower( trim( $expiry ) );
				$start_date = $this->get_subscription_start_date();
				$end_date   = strtotime( gmdate( 'Y-m-d H:i:s', strtotime( $expiry, $start_date ) ) );
				$this->update_meta_data( 'smart_subscription_expiry', $end_date );
			}
			$this->save();
		}


		/**
		 *  Method to get expiry
		 *
		 * @return mixed
		 */
		public function get_subscription_expiry() {

			return $this->get_meta( 'smart_subscription_expiry', 'never' );
		}
	}

endif;
