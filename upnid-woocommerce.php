<?php
/**
 * Plugin Name: Upnid
 * Plugin URI: https://github.com/upnid/upnid-woocommerce
 * Description: Upnid payment gateway for WooCommerce.
 * Author: Upnid
 * Author URI: https://upnid.com
 * Version: 1.0.1
 * Text Domain: upnid-woocommerce
 * Domain Path: /languages/
 *
 * @package WooCommerce_Upnid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Upnid' ) ) :
	
	/**
	 * WooCommerce WC_Upnid main class.
	 */
	class WC_Upnid {
		
		/**
		 * Plugin version.
		 *
		 * @var string
		 */
		const VERSION = '1.0.1';
		
		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $instance = null;
		
		/**
		 * Initialize the plugin public actions.
		 */
		private function __construct() {
			
			/*
			 * Load plugin text domain.
			 */
			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
			
			/*
			 * Checks with WooCommerce is installed.
			 */
			if ( class_exists( 'WC_Payment_Gateway' ) ) {
				$this->upgrade();
				$this->includes();
				
				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
				add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array(
					$this,
					'plugin_action_links'
				) );
			} else {
				add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			}
			
			/*
			 * Checks if Extra Checkout Fields is installed, and if it has a
			 * person type selected.
			 */
			if ( class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
				$wcbcf_settings = get_option( 'wcbcf_settings' );
				
				if ( $wcbcf_settings['person_type'] === '0' ) {
					$wcbcf_settings['person_type'] = '1';
					update_option( 'wcbcf_settings', $wcbcf_settings );
					add_action( 'admin_notices', array( $this, 'extra_checkout_fields_person_type_notice' ) );
				}
				
			} else {
				add_action( 'admin_notices', array( $this, 'extra_checkout_fields_missing_notice' ) );
			}
			
			/*
			 * Check if SSL is enabled.
			 */
			if ( ! is_ssl() && ! in_array( $_SERVER['REMOTE_ADDR'], array( '127.0.0.1', '::1' ) ) ) {
				add_action( 'admin_notices', array( $this, 'ssl_missing_notice' ) );
			}
			
		}
		
		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class.
		 */
		public static function get_instance() {
			/*
			 * If the single instance hasn't been set, set it now.
			 */
			if ( null === self::$instance ) {
				self::$instance = new self;
			}
			
			return self::$instance;
		}
		
		/**
		 * Includes required files.
		 */
		private function includes() {
			include_once dirname( __FILE__ ) . '/vendor/autoload.php';
			include_once dirname( __FILE__ ) . '/includes/class-wc-upnid-api.php';
			include_once dirname( __FILE__ ) . '/includes/class-wc-upnid-my-account.php';
			include_once dirname( __FILE__ ) . '/includes/class-wc-upnid-banking-ticket-gateway.php';
			include_once dirname( __FILE__ ) . '/includes/class-wc-upnid-credit-card-gateway.php';
		}
		
		/**
		 * Load the plugin text domain for translation.
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'upnid-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}
		
		/**
		 * Get templates path.
		 *
		 * @return string
		 */
		public static function get_templates_path() {
			return plugin_dir_path( __FILE__ ) . 'templates/';
		}
		
		/**
		 * Add the gateways to WooCommerce.
		 *
		 * @param array $methods WooCommerce payment methods.
		 *
		 * @return array
		 */
		public function add_gateway( $methods ) {
			$methods[] = 'WC_Upnid_Banking_Ticket_Gateway';
			$methods[] = 'WC_Upnid_Credit_Card_Gateway';
			
			return $methods;
		}
		
		/**
		 * Action links on plugin page.
		 *
		 * @param array $links Plugin links.
		 *
		 * @return array
		 */
		public function plugin_action_links( $links ) {
			$plugin_links = array();
			
			$banking_ticket = 'wc_upnid_banking_ticket_gateway';
			$credit_card = 'wc_upnid_credit_card_gateway';
			
			$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $banking_ticket ) ) . '">' . __( 'Bank Slip Settings', 'upnid-woocommerce' ) . '</a>';
			
			$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $credit_card ) ) . '">' . __( 'Credit Card Settings', 'upnid-woocommerce' ) . '</a>';
			
			return array_merge( $plugin_links, $links );
		}
		
		/**
		 * SSL fallback notice.
		 */
		public function ssl_missing_notice() {
			include dirname( __FILE__ ) . '/includes/admin/views/html-notice-missing-ssl.php';
		}
		
		/**
		 * WooCommerce fallback notice.
		 */
		public function woocommerce_missing_notice() {
			include dirname( __FILE__ ) . '/includes/admin/views/html-notice-missing-woocommerce.php';
		}
		
		/**
		 * Extra Checkout Fields for Brazil fallback notice.
		 */
		public function extra_checkout_fields_missing_notice() {
			include dirname( __FILE__ ) . '/includes/admin/views/html-notice-missing-extra-checkout-fields.php';
		}
		
		/**
		 * Extra Checkout Fields for Brazil person types notice.
		 */
		public function extra_checkout_fields_person_type_notice() {
			include dirname( __FILE__ ) . '/includes/admin/views/html-notice-person-type-extra-checkout-fields.php';
		}
		
		/**
		 * Upgrade the plugin.
		 *
		 * @since 1.0.0
		 */
		private function upgrade() {
			if ( is_admin() ) {
				if ( $old_options = get_option( 'woocommerce_upnid_settings' ) ) {
					/*
					 * Banking ticket options.
					 */
					$banking_ticket = array(
						'enabled'     => $old_options['enabled'],
						'title'       => 'Boleto bancário',
						'description' => '',
						'api_key'     => $old_options['api_key'],
						'debug'       => $old_options['debug'],
					);
					
					/*
					 * Credit card options.
					 */
					$credit_card = array(
						'enabled'              => $old_options['enabled'],
						'title'                => 'Cartão de crédito',
						'description'          => '',
						'api_key'              => $old_options['api_key'],
						'max_installment'      => $old_options['max_installment'],
						'smallest_installment' => $old_options['smallest_installment'],
						'interest_rate'        => $old_options['interest_rate'],
						'free_installments'    => $old_options['free_installments'],
						'debug'                => $old_options['debug'],
					);
					
					update_option( 'woocommerce_upnid-banking-ticket_settings', $banking_ticket );
					update_option( 'woocommerce_upnid-credit-card_settings', $credit_card );
					
					delete_option( 'woocommerce_upnid_settings' );
				}
			}
		}
		
	}
	
	add_action( 'plugins_loaded', array( 'WC_Upnid', 'get_instance' ) );

endif;
