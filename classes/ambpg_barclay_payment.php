<?php

if (!defined('ABSPATH')) {
	exit;
}



/**
 * Class Ambpg_Barclay_Payment_Gateway
 */
class Ambpg_Barclay_Payment_Gateway
{
	/**
	 * @var string
	 */
	private $dir;

	/**
	 * @var string
	 */
	private $token;

	/**
	 * @var string
	 */
	private $file;

	/**
	 * @var string
	 */
	private $version;

	/**
	 * Ambpg_Barclay_Payment_Gateway constructor.
	 * @access public
	 *
	 * @param $file
	 *
	 * @since 1.0
	 */
	public function __construct($file)
	{
		$this->dir = dirname($file);
		$this->file = $file;
		$this->version = '1.0.0';
		$this->token = 'ambpg-barclay';
		$this->addHooks();

	}

	/**
	 * Register various hooks
	 */
	private function addHooks()
	{
		add_action('plugins_loaded', [$this, 'initBarclayGateway']);
		add_filter('woocommerce_payment_gateways', [&$this, 'methodBarclayGateway']);
	}


	/**
	 * Function to initiate Payment Gateway
	 */
	public function initBarclayGateway()
	{
		if (!class_exists('WC_Payment_Gateway')) {
			return;
		}
		require 'includes/error-code-barclay.php';
		require 'includes/success-code-barclay.php';
		require 'ambpg_wc_gateway_barclay.php';

	}

	/**
	 * Function to send require methods to woocommerce for initialization of gateway
	 *
	 * @param array $methods
	 *
	 * @return array
	 */
	public function methodBarclayGateway(array $methods): array
	{
		$methods[] = 'Ambpg_WC_Gateway_Barclay';

		return $methods;
	}
}