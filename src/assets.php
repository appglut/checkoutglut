<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Asset registration for checkoutPage
 */

class CheckoutPageAssets {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    public function enqueue_frontend_assets() {
        $plugin_url = plugin_dir_url(__FILE__);
        
        // Enqueue CSS
        if (file_exists(__DIR__ . '/assets/style.css')) {
            wp_enqueue_style(
                'checkoutPage-style',
                $plugin_url . 'assets/style.css',
                [],
                filemtime(__DIR__ . '/assets/style.css')
            );
        }
        
        // Enqueue JS
        if (file_exists(__DIR__ . '/assets/script.js')) {
            wp_enqueue_script(
                'checkoutPage-script',
                $plugin_url . 'assets/script.js',
                ['jquery'],
                filemtime(__DIR__ . '/assets/script.js'),
                true
            );
        }
    }
    
    public function enqueue_admin_assets($hook) {
        // Only load on checkout field editor admin pages
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page check with sanitization
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';

        if (strpos($hook, 'shopglut') === false && empty($page) ||
            (!empty($page) && strpos($page, 'checkout') === false)) {
            return;
        }

        $plugin_url = plugin_dir_url(__FILE__);

        // Enqueue admin CSS
        if (file_exists(__DIR__ . '/assets/admin-style.css')) {
            wp_enqueue_style(
                'shopglut-checkout-admin-style',
                $plugin_url . 'assets/admin-style.css',
                ['wp-admin', 'dashicons'],
                filemtime(__DIR__ . '/assets/admin-style.css')
            );
        }

        // Enqueue admin JS
        if (file_exists(__DIR__ . '/assets/admin-script.js')) {
            wp_enqueue_script(
                'shopglut-checkout-admin-script',
                $plugin_url . 'assets/admin-script.js',
                ['jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-draggable'],
                filemtime(__DIR__ . '/assets/admin-script.js'),
                true
            );

            // Localize script with necessary data
            wp_localize_script('shopglut-checkout-admin-script', 'shopglut_checkout_fields', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('shopglut_checkout_fields_nonce'),
                'i18n' => [
                    'confirm_delete' => __('Are you sure you want to delete this field? This action cannot be undone.', 'shopglut'),
                    'confirm_reset' => __('Are you sure you want to reset to default settings? All customizations will be lost.', 'shopglut'),
                    'error_updating' => __('Error updating field. Please try again.', 'shopglut'),
                    'error_resetting' => __('Error resetting fields. Please try again.', 'shopglut'),
                    'field_updated' => __('Field updated successfully.', 'shopglut'),
                    'field_deleted' => __('Field deleted successfully.', 'shopglut'),
                    'fields_reset' => __('Fields reset successfully.', 'shopglut')
                ]
            ]);
        }
    }
}

// Initialize the assets class
new CheckoutPageAssets();
