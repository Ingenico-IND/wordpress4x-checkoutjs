<?php
/*
Plugin Name: Worldline
Plugin URI: 
Description: Worldline ePayments is India's leading digital payment solutions company. Being a company with more than 45 years of global payment experience, we are present in India for over 20 years and are powering over 550,000 businesses with our tailored payment solution.
Version: 1.0 
Author: Wordldline
Author URI: https://www.worldline.com
Copyright: 
License: 
License URI: 
*/
if (!defined('ABSPATH'))
	exit;
include_once(dirname(__FILE__) . '/database_table.php');
register_activation_hook(__FILE__, 'database_install1');

include_once(dirname(__FILE__) . '/offline.php');
include_once(dirname(__FILE__) . '/reconcilation.php');

add_action('plugins_loaded', 'woocommerce_paynimo_init', 0);
add_action('rest_api_init', 'my_S2S_route');


function woocommerce_paynimo_init()
{
	if (!class_exists('WC_Payment_Gateway')) return;
	class WC_worldline extends WC_Payment_Gateway
	{

		public function __construct()
		{
			global $woocommerce;
			$this->id           = 'worldline';
			$this->method_title = __('Worldline', 'worldline');
			$this->method_description = __("Worldline ePayments is India's leading digital payment solutions company. Being a company with more than 45 years of global payment experience, we are present in India for over 20 years and are powering over 550,000 businesses with our tailored payment solution.", 'worldline');
			$this->icon         =  plugins_url('images/worldline-mint-checkout.png', __FILE__);
			$this->has_fields   = true;
			$this->supports = array(
				'products',
				'refunds',
			);
			$this->init_form_fields();
			$this->init_settings();
			$this->currency_type  = get_woocommerce_currency();
			$this->title = (isset($this->settings['title'])) ? $this->settings['title'] : null;
			$this->description  = (isset($this->settings['description'])) ? $this->settings['description'] : null;
			$this->worldline_merchant_code      = (isset($this->settings['worldline_merchant_code'])) ? $this->settings['worldline_merchant_code'] : null;
			$this->worldline_SALT      = (isset($this->settings['worldline_SALT'])) ? $this->settings['worldline_SALT'] : null;
			$this->webservice_locator  = (isset($this->settings['webservice_locator'])) ? $this->settings['webservice_locator'] : null;
			$this->worldline_merchant_scheme_code   = (isset($this->settings['worldline_merchant_scheme_code'])) ? $this->settings['worldline_merchant_scheme_code'] : null;
			$this->worldline_decline_msg      = (isset($this->settings['worldline_decline_msg'])) ? $this->settings['worldline_decline_msg'] : null;
			$this->worldline_success_msg      = (isset($this->settings['worldline_success_msg'])) ? $this->settings['worldline_success_msg'] : null;
			$this->merchant_logo_url      = (isset($this->settings['merchant_logo_url'])) ? $this->settings['merchant_logo_url'] : null;
			$this->PRIMARY_COLOR_CODE      = (isset($this->settings['PRIMARY_COLOR_CODE'])) ? $this->settings['PRIMARY_COLOR_CODE'] : null;
			$this->SECONDARY_COLOR_CODE    = (isset($this->settings['SECONDARY_COLOR_CODE'])) ? $this->settings['SECONDARY_COLOR_CODE'] : null;
			$this->BUTTON_COLOR_CODE_1      = (isset($this->settings['BUTTON_COLOR_CODE_1'])) ? $this->settings['BUTTON_COLOR_CODE_1'] : null;
			$this->BUTTON_COLOR_CODE_2      = (isset($this->settings['BUTTON_COLOR_CODE_2'])) ? $this->settings['BUTTON_COLOR_CODE_2'] : null;
			//----------------------------
			$this->worldline_payment_mode      = (isset($this->settings['worldline_payment_mode'])) ? $this->settings['worldline_payment_mode'] : null;
			$this->enableNewWindowFlow      = (isset($this->settings['enableNewWindowFlow'])) ? $this->settings['enableNewWindowFlow'] : null;
			$this->enableExpressPay      	= (isset($this->settings['enableExpressPay'])) ? $this->settings['enableExpressPay'] : null;
			$this->separateCardMode      	= (isset($this->settings['separateCardMode'])) ? $this->settings['separateCardMode'] : null;
			$this->enableMerTxnDetails     	= (isset($this->settings['enableMerTxnDetails'])) ? $this->settings['enableMerTxnDetails'] : null;
			$this->payment_mode_order      	= (isset($this->settings['payment_mode_order'])) ? $this->settings['payment_mode_order'] : null;
			$this->checkoutElement      	= (isset($this->settings['checkoutElement'])) ? $this->settings['checkoutElement'] : null;
			$this->handle_response_on_popup   = (isset($this->settings['handle_response_on_popup'])) ? $this->settings['handle_response_on_popup'] : null;
			$this->enableInstrumentDeRegistration = (isset($this->settings['enableInstrumentDeRegistration'])) ? $this->settings['enableInstrumentDeRegistration'] : null;
			$this->txnType   			= (isset($this->settings['txnType'])) ? $this->settings['txnType'] : null;
			$this->hideSavedInstruments  = (isset($this->settings['hideSavedInstruments'])) ? $this->settings['hideSavedInstruments'] : null;
			$this->saveInstrument      	= (isset($this->settings['saveInstrument'])) ? $this->settings['saveInstrument'] : null;
			$this->merchantMsg      	= (isset($this->settings['merchantMsg'])) ? $this->settings['merchantMsg'] : null;
			$this->disclaimerMsg      	= (isset($this->settings['disclaimerMsg'])) ? $this->settings['disclaimerMsg'] : null;

			if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
				add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
			} else {
				add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
			}
			$this->notify_url = add_query_arg('wc-api', 'WC_worldline', home_url('/'));
			$this->msg['message'] = "";
			$this->msg['class']   = "";

			add_action('woocommerce_api_wc_worldline', array($this, 'check_paynimo_response'));
			add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_merchantid_backend'), 10, 1);
			add_action('woocommerce_receipt_worldline', array($this, 'receipt_page'));
			add_action('init', 'register_session');
			if (isset($_POST['worldline_cus_cancel'])) {
				wc_add_notice("Payment cancelled", "error");
			}
		}


		function register_session()
		{
			if (!session_id())
				session_start();
		}

		public function get_errors()
		{
			return $this->errors;
		}
		/**
		 * Display admin error messages.
		 */
		public function display_errors()
		{
			if ($this->get_errors()) {
				echo '<div id="woocommerce_errors" class="error notice is-dismissible">';
				foreach ($this->get_errors() as $error) {
					echo '<p>' . wp_kses_post($error) . '</p>';
				}
				echo '</div>';
			}
		}

		/**
		 * Generates the order form
		 **/
		function generateOrderForm()
		{
			$wc_co_url = wc_get_checkout_url();
			return <<<EOT
				<form name='worldlinecancelform' action="$wc_co_url" method="POST">
				<input type="hidden" name="worldline_cus_cancel" value="1">
				</form>
<p id="msg-success" class="woocommerce-info woocommerce-message">
Please wait while we are processing your payment.
</p>
<p>
    <button id="btn-worldline">Pay Now</button>
    <button id="btn-worldline-cancel" onclick="document.worldlinecancelform.submit()">Cancel</button>
</p>
EOT;
		}

		function init_form_fields()
		{
			$this->form_fields = array(
				'enabled' => array(
					'title' => __('Enable/Disable', 'worldline'),
					'type' => 'checkbox',
					'label' => __('Enable Worldline Payment Module.', 'worldline'),
					'default' => 'no'
				),
				'title' => array(
					'title' => __('<span style="color: #a00;">* </span>Title:', 'worldline'),
					'type' => 'text',
					'id' => "title",
					'desc_tip'    => true,
					'placeholder' => __('Worldline', 'woocommerce'),
					'description' => __('Your desired title name will be show during checkout proccess.', 'worldline'),
					'default' => __('Cards / UPI / Netbanking / Wallets', 'worldline')
				),
				'description' => array(
					'title' => __('<span style="color: #a00;">* </span>Description:', 'worldline'),
					'type' => 'textarea',
					'desc_tip'    => true,
					'placeholder' => __('Description', 'woocommerce'),
					'description' => __('Worldline Payment Gateway', 'worldline'),
					'default' => __('Worldline Payment Gateway', 'worldline')
				),
				'worldline_merchant_code' => array(
					'title' => __('<span style="color: #a00;">* </span>Merchant Code', 'worldline'),
					'type' => 'text',
					'desc_tip'    => true,
					'placeholder' => __('Merchant Code', 'woocommerce'),
					'description' => __('Merchant Code')
				),
				'worldline_SALT' => array(
					'title' => __('<span style="color: #a00;">* </span>SALT', 'worldline'),
					'type' => 'text',
					'desc_tip'    => true,
					'placeholder' => __('SALT', 'woocommerce'),
					'description' => __('SALT')
				),
				'webservice_locator' => array(
					'title'       => __('<span style="color: #a00;">* </span>Payment Type', 'woocommerce'),
					'type'        => 'select',
					'class'    => 'chosen_select',
					'css'      => 'min-width:350px;',
					'description' => __('For TEST mode amount will be charge 1', 'woocommerce'),
					'default'     => 'Test',
					'desc_tip'    => false,
					'options'     => array(
						'Test'          => __('TEST', 'woocommerce'),
						'Live'          => __('LIVE', 'woocommerce'),
					)
				),
				'worldline_merchant_scheme_code' => array(
					'title' => __('<span style="color: #a00;">* </span>Merchant Scheme Code', 'worldline'),
					'type' => 'text',
					'desc_tip'    => true,
					'placeholder' => __('Merchant Scheme Code', 'woocommerce'),
					'description' => __('Merchant Scheme Code')
				),
				'worldline_success_msg' => array(
					'title' => __('<span style="color: #a00;">* </span>Success Message', 'worldline'),
					'type' => 'textarea',
					'desc_tip'    => true,
					'default' => 'Thank you for shopping with us. Your account has been charged and your transaction is successful.',
					'description' => __('Success Message')
				),
				'worldline_decline_msg' => array(
					'title' => __('<span style="color: #a00;">* </span>Decline Message', 'worldline'),
					'type' => 'textarea',
					'desc_tip'    => true,
					'default' => 'Thank you for shopping with us. However, the transaction has been declined.',
					'description' => __('Decline Message')
				),
				'merchant_logo_url' => array(
					'title' => __('<span style="color: #a00;">* </span>Merchant Logo URL', 'worldline'),
					'type' => 'text',
					'desc_tip'    => false,
					'placeholder' => __('Merchant logo URL', 'woocommerce'),
					'default' => 'https://www.paynimo.com/CompanyDocs/company-logo-md.png',
					'description' => __('An absolute URL pointing to a logo image of merchant which will show on checkout popup')
				),
				'PRIMARY_COLOR_CODE' => array(
					'title' => __('<span style="color: #a00;">* </span>Primary Color Code', 'worldline'),
					'type' => 'text',
					'desc_tip'    => false,
					'default' => '#3977b7',
					'description' => __('Color value can be hex, rgb or actual color name')
				),
				'SECONDARY_COLOR_CODE' => array(
					'title' => __('<span style="color: #a00;">* </span>Secondary Color Code', 'worldline'),
					'type' => 'text',
					'desc_tip'    => false,
					'default' => '#FFFFFF',
					'description' => __('Color value can be hex, rgb or actual color name')
				),
				'BUTTON_COLOR_CODE_1' => array(
					'title' => __('<span style="color: #a00;">* </span>Button Color Code 1', 'worldline'),
					'type' => 'text',
					'desc_tip'    => false,
					'default' => '#1969bb',
					'description' => __('Color value can be hex, rgb or actual color name')
				),
				'BUTTON_COLOR_CODE_2' => array(
					'title' => __('<span style="color: #a00;">* </span>Button Color Code 2', 'worldline'),
					'type' => 'text',
					'desc_tip'    => false,
					'default' => '#FFFFFF',
					'description' => __('Color value can be hex, rgb or actual color name')
				),
				'worldline_payment_mode' => array(
					'title'       => __('<span style="color: #a00;">* </span>Payment Mode', 'woocommerce'),
					'type'        => 'select',
					'class'    => 'chosen_select',
					'css'      => 'min-width:350px;',
					'description' => __('If Bank selection is at worldline ePayments India Pvt. Ltd. end then select all, if bank selection at Merchant end then pass appropriate mode respective to selected option', 'woocommerce'),
					'default'     => 'all',
					'desc_tip'    => false,
					'options'     => array(
						'all'          => __('all', 'woocommerce'),
						'cards'          => __('cards', 'woocommerce'),
						'netBanking'      => __('netBanking', 'woocommerce'),
						'UPI'          => __('UPI', 'woocommerce'),
						'imps'          => __('imps', 'woocommerce'),
						'wallets'          => __('wallets', 'woocommerce'),
						'cashCards'          => __('cashCards', 'woocommerce'),
						'NEFTRTGS'          => __('NEFTRTGS', 'woocommerce'),
						'emiBanks'          => __('emiBanks', 'woocommerce'),
					)
				),
				'enableNewWindowFlow' => array(
					'title'       => __('<span style="color: #a00;">* </span>Enable new window flow', 'woocommerce'),
					'type'        => 'select',
					'class'    => 'chosen_select',
					'css'      => 'min-width:350px;',
					'description' => __('If this feature is enabled, then bank page will open in new window', 'woocommerce'),
					'default'     => 1,
					'desc_tip'    => false,
					'options'     => array(
						0          => __('Disable', 'woocommerce'),
						1         => __('Enable', 'woocommerce'),
					)
				),
				'enableExpressPay' => array(
					'title'       => __('<span style="color: #a00;">* </span>Enable Express Pay', 'woocommerce'),
					'type'        => 'select',
					'class'    => 'chosen_select',
					'css'      => 'min-width:350px;',
					'description' => __('To enable saved payments set its value to Enable', 'woocommerce'),
					'default'     => 1,
					'desc_tip'    => false,
					'options'     => array(
						0          => __('Disable', 'woocommerce'),
						1         => __('Enable', 'woocommerce'),
					)
				),
				'separateCardMode' => array(
					'title'       => __('<span style="color: #a00;">* </span>Separate Card Mode', 'woocommerce'),
					'type'        => 'select',
					'class'    => 'chosen_select',
					'css'      => 'min-width:350px;',
					'description' => __('If this feature is enabled checkout shows two separate payment mode(Credit Card and Debit Card)', 'woocommerce'),
					'default'     => 1,
					'desc_tip'    => false,
					'options'     => array(
						0          => __('Disable', 'woocommerce'),
						1         => __('Enable', 'woocommerce'),
					)
				),
				'merchantMsg' => array(
					'title' => __('<span style="color: #a00;">* </span>Merchant Message', 'worldline'),
					'type' => 'text',
					'desc_tip'    => false,
					'placeholder' => __('Merchant Message', 'woocommerce'),
					'description' => __('Customize message from merchant which will be shown to customer in checkout page')
				),

				'disclaimerMsg' => array(
					'title' => __('<span style="color: #a00;">* </span>Disclaimer Message', 'worldline'),
					'type' => 'text',
					'desc_tip'    => false,
					'placeholder' => __('Disclaimer Message', 'woocommerce'),
					'description' => __('Customize disclaimer message from merchant which will be shown to customer in checkout page')
				),

				'enableMerTxnDetails' => array(
					'title'       => __('<span style="color: #a00;">* </span>Merchant Transaction Details', 'woocommerce'),
					'type'        => 'select',
					'class'    => 'chosen_select',
					'css'      => 'min-width:350px;',
					'description' => __('Merchant Transaction Details', 'woocommerce'),
					'default'     => 1,
					'desc_tip'    => false,
					'options'     => array(
						0          => __('Disable', 'woocommerce'),
						1         => __('Enable', 'woocommerce'),
					)
				),
				'enableInstrumentDeRegistration' => array(
					'title'       => __('<span style="color: #a00;">* </span>Enable InstrumentDeRegistration', 'woocommerce'),
					'type'        => 'select',
					'class'    => 'chosen_select',
					'css'      => 'min-width:350px;',
					'description' => __('If this feature is enabled, you will have an option to delete saved cards', 'woocommerce'),
					'default'     => 0,
					'desc_tip'    => false,
					'options'     => array(
						0          => __('Disable', 'woocommerce'),
						1         => __('Enable', 'woocommerce'),
					)
				),
				'hideSavedInstruments' => array(
					'title'       => __('<span style="color: #a00;">* </span>Hide Saved Instruments', 'woocommerce'),
					'type'        => 'select',
					'class'    => 'chosen_select',
					'css'      => 'min-width:350px;',
					'description' => __('If enabled checkout hides saved payment options even in case of enableExpressPay is enabled.', 'woocommerce'),
					'default'     => 0,
					'desc_tip'    => false,
					'options'     => array(
						0          => __('Disable', 'woocommerce'),
						1         => __('Enable', 'woocommerce'),
					)
				),
				'saveInstrument' => array(
					'title'       => __('<span style="color: #a00;">* </span>Save Instrument', 'woocommerce'),
					'type'        => 'select',
					'class'    => 'chosen_select',
					'css'      => 'min-width:350px;',
					'description' => __('Enable this feature to vault instrument', 'woocommerce'),
					'default'     => 0,
					'desc_tip'    => false,
					'options'     => array(
						0          => __('Disable', 'woocommerce'),
						1         => __('Enable', 'woocommerce'),
					)
				),
				'txnType' => array(
					'title'       => __('<span style="color: #a00;">* </span>Transaction Type', 'woocommerce'),
					'type'        => 'select',
					'class'    => 'chosen_select',
					'css'      => 'min-width:350px;',
					'description' => __('Transaction Type', 'woocommerce'),
					'default'     => 'SALE',
					'desc_tip'    => true,
					'options'     => array(
						'SALE'    => __('SALE', 'woocommerce'),
					)
				),
				'payment_mode_order' => array(
					'title' => __('<span style="color: #a00;">* </span>Payment Mode Order', 'worldline'),
					'type' => 'textarea',
					'desc_tip'    => false,
					'description' => __("Place order in this format: \r\n\r\n cards,netBanking,imps,wallets,cashCards,UPI,MVISA,debitPin,NEFTRTGS,emiBanks")
				),

				'handle_response_on_popup' => array(
					'title'       => __('<span style="color: #a00;">* </span>Display Transaction Message on Popup', 'woocommerce'),
					'type'        => 'select',
					'class'    => 'chosen_select',
					'css'      => 'min-width:350px;',
					'description' => __('Handle Response on popup', 'woocommerce'),
					'default'     => 'no',
					'desc_tip'    => true,
					'options'     => array(
						'no'   => __('Disable', 'woocommerce'),
						'yes'    => __('Enable', 'woocommerce'),
					)
				),
				'checkoutElement' => array(
					'title'       => __('<span style="color: #a00;">* </span>Embed Payment Gateway On Page', 'woocommerce'),
					'type'        => 'select',
					'class'    => 'chosen_select',
					'css'      => 'min-width:350px;',
					'description' => __('Embed Payment Gateway On Page', 'woocommerce'),
					'default'     => '',
					'desc_tip'    => true,
					'options'     => array(
						''          					=> __('Disable', 'woocommerce'),
						'#worldline_payment_form'         => __('Enable', 'woocommerce'),
					)
				),
			);
		}

		public function admin_options()
		{
			echo '<h3>' . __('Worldline Payment Gateway', 'worldline') . '</h3>';
?>
			<br>
			<a href="#" target="_blank"><img style="width: 410px;" src="<?php echo $this->icon = plugins_url('images/worldline-mint.png', __FILE__); ?>" /></a>
		<?php
			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';
		}

		function payment_fields()
		{
			if ($this->description) echo wpautop(wptexturize($this->description));
		}

		function receipt_page($order)
		{
			echo $this->generate_paynimo_form($order);
		}

		function process_payment($order_id)
		{
			$order = new WC_Order($order_id);
			return array('result' => 'success', 'redirect' => add_query_arg('order-pay', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay')))));
		}

		function display_merchantid_backend($order)
		{
			global $wpdb;
			$txnid = $order->get_transaction_id();
			if ($order->get_payment_method() == 'worldline') {
				$table_name = $wpdb->prefix . 'worldlinedetails';
				$query = $wpdb->get_results("SELECT merchantid FROM " . $table_name . " WHERE orderid =" . $order->get_id());
				$result = $query[0]->merchantid;
				echo "<p><strong>Merchant Ref No: </strong><br>" . $result . "</p>";
				if ($txnid) {
					echo "<p><strong>TPSL Transaction ID: </strong><br>" . $txnid . "</p>";
				} else {
					null;
				}
			} else {
				null;
			}
		}

		function process_refund($order_id, $amount = null, $reason = '')
		{
			$order = wc_get_order($order_id);

			$transaction_id = $order->get_transaction_id();
			$order_date = $order->get_date_created()->format('d-m-Y');
			$currency = $order->get_currency();
			$merchant_code = $this->worldline_merchant_code;

			if ($this->webservice_locator == 'Test') {
				$Amount = '1.00';
			} else {
				$Amount = $amount;
			}
			$request_array = array(
				"merchant" => array("identifier" => $merchant_code),
				"cart" => (object) null,
				"transaction" => array(
					"deviceIdentifier" => "S",
					"amount" => $Amount,
					"currency" => $currency,
					"dateTime" => $order_date,
					"token" => $transaction_id,
					"requestType" => "R"
				)
			);

			$refund_data = json_encode($request_array);
			$refund_url = "https://www.paynimo.com/api/paynimoV2.req";
			$options = array(
				'http' => array(
					'method'  => 'POST',
					'content' => $refund_data,
					'header' =>  "Content-Type: application/json\r\n" .
						"Accept: application/json\r\n"
				)
			);
			$context = stream_context_create($options);
			$response_array = json_decode(file_get_contents($refund_url, false, $context));
			$status_code = $response_array->paymentMethod->paymentTransaction->statusCode;
			$status_message = $response_array->paymentMethod->paymentTransaction->statusMessage;
			$error_message = $response_array->paymentMethod->paymentTransaction->errorMessage;
			if ($status_code == '0400') {
				$order->add_order_note($status_message, $is_customer_note = 1, $added_by_user = false);
				return true;
			} else if ($status_code == '0399') {
				return new WP_Error('error', 'Refund not Applicable');
			} else {
				return new WP_Error('error', $status_message);
			}
		}

		function S_call($identifier, $currency, $transaction_id)
		{
			$request_array = array(
				"merchant" => array("identifier" => $identifier),
				"transaction" => array(
					"deviceIdentifier" => "S",
					"currency" => $currency,
					"dateTime" => date("Y-m-d"),
					"token" => $transaction_id,
					"requestType" => "S"
				)
			);
			$Scall_data = json_encode($request_array);
			$Scall_url = "https://www.paynimo.com/api/paynimoV2.req";
			$options = array(
				'http' => array(
					'method'  => 'POST',
					'content' => json_encode($request_array),
					'header' =>  "Content-Type: application/json\r\n" .
						"Accept: application/json\r\n"
				)
			);
			$context  = stream_context_create($options);
			$response_array = json_decode(file_get_contents($Scall_url, false, $context));
			$status_code = $response_array->paymentMethod->paymentTransaction->statusCode;
			if ($status_code) {
				return $status_code;
			} else {
				return 'Failed';
			}
		}

		function create_response_logs($str)
		{
			$dir_path = plugin_dir_path(__FILE__);
			$directoryname = 'logs';
			$dir_name = $dir_path . 'logs/';
			$file_name = 'worldline_logs' . date("Y-m-d") . '.log';
			if (!file_exists($file_name)) {
				$myfile = fopen($dir_name . $file_name, "a");
				$txt =  "\r\n" . "worldline Response:" . $str;
				$write_file = fwrite($myfile, $txt);
			}
		}

		function create_request_logs($str)
		{
			$dir_path = plugin_dir_path(__FILE__);
			$directoryname = 'logs';
			$dir_name = $dir_path . 'logs/';
			$file_name = 'worldline_logs' . date("Y-m-d") . '.log';
			if (!file_exists($file_name)) {
				$myfile = fopen($dir_name . $file_name, "a");
				$txt =  "\r\n" . "worldline Request:" . $str;
				$write_file = fwrite($myfile, $txt);
			}
		}

		function getErrorStatusMessage($code)
		{
			$messages = [
				"0300" => "Successful Transaction",
				"0392" => "Transaction cancelled by user either in Bank Page or in PG Card /PG Bank selection",
				"0396" => "Transaction response not received from Bank, Status Check on same Day",
				"0397" => "Transaction Response not received from Bank. Status Check on next Day",
				"0399" => "Failed response received from bank",
				"0400" => "Refund Initiated Successfully",
				"0401" => "Refund in Progress (Currently not in used)",
				"0402" => "Instant Refund Initiated Successfully(Currently not in used)",
				"0499" => "Refund initiation failed",
				"9999" => "Transaction not found :Transaction not found in PG"
			];
			if (in_array($code, array_keys($messages))) {
				return $messages[$code];
			}
			return null;
		}
		function check_paynimo_response()
		{
			global $woocommerce;
			$msg['class']   = 'error';
			$msg['message'] = $this->worldline_decline_msg;
			$identifier = $this->worldline_merchant_code;
			$currency = get_woocommerce_currency();
			$transactionCancelInProccess = false;
			if ($_POST) {
				$response = $_POST;
				if (is_array($response)) {
					$str = $response['msg'];
					$logs = $this->create_response_logs($str);
				}

				$response1 = explode('|', $str);
				$status = $response1[0];
				if ($status != '') {

					$merchantTxnRefNumber = $response1[3];
					$response_message = $response1[1];
					$response_message2 = $response1[2];
					if (!$response_message2) {
						$response_message2 = "Transaction Failed";
					}

					$transaction_id = $response1[5];
					//fetch statuscode form response
					$error_status_msg = $this->getErrorStatusMessage($status);
					//fetch orderid from response
					$status2 = $response1[7];
					$response_cart = explode('orderid:', $status2);
					$oid_1 = $response_cart[1];
					$oid_2 = explode('}', $oid_1);
					$order_id = $oid_2[0];
					if (!$order_id) {
						$order_id = $woocommerce->session->order_id;
					}
					$transauthorised = false;

					global $wpdb;
					$table_name = $wpdb->prefix . 'worldlinedetails';
					$query = $wpdb->query("UPDATE $table_name SET merchantid = $merchantTxnRefNumber WHERE orderid=$order_id");
					// dual verification of the hash string
					$hashstring = array_pop($response1);
					$array_without_hash = $response1;
					$string_without_hash = implode("|", $array_without_hash);
					$salt_token = $string_without_hash . '|' . $this->worldline_SALT;
					$hashed_string_token = hash('sha512', $salt_token);

					if ($order_id != '') {
						try {
							$order = new WC_Order($order_id);
							$set_transaction_id = $order->set_transaction_id($transaction_id);
							$saved  = $order->save();
							if ($order->status !== 'completed') {
								if ($status == '300') {
									if ($hashed_string_token == $hashstring) {
										if ($this->S_call($identifier, $currency, $transaction_id) == '300') {
											$transauthorised = true;
											$msg['message'] = $this->worldline_success_msg . "<br>" . 'Transaction Status: ' . $error_status_msg;;
											$msg['class'] = 'success';

											if ($order->status != 'processing') {
												$order->payment_complete();
											}
											$woocommerce->cart->empty_cart();
										}
									}
								} else {
									$msg['class'] = 'error';
									if ($hashed_string_token != $hashstring) {
										$transactionCancelInProccess = true;
										$msg['message'] = $this->worldline_decline_msg . "<br>" . 'Transaction Error Message from Payment Gateway: Hash Validation Failed';
									} else {
										$transactionCancelInProccess = true;
										$msg['message'] = $this->worldline_decline_msg . "<br>" . 'Transaction Status: ' . $error_status_msg;
									}
									//300 status code error
									$order->update_status('cancelled');
									$woocommerce->cart->empty_cart();
								}
							}
						} catch (Exception $e) {
							$msg['class'] = 'error';
							$msg['message'] = $this->worldline_decline_msg . "<br>" . 'Transaction Status: ' . $error_status_msg;
							$woocommerce->cart->empty_cart();
						}
					}
				} else {
					$transactionCancelInProccess = true;
					$msg['class'] = 'error';
					$msg['message'] = $this->worldline_decline_msg . "<br>" . 'Error Message: Empty Response from Payment Gateway';
					$woocommerce->cart->empty_cart();
					$order_id = $woocommerce->session->order_id;
					$order = new WC_Order($order_id);
					$order->update_status('cancelled');
				}
			}

			if (function_exists('wc_add_notice')) {
				wc_add_notice($msg['message'], $msg['class']);
			} else {
				if ($msg['class'] == 'success') {
					$woocommerce->add_message($msg['message']);
				} else {
					$woocommerce->add_error($msg['message']);
				}
				$woocommerce->set_messages();
			}

			if ($transactionCancelInProccess) {
				$redirect_url = get_permalink(woocommerce_get_page_id('myaccount'));
				wp_redirect($redirect_url);
				exit;
			} else {
				$redirect_url = $order->get_checkout_order_received_url();
				wp_redirect($redirect_url);
				exit;
			}
		}

		public function generate_paynimo_form($order_id)
		{
			global $woocommerce;
			$order = new WC_Order($order_id);
			$woocommerce->session->order_id = $order_id;
			$order_id = $order_id . '_' . date("ymds");
			$order_id1 =	$woocommerce->session->order_id;
			$cur_date = date("d-m-Y");
			// checkoutjs
			if ($this->webservice_locator == 'Test') {
				$amount = '1.00';
			} else {
				$amount = $order->get_total();
			}
			$customerMobNumber = $order->get_billing_phone();
			if (strpos($customerMobNumber, '+') !== false) {
				$customerMobNumber = str_replace("+", "", $customerMobNumber);
			}
			$merchantTxnRefNumber = rand(1, 1000000);
			global $wpdb;
			$table_name = $wpdb->prefix . 'worldlinedetails';
			$wpdb->insert(
				$table_name,
				array(
					'orderid' => $order_id,
					'merchantid' => $merchantTxnRefNumber,
				)
			);
			$cusid = $order->get_customer_id();
			if (!$cusid) {
				$cusid_raw = rand(1, 1000000);
			} else {
				$cusid_raw = $cusid;
			}
			$setcustid = $order->set_customer_id($cusid_raw);
			$currency_symbol = $this->currency_type;
			$customerName = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
			$data = array();
			$data['Amount'] = $amount;
			$data['mrctCode'] = $this->worldline_merchant_code;
			$data['merchantTxnRefNumber'] = $merchantTxnRefNumber;
			$data['CustomerId'] = 'cons' . $cusid_raw;
			$data['customerMobNumber'] = $customerMobNumber;
			$data['email'] = $order->get_billing_email();
			$data['SALT'] = $this->worldline_SALT;
			if ($this->handle_response_on_popup == 'yes' &&  (int)$this->enableNewWindowFlow == 1) {
				$data['returnUrl'] = '';
			} else if ($this->handle_response_on_popup == 'no' && (int)$this->enableNewWindowFlow == 1) {
				$data['returnUrl'] = $this->notify_url;
			} else {
				$data['returnUrl'] = $this->notify_url;
			}
			//---------------------------Features-------------------------
			$data['scheme'] = $this->worldline_merchant_scheme_code;
			$data['currency'] = $currency_symbol;
			$data['orderId'] = $order_id1;
			$data['CustomerName'] = $customerName;
			$data['worldline_payment_mode'] = $this->worldline_payment_mode;
			$data['enableNewWindowFlow'] = (int)$this->enableNewWindowFlow;
			$data['enableExpressPay'] = (int)$this->enableExpressPay;
			if ($data['enableExpressPay'] == 1) {
				$data['enableInstrumentDeRegistration'] = (int)$this->enableInstrumentDeRegistration;
				$data['hideSavedInstruments'] = (int)$this->hideSavedInstruments;
			} else {
				$data['hideSavedInstruments'] = 0;
				$data['enableInstrumentDeRegistration'] = 0;
			}

			$data['separateCardMode'] = (int)$this->separateCardMode;
			$data['enableMerTxnDetails'] = (int)$this->enableMerTxnDetails;
			$data['saveInstrument'] = (int)$this->saveInstrument;
			$data['checkout_url'] = wc_get_checkout_url();
			$data['txnType'] = $this->txnType;
			$data['merchantMsg'] = $this->merchantMsg;
			$data['disclaimerMsg'] = $this->disclaimerMsg;

			$data['checkoutElement'] = $this->checkoutElement;
			$payment_order_mode_raw =  $this->payment_mode_order;
			$payment_order_mode =  explode(",", $payment_order_mode_raw);
			if(isset($payment_order_mode)){
				$payment_order_mode = $payment_order_mode;
			} else {
				$payment_order_mode = '';
			}


			if (!$payment_order_mode_raw) {				
				$data['paymentModeOrder'] = '';
			} else {				
				$data['paymentModeOrder'] = $payment_order_mode;
			}

			if ($this->merchant_logo_url && @getimagesize($this->merchant_logo_url)) {
				$merchant_logo_url = $this->merchant_logo_url;
			} else {
				$merchant_logo_url = 'https://www.paynimo.com/CompanyDocs/company-logo-md.png';
			}
			$data['merchantLogoUrl'] = $merchant_logo_url;

			if ($this->PRIMARY_COLOR_CODE) {
				$PRIMARY_COLOR_CODE = $this->PRIMARY_COLOR_CODE;
			} else {
				$PRIMARY_COLOR_CODE = '#3977b7';
			}
			$data['PRIMARY_COLOR_CODE'] = $PRIMARY_COLOR_CODE;

			if ($this->SECONDARY_COLOR_CODE) {
				$SECONDARY_COLOR_CODE = $this->SECONDARY_COLOR_CODE;
			} else {
				$SECONDARY_COLOR_CODE = '#FFFFFF';
			}
			$data['SECONDARY_COLOR_CODE'] = $SECONDARY_COLOR_CODE;

			if ($this->BUTTON_COLOR_CODE_1) {
				$BUTTON_COLOR_CODE_1 = $this->BUTTON_COLOR_CODE_1;
			} else {
				$BUTTON_COLOR_CODE_1 = '#1969bb';
			}
			$data['BUTTON_COLOR_CODE_1'] = $BUTTON_COLOR_CODE_1;

			if ($this->BUTTON_COLOR_CODE_2) {
				$BUTTON_COLOR_CODE_2 = $this->BUTTON_COLOR_CODE_2;
			} else {
				$BUTTON_COLOR_CODE_2 = '#FFFFFF';
			}
			$data['BUTTON_COLOR_CODE_2'] = $BUTTON_COLOR_CODE_2;

			$datastring = $data['mrctCode'] . "|" . $data['merchantTxnRefNumber'] . "|" . $data['Amount'] . "|" . "|" . $data['CustomerId'] . "|" . $data['customerMobNumber'] . "|" . $data['email'] . "||||||||||" . $data['SALT'];
			$hashed = hash('sha512', $datastring);
			$data['token'] = $hashed;
			$checkout_url = wc_get_checkout_url();
			$logs = $this->create_request_logs($datastring);
			echo $this->generateOrderForm();
		?>
			<div id="worldline_payment_form">
			</div>
			<form action="<?php echo $this->notify_url ?>" id="response-form" method="POST">
				<input type="hidden" name="msg" value="" id="response-string">
			</form>
			<script src="https://www.paynimo.com/paynimocheckout/client/lib/jquery.min.js" type="text/javascript"></script>
			<script type="text/javascript" src="https://www.paynimo.com/Paynimocheckout/server/lib/checkout.js"></script>
			<script type="text/javascript">
				$(document).ready(function() {
					$('#btn-worldline').trigger('click');
				});

				$('#btn-worldline').click(function() {
					var data = <?php echo json_encode($data); ?>;
					console.log(data);
					var configJson = {
						'tarCall': false,
						'features': {
							'showPGResponseMsg': true,
							'enableNewWindowFlow': data['enableNewWindowFlow'], //for hybrid applications please disable this by passing false
							'enableExpressPay': data['enableExpressPay'],
							'enableMerTxnDetails': data['enableMerTxnDetails'],
							'enableAbortResponse': false,
							'enableInstrumentDeRegistration': data['enableInstrumentDeRegistration'],
							'hideSavedInstruments': data['hideSavedInstruments'],
							'separateCardMode': data['separateCardMode'],
						},
						'consumerData': {
							'deviceId': 'WEBSH2',
							'token': data['token'],
							'responseHandler': handleResponse,
							'returnUrl': data['returnUrl'],
							'paymentMode': data['worldline_payment_mode'],
							'checkoutElement': data['checkoutElement'],
							'paymentModeOrder': data['paymentModeOrder'],
							'merchantLogoUrl': data['merchantLogoUrl'],
							'merchantId': data['mrctCode'],
							'merchantMsg': data['merchantMsg'],
							'disclaimerMsg': data['disclaimerMsg'],
							'currency': data['currency'],
							'consumerId': data['CustomerId'],
							'consumerMobileNo': data['customerMobNumber'],
							'consumerEmailId': data['email'],
							'txnId': data['merchantTxnRefNumber'],
							'txnType': data['txnType'],
							'saveInstrument': data['saveInstrument'],
							'items': [{
								'itemId': data['scheme'],
								'amount': data['Amount'],
								'comAmt': '0'
							}],
							'cartDescription': '}{custname:' + data['CustomerName'] + '}{orderid:' + data['orderId'],
							'merRefDetails': [{
								"name": "Txn. Ref. ID",
								"value": data['merchantTxnRefNumber']
							}],
							'customStyle': {
								'PRIMARY_COLOR_CODE': data['PRIMARY_COLOR_CODE'],
								'SECONDARY_COLOR_CODE': data['SECONDARY_COLOR_CODE'],
								'BUTTON_COLOR_CODE_1': data['BUTTON_COLOR_CODE_1'],
								'BUTTON_COLOR_CODE_2': data['BUTTON_COLOR_CODE_2']
							},
						}
					};

					$.pnCheckout(configJson);
					if (configJson.features.enableNewWindowFlow) {
						pnCheckoutShared.openNewWindow();
					}
					$(".checkout-detail-box-inner .popup-close,.confirmBox .errBtnCancel").on("click", function() {
						// window.location = data['checkout_url'];
					});

					function handleResponse(res) {
						if (typeof res != 'undefined' && typeof res.paymentMethod != 'undefined' && typeof res.paymentMethod.paymentTransaction != 'undefined' && typeof res.paymentMethod.paymentTransaction.statusCode != 'undefined' && res.paymentMethod.paymentTransaction.statusCode == '0300') {
							let stringResponse = res.stringResponse;
							console.log(stringResponse);
							$("#response-string").val(stringResponse);
							$("#response-form").submit();
						} else {

						}
					};
				});
			</script>

			</body>

			</html>
<?php
		}
	}

	// Add the Gateway to WooCommerce
	function woocommerce_add_paynimo_gateway($methods)
	{
		$methods[] = 'WC_worldline';
		return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'woocommerce_add_paynimo_gateway');
}

function my_S2S_route()
{
	register_rest_route(
		'worldline',
		'/s2sverification',
		array(
			'methods' => 'POST',
			'callback' => 'callback_S2S',
			'permission_callback' => '__return_true',
		)
	);
}


function callback_S2S()
{
	global $woocommerce;
	$wc_class = new WC_worldline();

	$response = $_GET;
	if (!$response) {
		return 'No msg parameter in params';
		exit;
	}
	if (!$response['msg']) {
		return 'Empty Response Received';
		exit;
	}
	if (is_array($response)) {
		$str = $response['msg'];
	}
	$response1 = explode('|', $str);
	$response_message = $response1[1];
	$response_message2 = $response1[2];
	$merchantTxnRefNumber = $response1[3];
	$transaction_id = $response1[5];
	$status = $response1[0]; //fetch statuscode form response
	//fetch orderid from response
	$status2 = $response1[7];
	$response_cart = explode('orderid:', $status2);
	$oid_1 = $response_cart[1];
	$oid_2 = explode('}', $oid_1);

	$order_id = $oid_2[0];
	if (!$order_id) {
		$order_id = $woocommerce->session->order_id;
	}
	$transauthorised = false;

	global $wpdb;
	$table_name = $wpdb->prefix . 'worldlinedetails';
	$query = $wpdb->query("UPDATE $table_name SET merchantid = $merchantTxnRefNumber WHERE orderid=$order_id");
	// dual verification of the hash string
	$hashstring = array_pop($response1);
	$array_without_hash = $response1;
	$string_without_hash = implode("|", $array_without_hash);
	$salt_token = $string_without_hash . '|' . $wc_class->worldline_SALT;
	$hashed_string_token = hash('sha512', $salt_token);
	$dir_path = plugin_dir_path(__FILE__);
	$directoryname = 'logs';
	$dir_name = $dir_path . 'logs/';
	if ($order_id != '') {
		$order = new WC_Order($order_id);
		$set_transaction_id = $order->set_transaction_id($transaction_id);
		if ($order->status !== 'completed') {
			if ($status == '300') {
				if ($hashstring == $hashed_string_token) {
					$file_name = 'worldline_logs' . date("Y-m-d") . '.log';
					if (!file_exists($file_name)) {
						$myfile = fopen($dir_name . $file_name, "a");
						$txt =  "\r\n" . "Response_S2S:" . $str;
						$write_file = fwrite($myfile, $txt);
					}
					$order->update_status('processing');
					$return_string = $merchantTxnRefNumber . "|" . $transaction_id . "|1";
					return $return_string;
				} else {
					return 'Hash Validation Failed';
				}
			} else {
				$file_name = 'worldline_logs' . date("Y-m-d") . '.log';
				if (!file_exists($file_name)) {
					$myfile = fopen($dir_name . $file_name, "a");
					$txt = "\r\n" . "Response_S2S:" . $str;
					$write_file = fwrite($myfile, $txt);
				}
				$order->update_status('cancelled');
				$return_string = $merchantTxnRefNumber . "|" . $transaction_id . "|0";
				return $return_string;
			}
		}
	}
}
