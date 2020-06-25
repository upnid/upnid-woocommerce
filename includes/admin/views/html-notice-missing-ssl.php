<?php
/**
 * Notice: Currency not supported.
 *
 * @package WooCommerce_Upnid/Admin/Notices
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

    <div class="error inline">
        <p>
            <strong><?php esc_html_e( 'Warning', 'upnid-woocommerce' ); ?></strong>:
			<?php esc_html_e( 'You need to enable SSL (HTTPS) on this domain in order to offer a secure checkout to your customers.', 'upnid-woocommerce' ); ?>
            <a href="https://tudosobrehospedagemdesites.com.br/configurar-ssl-no-wordpress/" target="_blank">
				<?php esc_html_e( 'Click here to know more.', 'upnid-woocommerce' ) ?>
            </a>
        </p>
    </div>

<?php
