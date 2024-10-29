<?php

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('ambpg_generate_random_string')) {
	function ambpg_generate_random_string($length = 10)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$salt = random_bytes($length);

		return hash_pbkdf2("sha256", $characters, $salt, 20000);
	}
}

if (!function_exists('ambpg_generate_new_hash')) {
	function ambpg_generate_new_hash()
	{
		return hash('sha512', ambpg_generate_random_string(5));
	}
}

/**
 * Settings for Barclay Payment Gateway
 */
return apply_filters(
	'wc_barclay_epdq_settings',
	[
		'enabled' => [
			'title' => esc_html__('Enable/Disable', 'woocommerce'),
			'type' => 'checkbox',
			'label' => esc_html__('Enable AM Barclay Checkout', 'woocommerce'),
			'default' => 'no',
		],
		'debug' => [
			'title' => esc_html__('Debug log', 'woocommerce'),
			'type' => 'checkbox',
			'label' => esc_html__('Enable logging', 'woocommerce'),
			'default' => 'no',
			'description' => sprintf(
				// Translators: %s is the path to the log file for AM Barclay events.
				esc_html__(
					'Log AM Barclay events, such as IPN requests, inside %s Note: this may log personal information. We recommend using this for debugging purposes only and deleting the logs when finished.',
					'woocommerce'
				),
				'<code>' . WC_Log_Handler_File::get_log_file_path('ambpg-barclay') . '</code>'
			),
		],
		'title' => [
			'title' => esc_html__('Title', 'woocommerce'),
			'type' => 'text',
			'description' => esc_html__(
				'Title of the payment process. This name will be visible throughout the site and the payment page.',
				'woocommerce'
			),
			'default' => 'AM Barclay Checkout',
			'desc_tip' => true,
		],
		'description' => [
			'title' => esc_html__('Description', 'woocommerce'),
			'type' => 'textarea',
			'description' => esc_html__(
				'The payment procedure is described in detail. This description will be visible throughout the site, as well as on the payment page.',
				'woocommerce'
			),
			'default' => 'Use Barclay Bank\'s payment platform and pay with your debit or credit card.',
			'desc_tip' => true,
		],
		'access_key' => [
			'title' => esc_html__('PSPID', 'woocommerce'),
			'type' => 'text',
			'description' => esc_html__(
				'Your Barclay account\'s PSPID. This is the id you use to access the Barclay Bank admin panel.',
				'woocommerce'
			),
			'default' => '',
			'desc_tip' => true,
		],
		'status' => [
			'title' => esc_html__('Environment', 'woocommerce'),
			'type' => 'select',
			'options' => ['test' => 'Sandbox', 'live' => 'Production'],
			'description' => esc_html__(
				'The Environment indicates whether you are ready to run your business or if it is still in testing mode. No payments will be processed if the test is selected. Please see the user guide provided by the AM Barclay Gateway service for more information.',
				'woocommerce'
			),
			'default' => '',
			'desc_tip' => true,
		],
		'sha_in' => [
			'title' => esc_html__('SHA-IN Passphrase', 'woocommerce'),
			'type' => 'text',
			'description' => esc_html__(
				'To improve security, the SHA-IN signature will encode the parameter passed to the payment processor via the hidden fields.',
				'woocommerce'
			),
			'default' => ambpg_generate_new_hash(),
			'desc_tip' => true,
		],
		'sha_out' => [
			'title' => esc_html__('SHA-OUT Passphrase', 'woocommerce'),
			'type' => 'text',
			'description' => esc_html__(
				'To improve security, the SHA-OUT signature will encrypt the parameter supplied from the payment processor to the redirection url.',
				'woocommerce'
			),
			'default' => ambpg_generate_new_hash(),
			'desc_tip' => true,
		],
		'sha_method' => [
			'title' => esc_html__('SHA encryption method', 'woocommerce'),
			'type' => 'select',
			'options' => [0 => 'SHA-1', 1 => 'SHA-256', 2 => 'SHA-512'],
			'description' => esc_html__(
				'SHA encryption technique - this must be the same as what you have configured in the EPDQ backoffice.',
				'woocommerce'
			),
			'default' => 2,
			'desc_tip' => true,
		],
		'error_notice' => [
			'title' => esc_html__('Error Notice', 'woocommerce'),
			'type' => 'textarea',
			'description' => esc_html__(
				'In case if there something went wrong while checking out what message will be displayed to the customer.',
				'woocommerce'
			),
			'default' => '',
			'desc_tip' => true,
		],
		'payment_method' => [
			'title' => esc_html__('Payment Method', 'woocommerce'),
			'type' => 'multiselect',
			'class' => 'wc-enhanced-select',
			'description' => esc_html__('Payment method decided by the merchant', 'woocommerce'),
			'default' => [],
			'desc_tip' => false,
			'options' => [
				'PAYPAL' => esc_html__('PAYPAL', 'woocommerce'),
				'CreditCard' => esc_html__('Credit Card', 'woocommerce'),
			],
			'custom_attributes' => [
				'data-placeholder' => esc_html__('Select payment methods', 'woocommerce'),
			],
		],
		'brand_cards' => [
			'title' => esc_html__('Brand Cards', 'woocommerce'),
			'type' => 'select',
			'class' => 'wc-enhanced-select',
			'css' => 'width: 400px;',
			'description' => esc_html__('Brand of cards selected by the merchant, e.g., VISA, MAESTRO. If blank, all cards are accepted.', 'woocommerce'),
			'default' => '',
			'desc_tip' => true,
			'options' => [
				'' => 'all',
				'VISA' => esc_html__('VISA', 'woocommerce'),
				'Maestro' => esc_html__('Maestro', 'woocommerce'),
				'MasterCard' => esc_html__('MasterCard', 'woocommerce'),
				'AMERICAN_EXPRESS' => esc_html__('American Express', 'woocommerce'),
				'JCB' => esc_html__('JCB', 'woocommerce'),
			],
			'custom_attributes' => [
				'data-placeholder' => esc_html__('Select brand cards', 'woocommerce'),
			],
		],
		'secure_3d' => [
			'title' => esc_html__('Secured With 3D', 'woocommerce'),
			'type' => 'select',
			'options' => [
				'MAINW' => esc_html__('Main Window (Default)', 'woocommerce'),
				'POPUP' => esc_html__('Popup Window', 'woocommerce'),
			],
			'description' => esc_html__('Require Secure 3D Payments. Main Window is default as recommended as many card services do not support them.', 'woocommerce'),
			'default' => 'MAINW',
			'desc_tip' => false,
		],
		'method_list' => [
			'title' => esc_html__('Payment Method List', 'woocommerce'),
			'type' => 'multiselect',
			'description' => esc_html__('List of card services accepted for payments, e.g., VISA;iDEA.', 'woocommerce'),
			'class' => 'wc-enhanced-select',
			'css' => 'width: 400px;',
			'default' => [],
			'desc_tip' => true,
			'options' => [
				'' => 'all',
				'VISA' => esc_html__('VISA', 'woocommerce'),
				'Maestro' => esc_html__('Maestro', 'woocommerce'),
				'MasterCard' => esc_html__('MasterCard', 'woocommerce'),
				'AMERICAN_EXPRESS' => esc_html__('American Express', 'woocommerce'),
				'JCB' => esc_html__('JCB', 'woocommerce'),
			],
			'custom_attributes' => [
				'data-placeholder' => esc_html__('Select payment methods', 'woocommerce'),
			],
		],
		'operation' => [
			'title' => esc_html__('Operation', 'woocommerce'),
			'type' => 'select',
			'options' => ['RES' => 'Request for Authorisation', 'SAL' => 'Request for sale (payment)'],
			'description' => esc_html__('Operation Code For Transaction ', 'woocommerce'),
			'default' => '', //VISA;iDEA
			'desc_tip' => false,
		],
		'show_logo' => [
			'title' => esc_html__('Show Barclay Accepted Card on Payment Page', 'woocommerce'),
			'type' => 'checkbox',
			'label' => esc_html__('Show Barclay Accepted Card on Payment Page', 'woocommerce'),
			'default' => 'yes',
		],
	]
);
