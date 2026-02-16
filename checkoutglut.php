<?php
/**
 * Plugin Name: CheckoutGlut - Checkout Fields for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/shopglut
 * Description: Powerful WooCommerce checkout field editor with support for classic and block checkout, custom fields, field reordering, validation, and more.
 * Version: 1.0.1
 * Author: AppGlut
 * Author URI: https://appglut.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: checkoutglut
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CHECKOUTGLUT_VERSION', '1.0.0' );
define( 'CHECKOUTGLUT_FILE', __FILE__ );
define( 'CHECKOUTGLUT_PATH', plugin_dir_path( __FILE__ ) );
define( 'CHECKOUTGLUT_URL', plugin_dir_url( __FILE__ ) );
define( 'CHECKOUTGLUT_BASENAME', plugin_basename( __FILE__ ) );
define( 'CHECKOUTGLUT_MENU_SLUG', 'shopglut_checkout_fields' );

// Load core classes
require_once CHECKOUTGLUT_PATH . 'src/CheckoutFieldsManager.php';
require_once CHECKOUTGLUT_PATH . 'src/BlockCheckoutFields.php';
require_once CHECKOUTGLUT_PATH . 'src/CheckoutFieldsInit.php';
require_once CHECKOUTGLUT_PATH . 'src/CheckoutFieldsDisplay.php';

// Load WelcomePage class
if ( is_admin() && file_exists( CHECKOUTGLUT_PATH . 'src/WelcomePage.php' ) ) {
	require_once CHECKOUTGLUT_PATH . 'src/WelcomePage.php';
}

// Hook into WooCommerce initialization
add_action( 'woocommerce_init', 'checkoutglut_plugin_initialize' );

function checkoutglut_plugin_initialize() {
	// Ensure that WooCommerce is loaded before proceeding
	if ( class_exists( 'WooCommerce' ) ) {
		// Run CheckoutGlut initialization
		\CheckoutGlut\CheckoutFieldsManager::get_instance();
	}
}

// Activation hook
register_activation_hook( __FILE__, function() {
	// Create database tables
	if ( class_exists( 'CheckoutGlut\CheckoutFieldsInit' ) ) {
		CheckoutGlut\CheckoutFieldsInit::create_tables();
	}
	// Set transient to redirect to welcome page
	set_transient( 'checkoutglut_activation_redirect', true, 30 );
} );

// Admin init hook for redirect to welcome page
add_action( 'admin_init', function() {
	// Check if we should redirect to welcome page
	if ( get_transient( 'checkoutglut_activation_redirect' ) ) {
		delete_transient( 'checkoutglut_activation_redirect' );

		// Don't redirect if activating from network admin or bulk activation
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Checking WordPress core parameter during plugin activation
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		// Check if individual menu is enabled
		$show_menu = get_option( 'shopglut_integration_settings', array() );
		$individual_menu_enabled = isset( $show_menu['checkoutglut-show-menu'] ) && $show_menu['checkoutglut-show-menu'] == '1';

		// Redirect to welcome page if individual menu is enabled, otherwise to main page
		if ( $individual_menu_enabled ) {
			wp_safe_redirect( admin_url( 'admin.php?page=checkoutglut-welcome' ) );
		} else {
			wp_safe_redirect( admin_url( 'admin.php?page=' . CHECKOUTGLUT_MENU_SLUG ) );
		}
		exit;
	}
} );
