<?php
/*
Plugin Name: TheCartPress - Quickpay Payment Gateway
Plugin URI: http://perfect-solution.dk
Description: Integrate your Quickpay payment gateway with TheCartPress.
Version: 2.0.0
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

			function getFields() {
				$fields = array(
					'quickpay_merchant_id' => array(
						'title' => __('Merchant ID', 'tcp-quickpay'),
						'type' => 'text',
						'description' => __('Your Payment Window agreement merchant id. Found in the "Integration" tab inside the Quickpay manager.', 'tcp-quickpay')
					),
                    'quickpay_agreement_id' => array(
						'title' => __('Agreement ID', 'tcp-quickpay'),
						'type' => 'text',
						'description' => __('Your Payment Window agreement id. Found in the "Integration" tab inside the Quickpay manager.', 'tcp-quickpay')
					),	
					'quickpay_privatekey' => array(
						'title' => __('Private key', 'tcp-quickpay'),
						'type' => 'text',
						'description' => __('Your Payment Window agreement private key. Found in the "Integration" tab inside the Quickpay manager.', 'tcp-quickpay')
					),
					'quickpay_agreement_apikey' => array(
						'title' => __('API key', 'tcp-quickpay'),
						'type' => 'text',
						'description' => 'Your Payment Window agreement API key. Found in the "Integration" tab inside the Quickpay manager.'
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
						'description' => __( 'Default: creditcard. Type in the cards you wish to accept (comma separated). See the valid payment types here: <b>http://quickpay.dk/features/cardtypelock/</b>', 'tcp-quickpay' ), 
						'default' => __( 'creditcard', 'tcp-quickpay' )					
					),	
					'quickpay_autocapture' => array(
						'title' => __( 'Allow autocapture', 'tcp-quickpay' ), 
						'type' => 'checkbox', 
						'label' => __( 'Enable/Disable', 'tcp-quickpay' ), 
						'description' => __( 'Automatically capture payments.', 'tcp-quickpay' ), 
						'default' => 'no'
					),
					'quickpay_button_text' => array(
						'title' => __('Payment button text', 'tcp-quickpay'),
						'type' => 'text',
						'description' => __('The text shown on the button redirecting to the Quickpay payment window', 'tcp-quickpay'),
						'default' => __('Open Quickpay payment window', 'tcp-quickpay')
					),
					'quickpay_autofee' => array(
						'title' => __( 'Enable autofee', 'tcp-quickpay' ), 
						'type' => 'checkbox', 
						'label' => __( 'Enable/Disable', 'tcp-quickpay' ), 
						'description' => __( 'If enabled, the fee charged by the acquirer will be calculated and added to the transaction amount.', 'tcp-quickpay' ), 
						'default' => 'no'
					),
					'quickpay_branding_id' => array(
						'title' => __( 'Branding ID', 'tcp-quickpay' ), 
						'type' => 'text', 
						'description' => __( 'Leave empty if you have no custom branding options', 'tcp-quickpay' )				
					),	
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
				$data = tcp_get_payment_plugin_data( get_class( $this ), $instance );
				$notice = $this->getNotice( $instance, $shippingCountry, $shoppingCart, $order_id );

                $params = array(
                    'agreement_id'      => $data['quickpay_agreement_id'],
                    'merchant_id'       => $data['quickpay_merchant_id'],
                    'subscription'      => 0,
                    'description'       => '',
                    'language'          => $data['quickpay_language'],
                    'order_id'          => str_pad($order_id , 4, 0, STR_PAD_LEFT),
                    'amount'            => $this->format_price( Orders::getTotal( $order_id ) ),
                    'currency'          => tcp_get_the_currency_iso(),
                    'continueurl'       => add_query_arg( 'tcp_checkout', 'ok', tcp_get_the_checkout_url() ),
                    'cancelurl'         => add_query_arg( 'tcp_checkout', 'ko', tcp_get_the_checkout_url() ),
                    'callbackurl'       => add_query_arg( array( 'action' => 'tcp_quickpay_ipn', 'instance' => $instance ), admin_url( 'admin-ajax.php' ) ),
                    'autocapture'       => isset($data['quickpay_autocapture']) && $data['quickpay_autocapture'] == 'yes' ? 1 : 0,
                    'autofee'           => isset($data['quickpay_autofee']) && $data['quickpay_autofee'] == 'yes' ? 1 : 0,
                    'payment_methods'   => $data['quickpay_cardtypelock'],
                    'branding_id'       => $data['quickpay_branding_id'],
                    'version'           => 'v10'
                );
                
                ksort( $params );

                $checksum = hash_hmac("sha256", implode( " ", $params ), $data['quickpay_agreement_apikey'] );   
                
				echo '
                    <form action="https://payment.quickpay.net/" method="post" id="quickpay-payment-form">
                        <input type="hidden" name="version" value="' . $params['version'] .'">
                        <input type="hidden" name="merchant_id" value="' . $params['merchant_id'] . '">
                        <input type="hidden" name="agreement_id" value="' . $params['agreement_id'] . '">
                        <input type="hidden" name="subscription" value="' . $params['subscription'] . '">
                        <input type="hidden" name="description" value="' . $params['description'] . '">
                        <input type="hidden" name="language" value="' . $params['language'] . '">
                        <input type="hidden" name="order_id" value="' . $params['order_id'] . '">
                        <input type="hidden" name="amount" value="' . $params['amount'] . '">
                        <input type="hidden" name="currency" value="' . $params['currency'] . '">
                        <input type="hidden" name="continueurl" value="' . $params['continueurl'] . '">
                        <input type="hidden" name="cancelurl" value="' . $params['cancelurl'] . '">
                        <input type="hidden" name="callbackurl" value="' . $params['callbackurl'] . '">
                        <input type="hidden" name="autocapture" value="' . $params['autocapture'] . '">
                        <input type="hidden" name="autofee" value="' . $params['autofee'] . '">
                        <input type="hidden" name="payment_methods" value="' . $params['payment_methods'] . '">
                        <input type="hidden" name="branding_id" value="' . $params['branding_id'] . '">
                        <input type="hidden" name="checksum" value="' . $checksum . '">
                        <input type="submit" value="' .$data['quickpay_button_text'].'" /><br>
                    </form> 
				';
				echo '<p>', $notice, '</p>';
				require_once( TCP_CHECKOUT_FOLDER . 'ActiveCheckout.class.php' );
			}

			function tcp_quickpay_ipn() {
                error_log('callback reached');
                $request_body = file_get_contents("php://input");
                $json = json_decode( $request_body );
                
				if(isset($_GET['instance']) && isset($json->order_id)) {
                    error_log('instance set and order id set');
					$instance = $_GET['instance'];
					if(is_numeric($instance)) {
						$order_id = intval($json->order_id);
						// Retrieve gateway plugin data
						$settings = (object) tcp_get_payment_plugin_data( 'QuickpayForTheCartPress', $instance );
						// Validate the response from Quickpay and set new order state
						$order_status = $this->validate_response($settings) ? $settings->new_status : tcp_get_cancelled_order_status();
                        
                        // Last operation
                        $operation = end( $json->operations );
                        
						// Transaction status
						$additional = 'Transaction status: ' . $operation->aq_status_msg;
                        
                        // Check if this is a test transaction
                        if( $json->test_mode == 'true' ) {
                            $additional .= "\n";
                            $additional .= 'NB: This is a test transaction!';
                        }
                        
						// Update the order with new state, transaction id and transaction status message
						Orders::editStatus( $order_id, $order_status, $json->id, $additional );
						// Send order email
						ActiveCheckout::sendMails( $order_id, $additional );
					}
				}
                error_log('--------------------');
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

			static function validate_response( $settings ) 
            {
                $request_body = file_get_contents("php://input");
             
                if( ! isset( $_SERVER["HTTP_QUICKPAY_CHECKSUM_SHA256"] ) ) 
                {
                    return FALSE;
                }

                return hash_hmac( 'sha256', $request_body, $settings->quickpay_privatekey ) == $_SERVER["HTTP_QUICKPAY_CHECKSUM_SHA256"];	
			}
		}

		if ( function_exists( 'tcp_register_payment_plugin' ) ) tcp_register_payment_plugin( 'QuickpayForTheCartPress' );

		$Quickpay_Instance = new QuickpayForTheCartPress();
		add_action( 'wp_ajax_tcp_quickpay_ipn'		, array( $Quickpay_Instance, 'tcp_quickpay_ipn' ) );
		add_action( 'wp_ajax_nopriv_tcp_quickpay_ipn'	, array( $Quickpay_Instance, 'tcp_quickpay_ipn' ) );
	}
}// class_exists check
?>