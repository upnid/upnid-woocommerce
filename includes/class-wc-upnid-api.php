<?php
/**
 * Upnid API
 *
 * @package WooCommerce_Upnid/API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Upnid_API class.
 */
class WC_Upnid_API {
	
	/**
	 * Gateway class.
	 *
	 * @var WC_Payment_Gateway
	 */
	protected $gateway;
	
	/**
	 * API URL.
	 *
	 * @var string
	 */
	protected $api_url = 'https://api.upnid.com/2020-06/graphql';
	
	/**
	 * Dashboard URL.
	 *
	 * @var string
	 */
	public $dashboard_url = 'https://next.upnid.com/payments/view/';
	
	/**
	 * Constructor.
	 *
	 * @param WC_Upnid_Banking_Ticket_Gateway|WC_Upnid_Credit_Card_Gateway $gateway Gateway instance.
	 */
	public function __construct( $gateway = null ) {
		$this->gateway = $gateway;
	}
	
	/**
	 * Get API URL.
	 *
	 * @return string
	 */
	public function get_api_url() {
		return $this->api_url;
	}
	
	/**
	 * Returns a bool that indicates if currency is amongst the supported ones.
	 *
	 * @return bool
	 */
	public function using_supported_currency() {
		return 'BRL' === get_woocommerce_currency();
	}
	
	/**
	 * Only numbers.
	 *
	 * @param string|int $string String to convert.
	 *
	 * @return string|int
	 */
	protected function only_numbers( $string ) {
		return preg_replace( '([^0-9])', '', $string );
	}
	
	/**
	 * Get the smallest installment amount.
	 *
	 * @return int
	 */
	public function get_smallest_installment() {
		return ( 1 > $this->gateway->smallest_installment ) ? 100 : wc_format_decimal( $this->gateway->smallest_installment ) * 100;
	}
	
	/**
	 * Get the interest rate.
	 *
	 * @return float
	 */
	public function get_interest_rate() {
		return wc_format_decimal( $this->gateway->interest_rate );
	}
	
	/**
	 * Get the installments.
	 *
	 * @param float $amount Order amount.
	 *
	 * @return array
	 */
	public function get_installments( $amount ) {
		
		$installments = array();
		
		$interest_rate = $this->get_interest_rate();
		
		for ( $index = 1; $index <= $this->gateway->max_installment; $index ++ ) {
			
			$installment_amount = ( $index <= $this->gateway->free_installments ) ? $this->get_installment_value( $amount, $index ) : $this->get_installment_value( $amount, $index, $interest_rate );
			
			$installments[ $index ] = array(
				'installment'        => $index,
				'amount'             => $installment_amount * $index * 100,
				'installment_amount' => $installment_amount * 100
			);
		}
		
		return $installments;
	}
	
	/**
	 * Interest amortization.
	 *
	 * @param $total
	 * @param $installments
	 * @param float $interest
	 *
	 * @return string
	 */
	private function get_installment_value( $total, $installments, $interest = 0.0 ) {
		if ( $interest == 0 ) {
			return wc_format_decimal( $total / $installments, 2 );
		} else {
			$interest_rate = $interest / 100.00;
			$installment_value = pow( ( 1 + $interest_rate ), $installments );
			$installment_value = $installment_value / ( pow( ( 1 + $interest_rate ), $installments ) - 1 );
			$installment_value = $total * $interest_rate * $installment_value;
			
			return wc_format_decimal( $installment_value, 2 );
		}
	}
	
	/**
	 * Save order meta fields.
	 * Save fields as meta data to display on order's admin screen.
	 *
	 * @param int $id Order ID.
	 * @param stdClass $data Order data.
	 */
	protected function save_order_meta_fields( $id, $data ) {
		
		// Transaction data.
		$payment_data = array_map(
			'sanitize_text_field',
			array(
				'payment_method' => $data->createPayment->status,
				'installments'   => $data->createPayment->installments,
				'card_brand'     => ( isset( $data->createPayment->card->brand ) ) ? $data->createPayment->card->brand : '',
				'boleto_url'     => ( isset( $data->createPayment->boleto->url ) ) ? $data->createPayment->boleto->url : '',
				'boleto_barcode' => ( isset( $data->createPayment->boleto->number ) ) ? $data->createPayment->boleto->number : '',
			)
		);
		
		// Meta data.
		$meta_data = array(
			__( 'Banking Ticket URL', 'upnid-woocommerce' ) => ( isset( $data->createPayment->boleto->url ) ) ? sanitize_text_field( $data->createPayment->boleto->url ) : '',
			__( 'Credit Card', 'upnid-woocommerce' )        => ( isset( $data->createPayment->card->brand ) ) ? sanitize_text_field( $data->createPayment->card->brand ) : '',
			__( 'Installments', 'upnid-woocommerce' )       => sanitize_text_field( $data->createPayment->installments ),
			__( 'Total paid', 'upnid-woocommerce' )         => number_format( intval( $data->createPayment->amount ) / 100, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ),
			'_wc_upnid_transaction_data'                    => $payment_data,
			'_wc_upnid_transaction_id'                      => sanitize_text_field( $data->createPayment->id ),
			'_transaction_id'                               => sanitize_text_field( $data->createPayment->id ),
		);
		
		$order = wc_get_order( $id );
		
		// WooCommerce 3.0 or later.
		if ( ! method_exists( $order, 'update_meta_data' ) ) {
			foreach ( $meta_data as $key => $value ) {
				update_post_meta( $id, $key, $value );
			}
		} else {
			foreach ( $meta_data as $key => $value ) {
				$order->update_meta_data( $key, $value );
			}
			
			$order->save();
		}
	}
	
	/**
	 * Process regular payment.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array Redirect data.
	 */
	public function process_regular_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		
		$validation_errors = array();
		
		$sanitized_post = $this->sanitize_array_data( $_POST );
		
		$_line_tems = '';
		$_payment_method = '';
		$_document_number = '';
		$_customer_name = '';
		$_amount = $order->get_total();
		$_installments = ( ! empty( $sanitized_post['upnid-installments'] ) ) ? intval( $sanitized_post['upnid-installments'] ) : 1;
		
		/*
		* Create lineItems object
		*/
		foreach ( $order->get_items() as $item ) {
			$price = wc_format_decimal( $item['total'], 2 ) * 100;
			$_line_tems .= <<<GRAPHQL
			    {
			        type: SKU,
			        sku: {
			          name: "{$item['name']}"
			          price: {$price},
			          product: {
			            name: "{$item['name']}"
			          },
			          metadata: {
			            woocommerce_product_id: {$item['product_id']},
			            woocommerce_variation_id: {$item['variation_id']}
			          }
			        }
			    },
GRAPHQL;
		}
		
		/*
		 * Credit card order preparation
		 */
		if ( $order->get_payment_method() === 'upnid-credit-card' ) {
			
			if ( ! empty( $sanitized_post['upnid-card-number'] ) &&
			     ! empty( $sanitized_post['upnid-card-expiry'] ) &&
			     ! empty( $sanitized_post['upnid-card-cvc'] ) &&
			     ! empty( $sanitized_post['upnid-card-holder-name'] ) ) {
				
				/*
				 * Get the total amount including interest.
				 */
				$possible_installments = $this->get_installments( $_amount );
				$_amount = $possible_installments[ intval( $_installments ) ]['amount'];
				
				/*
				 * Credit card info.
				 */
				$card_number = $this->only_numbers( $sanitized_post['upnid-card-number'] );
				$card_expiry = $this->only_numbers( $sanitized_post['upnid-card-expiry'] );
				$card_expiry_month = substr( $card_expiry, 0, 2 );
				$card_expiry_year = substr( $card_expiry, 2, 4 );
				
				$_payment_method .= <<<GRAPHQL
				    method: CREDIT_CARD,
				    card: {
				      holderName: "{$sanitized_post['upnid-card-holder-name']}"
				      number: "{$card_number}"
				      expirationMonth: "{$card_expiry_month}"
				      expirationYear: "{$card_expiry_year}"
				      cvv: "{$sanitized_post['upnid-card-cvc']}"
				    },
GRAPHQL;
			} else {
				$validation_errors[] = array( 'message' => __( 'Missing credit card data, please review your data and try again or contact us for assistance.', 'upnid-woocommerce' ) );
			}
			
		} else {
			
			/*
			 * Boleto order preparation.
			 */
			$expires_at = date( 'Y-m-d', strtotime( '+ 3 days' ) );
			$_amount = $order->get_total() * 100;
			
			$_payment_method .= <<<GRAPHQL
				method: BOLETO,
				boleto: {
				  expiresAt: "{$expires_at}"
				}
GRAPHQL;
		}
		
		/*
		 * Document number.
		 *
		 * First, check if Extra_Checkout_Fields plugin is installed. If so,
		 * updates the document number and customer name accordingly. If not,
		 * try to get document info from the user account. If it really does
		 * not exist, we need to return an error to the user.
		 */
		if ( class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
			
			$wcbcf_settings = get_option( 'wcbcf_settings' );
			
			if ( ( '1' === $wcbcf_settings['person_type'] && '1' === $sanitized_post['billing_persontype'] ) || '2' === $wcbcf_settings['person_type'] ) {
				$_customer_name = $sanitized_post['billing_first_name'] . " " . $sanitized_post['billing_last_name'];
				$_document_number = $this->only_numbers( $sanitized_post['billing_cpf'] );
			}
			
			if ( ( '1' === $wcbcf_settings['person_type'] && '2' === $sanitized_post['billing_persontype'] ) || '3' === $wcbcf_settings['person_type'] ) {
				$_customer_name = $sanitized_post['billing_company'];
				$_document_number = $this->only_numbers( $sanitized_post['billing_cnpj'] );
			}
			
		}
		
		if ( empty( $_document_number ) && ! empty( $sanitized_post['billing_cpf'] ) ) {
			
			$_customer_name = $sanitized_post['billing_first_name'] . " " . $sanitized_post['billing_last_name'];
			$_document_number = $this->only_numbers( $sanitized_post['billing_cpf'] );
			
		} else if ( empty( $_document_number ) && ! empty( $sanitized_post['billing_cnpj'] ) ) {
			
			$_customer_name = $sanitized_post['billing_company'];
			$_document_number = $this->only_numbers( $sanitized_post['billing_cnpj'] );
			
		} else if ( empty( $_document_number ) ) {
			$validation_errors[] = array( 'message' => __( 'Missing person type and document data, please review your data and try again or contact us for assistance.', 'upnid-woocommerce' ) );
		}
		
		/*
		 * Check if we do not have any transaction errors.
		 */
		if ( count( $validation_errors ) ) {
			
			foreach ( $validation_errors as $error ) {
				wc_add_notice( $error['message'], 'error' );
			}
			
			return array(
				'result' => 'fail',
			);
			
		} else {
			
			/*
			 * Setup GraphQL mutation.
			 */
			$graphql_query = <<<GRAPHQL
			mutation {
			  createPayment(input: {
			    {$_payment_method}
			    amount: {$_amount},
			    customer: {
			      name: "{$_customer_name}"
			      email: "{$sanitized_post['billing_email']}"
			      phone: "{$sanitized_post['billing_phone']}"
			      document: "{$_document_number}"
			    },
			    shippingAddress: {
			      line1: "{$sanitized_post['billing_address_1']}",
			      line2: "{$sanitized_post['billing_number']}"
			      line3: "{$sanitized_post['billing_address_2']}"
			      neighborhood: "{$sanitized_post['billing_neighborhood']}"
			      city: "{$sanitized_post['billing_city']}"
			      state: "{$sanitized_post['billing_state']}"
			      postalCode: "{$sanitized_post['billing_postcode']}"
			    }
			    lineItems: [
			      {$_line_tems}
			    ],
			    installments: {$_installments},
			  }) {
			    id
			    status
			    installments
			    amount
			    method
			    card {
			      brand
			    }
			    declineReason
			    boleto {
			      url
			      number
			    }
			  }
			}
GRAPHQL;
			
			/*
			 * Executes mutation and collect results.
			 */
			try {
				
				if ( 'yes' === $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'Trying to execute GraphQL mutation for order ' . $order_id );
				}
				
				$transaction = $this->do_request( $graphql_query );
				
			} catch ( Exception $error ) {
				
				wc_add_notice( __( 'Oops... Something went wrong. Could you please validated the data that you have inputed and try again?', 'upnid-woocommerce' ), 'error' );
				
				if ( 'yes' === $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'Error when executing GraphQL mutation for order ' . $order_id . '. Error: ' . $error );
				}
				
				return array(
					'result' => 'fail',
				);
			}
			
			if ( 'yes' === $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'GraphQL mutation for order ' . $order_id . ' executed succesfully.' );
			}
			
			/*
			 * Checks if the mutation returned the expected response.
			 */
			if ( isset( $transaction->createPayment ) ) {
				
				if ( 'yes' === $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'createPayment returned for order ' . $order_id . '. Proceeding.' );
				}
				
				/*
				 * If so, proceed with the order.
				 */
				$this->save_order_meta_fields( $order_id, $transaction );
				$this->process_order_status( $order, $transaction->createPayment->status );
				
				/*
				 * Empty the cart.
				 */
				WC()->cart->empty_cart();
				
				/*
				 * Redirect to thanks page.
				 */
				
				return array(
					'result'   => 'success',
					'redirect' => $this->gateway->get_return_url( $order ),
				);
				
			} else {
				
				if ( 'yes' === $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'createPayment did not return for order ' . $order_id . '. Aborting.' );
				}
				
				wc_add_notice( __( 'Oops... Looks like we could not proceed with your payment. Could you check your infos and try again?', 'upnid-woocommerce' ), 'error' );
				
				return array(
					'result' => 'fail',
				);
			}
			
		}
		
	}
	
	/**
	 * Does a GraphQL request to the API URL and return its results.
	 *
	 * @param string $graphql The GraphQL query/mutation.
	 *
	 * @return array|object
	 */
	private function do_request( $graphql ) {
		/*
		* Setup GraphQL client.
		*/
		$client = new GraphQL\Client(
			$this->get_api_url(),
			[ 'Authorization' => 'Bearer ' . $this->gateway->api_key ]
		);
		
		$results = $client->runRawQuery( $graphql );
		
		return $results->getData();
	}
	
	/**
	 * Send email notification.
	 *
	 * @param string $subject Email subject.
	 * @param string $title Email title.
	 * @param string $message Email message.
	 */
	protected function send_email( $subject, $title, $message ) {
		$mailer = WC()->mailer();
		$mailer->send( get_option( 'admin_email' ), $subject, $mailer->wrap_message( $title, $message ) );
	}
	
	/**
	 * Webhook handler.
	 */
	public function webhook_handler() {
		//@ob_clean();
		
		// Takes raw data from the request
		$json_body = file_get_contents( 'php://input' );
		
		// Converts it into a PHP object
		$webhook_response = json_decode( $json_body );
		
		$_upnid_signature = isset( $_SERVER['HTTP_X-UPNID-SIGNATURE'] ) ? $_SERVER['HTTP_X-UPNID-SIGNATURE'] : null;
		
		if ( 'yes' === $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Webhook received from Upnid. ' . json_encode( $webhook_response ) );
		}
		
		if ( $webhook_response && $this->check_webhook_secret( $json_body, $_upnid_signature ) ) {
			header( 'HTTP/1.1 200 OK' );
			
			if ( 'yes' === $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'Webhook received from Upnid. Signature ' . $_upnid_signature . ' was validated. ' . json_encode( $webhook_response ) );
			}
			
			$this->process_successful_webhook( $webhook_response );
			
			// Deprecated action since 2.0.0.
			do_action( 'wc_upnid_valid_webhook_request', $webhook_response );
			
			exit;
		} else {
			
			if ( 'yes' === $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'Webhook received from Upnid. Signature ' . $_upnid_signature . ' is not valid!' );
			}
			
			wp_die( esc_html__( 'Upnid Request Failure', 'upnid-woocommerce' ), '', array( 'response' => 401 ) );
		}
	}
	
	/**
	 * Check if Upnid response is valid.
	 *
	 * @param string $json_body Webhook response data.
	 * @param string $signature Upnid signature header.
	 *
	 * @return bool
	 */
	public function check_webhook_secret( $json_body, $signature ) {
		
		$secret = get_option( $this->gateway->webhook_endpoint . '_webhook' );
		
		$_signature = hash_hmac( 'sha256', $json_body, $secret );
		
		if ( $signature === $_signature ) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Process successeful Webhook requests.
	 *
	 * @param stdClass|bool $posted Posted data.
	 */
	public function process_successful_webhook( $posted ) {
		global $wpdb;
		
		//$posted = wp_unslash( $posted );
		$order_id = absint( $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wc_upnid_transaction_id' AND meta_value = %d", intval( $posted->id ) ) ) );
		$order = wc_get_order( $order_id );
		$status = sanitize_text_field( $posted->status );
		
		if ( $order && $order->get_id() === $order_id ) {
			$this->process_order_status( $order, $status );
		}
		
	}
	
	/**
	 * Checks if we already have created webhooks on the Upnid account.
	 * Creates if we have not.
	 */
	public function webhook_setup() {
		
		if ( $this->gateway->api_key &&
		     ! get_option( $this->gateway->webhook_endpoint . '_webhook' ) &&
		     ! in_array( $_SERVER['REMOTE_ADDR'], array( '127.0.0.1', '::1' ) ) ) {
			
			$webhook_url = home_url( '/wc-api/' ) . $this->gateway->webhook_endpoint;
			$secret = bin2hex( openssl_random_pseudo_bytes( 32 ) );
			
			$graphql = <<<GRAPHQL
			    mutation {
				  createWebhook(input: {
				    url: "{$webhook_url}",
				    eventTypes: [
				      PAYMENT_PAID,
				      PAYMENT_DISPUTED,
				      PAYMENT_REFUNDED,
				    ]
				    secret: "{$secret}"
				  }) {
				    id
				  }
				}
GRAPHQL;
			
			/*
			 * Executes mutation and collect results.
			 */
			try {
				
				if ( 'yes' === $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'Trying to execute GraphQL mutation to create a webhook endpoint for ' . $webhook_url );
				}
				
				$request = $this->do_request( $graphql );
				
			} catch ( Exception $error ) {
				
				if ( 'yes' === $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'Error when executing GraphQL mutation to create a webhook endpoint for ' . $webhook_url . '. Error: ' . $error );
				}
				
				return;
			}
			
			if ( isset( $request->createWebhook->id ) ) {
				add_option( $this->gateway->webhook_endpoint . '_webhook', $secret );
			}
		}
	}
	
	/**
	 * Process the order status.
	 *
	 * @param WC_Order $order Order data.
	 * @param string $status Transaction status.
	 */
	public function process_order_status( $order, $status ) {
		if ( 'yes' === $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Payment status for order ' . $order->get_order_number() . ' is now: ' . $status );
		}
		
		switch ( $status ) {
			case 'AUTHORIZED' :
				if ( ! in_array( $order->get_status(), array( 'processing', 'completed' ), true ) ) {
					$order->update_status( 'on-hold', __( 'Upnid: The transaction was authorized.', 'upnid-woocommerce' ) );
				}
				
				break;
			case 'PENDING_REFUND':
				$order->update_status( 'on-hold', __( 'Upnid: A refund was requested for this transaction. Waiting for refund confirmation.', 'upnid-woocommerce' ) );
				
				break;
			case 'PROCESSING' :
				$order->update_status( 'on-hold', __( 'Upnid: The transaction is being processed.', 'upnid-woocommerce' ) );
				
				break;
			case 'PAID' :
				if ( ! in_array( $order->get_status(), array( 'processing', 'completed' ), true ) ) {
					$order->add_order_note( __( 'Upnid: Transaction paid.', 'upnid-woocommerce' ) );
				}
				
				// Changing the order for processing and reduces the stock.
				$order->payment_complete();
				
				break;
			case 'PENDING' :
				$order->update_status( 'on-hold', __( 'Upnid: The banking ticket was issued but not paid yet.', 'upnid-woocommerce' ) );
				
				break;
			case 'DECLINED' :
				$order->update_status( 'failed', __( 'Upnid: The transaction was rejected by the card company or by fraud.', 'upnid-woocommerce' ) );
				
				$transaction_id = get_post_meta( $order->get_id(), '_wc_upnid_transaction_id', true );
				$transaction_url = '<a href="' . $this->dashboard_url . intval( $transaction_id ) . '">' . $this->dashboard_url . intval( $transaction_id ) . '</a>';
				
				/* translators: %s transaction details url */
				$this->send_email(
					sprintf( esc_html__( 'The transaction for order %s was rejected by the card company or by fraud', 'upnid-woocommerce' ), $order->get_order_number() ),
					esc_html__( 'Transaction failed', 'upnid-woocommerce' ),
					sprintf( esc_html__( 'Order %1$s has been marked as failed, because the transaction was rejected by the card company or by fraud, for more details, see %2$s.', 'upnid-woocommerce' ), $order->get_order_number(), $transaction_url )
				);
				
				break;
			case 'DISPUTED' :
				$order->update_status( 'cancelled', __( 'Upnid: The transaction was disputed by the credit card company or by the customer.', 'upnid-woocommerce' ) );
				
				$transaction_id = get_post_meta( $order->get_id(), '_wc_upnid_transaction_id', true );
				$transaction_url = '<a href="' . $this->dashboard_url . intval( $transaction_id ) . '">' . $this->dashboard_url . intval( $transaction_id ) . '</a>';
				
				/* translators: %s transaction details url */
				$this->send_email(
					sprintf( esc_html__( 'The transaction for order %s was disputed', 'upnid-woocommerce' ), $order->get_order_number() ),
					esc_html__( 'Transaction disputed', 'upnid-woocommerce' ),
					sprintf( esc_html__( 'Order %1$s has been marked as disputed, because the transaction was disputed by the credit card company or by the customer, for more details, see %2$s.', 'upnid-woocommerce' ), $order->get_order_number(), $transaction_url )
				);
				
				break;
			case 'REFUNDED' :
				$order->update_status( 'refunded', __( 'Upnid: The transaction was refunded.', 'upnid-woocommerce' ) );
				
				$transaction_id = get_post_meta( $order->get_id(), '_wc_upnid_transaction_id', true );
				$transaction_url = '<a href="' . $this->dashboard_url . intval( $transaction_id ) . '">' . $this->dashboard_url . intval( $transaction_id ) . '</a>';
				
				$this->send_email(
					sprintf( esc_html__( 'The transaction for order %s refunded', 'upnid-woocommerce' ), $order->get_order_number() ),
					esc_html__( 'Transaction refunded', 'upnid-woocommerce' ),
					sprintf( esc_html__( 'Order %1$s has been marked as refunded by Upnid, for more details, see %2$s.', 'upnid-woocommerce' ), $order->get_order_number(), $transaction_url )
				);
				
				break;
			
			default :
				break;
		}
	}
	
	/**
	 * Sanitizes all data from an array.
	 *
	 * @param array $array The array to be sanitized.
	 *
	 * @return array
	 */
	private function sanitize_array_data( $array ) {
		if ( is_array( $array ) ) {
			foreach ( $array as $key => $value ) {
				$array[ $key ] = sanitize_text_field( $value );
			}
		}
		
		return $array;
	}
}
