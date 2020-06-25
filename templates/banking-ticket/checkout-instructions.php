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

<div id="upnid-bank-slip-instructions">
    <p>
		<?php esc_html_e( 'After clicking "Place order" you will have access to banking banking ticket which you can print and pay in your internet banking or in a lottery retailer.', 'upnid-woocommerce' ); ?>
    </p>
    <div class="upnid-bank-slip-note" style="padding: 5px; border: 1px solid #ddd; margin-top: 10px;">
		<?php esc_html_e( 'Note: The order will be confirmed only after the payment approval.', 'upnid-woocommerce' ); ?>
    </div>
</div>
