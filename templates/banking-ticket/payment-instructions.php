<?php
/**
 * Bank Slip - Payment instructions.
 *
 * @author  Upnid
 * @package WooCommerce_Upnid/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="woocommerce-message">
	<span><a class="button" href="<?php echo esc_url( $url ); ?>" target="_blank"><?php esc_html_e( 'Pay the banking ticket', 'upnid-woocommerce' ); ?></a><?php esc_html_e( 'Please click in the following button to view your banking ticket.', 'upnid-woocommerce' ); ?><br /><?php esc_html_e( 'You can print and pay in your internet banking or in a lottery retailer.', 'upnid-woocommerce' ); ?><br /><?php esc_html_e( 'After we receive the banking ticket payment confirmation, your order will be processed.', 'upnid-woocommerce' ); ?></span>
</div>
