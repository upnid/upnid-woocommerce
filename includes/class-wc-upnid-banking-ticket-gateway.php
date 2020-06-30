<?php
/**
 * Upnid Banking Ticket gateway
 *
 * @package WooCommerce_Upnid/Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Upnid_Banking_Ticket_Gateway class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Upnid_Banking_Ticket_Gateway extends WC_Payment_Gateway {
	

	/**
	 * @var WC_Logger
	 */
	public $log;
	
	/**
	 * @var string
	 */
	public $api_key;
	
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
	public $webhook_endpoint;
	
	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id = 'upnid-banking-ticket';
		$this->icon = apply_filters( 'wc_upnid_banking_ticket_icon', false );
		$this->has_fields = true;
		$this->method_title = __( 'Upnid - Banking Ticket', 'upnid-woocommerce' );
		$this->method_description = __( 'Accept banking ticket payments using Upnid.', 'upnid-woocommerce' );
		$this->view_transaction_url = 'https://next.upnid.com/payments/view/%s';
		
		// Load the form fields.
		$this->init_form_fields();
		
		// Load the settings.
		$this->init_settings();
		
		// Define user set variables.
		$this->title = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->api_key = $this->get_option( 'api_key' );
		$this->debug = $this->get_option( 'debug' );
		$this->webhook_endpoint = 'wc_upnid_banking_ticket_gateway';
		
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
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 3 );
		add_action( 'woocommerce_api_'.$this->webhook_endpoint, array( $this, 'webhook_handler' ) );
		
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
			'enabled'     => array(
				'title'   => __( 'Enable/Disable', 'upnid-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Upnid Banking Ticket', 'upnid-woocommerce' ),
				'default' => 'no',
			),
			'title'       => array(
				'title'       => __( 'Title', 'upnid-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'upnid-woocommerce' ),
				'desc_tip'    => true,
				'default'     => __( 'Banking Ticket', 'upnid-woocommerce' ),
			),
			'description' => array(
				'title'       => __( 'Description', 'upnid-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'upnid-woocommerce' ),
				'desc_tip'    => true,
				'default'     => __( 'Pay with Banking Ticket', 'upnid-woocommerce' ),
			),
			'integration' => array(
				'title'       => __( 'Integration Settings', 'upnid-woocommerce' ),
				'type'        => 'title',
				'description' => '',
			),
			'api_key'     => array(
				'title'             => __( 'Upnid API Key', 'upnid-woocommerce' ),
				'type'              => 'text',
				'description'       => sprintf( __( 'Please enter your Upnid API Key. This is needed to process the payment and notifications. This API Key must have permissions to create, update and delete products, payments and webhooks. It is possible get your API Key in %s.', 'upnid-woocommerce' ), '<a href="https://next.upnid.com/developer/api-keys">' . __( 'Upnid Dashboard > My Account page', 'upnid-woocommerce' ) . '</a>' ),
				'default'           => '',
				'custom_attributes' => array(
					'required' => 'required',
				),
			),
			'testing'     => array(
				'title'       => __( 'Gateway Testing', 'upnid-woocommerce' ),
				'type'        => 'title',
				'description' => '',
			),
			'debug'       => array(
				'title'       => __( 'Debug Log', 'upnid-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'upnid-woocommerce' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Log Upnid events, such as API requests. You can check the log in %s', 'upnid-woocommerce' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.log' ) ) . '">' . __( 'System Status &gt; Logs', 'upnid-woocommerce' ) . '</a>' ),
			),
		);
	}
	
	/**
	 * Payment fields.
	 */
	public function payment_fields() {
		if ( $description = $this->get_description() ) {
			echo wp_kses_post( wpautop( wptexturize( $description ) ) );
		}
		
		wc_get_template(
			'banking-ticket/checkout-instructions.php',
			array(),
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
		
		if ( isset( $data['boleto_url'] ) && in_array( $order->get_status(), array(
				'processing',
				'on-hold'
			), true ) ) {
			
			wc_get_template(
				'banking-ticket/payment-instructions.php',
				array(
					'url' => $data['boleto_url'],
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
		
		if ( isset( $data['boleto_url'] ) ) {
			$email_type = $plain_text ? 'plain' : 'html';
			
			wc_get_template(
				'banking-ticket/emails/' . $email_type . '-instructions.php',
				array(
					'url' => $data['boleto_url'],
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
