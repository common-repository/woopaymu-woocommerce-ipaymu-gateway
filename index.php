<?php
/*
Plugin Name: Woopaymu Woocommerce Ipaymu indonesia payment Gateway
Plugin URI: http://woocommerce.com
Description: Accept payments through iPaymu for your woocommerce Store, a payment gateway for Indonesia.
Version: 1.1.0
Author: alfredo edo
Author URI: http://juraganscript.blogspot.com/

	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
	
*/


add_action('plugins_loaded', 'woocommerce_gateway_name_init', 0);
 
function woocommerce_gateway_name_init() {
 
	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
 
	/**
 	 * Localisation
	 */
	load_plugin_textdomain('wc-gateway-name', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
    
	/**
 	 * Gateway class
 	 */
	class WC_Gateway_Name extends WC_Payment_Gateway {
		 public function __construct(){
				$this->id			= 'ipaymu';
				$this->icon 		= WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . '/assets/ipaymu_badge.png';
				$this->has_fields 	= false;
					
				// Load the form fields
				$this->init_form_fields();
				
				// Load the settings.
				$this->init_settings();
	
				// Get setting values
				$this->enabled 		= $this->settings['enabled'];
				$this->title 		= "Ipaymu Payment";
				$this->description	= $this->settings['description'];
				$this->apikey		= $this->settings['apikey'];
				$this->password		= $this->settings['password'];
				$this->processor_id = $this->settings['processor_id'];
				$this->salemethod	= $this->settings['salemethod'];
				$this->gatewayurl	= $this->settings['gatewayurl'];
				$this->order_prefix = $this->settings['order_prefix'];
				$this->debugon		= $this->settings['debugon'];
				$this->debugrecip	= $this->settings['debugrecip'];
				$this->cvv			= $this->settings['cvv'];
	
				// Hooks
				add_action('init', array(&$this, 'check_payu_response'));
				  if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
							add_action( 'woocommerce_update_options_payment_gateways_' . $this->id,
										array( &$this, 'process_admin_options' ) 
									   );
							add_action('woocommerce_receipt_'.$this->id, array(&$this, 'receipt_page'));
							add_action('admin_notices_'.$this->id, array(&$this,'ipaymu_ssl_check'));
			//				add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
							add_action('woocommerce_thankyou_'.$this->id, array(&$this, 'thankyou_page'));
						 } else {
							add_action( 'woocommerce_update_options_payment_gateways', 
										array( &$this, 'process_admin_options' ) 
										);
											add_action('woocommerce_receipt_ipaymu', array(&$this, 'receipt_page'));
							add_action('admin_notices', array(&$this,'ipaymu_ssl_check'));
			//				add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
							add_action('woocommerce_thankyou_ipaymu', array(&$this, 'thankyou_page'));

						}
		 }
		
		/**
	 	* Check if SSL is enabled and notify the user
	 	**/
		function ipaymu_ssl_check() {
		     
		     if (get_option('woocommerce_force_ssl_checkout')=='no' && $this->enabled=='yes') :
		     
		     	echo '<div class="error"><p>'.sprintf(__('iPaymu is enabled and the <a href="%s">force SSL option</a> is disabled; your checkout is not secure! Please enable SSL and ensure your server has a valid SSL certificate.', 'woothemes'), admin_url('admin.php?page=settings')).'</p></div>';
		     
		     endif;
		}	
		/**
	     * Initialize Gateway Settings Form Fields
	     */
	    function init_form_fields() {
	    
	    	$this->form_fields = array(
				'enabled' => array(
								'title' => __( 'Enable/Disable', 'woothemes' ), 
								'label' => __( 'Enable iPaymu', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => '', 
								'default' => 'no'
							), 
				'title' => array(
								'title' => __( 'Title', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' ), 
								'default' => __( 'Pembayaran iPaymu)', 'woothemes' )
							), 
				'description' => array(
								'title' => __( 'Description', 'woothemes' ), 
								'type' => 'textarea', 
								'description' => __( 'This controls the description which the user sees during checkout.', 'woothemes' ), 
								'default' => 'Sistem pembayaran menggunakan iPaymu.'
							),  
				'apikey' => array(
								'title' => __( 'API Key', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Daftar Ipaymu & Dapatkan API Key <a href=https://my.ipaymu.com/?rid=rekeningbersama target=_blank>di sini</a></small>.', 'woothemes' ), 
								'default' => ''
							),
				'debugrecip' => array(
								'title' => __( 'Debugging Email', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Who should receive the debugging emails.', 'woothemes' ), 
								'default' =>  get_option('admin_email')
							),
				);
	    }
		
		
		/**
		 * Admin Panel Options 
		 * - Options for bits like 'title' and availability on a country-by-country basis
		 **/
		public function admin_options() {
			?>
			<h3><?php _e('iPaymu','woothemes'); ?></h3>	    	
	    	<p><?php _e( 'iPaymu Gateway built from modified Woo\'s Inspire Commerce Gateway by <br /><br />iPaymu works by adding credit card fields on the checkout page, and then sending the details to iPaymu for verification.', 'woothemes' ); ?></p>
	    	<table class="form-table">
	    		<?php $this->generate_settings_html(); ?>
			</table><!--/.form-table-->    	
	    	<?php
	    }
	    	    
	    function thankyou_page($order_id) {
		
			global $woocommerce;

			$order = &new WC_Order( $order_id );
			$order->payment_complete();
		}
	    /**
		 * Payment fields for iPaymu.
		 **/
	    function payment_fields() {
			?>
			<?php if ($this->description) : ?><p><?php echo $this->description; ?></p><?php endif; 
	    }
	
	
		public function generate_ipaymu_form( $order_id ) { 
		global $woocommerce;

		$order = &new WC_Order( $order_id );
		$url = 'https://my.ipaymu.com/payment.htm';
		
		//$order->payment_complete();
		
		// Cart Contents
		$item_loop = 0;
		if (sizeof($order->items)>0) : foreach ($order->items as $item) :
			if ($item['qty']) :
				
				$item_loop++;
				
				$ipaymu_args['item_name_'.$item_loop] = $item['name'];
				$ipaymu_args['quantity_'.$item_loop] = $item['qty'];
				$ipaymu_args['amount_'.$item_loop] = $item['cost'];
				
			endif;
		endforeach; endif;
		
		// Shipping Cost
		$item_loop++;
		$ipaymu_args['item_name_'.$item_loop] = __('Shipping cost', 'woothemes');
		$ipaymu_args['quantity_'.$item_loop] = '1';
		$ipaymu_args['amount_'.$item_loop] = number_format($order->order_shipping, 2);
		
		$ipaymu_args_array = array();

		foreach ($ipaymu_args as $key => $value) {
			$ipaymu_args_array[] = '<input type="hidden" name="'.$key.'" value="'.esc_attr( $value ).'" />';
		}
		
		// Prepare Parameters
		$params = array(
					'key'      => ''.$this->apikey.'', // API Key Merchant / Penjual
					'action'   => 'payment',
					'product'  => 'Order : #'.$order_id.'',
					'price'    => ''.$order->order_total.'', // Total Harga
					'quantity' => 1,
					'comments' => 'Transaksi Pembelian', // Optional           
					'ureturn'  => add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id')))),
					'unotify'  => 'http://ldomain.com/notify.php',
					'ucancel'  => 'http://domain.com/cancel.php',
					'format'   => 'json' // Format: xml / json. Default: xml 
				);
		
		$params_string = http_build_query($params);
		
		//open connection
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POST, count($params));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		
		//execute post
		$request = curl_exec($ch);
		
		$result = json_decode($request, true);
		var_dump($result, $params,$url);
		
		if( isset($result['url']) )
			header('location: '. $result['url']);
		else {
			$error_code = $result['Status'];
			$error_desc = $result['Keterangan'];

		}
				
		//close connection
		curl_close($ch);

}
	
		/**
		 * Process the payment and return the result
		 **/
	
		function process_payment( $order_id ) {
			global $woocommerce;

			$order = &new WC_Order( $order_id );
			

	
	
			// ************************************************ 
			// Retreive response
	
	
				//if ($response['response'] == 1) {
					// Successful payment
	
					//$order->add_order_note( __('iPaymu payment completed', 'woocommerce') . ' (Transaction ID: ' . $response['transaction_id'] . ')' );
					
					//$order->payment_complete();
					//$woocommerce->cart->empty_cart();

					// Empty awaiting payment session
					//unset($_SESSION['order_awaiting_payment']);

						
					// Return thank you redirect
					return array(
						'result' 	=> 'success',
						'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_pay_page_id'))))
					);
	
				
	
		}
		

		/**
		Validate payment form fields
		**/
		
		public function validate_fields() {
			global $woocommerce;
			
			return true;
		}


		/**
		 * receipt_page
		 **/
		function receipt_page( $order ) {
			global $woocommerce;
			echo '<p>'.__('Thank you for your order.', 'woocommerce').'</p>';
			echo $this->generate_ipaymu_form( $order );
			
		}
		
		/**
		 * Get post data if set
		 **/
		private function get_post($name) {
			if(isset($_POST[$name])) {
				return $_POST[$name];
			}
			return NULL;
		}
		
		/**
		 * Successful Payment!
		 **/
		function successful_request( $posted ) {
			
			// Custom holds post ID
			$accepted_types = array('cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money');
		
				
				$order = new WC_Order( (int) $posted['product'] );
		
				// Sandbox fix
				$posted['payment_status'] = 'completed';
							
				if ($order->status !== 'completed') :
					// We are here so lets check status and do actions
					switch (strtolower($posted['payment_status'])) :
						case 'completed' :
							// Payment completed
							$order->add_order_note( __('IPN payment completed', 'woothemes') );
							$order->payment_complete();
						break;
						default:
							// No action
						break;
					endswitch;
				endif;
				
				exit;
				
			}
			

		/**
		 * Send debugging email
		 **/
		function send_debugging_email( $debug ) {
			
			if ($this->debugon!='yes') return; // Debug must be enabled
			if (!$this->debugrecip) return; // Recipient needed
			
			// Send the email
			wp_mail( $this->debugrecip, __('iPaymu Debug', 'woothemes'), $debug );
			
		} 

	}
		
	/**
 	* Add the Gateway to WooCommerce
 	**/
	function add_ipaymu_gateway( $methods ) {
		$methods[] = 'WC_Gateway_Name'; return $methods;
	}
	
	add_filter('woocommerce_payment_gateways', 'add_ipaymu_gateway' );
} 
