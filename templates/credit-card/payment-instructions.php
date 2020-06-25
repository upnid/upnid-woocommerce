<?php
/**
 * Credit Card - Payment instructions.
 *
 * @author  Upnid
 * @package WooCommerce_Upnid/Templates
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="woocommerce-message">
	<span>
        <?php printf( wp_kses( __( 'Payment successfully made using %1$s credit card in %2$s.', 'upnid-woocommerce' ), array( 'strong' => array() ) ), '<strong>' . esc_html( $card_brand ) . '</strong>', '<strong>' . intval( $installments ) . 'x</strong>' ); ?>
    </span>
</div>
