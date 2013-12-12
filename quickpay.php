<?php
/*
Plugin Name: TheCartPress - Quickpay Payment Gateway
Plugin URI: http://perfect-solution.dk
Description: Integrate your Quickpay payment gateway with TheCartPress.
Version: 1.0
Author: PerfectSolution
Author URI: http://perfect-solution.dk
*/

if ( !defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'TCPSkeletonLoader' ) ) {

	require_once('classes/form-builder.php');

	class TheCartPress_Quickpay_Loader {
			public static $plugin_title = 'TheCartPress - Quickpay payment gateway';
			public static $plugin_description = 'Integrates Quickpay payment gateway with yout TheCartPress shop.';
	        /**
	         * Checks if TheCartPress is activated
	         *
	         * @since 1.0
	         */
	        static function init() {
	            if ( ! function_exists( 'is_plugin_active' ) ) {
	                    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	            }
	            if ( ! is_plugin_active( 'thecartpress/TheCartPress.class.php' ) ) {
	                    add_action( 'admin_notices', array( __CLASS__,        'admin_notices' ) );
	            }
	        }

	        /**
	         * Displays a message if TheCartPress is not activated 
	         *
	         * @since 1.0
	         */
	        static function admin_notices() {
	                echo '<div class="error"><p>', __( '<strong>' . self::$plugin .'</strong> requires TheCartPress plugin activated.', 'tcp-skeleton' ), '</p></div>';
	        }

	        /**
	         * Loads the plugin itself
	         *
	         * @since 1.0
	         */
	        static function tcp_init() {
				tcp_load_quickpay_plugin();
	        }
	}
	//WordPress hooks
	add_action( 'init'		, array( 'TheCartPress_Quickpay_Loader', 'init' ) );
	//TheCartPress hooks
	add_action( 'tcp_init'		, array( 'TheCartPress_Quickpay_Loader', 'tcp_init' ) );

	/**
	 * Loads the skeleton payment/shipping plugin
	 *
	 * @since 1.0
	 */
	function tcp_load_quickpay_plugin() {
	        
		class QuickpayForTheCartPress extends TCP_Plugin {
			const protocol = 7;

			function getFields() {
				$fields = array(
					'merchant_id' => array(
						'title' => __('Quickpay Merchant ID', 'tcp-quickpay'),
						'type' => 'text',
						'description' => __('Type in your merchant ID from Quickpay.', 'tcp-quickpay')
					),	
					'quickpay_md5secret' => array(
						'title' => __('Secret MD5 string', 'tcp-quickpay'),
						'type' => 'text',
						'description' => __('This is the unique MD5 secret key, which the system uses to verify your transactions.', 'tcp-quickpay')
					),
					'quickpay_apikey' => array(
						'title' => __('Quickpay API key', 'tcp-quickpay'),
						'type' => 'text',
						'description' => 'The API key is unique and can be requested from within the Quickpay Administrator Tool'
					),
					'quickpay_language' => array(
						'title' => __('Language', 'tcp-quickpay'),
						'description' => __('Payment Window Language', 'tcp-quickpay'),
						'type' => 'select',
						'options' => array(
							'da' => 'Danish',
							'de'=>'German', 
							'en'=>'English', 
							'fr'=>'French', 
							'it'=>'Italian', 
							'no'=>'Norwegian', 
							'nl'=>'Dutch', 
							'pl'=>'Polish', 
							'se'=>'Swedish'
						)
					),
					'quickpay_cardtypelock' => array(
						'title' => __( 'Cardtype lock', 'tcp-quickpay' ), 
						'type' => 'text', 
						'description' => __( 'Default: creditcard. Type in the cards you wish to accept (comma separated). See the valid payment types here: <b>http://quickpay.dk/features/cardtypelock/</b>', 'woothemes' ), 
						'default' => __( 'creditcard', 'tcp-quickpay' )					
					),	
					'quickpay_autocapture' => array(
						'title' => __( 'Allow autocapture', 'tcp-quickpay' ), 
						'type' => 'checkbox', 
						'label' => __( 'Enable/Disable', 'tcp-quickpay' ), 
						'description' => __( 'Automatically capture payments.' ), 
						'default' => 'no'
					),
					'quickpay_button_text' => array(
						'title' => __('Payment button text', 'tcp-quickpay'),
						'type' => 'text',
						'description' => __('The text shown on the button redirecting to the Quickpay payment window', 'tcp-quickpay'),
						'default' => __('Open Quickpay payment window', 'tcp-quickpay')
					)
				);

				return $fields;			
			}
			function getTitle() {
				return TheCartPress_Quickpay_Loader::$plugin_title;
			}

			function getDescription() {
				return TheCartPress_Quickpay_Loader::$plugin_description;
			}

			function showEditFields( $data ) {
				$fields = $this->getFields();

				foreach($fields as $id => $field) {
					if(method_exists('Quickpay_Form_Builder', $field['type'])) {
						call_user_func_array(array('Quickpay_Form_Builder', $field['type']), array($id, $field, $data));
					}
				}
			}

			function saveEditFields( $data ) {
				$fields = $this->getFields();
				foreach($fields as $id => $field) {	
					$data[$id] = isset($_REQUEST[$id]) ? $_REQUEST[$id] : '';
				}
				return $data;
			} 

			function getCheckoutMethodLabel( $instance, $shippingCountry, $shoppingCart = false) {
				$data = tcp_get_payment_plugin_data( 'QuickpayForTheCartPress', $instance );
				$title = isset( $data['title'] ) ? $data['title'] : $this->getTitle();
				return tcp_string( 'TheCartPress', 'pay_QuickpayForTheCartPress-title', $title ); //multilanguage
			}

			function getCost( $instance, $shippingCountry, $shoppingCart = false ) {			
				return 0;
			}

			function getNotice(  $instance, $shippingCountry, $shoppingCart, $order_id = 0 ) {
				$data = tcp_get_payment_plugin_data( get_class( $this ), $instance );
				return isset( $data['notice'] ) ? $data['notice'] : '';
			}

			function showPayForm( $instance, $shippingCountry, $shoppingCart, $order_id ) {
				$order_number = str_pad($order_id , 4, 0, STR_PAD_LEFT);
				$data = tcp_get_payment_plugin_data( get_class( $this ), $instance );
				$notice = $this->getNotice( $instance, $shippingCountry, $shoppingCart, $order_id );
				$msgtype = 'authorize';
				$notify_url = add_query_arg( 
					array(
						'action' => 'tcp_quickpay_ipn',
						'instance' => $instance
					), admin_url( 'admin-ajax.php' ) );

				$continue_url = add_query_arg( 'tcp_checkout', 'ok', tcp_get_the_checkout_url() );
				$cancel_url = add_query_arg( 'tcp_checkout', 'ko', tcp_get_the_checkout_url() );

				$currency = tcp_get_the_currency_iso();
				$amount = $this->format_price( Orders::getTotal( $order_id ) );
				$cardtypelock = $data['quickpay_cardtypelock'];
				$autocapture = isset($data['quickpay_autocapture']) && $data['quickpay_autocapture'] == 'yes' ? 1 : 0;

				$md5check = md5(
					self::protocol . $msgtype . $data['merchant_id'] . $data['quickpay_language'] . $order_number
					.$amount . $currency . $continue_url . $cancel_url . $notify_url . $autocapture
					.$cardtypelock . $data['quickpay_md5secret']
				);

				echo '
					<form id="quickpay_payment_form" action="https://secure.quickpay.dk/form/" method="post">
						<input type="hidden" name="protocol" value="'.self::protocol.'" />
						<input type="hidden" name="msgtype" value="'.$msgtype.'" />
						<input type="hidden" name="merchant" value="'.$data['merchant_id'].'" />
						<input type="hidden" name="language" value="'.$data['quickpay_language'].'" />
						<input type="hidden" name="ordernumber" value="'. $order_number .'" />
						<input type="hidden" name="amount" value="'.$amount.'" />
						<input type="hidden" name="currency" value="'.$currency.'" />
						<input type="hidden" name="continueurl" value="'.$continue_url.'" />
						<input type="hidden" name="cancelurl" value="'.$cancel_url.'" />
						<input type="hidden" name="callbackurl" value="'.$notify_url.'" />
						<input type="hidden" name="autocapture" value="'.$autocapture.'" />
						<input type="hidden" name="cardtypelock" value="'.$cardtypelock.'" />		
						<input type="hidden" name="md5check" value="'.$md5check.'" />
						<input type="submit" value="'.$data['quickpay_button_text'].'" />
					</form>
				';
				echo '<p>', $notice, '</p>';
				Orders::editStatus( $order_id, $data['new_status'] );
				require_once( TCP_CHECKOUT_FOLDER . 'ActiveCheckout.class.php' );
			}

			function tcp_quickpay_ipn() {
				if(isset($_GET['instance']) && isset($_POST['qpstat'])) {
					$response = (object) $_POST;
					$instance = $_GET['instance'];
					if(is_numeric($instance)) {
						$order_id = intval($response->ordernumber);
						// Retrieve gateway plugin data
						$data = (object) tcp_get_payment_plugin_data( 'QuickpayForTheCartPress', $instance );
						// Validate the response from Quickpay and set new order state
						$order_status = $this->validate_response($response, $data->quickpay_md5secret) ? Orders::$ORDER_PROCESSING : tcp_get_cancelled_order_status();
						// Transaction status
						$additional = 'Transaction status: ' . $response->qpstatmsg;
						// Update the order with new state, transaction id and transaction status message
						Orders::editStatus( $order_id, $order_status, $response->transaction, $additional );
						// Send order email
						ActiveCheckout::sendMails( $order_id, $additional );
					}
				}
			}

			function format_price( $price ) {
				return number_format($price * 100, 0, '', '');
			}

			static function response_md5($p, $secret) {
				if(is_object($p)) {
					$cardexpire = isset($p->cardexpire) ? $p->cardexpire : '';

					$md5 =  $p->msgtype.$p->ordernumber.$p->amount.$p->currency.$p->time.$p->state.$p->qpstat.$p->qpstatmsg.
						    $p->chstat.$p->chstatmsg.$p->merchant.$p->merchantemail.$p->transaction.$p->cardtype.$p->cardnumber.
						    $p->cardhash.$cardexpire.$p->acquirer.$p->splitpayment.$p->fraudprobability.$p->fraudremarks.$p->fraudreport.
						    $p->fee.$secret;
					
					return md5($md5);				
				}

				return FALSE;				
			}

			static function validate_response($response, $secret) {
				if(isset($response->ordernumber) AND isset($response->qpstat)) {
					if(self::response_md5($response, $secret) == $response->md5check AND $response->qpstat === '000') {
						return TRUE;
					}
					return FALSE;
				}	
				return FALSE;		
			}
		}

		if ( function_exists( 'tcp_register_payment_plugin' ) ) tcp_register_payment_plugin( 'QuickpayForTheCartPress' );

		$Quickpay_Instance = new QuickpayForTheCartPress();
		add_action( 'wp_ajax_tcp_quickpay_ipn'		, array( $Quickpay_Instance, 'tcp_quickpay_ipn' ) );
		add_action( 'wp_ajax_nopriv_tcp_quickpay_ipn'	, array( $Quickpay_Instance, 'tcp_quickpay_ipn' ) );
	}
}// class_exists check
?>