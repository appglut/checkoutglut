<?php
namespace CheckoutGlut;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to initialize and integrate checkout fields functionality
 */
class CheckoutFieldsInit {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialize hooks
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Initialize the CheckoutFieldsDisplay class
		add_action( 'init', array( $this, 'init_display_class' ) );

		// Hook for saving custom field values during checkout
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_custom_checkout_field_values' ), 10, 1 );

		// Support for saving block checkout field values
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Package' ) ) {
			add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'save_block_checkout_field_values' ), 10, 1 );
		}

		// Debug hook - optional, remove in production
		// add_action('woocommerce_thankyou', array($this, 'debug_custom_field_values'), 10, 1);
	}

	/**
	 * Initialize display class
	 */
	public function init_display_class() {
		CheckoutFieldsDisplay::get_instance();
	}

	/**
	 * Save custom checkout field values during standard checkout
	 *
	 * @param int $order_id Order ID
	 */
	public function save_custom_checkout_field_values( $order_id ) {
		// Get field definitions from CheckoutFieldsManager
		$checkout_fields_manager = CheckoutFieldsManager::get_instance();
		$custom_fields = $checkout_fields_manager->getCustomFieldsFromDatabase();

		// Define sections
		$field_sections = array( 'billing', 'shipping', 'additional', 'address', 'contact', 'order' );

		// Loop through all sections and fields
		foreach ( $field_sections as $section ) {
			if ( isset( $custom_fields[ $section ] ) && ! empty( $custom_fields[ $section ] ) ) {
				foreach ( $custom_fields[ $section ] as $field_id => $field ) {
					// Skip disabled fields
					if ( isset( $field['enabled'] ) && ! $field['enabled'] ) {
						continue;
					}

					// Check if field exists in POST data
					// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce verification for checkout process
					if ( isset( $_POST[ $field_id ] ) ) {
						// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce verification for checkout process
						$value = sanitize_text_field( wp_unslash( $_POST[ $field_id ] ) );

						// Skip empty values
						if ( empty( $value ) && $value !== '0' ) {
							continue;
						}

						// Sanitize value based on field type
						switch ( $field['type'] ) {
							case 'email':
								$value = sanitize_email( $value );
								break;
							case 'url':
								$value = esc_url_raw( $value );
								break;
							case 'textarea':
								$value = sanitize_textarea_field( $value );
								break;
							case 'checkbox':
								$value = sanitize_text_field( $value );
								break;
							case 'select':
							case 'radio':
								$value = sanitize_text_field( $value );
								break;
							default:
								$value = sanitize_text_field( $value );
								break;
						}

						// Save value to order meta with underscore prefix
						update_post_meta( $order_id, '_' . $field_id, $value );
					}
				}
			}
		}
	}

	/**
	 * Save block checkout field values
	 *
	 * @param WC_Order $order Order object
	 */
	public function save_block_checkout_field_values( $order ) {

		$order_id = $order->get_id();

		// Get block checkout fields
		$checkout_fields_manager = CheckoutFieldsManager::get_instance();
		$block_fields = $checkout_fields_manager->getBlockCheckoutFieldsFromDatabase();

		// If no block fields, return
		if ( empty( $block_fields ) ) {
			return;
		}

		// Define sections
		$field_sections = array( 'billing', 'shipping', 'additional', 'address', 'contact', 'order' );

		// Process each section
		foreach ( $field_sections as $section ) {
			if ( isset( $block_fields[ $section ] ) && ! empty( $block_fields[ $section ] ) ) {
				foreach ( $block_fields[ $section ] as $field_id => $field ) {
					// Skip disabled fields
					if ( isset( $field['enabled'] ) && ! $field['enabled'] ) {
						continue;
					}

					// Skip already saved fields
					if ( metadata_exists( 'post', $order_id, '_' . $field_id ) ) {
						continue;
					}

					// Get field value from request
					// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce verification for checkout API process
					$value = isset( $_POST[ $field_id ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field_id ] ) ) : '';

					// Skip empty values
					if ( empty( $value ) && $value !== '0' ) {
						continue;
					}

					// Sanitize value based on field type
					switch ( $field['type'] ) {
						case 'email':
							$value = sanitize_email( $value );
							break;
						case 'url':
							$value = esc_url_raw( $value );
							break;
						case 'textarea':
							$value = sanitize_textarea_field( $value );
							break;
						default:
							$value = sanitize_text_field( $value );
							break;
					}

					// Save value to order meta with underscore prefix
					update_post_meta( $order_id, '_' . $field_id, $value );
				}
			}
		}
	}

	/**
	 * Debug function to check saved custom field values
	 * 
	 * @param int $order_id Order ID
	 */
	public function debug_custom_field_values( $order_id ) {
		// Get all post meta for the order
		$order_meta = get_post_meta( $order_id );

		// Only show debug output if WP_DEBUG is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			echo '<div style="background:#f8f8f8; padding:15px; margin:15px 0; border:1px solid #ddd;">';
			echo '<h3>Debug: Custom Field Values Saved in Order #' . esc_html($order_id) . '</h3>';
			echo '<pre>';

			// Filter only custom field values (with underscore prefix)
			$custom_field_values = array();
			foreach ( $order_meta as $key => $value ) {
				// Check if key starts with underscore and is not a WooCommerce internal field
				if ( substr( $key, 0, 1 ) === '_' &&
					substr( $key, 0, 5 ) !== '_wc_' &&
					substr( $key, 0, 11 ) !== '_woocommerce' ) {
					$custom_field_values[ $key ] = maybe_unserialize( $value[0] );
				}
			}

			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Debug output only shown when WP_DEBUG is enabled
			print_r( $custom_field_values );
			echo '</pre>';
			echo '</div>';
		}
	}

	/**
	 * Get instance
	 *
	 * @return self
	 */
	public static function get_instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Create database table for checkout fields
	 */
	public static function create_tables() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'shopglut_checkout_fields';
		$charset_collate = $wpdb->get_charset_collate();

		// Check if table exists
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for table existence check
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );

		if ( $table_exists ) {
			return;
		}

		// Create table
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for table creation
		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			section varchar(50) NOT NULL,
			field_id varchar(100) NOT NULL,
			type varchar(50) NOT NULL,
			label varchar(255) NOT NULL,
			placeholder varchar(255) DEFAULT '',
			class varchar(255) DEFAULT '',
			required tinyint(1) NOT NULL DEFAULT 0,
			priority int(11) NOT NULL DEFAULT 10,
			options longtext DEFAULT '',
			validation varchar(255) DEFAULT '',
			enabled tinyint(1) NOT NULL DEFAULT 1,
			custom tinyint(1) NOT NULL DEFAULT 0,
			display_in_emails tinyint(1) NOT NULL DEFAULT 1,
			display_in_order tinyint(1) NOT NULL DEFAULT 1,
			block_checkout tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY section (section),
			KEY field_id (field_id),
			KEY enabled (enabled)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}

// Initialize the integration
