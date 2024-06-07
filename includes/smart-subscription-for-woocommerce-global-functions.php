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
if ( ! function_exists( 'ssfw_get_recurring_time_period' ) ) {

	/**
	 * Return the list of recurring time period.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	function ssfw_get_recurring_time_period() {
		$time_period = array(
			'days'   => __( 'days', 'smart-subscription-for-woocommerce' ),
			'weeks'  => __( 'weeks', 'smart-subscription-for-woocommerce' ),
			'months' => __( 'months', 'smart-subscription-for-woocommerce' ),
			'years'  => __( 'years', 'smart-subscription-for-woocommerce' ),
		);

		return apply_filters( 'ssfw_recurring_time_period', $time_period );
	}
}

if ( ! function_exists( 'ssfw_check_if_subscription' ) ) {

	/**
	 * Check subsription product
	 *
	 * @param object $product NA.
	 * @return bool
	 */
	function ssfw_check_if_subscription( $product ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( ! $product instanceof WC_Product ) {
			return false;
		}

		$is_subscription_product = $product->get_meta( '_ssfw_subscription_product' );
		$recurring_period        = $product->get_meta( 'smart_subscription_recurring_input' );

		return apply_filters( 'ssfw_check_if_subscription_product', ( 'yes' === $is_subscription_product && '' !== $recurring_period ), $product->get_id() );
	}
}

if ( ! function_exists( 'ssfw_change_subscription_price_html' ) ) {
	/**
	 * Change price html
	 *
	 * @param string $price_html NA.
	 * @param object $product NA.
	 * @return string
	 */
	function ssfw_change_subscription_price_html( $price_html, $product ) {
		$recurring_input  = $product->get_meta( 'smart_subscription_recurring_input' );
		$recurring_period = $product->get_meta( 'smart_subscription_recurring_period' );
		if ( 1 === (int) $recurring_input ) {
			$recurring_period = str_replace( 's', '', $recurring_period );
		}
		$price_html .= " / {$recurring_input} {$recurring_period}";
		return apply_filters( 'ps_ssfw_change_subscription_price_html', $price_html );
	}
}


if ( ! function_exists( 'ssfw_check_if_subscription_product_exist' ) ) {
	/**
	 * Check subsription product in order.
	 *
	 * @param int $order_id NA.
	 * @return bool
	 */
	function ssfw_check_if_subscription_product_exist( $order_id ) {

		$is_suscription = false;
		$order          = wc_get_order( $order_id );
		if ( $order ) {
			foreach ( $order->get_items() as $item ) {
				$product_id = $item->get_product_id();
				$product    = wc_get_product( $product_id );
				if ( 'simple' !== $product->get_type() ) {
					continue;
				}
				if ( ssfw_check_if_subscription( $product ) ) {
					$is_suscription = true;
					break;
				}
			}
		}
		return $is_suscription;
	}
}



