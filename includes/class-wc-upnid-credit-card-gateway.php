<?php
/**
 * Upnid Credit Card gateway
 *
 * @package WooCommerce_Upnid/Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Upnid_Credit_Card_Gateway class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Upnid_Credit_Card_Gateway extends WC_Payment_Gateway {
	
	/**
	 * @var string
	 */
	public $api_key;
	
	/**
	 * @var WC_Logger
	 */
	public $log;
	
	/**
	 * @var string
	 */
	public $debug;
	
	/**
	 * @var WC_Upnid_API
	 */
	public $api;
	
	/**
	 * @var string
	 */
	public $max_installment;
	
	/**
	 * @var string
	 */
	public $smallest_installment;
	
	/**
	 * @var string
	 */
	public $interest_rate;
	
	/**
	 * @var string
	 */
	public $free_installments;
	
	/**
	 * @var string
	 */
	public $webhook_endpoint;
	
	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id = 'upnid-credit-card';
		$this->icon = apply_filters( 'wc_upnid_credit_card_icon', false );
		$this->has_fields = true;
		$this->method_title = __( 'Upnid - Credit Card', 'upnid-woocommerce' );
		$this->method_description = __( 'Accept credit card payments using Upnid.', 'upnid-woocommerce' );
		$this->view_transaction_url = 'https://next.upnid.com/payments/view/%s';
		$this->webhook_endpoint = 'wc_upnid_credit_card_gateway';
		
		// Load the form fields.
		$this->init_form_fields();
		
		// Load the settings.
		$this->init_settings();
		
		// Define user set variables.
		$this->title = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->api_key = $this->get_option( 'api_key' );
		$this->max_installment = $this->get_option( 'max_installment' );
		$this->smallest_installment = $this->get_option( 'smallest_installment' );
		$this->interest_rate = '2.99';
		$this->free_installments = $this->get_option( 'free_installments', '1' );
		$this->debug = $this->get_option( 'debug' );
		
		// Active logs.
		if ( 'yes' === $this->debug ) {
			$this->log = new WC_Logger();
		}
		
		// Set the API.
		$this->api = new WC_Upnid_API( $this );
		
		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );
		add_action( 'wp_enqueue_scripts', array( $this, 'checkout_scripts' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 3 );
		add_action( 'woocommerce_api_' . $this->webhook_endpoint, array( $this, 'webhook_handler' ) );
		
		/*
		 * Setup webhook on Upnid if it is not created.
		 */
		$this->webhook_setup();
	}
	
	/**
	 * Admin page.
	 */
	public function admin_options() {
		include dirname( __FILE__ ) . '/admin/views/html-admin-page.php';
	}
	
	/**
	 * Check if the gateway is available to take payments.
	 *
	 * @return bool
	 */
	public function is_available() {
		return parent::is_available() && ! empty( $this->api_key ) && $this->api->using_supported_currency();
	}
	
	/**
	 * Settings fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'              => array(
				'title'   => __( 'Enable/Disable', 'upnid-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Upnid Credit Card', 'upnid-woocommerce' ),
				'default' => 'no',
			),
			'title'                => array(
				'title'       => __( 'Title', 'upnid-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'upnid-woocommerce' ),
				'desc_tip'    => true,
				'default'     => __( 'Credit Card', 'upnid-woocommerce' ),
			),
			'description'          => array(
				'title'       => __( 'Description', 'upnid-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'upnid-woocommerce' ),
				'desc_tip'    => true,
				'default'     => __( 'Pay with Credit Card', 'upnid-woocommerce' ),
			),
			'cc_background'        => array(
				'title'       => __( 'Credit Card Color', 'upnid-woocommerce' ),
				'type'        => 'color',
				'description' => __( 'Starter background color for the credit card animation.', 'upnid-woocommerce' ),
				'default'     => '#226DE6'
			),
			'integration'          => array(
				'title'       => __( 'Integration Settings', 'upnid-woocommerce' ),
				'type'        => 'title',
				'description' => '',
			),
			'api_key'              => array(
				'title'             => __( 'Upnid API Key', 'upnid-woocommerce' ),
				'type'              => 'text',
				'description'       => sprintf( __( 'Please enter your Upnid API Key. This is needed to process the payment and notifications. This API Key must have permissions to create, update and delete products, payments and webhooks. It is possible get your API Key in %s.', 'upnid-woocommerce' ), '<a href="https://next.upnid.com/developer/api-keys">' . __( 'Upnid Dashboard > My Account page', 'upnid-woocommerce' ) . '</a>' ),
				'default'           => '',
				'custom_attributes' => array(
					'required' => 'required',
				),
			),
			'installments'         => array(
				'title'       => __( 'Installments', 'upnid-woocommerce' ),
				'type'        => 'title',
				'description' => '',
			),
			'max_installment'      => array(
				'title'       => __( 'Number of Installment', 'upnid-woocommerce' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'default'     => '12',
				'description' => __( 'Maximum number of installments possible with payments by credit card.', 'upnid-woocommerce' ),
				'desc_tip'    => true,
				'options'     => array(
					'1'  => '1',
					'2'  => '2',
					'3'  => '3',
					'4'  => '4',
					'5'  => '5',
					'6'  => '6',
					'7'  => '7',
					'8'  => '8',
					'9'  => '9',
					'10' => '10',
					'11' => '11',
					'12' => '12',
				),
			),
			'smallest_installment' => array(
				'title'       => __( 'Smallest Installment', 'upnid-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Please enter the value of the smallest installment, Note: it can not be lower than 1.', 'upnid-woocommerce' ),
				'desc_tip'    => true,
				'default'     => '1',
			),
			/*'interest_rate'        => array(
				'title'       => __( 'Interest rate', 'upnid-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Please enter with the interest rate amount. Note: use 0 to not charge interest.', 'upnid-woocommerce' ),
				'desc_tip'    => true,
				'default'     => '2.99',
			),*/
			'free_installments'    => array(
				'title'       => __( 'Free Installments', 'upnid-woocommerce' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'default'     => '1',
				'description' => __( 'Number of installments with interest free.', 'upnid-woocommerce' ),
				'desc_tip'    => true,
				'options'     => array(
					'0'  => _x( 'None', 'no free installments', 'upnid-woocommerce' ),
					'1'  => '1',
					'2'  => '2',
					'3'  => '3',
					'4'  => '4',
					'5'  => '5',
					'6'  => '6',
					'7'  => '7',
					'8'  => '8',
					'9'  => '9',
					'10' => '10',
					'11' => '11',
					'12' => '12',
				),
			),
			'testing'              => array(
				'title'       => __( 'Gateway Testing', 'upnid-woocommerce' ),
				'type'        => 'title',
				'description' => '',
			),
			'debug'                => array(
				'title'       => __( 'Debug Log', 'upnid-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'upnid-woocommerce' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Log Upnid events, such as API requests. You can check the log in %s', 'upnid-woocommerce' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.log' ) ) . '">' . __( 'System Status &gt; Logs', 'upnid-woocommerce' ) . '</a>' ),
			),
		);
	}
	
	/**
	 * Checkout scripts.
	 */
	public function checkout_scripts() {
		if ( is_checkout() ) {
			
			wp_enqueue_script( 'wc-credit-card-form' );
			
			wp_enqueue_script( 'upnid-checkout-cardjs', plugins_url( 'assets/libs/card/jquery.card.js', plugin_dir_path( __FILE__ ) ), array(
				'jquery',
				'jquery-blockui'
			), WC_Upnid::VERSION, true );
			
			wp_enqueue_script( 'upnid-checkout', plugins_url( 'assets/js/checkout.js', plugin_dir_path( __FILE__ ) ), array(
				'jquery',
				'jquery-blockui'
			), WC_Upnid::VERSION, true );
			
			wp_localize_script(
				'upnid-checkout',
				'wcUpnidParams',
				array(
					'uiColor' => apply_filters( 'wc_upnid_checkout_ui_color', '#1a6ee1' ),
				)
			);
		}
	}
	
	/**
	 * Renders credit card payment fields.
	 *
	 * @since 1.0.0
	 */
	public function payment_fields() {
		if ( $description = $this->get_description() ) {
			echo wp_kses_post( wpautop( wptexturize( $description ) ) );
		}
		
		$cart_total = $this->get_order_total();
		
		$installments = $this->api->get_installments( $cart_total );
		
		wc_get_template(
			'credit-card/payment-form.php',
			array(
				'cart_total'           => $cart_total,
				'max_installment'      => $this->max_installment,
				'smallest_installment' => $this->api->get_smallest_installment(),
				'installments'         => $installments,
				'cc_background'        => $this->get_option( 'cc_background', '#226DE6' )
			),
			'woocommerce/upnid/',
			WC_Upnid::get_templates_path()
		);
		
	}
	
	/**
	 * Process the payment.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array Redirect data.
	 */
	public function process_payment( $order_id ) {
		return $this->api->process_regular_payment( $order_id );
	}
	
	/**
	 * Thank You page message.
	 *
	 * @param int $order_id Order ID.
	 */
	public function thankyou_page( $order_id ) {
		$order = wc_get_order( $order_id );
		$data = get_post_meta( $order_id, '_wc_upnid_transaction_data', true );
		
		if ( isset( $data['installments'] ) && in_array( $order->get_status(), array(
				'processing',
				'on-hold'
			), true ) ) {
			wc_get_template(
				'credit-card/payment-instructions.php',
				array(
					'card_brand'   => $data['card_brand'],
					'installments' => $data['installments'],
				),
				'woocommerce/upnid/',
				WC_Upnid::get_templates_path()
			);
		}
	}
	
	/**
	 * Add content to the WC emails.
	 *
	 * @param object $order Order object.
	 * @param bool $sent_to_admin Send to admin.
	 * @param bool $plain_text Plain text or HTML.
	 *
	 * @return string                Payment instructions.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $sent_to_admin || ! in_array( $order->get_status(), array(
				'processing',
				'on-hold'
			), true ) || $this->id !== $order->payment_method ) {
			return;
		}
		
		$data = get_post_meta( $order->id, '_wc_upnid_transaction_data', true );
		
		if ( isset( $data['installments'] ) ) {
			$email_type = $plain_text ? 'plain' : 'html';
			
			wc_get_template(
				'credit-card/emails/' . $email_type . '-instructions.php',
				array(
					'card_brand'   => $data['card_brand'],
					'installments' => $data['installments'],
				),
				'woocommerce/upnid/',
				WC_Upnid::get_templates_path()
			);
		}
	}
	
	/**
	 * Webhook handler.
	 */
	public function webhook_handler() {
		$this->api->webhook_handler();
	}
	
	/**
	 * Sets up the webhook for this gateway.
	 */
	private function webhook_setup() {
		$this->api->webhook_setup();
	}
}
