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
?>


<div id="ssfw_subscription_product_data" class="panel woocommerce_options_panel ">
	<div class="options_group pricing show_if_simple ">
		<p class="form-field subscription_recurring_time_fields ">
			<label for="smart_subscription_recurring_input"><?php esc_html_e( 'Subscription Recurring Period', 'smart-subscription-for-woocommerce' ); ?></label>
			<input type="number" name="smart_subscription_recurring_input" id="smart_subscription_recurring_input" value="<?php echo esc_attr( $smart_subscription_recurring_input ); ?>" style="width:60px !important;margin-right:5px;">
			<select id="smart_subscription_recurring_period" name="smart_subscription_recurring_period">
				<?php foreach ( ssfw_get_recurring_time_period() as $time ) { ?>
					<option value="<?php echo esc_attr( $time ); ?>" <?php selected( $smart_subscription_recurring_period, $time, true ); ?>><?php echo esc_html( $time ); ?></option>
					<?php
				}
				?>
			</select>
			<span class="description" style="display:inline-block !important;float:right;"><?php esc_html_e( 'Recurring payment will be processed according to the set time period.', 'smart-subscription-for-woocommerce' ); ?></span>
		</p>
		<fieldset class="form-field subscription_recurring_expiry_fields ">
			<legend><?php esc_html_e( 'Set Subscription Expiry', 'smart-subscription-for-woocommerce' ); ?></legend>
			<ul class="wc-radios">
				<li><label><input name="smart_subscription_recurring_expiry" value="never" type="radio" class="select short" style="" <?php checked( esc_attr( 'never' ), esc_attr( ! empty( $smart_subscription_recurring_expiry ) ? $smart_subscription_recurring_expiry : 'never' ), true ); ?>><?php esc_html_e( 'Never End', 'smart-subscription-for-woocommerce' ); ?></label></li>
				<li><label><input name="smart_subscription_recurring_expiry" value="expire" type="radio" class="select short" style=""  <?php checked( esc_attr( 'expire' ), esc_attr( $smart_subscription_recurring_expiry ), true ); ?>><?php esc_html_e( 'Set Expiry', 'smart-subscription-for-woocommerce' ); ?></label></li>
			</ul>
		</fieldset>
		<p class="form-field subscription_recurring_expiry_time_fields" >
			<span><?php esc_html_e( 'Subscription will be expired in ', 'smart-subscription-for-woocommerce' ); ?></span>
			<input type="number" name="smart_subscription_recurring_expiry_time" id="smart_subscription_recurring_expiry_time" value="<?php echo esc_attr( $smart_subscription_recurring_expiry_time ); ?>" style="width:60px !important;float:none !important;" />
			<span class="subscription_recurring_period_text"><?php echo ! empty( $smart_subscription_recurring_period ) ? esc_html( $smart_subscription_recurring_period ) : 'days'; ?></span>
			<span class="description" style="display:inline-block !important;float:right;"><?php esc_html_e( 'Set the expiry time after which subscription will be expired.', 'smart-subscription-for-woocommerce' ); ?></span>
		</p>	
	</div>
	<?php
		wp_nonce_field( 'ssfw_subscription_product_security', 'ssfw_subscription_product_security' );
	?>
</div>
