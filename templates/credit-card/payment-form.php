<?php
/**
 * Credit Card - Checkout form.
 *
 * @author  Upnid
 * @package WooCommerce_Upnid/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<style>
    .jp-card .jp-card-front, .jp-card .jp-card-back {
        background-color: <?php echo esc_html($cc_background);?>;
    }
</style>

<div class="card-wrapper" style="margin-bottom:15px; margin-top: 15px;"></div>

<form action="" id="upnid">
    <fieldset id="upnid-credit-card-form">
        <p class="form-row form-row-first">
            <label for="upnid-card-holder-name"><?php esc_html_e( 'Card Holder Name', 'upnid-woocommerce' ); ?><span
                        class="required">*</span></label>
            <input id="upnid-card-holder-name" class="input-text" type="text" autocomplete="off"
                   style="font-size: 1.2em; padding: 8px;"/>
        </p>
        <p class="form-row form-row-last">
            <label for="upnid-card-number"><?php esc_html_e( 'Card Number', 'upnid-woocommerce' ); ?> <span
                        class="required">*</span></label>
            <input id="upnid-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20"
                   autocomplete="off"
                   placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;"
                   style="font-size: 1.2em; padding: 8px;"/>
        </p>
        <div class="clear"></div>
        <p class="form-row form-row-first">
            <label for="upnid-card-expiry"><?php esc_html_e( 'Expiry (MM/YY)', 'upnid-woocommerce' ); ?> <span
                        class="required">*</span></label>
            <input id="upnid-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text"
                   autocomplete="off" placeholder="<?php esc_html_e( 'MM / YY', 'upnid-woocommerce' ); ?>"
                   style="font-size: 1.2em; padding: 8px;"/>
        </p>
        <p class="form-row form-row-last">
            <label for="upnid-card-cvc"><?php esc_html_e( 'Card Code', 'upnid-woocommerce' ); ?> <span class="required">*</span></label>
            <input id="upnid-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off"
                   placeholder="<?php esc_html_e( 'CVC', 'upnid-woocommerce' ); ?>"
                   style="font-size: 1.2em; padding: 8px;"/>
        </p>
        <div class="clear"></div>
		<?php if ( apply_filters( 'wc_upnid_allow_credit_card_installments', 1 < $max_installment ) ) : ?>
            <p class="form-row form-row-wide">
                <label for="upnid-card-installments"><?php esc_html_e( 'Installments', 'upnid-woocommerce' ); ?> <span
                            class="required">*</span></label>
                <select id="upnid-installments"
                        style="font-size: 1.2em; padding: 8px; width: 100%;">
                    <option value="0"><?php printf( esc_html__( 'Please, select the number of installments', 'upnid-woocommerce' ) ); ?></option>
					<?php
					foreach ( $installments as $number => $installment ) :
						if ( 1 !== $number && $smallest_installment > $installment['installment_amount'] ) {
							break;
						}
						
						$interest = ( ( $cart_total * 100 ) < $installment['amount'] ) ? sprintf( __( '(total of %s)', 'upnid-woocommerce' ), strip_tags( wc_price( $installment['amount'] / 100 ) ) ) : __( '(interest-free)', 'upnid-woocommerce' );
						$installment_amount = strip_tags( wc_price( $installment['installment_amount'] / 100 ) );
						?>
                        <option value="<?php echo absint( $installment['installment'] ); ?>"><?php printf( esc_html__( '%1$dx of %2$s %3$s', 'upnid-woocommerce' ), absint( $installment['installment'] ), esc_html( $installment_amount ), esc_html( $interest ) ); ?></option>
					<?php endforeach; ?>
                </select>
            </p>
		<?php endif; ?>
    </fieldset>
</form>
