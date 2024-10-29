<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Ambpg_WC_Gateway_Barclay
 * @property string showLogo
 */
class Ambpg_WC_Gateway_Barclay extends WC_Payment_Gateway
{
	public const TEST_URL = 'https://mdepayments.epdq.co.uk/ncol/test/orderstandard.asp';
	public const LIVE_URL = 'https://payments.epdq.co.uk/ncol/prod/orderstandard.asp';
	public const BARCLAY_PAYMENT_SEPARATOR = ';';

	/**
	 * Whether or not logging is enabled
	 *
	 * @var bool
	 */
	public static $log_enabled = false;

	/**
	 * Logger instance
	 *
	 * @var WC_Logger
	 */
	public static $log = null;

	/**
	 * @var string
	 */
	protected $access_key;

	/**
	 * @var string
	 */
	private $status;

	/**
	 * @var string
	 */
	private $sha_in;

	/**
	 * @var string
	 */
	private $sha_out;

	/**
	 * @var int|string
	 */
	private $sha_method;

	/**
	 * @var bool
	 */
	private $debug;

	/**
	 * @var string
	 */
	private $error_notice;

	/**
	 * @var int
	 */
	private $cat_url;

	/**
	 * @var string
	 */
	private $aavscheck;

	/**
	 * @var string
	 */
	private $cvccheck;

	/**
	 * @var string
	 */
	private $payment_method;

	/**
	 * @var string
	 */
	private $brand_cards;

	/**
	 * @var string
	 */
	private $secure_3d;

	/**
	 * @var string
	 */
	private $method_list;

	/**
	 * @var string
	 */
	private $com_plus;

	/**
	 * @var string
	 */
	private $param_plus;

	/**
	 * @var string
	 */
	private $param_var;

	/**
	 * @var string
	 */
	private $api_user_id;

	/**
	 * @var string
	 */
	private $operation;

	/**
	 * @var string
	 */
	private $api_user_pswd;

	/**
	 * @var string
	 */
	private $notify_url;

	/**
	 * Ambpg_WC_Gateway_Barclay constructor.
	 */
	public function __construct()
	{
		$this->id = 'barclay';
		$this->has_fields = false;
		$this->order_button_text = __('Proceed to AM Barclay Gateway', 'woocommerce');
		$this->method_title = __('AM Barclay Gateway', 'woocommerce');
		$this->method_description = __(
			'AM Barclay Gateway redirects customers to Barclaycard to enter their payment information.',
			'woocommerce'
		);
		$this->supports = [
			'products',
			'refunds',
		];
		$this->icon = plugin_dir_url(__FILE__) . '../assets/ambpg_barclaycard_logo.png';

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		$this->title = $this->get_option('title');
		$this->title = ($this->title !== null && $this->title !== '') ? $this->title : __(
			'AM Barclay Gateway',
			'woocommerce'
		);
		$this->description = $this->get_option('description');
		$this->access_key = $this->get_option('access_key');
		$this->debug = 'yes' === $this->get_option('debug', 'no');
		self::$log_enabled = $this->debug;
		$this->showLogo = $this->get_option('show_logo');

		$this->status = $this->get_option('status');
		$this->error_notice = $this->get_option('error_notice');
		$this->sha_in = $this->get_option('sha_in');
		$this->sha_out = $this->get_option('sha_out');
		$this->sha_method = $this->get_option('sha_method');
		$this->sha_method = ($this->sha_method != '') ? $this->sha_method : 0;

		$this->cat_url = wc_get_page_id('shop');

		$this->aavscheck = $this->get_option('aavcheck');
		$this->cvccheck = $this->get_option('cvccheck');

		$this->payment_method = is_array($this->get_option('payment_method')) ? implode(
			self::BARCLAY_PAYMENT_SEPARATOR,
			$this->get_option('payment_method')
		) : '';
		$this->brand_cards = is_array($this->get_option('brand_cards')) ? implode(
			self::BARCLAY_PAYMENT_SEPARATOR,
			$this->get_option('brand_cards')
		) : '';
		$this->secure_3d = $this->get_option('secure_3d');
		$this->method_list = is_array($this->get_option('method_list')) ? join(
			self::BARCLAY_PAYMENT_SEPARATOR,
			$this->get_option('method_list')
		) : '';

		$this->com_plus = $this->get_option('com_plus');
		$this->param_plus = $this->get_option('param_plus');

		$this->param_var = $this->get_option('param_var');

		//Recommended for Direct Payments and Advanced Payments
		$this->operation = $this->get_option('operation');
		$this->api_user_id = $this->get_option('api_user_id');
		$this->api_user_pswd = $this->get_option('api_user_pswd');

		$this->notify_url = WC()->api_request_url('Ambpg_WC_Gateway_Barclay');

		$this->add_payment_hooks();
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields()
	{
		$this->form_fields = include __DIR__ . '/includes/settings-barclay.php';
	}

	/**
	 * Payment Hooks
	 */
	private function add_payment_hooks()
	{
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
		add_action('woocommerce_receipt_barclay', [$this, 'ambpg_barclay_receipt_page']);
		add_action('woocommerce_api_ambpg_wc_gateway_barclay', [$this, 'ambpg_barclay_check_barclay_response']);
		add_action('admin_notices', [$this, 'admin_notice']);
	}

	/**
	 * @param int $order_id
	 *
	 * @return array
	 */
	public function process_payment($order_id): array
	{
		global $woocommerce;
		$order = new WC_Order($order_id);


		return [
			'result' => 'success',
			'redirect' => $order->get_checkout_payment_url(true)
		];
	}
	public function admin_notice()
	{
		// Get the settings for the payment gateway
		$settings = get_option('wc_barclay_epdq_settings');

		// Check if the payment gateway is enabled
		if (!empty($settings['enabled']) && $settings['enabled'] === 'yes') {
			// Display the admin notice
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<?php esc_html_e('If the payment option is not showing on the checkout page, please ensure you are using the [woocommerce_checkout] shortcode on your checkout page.', 'woocommerce'); ?>
				</p>
			</div>
			<?php
		}
	}
	/**
	 * @param $order
	 */
	public function ambpg_barclay_receipt_page($order)
	{
		// Escaping static text
		echo '<p>' . esc_html__(
			'Thank you for your order, please click the button below to pay with AM Barclay Gateway.',
			'woocommerce'
		) . '</p>';

		// Generate the form
		$form = $this->ambpg_barclay_generate_barclay_form($order);

		// Define allowed HTML tags and attributes
		$allowed_tags = array(
			'form' => array(
				'action' => true,
				'method' => true,
				'id' => true
			),
			'input' => array(
				'type' => true,
				'name' => true,
				'value' => true,
				'class' => true,
				'id' => true
			),
			'button' => array(
				'type' => true,
				'name' => true,
				'href' => true,
				'class' => true,
				'id' => true
			),
			// Add other tags and attributes as needed
		);

		// Escape the form content with wp_kses()
		echo wp_kses($form, $allowed_tags);
	}


	/**
	 * @param $order
	 *
	 * @return string
	 */
	public function ambpg_barclay_generate_barclay_form($order)
	{
		$order = wc_get_order($order);
		$barclay_args = $this->get_barclay_fields($order);

		$shasign = '';
		$shasign_arg = [];

		ksort($barclay_args);

		foreach ($barclay_args as $key => $value) {
			if ($value == '') {
				continue;
			}
			$shasign_arg[] = $key . '=' . $value;
		}

		$shasign = hash($this->getShaMethod(), implode($this->sha_in, $shasign_arg) . $this->sha_in);

		$barclay_html_args = [];
		foreach ($barclay_args as $key => $value) {
			if ($value == '') {
				continue;
			}
			$barclay_html_args[] = '<input type="hidden" name="' . $key . '" value="' . $value . '"/>';
		}

		if (isset($this->status) && ($this->status == 'test' || $this->status == 'live')) {
			$url = $this->status == 'test' ? self::TEST_URL : self::LIVE_URL;

			return
				'<form action="' . esc_url($url) . '" method="post" id="epdq_payment_form">' .
				implode('', $barclay_html_args) .
				wp_nonce_field('ambpg_barclay_action', 'ambpg_barclay_nonce', true, false) . // Add nonce field
				'<input type="hidden" name="SHASIGN" value="' . esc_html($shasign) . '"/>' .
				'<input type="submit" class="button alt" id="submit_epdq_payment_form" value="' . esc_html__(
					'Pay via AM Barclay Gateway',
					'woocommerce'
				) . '" />' .
				'<button class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . esc_html__(
					'Cancel order &amp; restore cart',
					'woocommerce'
				) . '</button>' .
				'</form>';

		} else {
			return '<p class="error">' . $this->error_notice . '</p>';
		}
	}

	/**
	 * @param $order_id
	 *
	 * @return array
	 */
	public function get_barclay_fields($order_id)
	{
		$order = wc_get_order($order_id);

		$barclay_args = [
			'PSPID' => $this->access_key,
			'ORDERID' => $order->id,
			'AMOUNT' => $order->order_total * 100,
			'CURRENCY' => get_woocommerce_currency(),
			'LANGUAGE' => get_bloginfo('language'),
			'CN' => $order->billing_first_name . ' ' . $order->billing_last_name,
			'EMAIL' => $order->billing_email,
			'OWNERZIP' => $order->billing_postcode,
			'OWNERADDRESS' => $order->billing_address_1,
			'OWNERADDRESS2' => $order->billing_address_2,
			'OWNERCTY' => $order->billing_country,
			'OWNERTOWN' => $order->billing_city,
			'OWNERTELNO' => $order->billing_phone,

			'ACCEPTURL' => $this->notify_url,
			'DECLINEURL' => $this->notify_url,
			'EXCEPTIONURL' => $this->notify_url,
			'CANCELURL' => $this->notify_url,
			'CANCELURL' => esc_url_raw($order->get_cancel_order_url_raw()),
			'BACKURL' => esc_url_raw(home_url()),
			'HOMEURL' => esc_url_raw(home_url()),
			'CATALOGURL' => get_permalink($this->cat_url),

			//payment method
			'PM' => $this->payment_method,
			'BRAND' => $this->brand_cards,
			'WIN3DS' => $this->secure_3d,
			'PMLISTTYPE' => $this->method_list,

			//Redirection on payment result
			'COMPLUS' => $this->com_plus,
			'PARAMPLUS' => $this->param_plus,

			//POST payment url
			'PARAMVAR' => $this->param_var,

			//Recommended for direct payments
			'OPERATION' => $this->operation,
			'USERID' => $this->api_user_id,
			'PASWD' => $this->api_user_pswd,
		];

		return $barclay_args;
	}

	/**
	 * @return string
	 */
	private function getShaMethod()
	{
		switch ($this->sha_method) {
			case 1:
				$shaMethod = 'sha256';
				break;
			case 2:
				$shaMethod = 'sha512';
				break;
			default:
				$shaMethod = 'sha1';
		}

		return $shaMethod;
	}

	/**
	 * Show Barclay Payment Logo
	 */
	public function payment_fields()
	{
		if ($this->showLogo === 'yes') {
			echo '<img src="' . esc_url(plugin_dir_url(__FILE__) . '../assets/epdq.gif') . '"/>';
		}
		parent::payment_fields();
	}

	/**
	 * Check Barclay Response
	 * @return bool
	 * @throws WC_Data_Exception
	 */
	public function ambpg_barclay_check_barclay_response()
	{
		// Clean the output buffer
		ob_clean();
		header('HTTP/1.1 200 OK');

		// Verify the nonce
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			if (
				!isset($_POST['ambpg_barclay_nonce']) ||
				!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ambpg_barclay_nonce'])), 'ambpg_barclay_action')
			) {
				wp_die('Nonce verification failed.');
			}
		}

		// Check if $_REQUEST is an array and not empty
		if (!is_array($_REQUEST) || empty($_REQUEST)) {
			wp_die('Invalid request data.');
		}

		$dataCheck = [];
		$dataCheck1 = [];

		foreach ($_REQUEST as $key => $value) {
			// Sanitize and validate each key and value
			$sanitized_key = sanitize_text_field($key);
			$sanitized_value = sanitize_text_field($value);

			// Escape before storing or outputting
			$escaped_key = esc_html($sanitized_key);
			$escaped_value = esc_html($sanitized_value);

			// Skip empty values
			if ($escaped_value === "") {
				continue;
			}

			$dataCheck[$escaped_key] = $escaped_value;
			$dataCheck1[strtoupper($escaped_key)] = strtoupper($escaped_value);
		}

		// Verify the SHA signature
		$verify = $this->checkShaOut($dataCheck);
		if (empty($dataCheck['SHASIGN']) || !$verify) {
			wp_die('Transaction is unsuccessful!');
		}

		return $this->transaction_successfull($dataCheck1);
	}


	/**
	 * @param array $dataCheck
	 *
	 * @return bool
	 */
	protected function checkShaOut(array $dataCheck)
	{
		$__result = false;
		$shaout = $this->sha_out;

		$origsig = $dataCheck['SHASIGN'];

		unset($dataCheck['SHASIGN'], $dataCheck['wc-api']);

		uksort($dataCheck, 'strcasecmp');

		$shasig = null;
		foreach ($dataCheck as $key => $value) {
			$shasig .= trim(strtoupper($key)) . '=' . mb_convert_encoding(trim($value), 'ISO-8859-1', 'UTF-8') . $shaout;
		}

		$shasig = strtoupper(hash($this->getShaMethod(), $shasig));

		if ($shasig == $origsig) {
			$__result = true;
		}

		return $__result;
	}

	/**
	 * Handles a successful transaction.
	 * @param array $args
	 *
	 * @return bool
	 * @throws WC_Data_Exception
	 */
	public function transaction_successfull(array $args)
	{
		extract($args);
		$order = new WC_Order($ORDERID);
		$statusMsg = sprintf('<b>Status Code:</b> %s :: %s </br>', esc_html($STATUS), esc_html($this->get_barclay_status_code($STATUS)));
		$errorMSG = sprintf('<b>Error Code:</b> %s :: %s </br>', esc_html($NCERROR), esc_html($this->get_barclay_error_code($NCERROR)));

		$acceptedOrderStatus = ['4', '5', '9', '41', '51', '91']; // Using string values for accepted order statuses

		$orderNote = $this->checkOrderArgs($args, $order);
		$died = '<p>' . esc_html('Transaction result is uncertain.') . '</p>';
		$died .= '<p>' . esc_html('Your order is cancelled and your cart is emptied.');
		$died .= '</br>' . esc_html('Go to your') . ' <a href="' . esc_url(get_permalink(get_option('woocommerce_myaccount_page_id'))) . '">' . esc_html('account') . '</a>' . esc_html('to process your order again or ');
		$died .= esc_html('go to') . ' <a href="' . esc_url(home_url()) . '">' . esc_html('homepage') . '</a></p>';
		$orderNote .= $statusMsg . $errorMSG;

		// Check if $STATUS is in the acceptedOrderStatus array
		if (in_array($STATUS, $acceptedOrderStatus)) {
			switch ($STATUS) {
				case '4':
				case '5':
				case '9':
					$orderNote .= esc_html__('Barclay ePDQ transaction is confirmed.', 'alakmalak-barclay-epdq-payment-gateway');
					$order->update_status('completed', $orderNote); // Update status to 'completed'
					$order->payment_complete();
					break;
				case '41':
				case '51':
				case '91':
					$orderNote .= esc_html__('Barclay ePDQ transaction is awaiting confirmation.', 'alakmalak-barclay-epdq-payment-gateway');
					$order->update_status('on-hold', $orderNote);
					break;
				case '1':
				case '2':
				case '93':
				case '52':
				case '92':
					$orderNote .= esc_html__('Order has failed.', 'alakmalak-barclay-epdq-payment-gateway');
					$order->update_status('cancelled', $orderNote);
					break;
				default:
					$order->update_status('cancelled', $died);
					break;
			}
		} else {
			$order->update_status('cancelled', $died);
		}
		self::log($orderNote); // Log the orderNote instead of $died

		$order->add_order_note($orderNote);
		if (!$order->get_transaction_id() && isset($args['PAYID'])) {
			$order->set_transaction_id($args['PAYID']);
		}
		if (isset(WC()->cart)) {
			WC()->cart->empty_cart();
		}

		return wp_redirect($this->get_return_url($order));
	}

	/**
	 * @param int|string $code
	 *
	 * @return string
	 */
	public function get_barclay_status_code($code)
	{
		return AMBPG_Epdq_SuccessCodes::ambpg_getMessage($code);
	}

	/**
	 * @param int|string $code
	 *
	 * @return string
	 */
	public function get_barclay_error_code($code): ?string
	{
		return AMBPG_Epdq_ErrorCodes::ambpg_getErrorMessage($code);
	}

	/**
	 * @param array $args
	 * @param WC_Order $order
	 *
	 * @return string
	 */

	private function checkOrderArgs(array $args, WC_Order $order)
	{
		$orderNote = '';
		$orderMsg = [
			'ORDERID' => esc_html__('Order ID: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'AMOUNT' => esc_html__('AMOUNT: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'CURRENCY' => esc_html__('Order currency: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'PM' => esc_html__('Payment Method: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'ACCEPTANCE' => esc_html__('Acceptance code returned by acquirer: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'STATUS' => esc_html__('Transaction status : %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'CARDNO' => esc_html__('Masked card number : %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'PAYID' => esc_html__('Payment reference in EPDQ system: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'NCERROR' => esc_html__('Error Code: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'BRAND' => esc_html__('Card brand (EPDQ system derives this from the card number) : %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'ED' => esc_html__('Payer\'s card expiry date : %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'TRXDATE' => esc_html__('Transaction Date: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'CN' => esc_html__('Cardholder/customer name: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'IP' => esc_html__('Customer\'s IP: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'AAVADDRESS' => esc_html__('AAV result for the address: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'AAVCHECK' => esc_html__('Result of the automatic address verification: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'AAVZIP' => esc_html__('AAV result for the zip code: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'BIN' => esc_html__('First 6 digits of credit card number: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'CCCTY' => esc_html__('Country where the card was issued: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'COMPLUS' => esc_html__('Custom value passed: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'CVCCHECK' => esc_html__('Result of the card verification code check: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'ECI' => esc_html__('Electronic Commerce Indicator: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'FXAMOUNT' => esc_html__('FXAMOUNT: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'FXCURRENCY' => esc_html__('FXCURRENCY: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'IPCTY' => esc_html__('Originating country of the IP address: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'SUBBRAND' => esc_html__('SUBBRAND: %s', 'alakmalak-barclay-epdq-payment-gateway'),
			'VC' => esc_html__('VC: %s', 'alakmalak-barclay-epdq-payment-gateway'),
		];
		foreach ($orderMsg as $key => $msg) {
			if (isset($args[$key]) && !empty($args[$key])) {
				// Translators: %1$s is the message, %2$s is the dynamic content from $args array
				$orderNote .= sprintf(
					esc_html__('%1$s: %2$s', 'ambpg-woocommerce-barclay-epdq-payment'),
					esc_html($msg),
					esc_html($args[$key])
				) . '<br>';
				update_post_meta($order->get_id(), '_barclay_' . $key, $args[$key]);
			}
		}
		return $orderNote;
	}

	/**
	 * @param $message
	 * @param string $level
	 */
	public static function log($message, string $level = 'info')
	{
		if (self::$log_enabled) {
			if (self::$log === null) {
				self::$log = wc_get_logger();
			}
			self::$log->log($level, $message, ['source' => 'ambpg-barclay']);
		}
	}

	/**
	 * Get a link to the transaction on the 3rd party gateway site (if applicable).
	 *
	 * @param WC_Order $order the order object.
	 *
	 * @return string transaction URL, or empty string.
	 */
	public function get_transaction_url($order): string
	{
		if ('live' == $this->status) {
			$this->view_transaction_url = self::LIVE_URL;
		} else {
			$this->view_transaction_url = self::TEST_URL;
		}

		return parent::get_transaction_url($order);
	}

	/**
	 * @param string $key
	 * @param string $value
	 *
	 * @return string
	 */
	public function validate_text_field($key, $value)
	{
		$value = is_null($value) ? '' : $value;

		return wp_kses_post(trim(stripslashes($value)));
	}
}