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


if ( ! class_exists( 'Ssfw_Cart' ) ) {
	/**
	 * Main class for Smart Subscription For Woocommerce
	 */
	class Ssfw_Cart {

		/**
		 * Variable to hold instance of this class
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Get single instance of this class
		 *
		 * @return Ssfw_Cart
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
			add_action( 'wp_enqueue_scripts', array( $this, 'ssfw_enqueue_scripts' ) );
			add_filter( 'woocommerce_cart_item_price', array( $this, 'change_price_html' ), 10, 3 );
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'checkout_order_processed' ), 10, 2 );
			add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'checkout_order_processed_hpos' ), 10, 1 );
			add_filter( 'woocommerce_is_sold_individually', array( $this, 'sold_subscription_individually' ), 10, 2 );
			add_filter( 'woocommerce_get_item_data', array( $this, 'get_item_data' ), 10, 2 );
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'unset_payment_gateways' ), 100 );
			add_filter( 'woocommerce_order_button_text', array( $this, 'change_place_order_btn_label' ) );
			add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'change_add_to_cart_label' ), 10, 2 );
			add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'change_add_to_cart_label' ), 10, 2 );
		}

		/**
		 * Enqueue style
		 */
		public function ssfw_enqueue_scripts() {
			if ( is_cart() || is_checkout() ) {
				wp_register_script( 'cart-items-price-format', SSFW_PLUGIN_URL . '/block/cart-items-price-format.js', array( 'wp-data' ), SSFW_PLUGIN_VERSION, true );
				wp_localize_script(
					'cart-items-price-format',
					'cart_obj',
					array(
						'ajaxurl'           => admin_url( 'admin-ajax.php' ),
						'place_order_label' => ! empty( trim( get_option( 'ssfw_place_order_label' ) ) ) ? get_option( 'ssfw_place_order_label' ) : '',
					)
				);
				wp_enqueue_script( 'cart-items-price-format' );
			}
		}

		/**
		 * Change price html
		 *
		 * @param float  $price NA.
		 * @param array  $item NA.
		 * @param string $key NA.
		 */
		public function change_price_html( $price, $item, $key ) {
			$product  = $item['data'];
			$quantity = $item['quantity'];
			if ( ! ssfw_check_if_subscription( $product->get_id() ) ) {
				return $price;
			}

			$price      = wc_get_price_to_display(
				$product,
				array(
					'qty'   => $quantity,
					'price' => $product->get_price(),
				)
			);
			$price_html = ssfw_change_subscription_price_html( wc_price( $price ), $product );
			return $price_html;
		}

		/**
		 * Checkout order processed
		 *
		 * @param object $_order NA.
		 *
		 *  @throws Exception As handling expception.
		 */
		public function checkout_order_processed_hpos( $_order ) {
			if ( ! self::check_if_cart_has_subscription() ) {
				return;
			}

			if ( 'stripe' == $_order->get_payment_method() ) {
				$request      = file_get_contents( 'php://input' );
				$request_data = json_decode( $request );

				if ( ! empty( $request_data ) && isset( $request_data->payment_data ) && ! empty( $request_data->payment_data ) ) {

					$payment_obj             = $request_data->payment_data;
					$is_payment_method_saved = false;

					foreach ( $payment_obj as $payment_data ) {
						if ( ( 'wc-stripe-new-payment-method' == $payment_data->key && 1 == $payment_data->value ) || ( 'save_payment_method' == $payment_data->key && 'yes' == $payment_data->value ) || ( 'isSavedToken' == $payment_data->key && 1 == $payment_data->value ) ) {
							$is_payment_method_saved = true;
							break;
						}
					}

					if ( ! $is_payment_method_saved ) {

						throw new Exception( __( 'Please enable <strong>Save payment information to my account for future purchases</strong> checkbox to proceed. ', 'smart-subscription-for-woocommerce' ) );
					}
				}
			}
			if ( ! empty( WC()->cart->cart_contents ) ) {
				foreach ( WC()->cart->cart_contents as $cart_item ) {
					$product_id = $cart_item['data']->get_id();
					if ( ! ssfw_check_if_subscription( $product_id ) ) {
						continue;
					}
					$smart_subscription_for_woocommerce = Smart_Subscription_For_Woocommerce::get_instance();
					$subscription_id                    = is_callable( array( $smart_subscription_for_woocommerce, 'create_subscription_order' ) ) ? $smart_subscription_for_woocommerce->create_subscription_order( $_order, false, $cart_item ) : false;
				}
			}
		}

		/**
		 * Checkout order processed
		 *
		 * @param int   $order_id NA.
		 * @param array $posted_data NA.
		 */
		public function checkout_order_processed( $order_id, $posted_data ) {

			$_order = wc_get_order( $order_id );
			if ( ! empty( WC()->cart->cart_contents ) ) {
				foreach ( WC()->cart->cart_contents as $cart_item ) {
					$product_id = $cart_item['data']->get_id();
					if ( ! ssfw_check_if_subscription( $product_id ) ) {
						continue;
					}
					$smart_subscription_for_woocommerce = Smart_Subscription_For_Woocommerce::get_instance();
					$subscription_id                    = is_callable( array( $smart_subscription_for_woocommerce, 'create_subscription_order' ) ) ? $smart_subscription_for_woocommerce->create_subscription_order( $_order, false, $cart_item ) : false;

				}
			}
		}



		/**
		 * Get item data
		 *
		 * @param array $data NA.
		 * @param array $cart_item NA.
		 */
		public function get_item_data( $data = array(), $cart_item = array() ) {

			if ( empty( $cart_item ) || ! ssfw_check_if_subscription( $cart_item['product_id'] ) ) {
				return $data;
			}
			$product_id = $cart_item['product_id'];
			$product    = wc_get_product( $product_id );
			$price_html = ssfw_change_subscription_price_html( '', $product );

			$data[] = array(
				'name'   => 'ssfw-subsrcription-price-html',
				'hidden' => true,
				'value'  => $price_html,
			);

			return $data;
		}


		/**
		 * Unset payment gatewways
		 *
		 * @param array $available_gateways NA.
		 */
		public function unset_payment_gateways( $available_gateways ) {

			$subscription_product_present = self::check_if_cart_has_subscription();

			if ( ! $subscription_product_present ) {
				return $available_gateways;
			}

			$ssfw_subscription_gateways = self::get_supported_payment_gateways();
			foreach ( $available_gateways as $key => $payment_gateway ) {
				if ( ! in_array( $key, $ssfw_subscription_gateways ) ) {
					unset( $available_gateways[ $key ] );
				}
			}
			return $available_gateways;
		}

		/**
		 * Check if cart has subscription
		 */
		public static function check_if_cart_has_subscription() {
			$check = false;
			if ( ! empty( WC()->cart ) && ! empty( WC()->cart->get_cart_contents() ) ) {
				foreach ( WC()->cart->get_cart_contents() as $index => $values ) {
					if ( ssfw_check_if_subscription( $values['data'] ) ) {
						$check = true;
					}
				}
			}

			return $check;
		}

		/**
		 * Get supported method
		 */
		public static function get_supported_payment_gateways() {
			$payment_gateway = array(
				'stripe',
			);
			return $payment_gateway;
		}

		/**
		 * Sold Individually
		 *
		 * @param bool $is_sold_individually NA.
		 * @param bool $product NA.
		 */
		public function sold_subscription_individually( $is_sold_individually, $product ) {
			return ssfw_check_if_subscription( $product ) ? true : $is_sold_individually;
		}

		/**
		 * Change order place label
		 *
		 * @param string $label NA.
		 */
		public function change_place_order_btn_label( $label ) {
			return ! empty( trim( get_option( 'ssfw_place_order_label' ) ) ) ? get_option( 'ssfw_place_order_label' ) : $label;
		}

		/**
		 * Change order add to cart label
		 *
		 * @param string $label NA.
		 * @param object $product NA.
		 */
		public function change_add_to_cart_label( $label, $product ) {
			return ( ssfw_check_if_subscription( $product ) || ! empty( trim( get_option( 'ssfw_add_to_cart_label' ) ) ) ) ? get_option( 'ssfw_add_to_cart_label' ) : $label;
		}

	}
} // End if class_exists check.

/**
 *  Kicking this off by calling 'get_instance()' method
 */

return Ssfw_Cart::get_instance();
