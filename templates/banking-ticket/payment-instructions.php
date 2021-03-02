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

<div class="woocommerce-message" style="display: block;">
	<span id="upnid-woocommerce-billet-buttons-container">
        <a class="button" href="<?php echo esc_url( $url ); ?>" id="upnid-woocommerce-pay-billet-button" target="_blank" style="margin-left: 5px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" style="display: unset;" viewBox="0 0 16 16">
              <path d="M1.5 1a.5.5 0 0 0-.5.5v3a.5.5 0 0 1-1 0v-3A1.5 1.5 0 0 1 1.5 0h3a.5.5 0 0 1 0 1h-3zM11 .5a.5.5 0 0 1 .5-.5h3A1.5 1.5 0 0 1 16 1.5v3a.5.5 0 0 1-1 0v-3a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 1-.5-.5zM.5 11a.5.5 0 0 1 .5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 1 0 1h-3A1.5 1.5 0 0 1 0 14.5v-3a.5.5 0 0 1 .5-.5zm15 0a.5.5 0 0 1 .5.5v3a1.5 1.5 0 0 1-1.5 1.5h-3a.5.5 0 0 1 0-1h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 1 .5-.5zM3 4.5a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0v-7zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0v-7zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0v-7zm2 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-7zm3 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0v-7z"/>
            </svg>
            <?php esc_html_e( 'Pay the banking ticket', 'upnid-woocommerce' ); ?>
        </a>
        <a class="button" href="https://wa.me/?text=<?php echo rawurlencode( esc_html_e( 'Billet from your purchase at', 'upnid-woocommerce' ) ); ?> <?php echo rawurlencode( get_bloginfo( 'name' ) ); ?><?php echo rawurlencode( ': ' ); ?><?php echo esc_url( $url ); ?>"
           id="upnid-woocommerce-whatsapp-button" target="_blank">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" style="display: unset;" viewBox="0 0 16 16">
              <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
            </svg>
            <?php esc_html_e( 'Send via WhatsApp', 'upnid-woocommerce' ); ?>
        </a>
    </span>
    <div id="upnid-woocommerce-billet-instructions">
        <span>
            <span style="font-size: 20px; font-weight: bold; margin-bottom: 10px;"><?php esc_html_e( 'Instructions', 'upnid-woocommerce' ); ?></span><br>
            <?php esc_html_e( '1. You can print and pay in your internet banking or in a lottery retailer.', 'upnid-woocommerce' ); ?><br/>
            <?php esc_html_e( '2. You can also pay via the internet using the barcode:', 'upnid-woocommerce' ); ?>
        </span>
        <div id="upnid-woocommerce-barcode-container"
             style="padding: 10px; border: 2px dashed rgba(0,0,0,0.2); background-color: rgba(0,0,0,0.1); margin-top: 10px;display: flex;align-items: center;justify-content: space-between;">
            <span id="upnid-woocommerce-barcode-input"
                  style="font-weight: bold; padding: 2px 4px; transition: all .3s;"><?php esc_html_e( $barcode ); ?></span>
            <button class="button" id="upnid-woocommerce-copy-barcode"
                    data-uw-copied-text="<?php esc_html_e( 'Copied!', 'upnid-woocommerce' ); ?>"
                    onclick="setClipboard('<?php esc_html_e( $barcode ); ?>')" style="float: right">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" style="display: unset;" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z"/>
                    <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1v-1z"/>
                    <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3z"/>
                </svg>
				<?php esc_html_e( 'Copy', 'upnid-woocommerce' ); ?>
            </button>
        </div>
    </div>
</div>

<script>
    function setClipboard(value) {
        var contentElement = document.getElementById("upnid-woocommerce-barcode-input");
        var buttonElement = document.getElementById("upnid-woocommerce-copy-barcode");
        var oldButtonText = buttonElement.innerHTML;
        var tempInput = document.createElement("input");
        tempInput.style = "position: absolute; left: -1000px; top: -1000px";
        tempInput.value = value;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand("copy");
        document.body.removeChild(tempInput);

        contentElement.style.backgroundColor = "rgba(0,0,0,0.2)";
        buttonElement.innerHTML = buttonElement.getAttribute('data-uw-copied-text');

        setTimeout(function () {
            contentElement.style.backgroundColor = "transparent";
            buttonElement.innerHTML = oldButtonText;
        }, 4000)
    }
</script>
