<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://profiles.wordpress.org/rushikshah
 * @since             1.0.0
 * @package           Ambpg_Barclay_Payment_Gateway
 *
 * @wordpress-plugin
 * Plugin Name:       AM Barclay ePDQ Payment Gateway
 * Plugin URI:        https://wordpress.org/plugins/alakmalak-barclay-epdq-payment-gateway
 * Description:       Enhance your website's payment efficiency with the AM Barclay ePDQ Payment Gateway Plugin — a seamless integration that ensures secure transactions and optimizes the Barclay ePDQ experience.
 * Version:           1.0.0
 * Author:            RushikShah
 * Author URI:        https://profiles.wordpress.org/rushikshah
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       alakmalak-barclay-epdq-payment-gateway
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('AMBPG_BARCLAY_PAYMENT_GATEWAY_VERSION', '1.0.0');

/**
 * Check if WooCommerce is active by inspecting active plugins
 */
if (
	in_array(
		'woocommerce/woocommerce.php',
		apply_filters('active_plugins', get_option('active_plugins')),
		true
	)
) {
	// Include the main payment gateway class
	require 'classes/ambpg_barclay_payment.php';

	// Create an instance of the AM Barclay Payment Gateway
	global $ambpg_Barclay_Payment_Gateway;
	$ambpg_Barclay_Payment_Gateway = new Ambpg_Barclay_Payment_Gateway(__FILE__);
}



