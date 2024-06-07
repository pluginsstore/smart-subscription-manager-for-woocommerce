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

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}


/**
 * Define your custom table class
 */
class Ssfw_Subscription_Table extends WP_List_Table {

	/**
	 * Prepare data items.
	 *
	 * @return void
	 */
	public function prepare_items() {
		// Define the number of items per page.
		$per_page = 10;

		// Get the current page number.
		$current_page = $this->get_pagenum();

		// Calculate the offset for the query.
		$offset = ( $current_page - 1 ) * $per_page;

		// Query to get the total number of orders.
		$total_items = wc_get_orders(
			array(
				'type'        => 'smart_subscription',
				'meta_query'  => array(
					'relation' => 'AND',
					array(
						'key'     => 'smart_subscription_parent_order_id',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => 'smart_subscription_product_id',
						'value'   => '',
						'compare' => '!=',
					),
				),
				'return'      => 'ids',
				'numberposts' => -1,
			)
		);

		$data = wc_get_orders(
			array(
				'type'       => 'smart_subscription',
				'limit'      => $per_page,
				'offset'     => $offset,
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => 'smart_subscription_parent_order_id',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => 'smart_subscription_product_id',
						'value'   => '',
						'compare' => '!=',
					),
				),
			)
		);

		// Define column headers.
		$columns = array(
			'subscription_order' => 'Subscription Order',
			'parent_order'       => 'Parent Order',
			'product'            => 'Product',
			'status'             => 'Status',
			'recurring'          => 'Recurring Amount',
			'payment_method'     => 'Payment Method',
			'next_payment_date'  => 'Next Payment Date',
			'expiry'             => 'Expiry',
		);

		// Sortable columns (optional).
		$sortable_columns = array();

		// Set pagination arguments.
		$this->set_pagination_args(
			array(
				'total_items' => count( $total_items ),
				'per_page'    => $per_page,
				'total_pages' => ceil( count( $total_items ) / $per_page ),
			)
		);

		// Set table data.
		$this->items           = $data;
		$this->_column_headers = array( $columns, array(), $sortable_columns );
	}
	/**
	 * Function to define.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'subscription_order' => 'Subscription Order',
			'parent_order'       => 'Parent Order',
			'product'            => 'Product',
			'status'             => 'Status',
			'recurring'          => 'Recurring Amount',
			'payment_method'     => 'Payment Method',
			'next_payment_date'  => 'Next Payment Date',
			'expiry'             => 'Expiry',
		);
		return $columns;
	}





	/**
	 * Function for default column.
	 *
	 * @param object $item is an object.
	 * @param string $column_name is a column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		$product_id      = $item->get_meta( 'smart_subscription_product_id' );
		$product         = wc_get_product( $product_id );
		$parent_order_id = $item->get_meta( 'smart_subscription_parent_order_id' );
		$parent_order    = wc_get_order( $parent_order_id );
		switch ( $column_name ) {
			case 'subscription_order':
				return '<strong>' . $item->get_id() . '</strong>';
			case 'parent_order':
				return ( $parent_order ) ? '<a href="' . $parent_order->get_edit_order_url() . '"><strong>#' . esc_attr( $parent_order_id ) . ' ' . $parent_order->get_billing_first_name() . ' ' . $parent_order->get_billing_last_name() . '</strong></a>' : '';
			case 'status':
				$status = $item->get_subscription_status();
				return '<span class="status-' . esc_attr( $status ) . '" style="border-radius: 0.2rem; padding: 0.3rem 0.7rem; text-transform: capitalize;' . ( 'active' === $status ? ' background-color: #a9dfbf; color: #196f3d;' : ' background-color: #e5e5e5; color: #777;' ) . '">' . esc_html( $status ) . '</span>';
			case 'product':
				return ( $product ) ? '<a href="' . get_edit_post_link( $product_id ) . '"><strong>' . $product->get_name() . '</strong></a>' : 'N/A';

			case 'recurring':
				return wc_price( $product->get_price() ) . '/' . $item->get_subscription_interval();
			case 'payment_method':
				return ( $parent_order ) ? $parent_order->get_payment_method_title() : 'N/A';

			case 'next_payment_date':
				return ( ! empty( $item->get_subscription_next_payment_date() ) ) ? gmdate( 'M d, Y', $item->get_subscription_next_payment_date() ) : '----';
			case 'expiry':
				return 'never' === $item->get_subscription_expiry() ? 'Never' : gmdate( 'M d, Y', $item->get_subscription_expiry() );
			default:
				return $item->$column_name;

		}

	}
}

$my_table = new Ssfw_Subscription_Table();
$my_table->prepare_items();
echo '<h1>' . esc_html__( 'Subscription Table', 'smart-subscription-for-woocommerce' ) . '</h1>';
$my_table->display();

