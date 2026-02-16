<?php
namespace CheckoutGlut;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main class for managing WooCommerce checkout fields
 *
 * This class handles the core functionality of the checkout field editor,
 * including adding, editing, and managing checkout fields.
 */
class CheckoutFieldsManager {

	/**
	 * Menu slug for the checkout fields editor
	 *
	 * @var string
	 */
	public $menu_slug = 'shopglut_checkout_fields';

	/**
	 * Singleton instance
	 *
	 * @var CheckoutFieldsManager
	 */
	private static $instance = null;

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private $version = '1.0.0';

	/**
	 * Default field sections in WooCommerce checkout
	 *
	 * @var array
	 */
	private $field_sections = array( 'billing', 'shipping', 'additional', 'address', 'contact', 'order' );

	/**
	 * Default field types available
	 *
	 * @var array
	 */
	private $field_types = array(
		'text' => 'Text',
		'password' => 'Password',
		'email' => 'Email',
		'tel' => 'Phone',
		'number' => 'Number',
		'textarea' => 'Textarea',
		'select' => 'Select',
		'multiselect' => 'Multi-Select',
		'radio' => 'Radio',
		'checkbox' => 'Checkbox',
		'checkbox_group' => 'Checkbox Group',
		'date' => 'Date',
		'time' => 'Time',
		'datetime-local' => 'DateTime Local',
		'month' => 'Month',
		'week' => 'Week',
		'url' => 'URL',
		'hidden' => 'Hidden',
		'heading' => 'Heading',
		'paragraph' => 'Paragraph'
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialize CheckoutFieldsInit class for field saving and display
		\CheckoutGlut\CheckoutFieldsInit::get_instance();

		// Initialize BlockCheckoutFields class for block checkout support
		new \CheckoutGlut\BlockCheckoutFields();

		// Hook into WooCommerce checkout fields
		add_filter( 'woocommerce_checkout_fields', array( $this, 'customizeCheckoutFields' ) );

		// Display custom fields on order admin pages
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'displayCustomFieldsInAdmin' ) );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'displayCustomFieldsInAdmin' ) );

		// Add custom fields to emails
		add_action( 'woocommerce_email_after_order_table', array( $this, 'displayCustomFieldsInEmail' ), 10, 4 );

		// Enqueue admin scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminScripts' ) );

		add_action( 'init', array( $this, 'registerBlockCheckoutFields' ) );

		add_action( 'admin_init', array( $this, 'ensure_all_fields_in_database' ) );

		// Add admin menu
		add_action( 'admin_menu', array( $this, 'addAdminMenu' ) );

		// Initialize AJAX handlers
		$this->initAjaxHandlers();
	}

	/**
	 * Add admin menu
	 */
	public function addAdminMenu() {
		// Always add submenu under WooCommerce
		add_submenu_page(
			'woocommerce', // Parent slug - under WooCommerce
			__( 'Checkout Fields', 'checkoutglut' ),
			__( 'Checkout Fields', 'checkoutglut' ),
			'manage_woocommerce',
			$this->menu_slug,
			array( $this, 'renderAdminPage' )
		);

		// Add individual top-level menu if enabled from integration settings
		if ( $this->showIndividualMenu() ) {
			add_menu_page(
				__( 'CheckoutGlut', 'checkoutglut' ),
				__( 'CheckoutGlut', 'checkoutglut' ),
				'manage_woocommerce',
				'checkoutglut_fields',
				array( $this, 'renderAdminPage' ),
				'dashicons-list-view',
				30
			);

			// Add Welcome submenu for individual menu
			add_submenu_page(
				'checkoutglut_fields',
				esc_html__( 'Welcome', 'checkoutglut' ),
				esc_html__( 'Welcome', 'checkoutglut' ),
				'manage_woocommerce',
				'checkoutglut-welcome',
				array( $this, 'render_welcome_page' )
			);
		}
	}

	/**
	 * Check if individual menu should be shown
	 *
	 * @return bool Whether to show the individual menu
	 */
	public function showIndividualMenu() {
		// Check if ShopGlut integration settings option exists and is enabled
		$show_menu = get_option( 'shopglut_integration_settings', array() );
		return isset( $show_menu['checkoutglut-show-menu'] ) && $show_menu['checkoutglut-show-menu'] == '1';
	}

	/**
	 * Initialize AJAX handlers
	 */
	private function initAjaxHandlers() {
		add_action( 'wp_ajax_shopglut_get_checkout_field', array( $this, 'ajaxGetCheckoutField' ) );
		add_action( 'wp_ajax_shopglut_save_checkout_field', array( $this, 'ajaxSaveCheckoutField' ) );
		add_action( 'wp_ajax_shopglut_delete_checkout_field', array( $this, 'ajaxDeleteCheckoutField' ) );
		add_action( 'wp_ajax_shopglut_reset_checkout_fields', array( $this, 'ajaxResetCheckoutFields' ) );
		add_action( 'wp_ajax_shopglut_toggle_checkout_fields', array( $this, 'ajaxToggleCheckoutFields' ) );
		add_action( 'wp_ajax_shopglut_toggle_checkout_field', array( $this, 'ajaxToggleCheckoutField' ) );
		add_action( 'wp_ajax_shopglut_update_field_priorities', array( $this, 'ajaxUpdateFieldPriorities' ) );
		add_action( 'wp_ajax_shopglut_update_block_field_priorities', array( $this, 'ajaxUpdateBlockFieldPriorities' ) );
		add_action( 'wp_ajax_shopglut_reorder_checkout_fields', array( $this, 'ajaxReorderCheckoutFields' ) );
	}

	/**
	 * Enqueue required admin scripts and styles
	 */
	public function enqueueAdminScripts( $hook ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page check with sanitization
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		// Check for both menu slugs - WooCommerce submenu and individual menu
		if ( ! in_array( $page, array( $this->menu_slug, 'checkoutglut_fields' ) ) ) {
			return;
		}

		$plugin_dir_url = plugin_dir_url( dirname( __FILE__ ) );

		// Add custom body class and hide admin elements for full-width mode
		add_action( 'admin_body_class', array( $this, 'addFullscreenBodyClass' ) );
		add_action( 'admin_head', array( $this, 'hideAdminElements' ) );
		add_action( 'admin_print_scripts', array( $this, 'addFullscreenScript' ), 999 );
		add_action( 'admin_notices', array( $this, 'hideAdminNotices' ), 999 );

		// Enqueue jQuery UI and core WP styles
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );

		// Enqueue our custom styles
		wp_enqueue_style(
			'shopglut-checkout-manager-css',
			plugin_dir_url(__FILE__) . 'assets/admin-style.css',
			array(),
			filemtime(__DIR__ . '/assets/admin-style.css')
		);

		// Enqueue our custom script
		wp_enqueue_script(
			'shopglut-checkout-manager-js',
			plugin_dir_url(__FILE__) . 'assets/admin-script.js',
			array( 'jquery', 'jquery-ui-dialog', 'jquery-ui-sortable' ),
			filemtime(__DIR__ . '/assets/admin-script.js'),
			true
		);

		// Localize script with data and translations
		wp_localize_script( 'shopglut-checkout-manager-js', 'shopglut_checkout_fields', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'shopglut_checkout_fields_nonce' ),
			'i18n' => array(
				'confirm_delete' => __( 'Are you sure you want to delete this field? This action cannot be undone.', 'shopglut' ),
				'confirm_reset' => __( 'Are you sure you want to reset to default fields? All your changes will be lost.', 'shopglut' ),
				'add_field' => __( 'Add New Checkout Field', 'shopglut' ),
				'edit_field' => __( 'Edit Checkout Field', 'shopglut' ),
				'no_fields' => __( 'No fields found for this section.', 'shopglut' ),
				'error_loading' => __( 'Error loading field data.', 'shopglut' ),
				'error_saving' => __( 'Error saving field data.', 'shopglut' ),
				'error_deleting' => __( 'Error deleting field.', 'shopglut' ),
				'error_resetting' => __( 'Error resetting fields.', 'shopglut' ),
				'error_updating' => __( 'Error updating fields.', 'shopglut' )
			)
		) );
	}

	/**
	 * Add JavaScript to force fullscreen mode and remove admin bar space
	 */
	public function addFullscreenScript() {
		?>
		<script>
			jQuery(document).ready(function($) {
				// Remove admin bar completely
				$('#wpadminbar').remove();

				// Remove admin bar space from html and body
				$('html').css({
					'margin-top': '0 !important',
					'padding-top': '0 !important'
				});
				$('body').css({
					'margin-top': '0 !important',
					'padding-top': '0 !important'
				});

				// Force remove admin-bar class styling
				$('html.admin-bar, body.admin-bar, .admin-bar').each(function() {
					this.style.setProperty('margin-top', '0', 'important');
					this.style.setProperty('padding-top', '0', 'important');
				});

				// Remove admin menu
				$('#adminmenuwrap, #adminmenuback, #adminmenu').remove();

				// Force full width with spacing
				$('#wpbody').css({
					'float': 'none',
					'width': '100%',
					'margin-left': '0',
					'padding-left': '0'
				});
				$('#wpcontent').css({
					'margin-left': '0',
					'padding-left': '0'
				});
				$('#wpbody-content').css({
					'padding-left': '0',
					'margin-left': '0',
					'float': 'none',
					'width': '100%'
				});

				// Add spacing to wrap
				$('.wrap').css({
					'max-width': '1600px',
					'padding': '40px',
					'margin': '0 auto'
				});
			});
		</script>
		<?php
	}

	/**
	 * Add fullscreen body class for checkout fields page
	 */
	public function addFullscreenBodyClass( $classes ) {
		$classes .= ' shopglut-checkout-fullscreen';
		return $classes;
	}

	/**
	 * Hide admin elements for fullscreen mode
	 */
	public function hideAdminElements() {
		?>
		<style>
			/* IMPORTANT: These styles must load early to prevent layout shift */

			/* Hide WP admin bar completely */
			#wpadminbar {
				display: none !important;
				height: 0 !important;
				min-height: 0 !important;
				visibility: hidden !important;
				position: absolute !important;
				top: -9999px !important;
			}

			/* Remove admin bar space - Use high specificity */
			html.admin-bar,
			html#wpadminbar,
			body.admin-bar,
			body#wpadminbar,
			.shopglut-checkout-fullscreen.admin-bar,
			.shopglut-checkout-fullscreen html.admin-bar,
			.shopglut-checkout-fullscreen body.admin-bar {
				margin-top: 0 !important;
				padding-top: 0 !important;
				min-height: auto !important;
			}

			/* Target admin-bar class on any element */
			.admin-bar,
			* .admin-bar,
			.shopglut-checkout-fullscreen .admin-bar {
				margin-top: 0 !important;
				padding-top: 0 !important;
			}

			/* Hide left admin menu completely */
			#adminmenuwrap,
			#adminmenuback,
			#adminmenu {
				display: none !important;
				width: 0 !important;
				min-width: 0 !important;
				visibility: hidden !important;
				position: absolute !important;
				left: -9999px !important;
			}

			/* Expand content area to full width with proper spacing */
			.shopglut-checkout-fullscreen,
			.shopglut-checkout-fullscreen html,
			.shopglut-checkout-fullscreen body {
				width: 100% !important;
				max-width: none !important;
				overflow-x: hidden !important;
			}

			.shopglut-checkout-fullscreen #wpwrap {
				width: 100% !important;
			}

			.shopglut-checkout-fullscreen #wpbody {
				float: none !important;
				width: 100% !important;
				margin-left: 0 !important;
				padding-left: 0 !important;
				padding-top: 0 !important;
				margin-top: 0 !important;
			}

			.shopglut-checkout-fullscreen #wpcontent {
				margin-left: 0 !important;
				padding-left: 0 !important;
				padding-top: 0 !important;
				margin-top: 0 !important;
			}

			.shopglut-checkout-fullscreen #wpbody-content {
				padding-left: 0 !important;
				margin-left: 0 !important;
				padding-top: 0 !important;
				margin-top: 0 !important;
				float: none !important;
				width: 100% !important;
			}

			/* Add comfortable spacing to wrap container */
			.shopglut-checkout-fullscreen .wrap {
				margin: 0 auto !important;
				max-width: 1600px !important;
				width: calc(100% - 80px) !important;
				padding: 40px !important;
			}

			.shopglut-checkout-fullscreen .shopglut-checkout-manager-wrap {
				max-width: 100% !important;
				width: 100% !important;
			}

			/* Hide footer admin bar */
			#wpfooter {
				display: none !important;
				height: 0 !important;
			}

			/* Hide screen options and help tabs */
			#screen-meta-links,
			#contextual-help-link-wrap,
			#show-settings-link {
				display: none !important;
			}

			/* Hide all admin notices */
			.notice,
			.update-nag,
			.updated,
			.error {
				display: none !important;
			}

			/* Remove any auto-fold class effects */
			.shopglut-checkout-fullscreen.auto-fold #wpbody,
			.shopglut-checkout-fullscreen.auto-fold .wrap,
			.shopglut-checkout-fullscreen.auto-fold #wpbody-content,
			.shopglut-checkout-fullscreen.auto-fold #wpcontent {
				margin-left: 0 !important;
				padding-left: 0 !important;
			}

			/* Add custom back to admin button styling */
			.shopglut-back-to-admin {
				display: inline-flex;
				align-items: center;
				gap: 8px;
				padding: 12px 24px;
				background: #2271b1;
				color: #fff;
				text-decoration: none;
				border-radius: 6px;
				font-size: 14px;
				font-weight: 500;
				transition: all 0.3s ease;
				box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
			}

			.shopglut-back-to-admin:hover {
				background: #135e96;
				color: #fff;
				transform: translateY(-2px);
				box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
			}

			.shopglut-back-to-admin .dashicons {
				font-size: 18px;
				width: 18px;
				height: 18px;
			}

			/* Responsive spacing */
			@media (max-width: 1600px) {
				.shopglut-checkout-fullscreen .wrap {
					width: calc(100% - 60px) !important;
					padding: 30px !important;
				}
			}

			@media (max-width: 1200px) {
				.shopglut-checkout-fullscreen .wrap {
					width: calc(100% - 50px) !important;
					padding: 25px !important;
				}
			}

			@media (max-width: 782px) {
				.shopglut-checkout-fullscreen .wrap {
					width: calc(100% - 40px) !important;
					padding: 20px !important;
				}
			}

			/* High specificity overrides to ensure spacing */
			body.shopglut-checkout-fullscreen .wrap,
			.shopglut-checkout-fullscreen > .wrap,
			#wpbody .shopglut-checkout-fullscreen .wrap {
				margin-left: auto !important;
				margin-right: auto !important;
				padding-left: 40px !important;
				padding-right: 40px !important;
				padding-top: 5px !important;
				padding-bottom: 40px !important;
			}
		</style>
		<?php
	}

	/**
	 * Hide admin notices on checkout fields page
	 */
	public function hideAdminNotices() {
		// Remove all notices to keep the page clean
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'network_admin_notices' );
		remove_all_actions( 'all_admin_notices' );
	}

	public function ensure_all_fields_in_database() {
		// Only run this on admin pages to avoid performance impact on frontend
		if ( ! is_admin() ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'shopglut_checkout_fields';
		$cache_key = 'shopglut_checkout_fields_table_exists';

		// Check if table exists first with caching
		$table_exists = wp_cache_get( $cache_key );
		if ( false === $table_exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for table existence check
			$table_exists = $wpdb->get_var( $wpdb->prepare(
				"SHOW TABLES LIKE %s",
				$table_name
			) );
			wp_cache_set( $cache_key, $table_exists, '', 3600 ); // Cache for 1 hour
		}

		if ( ! $table_exists ) {
			return;
		}

		// Get default WooCommerce fields
		if ( ! function_exists( 'WC' ) || ! WC()->checkout() ) {
			return;
		}

		$checkout = WC()->checkout();
		$default_fields = $checkout->get_checkout_fields();

		foreach ( $this->field_sections as $section ) {
			if ( isset( $default_fields[ $section ] ) ) {
foreach ( $default_fields[ $section ] as $field_id => $field ) {
					// Check if field exists in database with caching
					$cache_key = "shopglut_field_exists_{$field_id}_{$section}";
					$exists = wp_cache_get( $cache_key );

					if ( false === $exists ) {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for field existence check, using %i for table name
						$exists = $wpdb->get_var( $wpdb->prepare(
							"SELECT id FROM {$wpdb->prefix}shopglut_checkout_fields WHERE field_id = %s AND section = %s",
							$field_id,
							$section
						) );
						wp_cache_set( $cache_key, $exists, '', 300 ); // Cache for 5 minutes
					}

					if ( ! $exists ) {
						// Add field to database
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
						$result = $wpdb->insert(
							$table_name,
							array(
								'section' => $section,
								'field_id' => $field_id,
								'type' => isset( $field['type'] ) ? $field['type'] : 'text',
								'label' => isset( $field['label'] ) ? $field['label'] : '',
								'placeholder' => isset( $field['placeholder'] ) ? $field['placeholder'] : '',
								'class' => isset( $field['class'] ) ? ( is_array( $field['class'] ) ? implode( ' ', $field['class'] ) : $field['class'] ) : '',
								'required' => isset( $field['required'] ) ? (int) $field['required'] : 0,
								'priority' => isset( $field['priority'] ) ? (int) $field['priority'] : 10,
								'options' => isset( $field['options'] ) ? json_encode( $field['options'] ) : '',
								'validation' => isset( $field['validate'] ) ? ( is_array( $field['validate'] ) ? implode( ',', $field['validate'] ) : $field['validate'] ) : '',
								'enabled' => 1,
								'custom' => 0,
								'display_in_emails' => 1,
								'display_in_order' => 1,
								'block_checkout' => 0
							),
							array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d' )
						);

						
						if ( $result === false ) {
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
							// Failed to insert default field
						}
					}
				}
			}
		}
	}

	/**
	 * Customize checkout fields based on saved settings
	 *
	 * @param array $fields Default WooCommerce checkout fields
	 * @return array Modified checkout fields
	 */
	public function customizeCheckoutFields( $fields ) {
		// Get saved field configurations from database
		$custom_fields = $this->getCustomFieldsFromDatabase();

		if ( ! empty( $custom_fields ) ) {
			foreach ( $this->field_sections as $section ) {
				if ( isset( $custom_fields[ $section ] ) && ! empty( $custom_fields[ $section ] ) ) {
					// Process each field in the section
					foreach ( $custom_fields[ $section ] as $field_id => $field_config ) {
						// Skip disabled fields
						if ( ! $field_config['enabled'] ) {
							// Remove disabled fields from checkout
								if ( isset( $fields[ $section ][ $field_id ] ) ) {
									unset( $fields[ $section ][ $field_id ] );
								}
							continue;
						}

						// Add or update field configuration
						$fields[ $section ][ $field_id ] = $field_config;
					}
				}
			}
		}

		return $fields;
	}

	/**
	 * Get custom fields from database
	 *
	 * @return array Custom fields configuration
	 */
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	public function getCustomFieldsFromDatabase() {
		global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_name = $wpdb->prefix . 'shopglut_checkout_fields';

		// Check if table exists
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$table_name 
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		) );

		if ( ! $table_exists ) {
			return array();
		}

		$fields = array();
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			sprintf("SELECT * FROM `%s` WHERE block_checkout = 0 ORDER BY section, priority", esc_sql($table_name)), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Using sprintf with escaped table name
			ARRAY_A
		);

		if ( $results ) {
			foreach ( $results as $field ) {
				$section = $field['section'];
				$field_id = $field['field_id'];

				// Convert JSON options to array
				$options = ! empty( $field['options'] ) ? json_decode( $field['options'], true ) : array();
				
				// Ensure options is an array
				if ( ! is_array( $options ) ) {
					$options = array();
				}

				// Build field configuration
				$field_config = array(
					'type' => $field['type'],
					'label' => $field['label'],
					'placeholder' => $field['placeholder'],
					'class' => ! empty( $field['class'] ) ? explode( ' ', trim( $field['class'] ) ) : array(),
					'required' => (bool) $field['required'],
					'priority' => (int) $field['priority'],
					'custom' => (bool) $field['custom'],
					'display_in_emails' => (bool) $field['display_in_emails'],
					'display_in_order' => (bool) $field['display_in_order'],
					'enabled' => (bool) $field['enabled'],
					'block_checkout' => (bool) $field['block_checkout']
				);

				// Add options for select, multiselect, radio, checkbox_group
				if ( in_array( $field['type'], array( 'select', 'multiselect', 'radio', 'checkbox_group' ) ) && ! empty( $options ) ) {
					$field_config['options'] = $options;
				}

				// Add validation rules if any
				if ( ! empty( $field['validation'] ) ) {
					$validation_rules = explode( ',', $field['validation'] );
					$field_config['validate'] = array_map( 'trim', $validation_rules );
				}

				$fields[ $section ][ $field_id ] = $field_config;
			}
		}

		return $fields;
	}

	/**
	 * Get all fields from database including disabled ones
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	 * 
	 * @return array All fields
	 */
	public function getAllFieldsFromDatabase() {
		global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_name = $wpdb->prefix . 'shopglut_checkout_fields';

		// Check if table exists
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$table_name 
		) );

		if ( ! $table_exists ) {
			return array();
		}

// Get all fields from database with caching
		$cache_key = 'shopglut_all_checkout_fields';
		$results = wp_cache_get( $cache_key );

		if ( false === $results ) {
			$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.UnnecessaryPrepare -- Direct query required for custom table operation, no user input involved
				"SELECT * FROM {$wpdb->prefix}shopglut_checkout_fields ORDER BY section, priority", ARRAY_A
			);
			wp_cache_set( $cache_key, $results, '', 600 ); // Cache for 10 minutes
		}

		return $results ?: array();
	}

	/**
	 * Display custom fields in admin order page
	 *
	 * @param WC_Order $order Order object
	 */
	public function displayCustomFieldsInAdmin( $order ) {
		$order_id = $order->get_id();
		$custom_fields = $this->getCustomFieldsFromDatabase();
		$current_section = current_action() === 'woocommerce_admin_order_data_after_billing_address' ? 'billing' : 'shipping';

		if ( isset( $custom_fields[ $current_section ] ) && ! empty( $custom_fields[ $current_section ] ) ) {
			$has_custom_fields = false;
			$output = '<h3>' . esc_html( ucfirst( $current_section ) ) . ' ' . esc_html__( 'Custom Fields', 'shopglut' ) . '</h3>';
			$output .= '<div class="address">';

			foreach ( $custom_fields[ $current_section ] as $field_id => $field ) {
				if ( $field['display_in_order'] && $field['custom'] ) {
					$value = get_post_meta( $order_id, '_' . $field_id, true );
					if ( ! empty( $value ) ) {
						$has_custom_fields = true;
						$output .= '<p><strong>' . esc_html( $field['label'] ) . ':</strong> ' . esc_html( $value ) . '</p>';
					}
				}
			}

			$output .= '</div>';

			if ( $has_custom_fields ) {
				echo wp_kses_post( $output );
			}
		}

		// Show additional fields after shipping address
		if ( $current_section === 'shipping' && isset( $custom_fields['additional'] ) && ! empty( $custom_fields['additional'] ) ) {
			$has_additional_fields = false;
			$output = '<h3>' . esc_html__( 'Additional Fields', 'shopglut' ) . '</h3>';
			$output .= '<div class="address">';

			foreach ( $custom_fields['additional'] as $field_id => $field ) {
				if ( $field['display_in_order'] ) {
					$value = get_post_meta( $order_id, '_' . $field_id, true );
					if ( ! empty( $value ) ) {
						$has_additional_fields = true;
						$output .= '<p><strong>' . esc_html( $field['label'] ) . ':</strong> ' . esc_html( $value ) . '</p>';
					}
				}
			}

			$output .= '</div>';

			if ( $has_additional_fields ) {
				echo wp_kses_post( $output );
			}
		}
	}

	/**
	 * Display custom fields in order emails
	 *
	 * @param WC_Order $order Order object
	 * @param bool $sent_to_admin Whether the email is sent to admin
	 * @param bool $plain_text Whether the email is plain text
	 * @param WC_Email $email Email object
	 */
	public function displayCustomFieldsInEmail( $order, $sent_to_admin, $plain_text, $email ) {
		$order_id = $order->get_id();
		$custom_fields = $this->getCustomFieldsFromDatabase();

		if ( $plain_text ) {
			foreach ( $this->field_sections as $section ) {
				if ( isset( $custom_fields[ $section ] ) && ! empty( $custom_fields[ $section ] ) ) {
					$has_fields = false;
					$output = "\n" . ucfirst( $section ) . " " . esc_html__( 'Custom Fields', 'shopglut' ) . ":\n";

					foreach ( $custom_fields[ $section ] as $field_id => $field ) {
						if ( $field['display_in_emails'] && $field['custom'] ) {
							$value = get_post_meta( $order_id, '_' . $field_id, true );
							if ( ! empty( $value ) ) {
								$has_fields = true;
								$output .= $field['label'] . ": " . $value . "\n";
							}
						}
					}

					if ( $has_fields ) {
						echo wp_kses_post( $output );
					}
				}
			}
		} else {
			foreach ( $this->field_sections as $section ) {
				if ( isset( $custom_fields[ $section ] ) && ! empty( $custom_fields[ $section ] ) ) {
					$has_fields = false;
					$output = '<h2>' . ucfirst( $section ) . ' ' . esc_html__( 'Custom Fields', 'shopglut' ) . '</h2>';
					$output .= '<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; margin-bottom: 20px;">';

					foreach ( $custom_fields[ $section ] as $field_id => $field ) {
						if ( $field['display_in_emails'] && $field['custom'] ) {
							$value = get_post_meta( $order_id, '_' . $field_id, true );
							if ( ! empty( $value ) ) {
								$has_fields = true;
								$output .= '<tr>';
								$output .= '<th>' . esc_html( $field['label'] ) . ':</th>';
								$output .= '<td>' . esc_html( $value ) . '</td>';
								$output .= '</tr>';
							}
						}
					}

					$output .= '</table>';

					if ( $has_fields ) {
						echo wp_kses_post( $output );
					}
				}
			}
		}
	}

	/**
	 * AJAX handler for getting field data
	 */
	public function ajaxGetCheckoutField() {
		// Check nonce
		if ( ! check_ajax_referer( 'shopglut_checkout_fields_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed.', 'shopglut' ) );
		}

		// Check if field ID is provided
		if ( empty( $_POST['field_id'] ) ) {
			wp_send_json_error( __( 'Field ID is required.', 'shopglut' ) );
		}

		// Get field data with caching
		global $wpdb;
		$table_name = $wpdb->prefix . 'shopglut_checkout_fields';
		$field_id = sanitize_text_field( wp_unslash( $_POST['field_id'] ) );

		$cache_key = "shopglut_checkout_field_{$field_id}";
		$field = wp_cache_get( $cache_key );

		if ( false === $field ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for field lookup, using %i for table name
			$field = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}shopglut_checkout_fields WHERE id = %d",
				$field_id
			), ARRAY_A );
			wp_cache_set( $cache_key, $field, '', 300 ); // Cache for 5 minutes
		}

		if ( ! $field ) {
			wp_send_json_error( __( 'Field not found.', 'shopglut' ) );
		}

		wp_send_json_success( $field );
	}

	/**
	 * AJAX handler for saving field data
	 */
	public function ajaxSaveCheckoutField() {
		// Check nonce
		if ( ! check_ajax_referer( 'shopglut_checkout_fields_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed.', 'shopglut' ) );
		}

		// Determine if it's an edit operation or new field
		$is_edit = isset( $_POST['edit_field_section'] );
		$prefix = $is_edit ? 'edit_field_' : '';

		// Validate required fields
		$section_key = $prefix . 'section';
		$field_id_key = $prefix . 'id';
		$type_key = $prefix . 'type';
		$label_key = $prefix . 'label';

		if ( empty( $_POST[$section_key] ) || empty( $_POST[$field_id_key] ) || empty( $_POST[$type_key] ) || empty( $_POST[$label_key] ) ) {
			wp_send_json_error( __( 'Required fields are missing.', 'shopglut' ) );
		}

		// Sanitize and prepare field data
		$section = sanitize_text_field( wp_unslash( $_POST[$section_key] ) );
		$field_id = sanitize_key( wp_unslash( $_POST[$field_id_key] ) );
		
		// Add section prefix if not present
		if ( strpos( $field_id, $section . '_' ) !== 0 ) {
			$field_id = $section . '_' . $field_id;
		}

		$placeholder_key = $prefix . 'placeholder';
		$class_key = $prefix . 'class';
		$required_key = $prefix . 'required';
		$priority_key = $prefix . 'priority';
		$validation_key = $prefix . 'validation';
		$enabled_key = $prefix . 'enabled';
		$display_in_emails_key = $prefix . 'display_in_emails';
		$display_in_order_key = $prefix . 'display_in_order';

		$field_data = array(
			'section' => $section,
			'field_id' => $field_id,
			'type' => sanitize_text_field( wp_unslash( $_POST[$type_key] ) ),
			'label' => sanitize_text_field( wp_unslash( $_POST[$label_key] ) ),
			'placeholder' => isset( $_POST[$placeholder_key] ) ? sanitize_text_field( wp_unslash( $_POST[$placeholder_key] ) ) : '',
			'class' => isset( $_POST[$class_key] ) ? sanitize_text_field( wp_unslash( $_POST[$class_key] ) ) : '',
			'required' => isset( $_POST[$required_key] ) ? 1 : 0,
			'priority' => isset( $_POST[$priority_key] ) ? intval( $_POST[$priority_key] ) : 20,
			'validation' => isset( $_POST[$validation_key] ) ? sanitize_text_field( wp_unslash( $_POST[$validation_key] ) ) : '',
			'enabled' => isset( $_POST[$enabled_key] ) ? 1 : 0,
			'custom' => 1,
			'display_in_emails' => isset( $_POST[$display_in_emails_key] ) ? 1 : 0,
			'display_in_order' => isset( $_POST[$display_in_order_key] ) ? 1 : 0,
			'block_checkout' => 0,
			'options' => ''
		);

		// Process options for select, multiselect, radio, checkbox_group
		$options_key = $prefix . 'options';
		if ( in_array( $field_data['type'], array( 'select', 'multiselect', 'radio', 'checkbox_group' ) ) && ! empty( $_POST[$options_key] ) ) {
			$options_array = array();
			$lines = explode( "\n", sanitize_textarea_field( wp_unslash( $_POST[$options_key] ) ) );

			foreach ( $lines as $line ) {
				$line = trim( $line );
				if ( ! empty( $line ) ) {
					$parts = explode( '|', $line );
					$key = trim( $parts[0] );
					$value = isset( $parts[1] ) ? trim( $parts[1] ) : $key;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$options_array[ $key ] = $value;
				}
			}

			$field_data['options'] = json_encode( $options_array );
		}

		global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_name = $wpdb->prefix . 'shopglut_checkout_fields';

		// Check for database ID (for updates)
		$id_hidden_key = $prefix . 'id_hidden';
		$legacy_id_key = 'id'; // For backward compatibility with non-prefixed forms

		$db_field_id = null;
		if ( isset( $_POST[$id_hidden_key] ) && ! empty( $_POST[$id_hidden_key] ) ) {
			$db_field_id = intval( $_POST[$id_hidden_key] );
		} elseif ( isset( $_POST[$legacy_id_key] ) && ! empty( $_POST[$legacy_id_key] ) ) {
			$db_field_id = intval( $_POST[$legacy_id_key] );
		}

		if ( $db_field_id ) {
			// Update existing field
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->update(
				$table_name,
				$field_data,
				array( 'id' => $db_field_id ),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%d', '%d', '%d', '%d', '%s' ),
				array( '%d' )
			);

			if ( $result === false ) {
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				wp_send_json_error( __( 'Error updating field.', 'shopglut' ) . ' ' . $wpdb->last_error );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			}

			wp_send_json_success( array( 'id' => $db_field_id, 'message' => __( 'Field updated successfully.', 'shopglut' ) ) );
		} else {
			// Check if field with same field_id already exists in this section
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM %s WHERE field_id = %s AND section = %s", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder -- Using %s instead of %i for compatibility, table name escaped with esc_sql()
				$table_name,
				$field_data['field_id'],
				$field_data['section']
			) );

			if ( $existing ) {
				wp_send_json_error( __( 'A field with this ID already exists in this section.', 'shopglut' ) );
			}

			// Add new field
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->insert(
				$table_name,
				$field_data,
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%d', '%d', '%d', '%d', '%s' )
			);

			if ( ! $result ) {
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				wp_send_json_error( __( 'Error adding field.', 'shopglut' ) . ' ' . $wpdb->last_error );
			}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			wp_send_json_success( array( 'id' => $wpdb->insert_id, 'message' => __( 'Field added successfully.', 'shopglut' ) ) );
		}
	}

	/**
	 * AJAX handler for deleting field
	 */
	public function ajaxDeleteCheckoutField() {
		// Check nonce
		if ( ! check_ajax_referer( 'shopglut_checkout_fields_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed.', 'shopglut' ) );
		}

		// Check if field ID is provided
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $_POST['field_id'] ) ) {
			wp_send_json_error( __( 'Field ID is required.', 'shopglut' ) );
		}

		// Delete field
		global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_name = $wpdb->prefix . 'shopglut_checkout_fields';
		$field_id = intval( $_POST['field_id'] );

		// Check if it's a custom field with caching
		$cache_key = "shopglut_field_custom_{$field_id}";
		$field = wp_cache_get( $cache_key );

		if ( false === $field ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for field type check, using %i for table name
			$field = $wpdb->get_row( $wpdb->prepare(
				"SELECT custom FROM {$wpdb->prefix}shopglut_checkout_fields WHERE id = %d",
				$field_id
			) );
			wp_cache_set( $cache_key, $field, '', 300 ); // Cache for 5 minutes
		}

		if ( ! $field ) {
			wp_send_json_error( __( 'Field not found.', 'shopglut' ) );
		}

		if ( ! $field->custom ) {
			wp_send_json_error( __( 'Default fields cannot be deleted.', 'shopglut' ) );
		}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$table_name,
			array( 'id' => $field_id ),
			array( '%d' )
		);

		if ( ! $result ) {
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			wp_send_json_error( __( 'Error deleting field.', 'shopglut' ) . ' ' . $wpdb->last_error );
		}

		wp_send_json_success( array( 'message' => __( 'Field deleted successfully.', 'shopglut' ) ) );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	public function ajaxResetCheckoutFields() {
		// Check nonce
		if ( ! check_ajax_referer( 'shopglut_checkout_fields_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed.', 'shopglut' ) );
		}

		// Check if section is provided
		if ( empty( $_POST['section'] ) ) {
			wp_send_json_error( __( 'Section is required.', 'shopglut' ) );
		}

		$section = sanitize_text_field( wp_unslash( $_POST['section'] ) );

		if ( ! in_array( $section, $this->field_sections ) ) {
			wp_send_json_error( __( 'Invalid section.', 'shopglut' ) );
		}

		// Delete only rows for the specified section
		global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_name = $wpdb->prefix . 'shopglut_checkout_fields';

		// Delete records for the specific section
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$table_name,
			array( 'section' => $section ),
			array( '%s' )
		);

		if ( false === $result ) {
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			wp_send_json_error( __( 'Failed to reset fields for this section.', 'shopglut' ) . ' ' . $wpdb->last_error );
		}

		wp_send_json_success( array(
			// translators: %s is the section name
			'message' => sprintf( __( 'Fields for section "%s" have been reset successfully.', 'shopglut' ), $section )
		) );
	}
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

	/**
	 * AJAX handler for toggling all fields in a section
	 */
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	public function ajaxToggleCheckoutFields() {
		// Check nonce
		if ( ! check_ajax_referer( 'shopglut_checkout_fields_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed.', 'shopglut' ) );
		}

		// Check if section is provided
		if ( empty( $_POST['section'] ) ) {
			wp_send_json_error( __( 'Section is required.', 'shopglut' ) );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		$section = sanitize_text_field( wp_unslash( $_POST['section'] ) );
		$enable = isset( $_POST['enable'] ) ? (int) $_POST['enable'] : 0;

		// Validate section
		if ( ! in_array( $section, $this->field_sections ) ) {
			wp_send_json_error( __( 'Invalid section.', 'shopglut' ) );
		}

		global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_name = $wpdb->prefix . 'shopglut_checkout_fields';

		// Begin transaction
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'START TRANSACTION' );

		try {
			// Update all fields in the section
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->update(
				$table_name,
				array( 'enabled' => $enable ),
				array( 'section' => $section ),
				array( '%d' ),
				array( '%s' )
			);

			if ( $result !== false ) {
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( 'COMMIT' );

				// Set the message in a transient
				$message = $enable
					// translators: %s is the section name
					? sprintf( __( 'All fields in the %s section have been enabled.', 'shopglut' ), ucfirst( $section ) )
					// translators: %s is the section name
					: sprintf( __( 'All fields in the %s section have been disabled.', 'shopglut' ), ucfirst( $section ) );

				wp_send_json_success( array( 'message' => $message ) );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			} else {
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( 'ROLLBACK' );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				wp_send_json_error( __( 'Error updating fields.', 'shopglut' ) . ' ' . $wpdb->last_error );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			}
		} catch (Exception $e) {
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'ROLLBACK' );
			wp_send_json_error( __( 'Exception occurred while updating fields.', 'shopglut' ) );
		}
	}

	/**
	 * AJAX handler for toggling individual fields
	 */
	public function ajaxToggleCheckoutField() {
		// Check nonce
		if ( ! check_ajax_referer( 'shopglut_checkout_fields_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed.', 'shopglut' ) );
		}

		// Check if field ID is provided
		if ( empty( $_POST['field_id'] ) ) {
			wp_send_json_error( __( 'Field ID is required.', 'shopglut' ) );
		}

		// Toggle field status
		global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_name = $wpdb->prefix . 'shopglut_checkout_fields';
		$field_id = intval( $_POST['field_id'] );
		$enable = isset( $_POST['enable'] ) ? (int) $_POST['enable'] : 0;

		// Get field information for the message
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$field_info = $wpdb->get_row( $wpdb->prepare(
			"SELECT field_id, label FROM %s WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder -- Using %s instead of %i for compatibility, table name escaped with esc_sql()
			$table_name,
			$field_id
		) );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$table_name,
			array( 'enabled' => $enable ),
			array( 'id' => $field_id ),
			array( '%d' ),
			array( '%d' )
		);

		if ( $result === false ) {
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			wp_send_json_error( __( 'Error updating field.', 'shopglut' ) . ' ' . $wpdb->last_error );
		}

		// Prepare success message
		$field_name = $field_info ? ( $field_info->label ?: $field_info->field_id ) : __( 'Field', 'shopglut' );

		$message = $enable
			// translators: %s is the field name
			? sprintf( __( '%s has been enabled successfully.', 'shopglut' ), $field_name )
			// translators: %s is the field name
			: sprintf( __( '%s has been disabled successfully.', 'shopglut' ), $field_name );

		wp_send_json_success( array( 'message' => $message ) );
	}

	/**
	 * AJAX handler for reordering checkout fields
	 */
	public function ajaxReorderCheckoutFields() {
		// Check nonce
		if ( ! check_ajax_referer( 'shopglut_checkout_fields_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed.', 'shopglut' ) );
		}

		// Check if field order is provided
		if ( empty( $_POST['field_order'] ) ) {
			wp_send_json_error( __( 'Field order is required.', 'shopglut' ) );
		}

		// Check if section is provided
		if ( empty( $_POST['section'] ) ) {
			wp_send_json_error( __( 'Section is required.', 'shopglut' ) );
		}

		$field_order = json_decode( stripslashes( sanitize_text_field( wp_unslash( $_POST['field_order'] ) ) ), true );
		$section = sanitize_text_field( wp_unslash( $_POST['section'] ) );
		$is_block_field = isset( $_POST['is_block_field'] ) && sanitize_text_field( wp_unslash( $_POST['is_block_field'] ) ) === '1';

		if ( ! is_array( $field_order ) || empty( $field_order ) ) {
			wp_send_json_error( __( 'Invalid field order data.', 'shopglut' ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'shopglut_checkout_fields';
		$updated_count = 0;
		$errors = array();

		// Update field priorities based on new order
		foreach ( $field_order as $index => $field_id ) {
			// Handle both array format and direct field ID format
			if ( is_array( $field_id ) && isset( $field_id['id'] ) ) {
				$field_id = intval( $field_id['id'] );
			} else {
				$field_id = intval( $field_id );
			}

			if ( ! $field_id ) {
				continue;
			}

			$new_priority = ( $index + 1 ) * 10; // Start from 10, increment by 10

			// Update the field priority in database
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct update needed for field reordering, caching not beneficial for priority updates
			$result = $wpdb->update(
				$table_name,
				array( 'priority' => $new_priority ),
				array( 'id' => $field_id ),
				array( '%d' ),
				array( '%d' )
			);

			if ( $result === false ) {
				$errors[] = sprintf(
					// translators: %d is the field ID
					__( 'Failed to update field ID %d', 'shopglut' ),
					$field_id
				);
			} else {
				$updated_count++;
			}
		}

		// Check for errors
		if ( ! empty( $errors ) ) {
			wp_send_json_error( array(
				'message' => __( 'Some fields could not be reordered:', 'shopglut' ),
				'errors' => $errors,
				'updated_count' => $updated_count
			) );
		}

		// Success response
		$message = sprintf(
			// translators: %1$d is the number of fields updated, %2$s is the section name
			_n(
				'%1$d field in %2$s section has been reordered successfully.',
				'%1$d fields in %2$s section have been reordered successfully.',
				$updated_count,
				'shopglut'
			),
			$updated_count,
			ucfirst( $section )
		);

		wp_send_json_success( array(
			'message' => $message,
			'updated_count' => $updated_count,
			'section' => $section,
			'is_block_field' => $is_block_field
		) );
	}

	/**
	 * Get admin settings
	 * 
	 * @return array Settings
	 */
	public function getAdminSettings() {
		$settings = get_option( 'shopglut_checkout_fields_settings', array(
			'enable_label_override' => 1,
			'enable_placeholder_override' => 1,
			'enable_class_override' => 1,
			'enable_priority_override' => 1,
			'enable_required_override' => 1
		) );

		return $settings;
	}

	/**
	 * Save admin settings
	 * 
	 * @param array $settings Settings to save
	 * @return bool Whether the settings were saved
	 */
	public function saveAdminSettings( $settings ) {
		return update_option( 'shopglut_checkout_fields_settings', $settings );
	}

	/**
	 * Get settings for export
	 * 
	 * @return string Base64 encoded JSON
	 */
	public function getExportSettings() {
		$data = array(
			'fields' => $this->getAllFieldsFromDatabase(),
			'settings' => $this->getAdminSettings()
		);

		return base64_encode( json_encode( $data ) );
	}

	/**
	 * Import settings
	 * 
	 * @param string $data Base64 encoded JSON
	 * @return bool Whether the import was successful
	 */
	public function importSettings( $data ) {
		try {
			$decoded = json_decode( base64_decode( $data ), true );

			if ( empty( $decoded ) || ! isset( $decoded['fields'] ) || ! isset( $decoded['settings'] ) ) {
				return false;
			}

			// Import settings
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->saveAdminSettings( $decoded['settings'] );

			// Import fields
			global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$table_name = $wpdb->prefix . 'shopglut_checkout_fields';

			// Clear existing fields
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct query required for custom table operation
			$wpdb->query( $wpdb->prepare( "TRUNCATE TABLE %s", $table_name ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder -- Using %s instead of %i for compatibility, table name escaped with esc_sql()

			// Insert new fields
			foreach ( $decoded['fields'] as $field ) {
				unset( $field['id'] ); // Remove ID to get auto-increment

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->insert(
					$table_name,
					$field,
					array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d' )
				);
			}

			return true;
		} catch (
Exception $e) {
			// Import settings error
			return false;
		}
	}

	/**
	 * Get block checkout fields from database
	 *
	 * @return array Block checkout fields configuration
	 */
	public function getBlockCheckoutFieldsFromDatabase() {
		global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_name = $wpdb->prefix . 'shopglut_checkout_fields';

		// Check if table exists
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$table_name 
		) );

		if ( ! $table_exists ) {
			return array();
		}

		$fields = array();
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			sprintf("SELECT * FROM `%s` WHERE block_checkout = 1 AND enabled = 1 ORDER BY section, priority", esc_sql($table_name)), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Using sprintf with escaped table name
			ARRAY_A
		);

		if ( $results ) {
			foreach ( $results as $field ) {
				$section = $field['section'];
				$field_id = $field['field_id'];

				// Convert JSON options to array
				$options = ! empty( $field['options'] ) ? json_decode( $field['options'], true ) : array();
				
				// Ensure options is an array
				if ( ! is_array( $options ) ) {
					$options = array();
				}

				// Build field configuration
				$fields[ $section ][ $field_id ] = array(
					'type' => $field['type'],
					'label' => $field['label'],
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					'placeholder' => $field['placeholder'],
					'class' => ! empty( $field['class'] ) ? explode( ' ', trim( $field['class'] ) ) : array(),
					'required' => (bool) $field['required'],
					'priority' => (int) $field['priority'],
					'custom' => (bool) $field['custom'],
					'display_in_emails' => (bool) $field['display_in_emails'],
					'display_in_order' => (bool) $field['display_in_order'],
					'enabled' => (bool) $field['enabled'],
					'block_checkout' => (bool) $field['block_checkout'],
					'options' => $options,
				);
			}
		}

		return $fields;
	}

	/**
	 * Get block checkout field types
	 *
	 * @return array Supported block checkout field types
	 */
	public function getBlockFieldTypes() {
		return array(
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			'text' => 'Text',
			'select' => 'Select',
			'checkbox' => 'Checkbox'
		);
	}

	/**
	 * Add block checkout field
	 *
	 * @param array $field_data Field data
	 * @return int|false The ID of the inserted field or false on failure
	 */
	public function addBlockCheckoutField( $field_data ) {
		global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_name = $wpdb->prefix . 'shopglut_checkout_fields';

		// Set as block checkout field
		$field_data['block_checkout'] = 1;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		// Insert field
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->insert( $table_name, $field_data );

		if ( ! $result ) {
			return false;
		}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->insert_id;
	}

	/**
	 * Update block checkout field
	 *
	 * @param int $field_id Field ID
	 * @param array $field_data Field data
	 * @return bool Whether the update was successful
	 */
	public function updateBlockCheckoutField( $field_id, $field_data ) {
		global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_name = $wpdb->prefix . 'shopglut_checkout_fields';

		// Ensure it's a block checkout field
		$field_data['block_checkout'] = 1;

		// Update field
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$table_name,
			$field_data,
			array( 'id' => $field_id )
		);

		return $result !== false;
	}

	/**
	 * Delete block checkout field
	 *
	 * @param int $field_id Field ID
	 * @return bool Whether the deletion was successful
	 */
	public function deleteBlockCheckoutField( $field_id ) {
		global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_name = $wpdb->prefix . 'shopglut_checkout_fields';

		// Delete field
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$table_name,
			array( 'id' => $field_id )
		);

		return $result !== false;
	}

	/**
	 * Register custom checkout block fields with WooCommerce Blocks
	 */
	public function registerBlockCheckoutFields() {
		// Only load on frontend when WooCommerce Blocks is active
		if ( is_admin() || ! function_exists( 'wc_get_feature_list' ) || ! class_exists( 'Automattic\WooCommerce\Blocks\Package' ) ) {
			return;
		}
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		// Get block checkout fields
		$block_fields = $this->getBlockCheckoutFieldsFromDatabase();

		// No fields to register
		if ( empty( $block_fields ) ) {
			return;
		}

		// Enqueue script to register fields
		add_action( 'wp_enqueue_scripts', function () use ($block_fields) {
			// wp_enqueue_script(
			// 	'shopglut-block-checkout-fields',
			// 	plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/block-checkout-fields.js',
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			// 	array( 'wp-hooks', 'wc-blocks-checkout' ),
			// 	$this->version,
			// 	true
			// );

			// wp_localize_script( 'shopglut-block-checkout-fields', 'shopglutBlockFields', array(
			// 	'fields' => $block_fields
			// ));
		} );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * AJAX handler for updating field priorities
	 */
	public function ajaxUpdateFieldPriorities() {
		// Check nonce
		if ( ! check_ajax_referer( 'shopglut_checkout_fields_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed.', 'shopglut' ) );
		}
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		// Check required parameters
		if ( empty( $_POST['section'] ) || empty( $_POST['fields'] ) || ! is_array( $_POST['fields'] ) ) {
			wp_send_json_error( __( 'Invalid parameters.', 'shopglut' ) );
		}
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		$section = sanitize_text_field( wp_unslash( $_POST['section'] ) );
		$fields = isset( $_POST['fields'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['fields'] ) ) : array();
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		// Validate section
		if ( ! in_array( $section, $this->field_sections ) ) {
			wp_send_json_error( __( 'Invalid section.', 'shopglut' ) );
		}

		global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_name = $wpdb->prefix . 'shopglut_checkout_fields';

		// Begin transaction
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'START TRANSACTION' );

		try {
			foreach ( $fields as $field ) {
				if ( empty( $field['field_id'] ) ) {
					continue;
				}

				$field_id = $field['field_id'];
				$priority = intval( $field['priority'] );

				// Check if the field_id is a database ID (numeric) or a field key (string)
				if ( is_numeric( $field_id ) ) {
					// Update by database ID
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$result = $wpdb->update(
						$table_name,
						array( 'priority' => $priority ),
						array( 'id' => intval( $field_id ) ),
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
						array( '%d' ),
						array( '%d' )
					);
				} else {
					// Update by field key (like billing_first_name)
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$result = $wpdb->update(
						$table_name,
						array( 'priority' => $priority ),
						array( 'field_id' => $field_id, 'section' => $section ),
						array( '%d' ),
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
						array( '%s', '%s' )
					);
				}

				if ( $result === false ) {
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->query( 'ROLLBACK' );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					wp_send_json_error( __( 'Error updating field priorities.', 'shopglut' ) . ' ' . $wpdb->last_error );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				}
			}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'COMMIT' );
			wp_send_json_success( array( 'message' => __( 'Field priorities updated successfully.', 'shopglut' ) ) );
		} catch (Exception $e) {
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'ROLLBACK' );
			wp_send_json_error( __( 'Exception occurred while updating field priorities.', 'shopglut' ) );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		}
	}

	/**
	 * AJAX handler for updating block field priorities
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	 */
	public function ajaxUpdateBlockFieldPriorities() {
		// Check nonce
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! check_ajax_referer( 'shopglut_checkout_fields_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed.', 'shopglut' ) );
		}

		// Check required parameters
		if ( empty( $_POST['section'] ) || empty( $_POST['fields'] ) || ! is_array( $_POST['fields'] ) ) {
			wp_send_json_error( __( 'Invalid parameters.', 'shopglut' ) );
		}

		$section = sanitize_text_field( wp_unslash( $_POST['section'] ) );
		$fields = isset( $_POST['fields'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['fields'] ) ) : array();

		// Validate section
		if ( ! in_array( $section, $this->field_sections ) ) {
			wp_send_json_error( __( 'Invalid section.', 'shopglut' ) );
		}

		global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_name = $wpdb->prefix . 'shopglut_checkout_fields';

		// Begin transaction
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'START TRANSACTION' );

		try {
			foreach ( $fields as $field ) {
				if ( empty( $field['field_id'] ) ) {
					continue;
				}

				// Check if the field_id is numeric or a string identifier
				if ( is_numeric( $field['field_id'] ) ) {
					// Use database ID directly
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$result = $wpdb->update(
						$table_name,
						array( 'priority' => intval( $field['priority'] ) ),
						array( 'id' => intval( $field['field_id'] ) ),
						array( '%d' ),
						array( '%d' )
					);
				} else {
					// Use field identifier (like billing_first_name)
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$result = $wpdb->update(
						$table_name,
						array( 'priority' => intval( $field['priority'] ) ),
						array( 'field_id' => sanitize_text_field( $field['field_id'] ), 'section' => $section ),
						array( '%d' ),
						array( '%s', '%s' )
					);
				}

				if ( $result === false ) {
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->query( 'ROLLBACK' );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					wp_send_json_error( __( 'Error updating block field priorities.', 'shopglut' ) . ' ' . $wpdb->last_error );
				}
			}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'COMMIT' );
			wp_send_json_success( array( 'message' => __( 'Block field priorities updated successfully.', 'shopglut' ) ) );
		} catch (Exception $e) {
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'ROLLBACK' );
			wp_send_json_error( __( 'Exception occurred while updating field priorities.', 'shopglut' ) );
		}
	}

	/**
	 * Render the admin page for checkout field editor
	 */
	public function renderAdminPage() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Get the active tab
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'fields';

		// Transient message functionality removed

		// Process settings form submissions
		if ( isset( $_POST['save_settings'] ) && isset( $_POST['shopglut_checkout_fields_nonce'] ) ) {
			if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['shopglut_checkout_fields_nonce'] ) ), 'shopglut_checkout_fields_action' ) ) {
				$settings = array(
					'enable_label_override' => isset( $_POST['enable_label_override'] ) ? 1 : 0,
					'enable_placeholder_override' => isset( $_POST['enable_placeholder_override'] ) ? 1 : 0,
					'enable_class_override' => isset( $_POST['enable_class_override'] ) ? 1 : 0,
					'enable_priority_override' => isset( $_POST['enable_priority_override'] ) ? 1 : 0,
					'enable_required_override' => isset( $_POST['enable_required_override'] ) ? 1 : 0
				);

				if ( $this->saveAdminSettings( $settings ) ) {
					add_action( 'admin_notices', function () {
						echo '<div class="notice notice-success is-dismissible"><p>' .
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
							esc_html__( 'Settings saved successfully.', 'shopglut' ) .
							'</p></div>';
					} );
				}
			}
		}

		// Process import form submission
		if ( isset( $_POST['import_settings'] ) && isset( $_POST['shopglut_checkout_fields_nonce'] ) ) {
			if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['shopglut_checkout_fields_nonce'] ) ), 'shopglut_checkout_fields_action' ) ) {
				if ( isset( $_POST['import_data'] ) && ! empty( $_POST['import_data'] ) ) {
					if ( $this->importSettings( sanitize_textarea_field( wp_unslash( $_POST['import_data'] ) ) ) ) {
						add_action( 'admin_notices', function () {
							echo '<div class="notice notice-success is-dismissible"><p>' .
									esc_html__( 'Settings imported successfully.', 'shopglut' ) .
									'</p></div>';
						} );
					} else {
						add_action( 'admin_notices', function () {
							echo '<div class="notice notice-error is-dismissible"><p>' .
									esc_html__( 'Error importing settings. Invalid data format.', 'shopglut' ) .
									'</p></div>';
						} );
					}
				}
			}
		}

		// Process form submission
		$this->processFormSubmission();

		// Get current fields
		$custom_fields = $this->getCustomFieldsFromDatabase();
		$default_fields = $this->getDefaultWooCommerceFields();

		// Get admin settings
		$settings = $this->getAdminSettings();

		// Include admin template
		include_once dirname( __FILE__ ) . '/admin-page.php';
	}

	/**
	 * Get default WooCommerce checkout fields
	 *
	 * @return array Default WooCommerce fields
	 */
	private function getDefaultWooCommerceFields() {
		if ( ! function_exists( 'WC' ) || ! WC()->checkout() ) {
			return array();
		}
		
		$checkout = WC()->checkout();
		return $checkout->get_checkout_fields();
	}

	/**
	 * Get core field data from database
	 *
	 * @param string $field_key Field key (e.g., 'first_name', 'email')
	 * @return array|false Field data or false if not found
	 */
	public function getCoreFieldFromDatabase( $field_key ) {
		global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_name = $wpdb->prefix . 'shopglut_checkout_fields';

		// Sanitize field key
		$field_key = sanitize_key( $field_key );

		// Try to find the field in billing section first
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$field = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM %s WHERE field_id = %s OR field_id = %s OR field_id = %s", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder -- Using %s instead of %i for compatibility, table name escaped with esc_sql()
			$table_name,
			$field_key,
			'billing_' . $field_key,
			'shipping_' . $field_key
		), ARRAY_A );

		return $field ?: false;
	}

	/**
	 * Process form submission for field editor
	 */
	private function processFormSubmission() {
		if ( ! isset( $_POST['shopglut_checkout_fields_nonce'] ) ||
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['shopglut_checkout_fields_nonce'] ) ), 'shopglut_checkout_fields_action' ) ) {
			return;
		}

		global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_name = $wpdb->prefix . 'shopglut_checkout_fields';

		// Set up variables to track action result for redirect
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe admin page check with sanitization
		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : $this->menu_slug;
		$redirect_args = array(
			'page' => $current_page,
			'tab' => isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'fields',
		);
		$action_result = 'success';
		$action_type = '';

		// Handle field addition
		if ( isset( $_POST['add_field'] ) ) {
			$section = isset( $_POST['new_field_section'] ) ? sanitize_text_field( wp_unslash( $_POST['new_field_section'] ) ) : '';
			$field_id = isset( $_POST['new_field_id'] ) ? sanitize_key( wp_unslash( $_POST['new_field_id'] ) ) : '';

			// Add section prefix if not present
			if ( strpos( $field_id, $section . '_' ) !== 0 ) {
				$field_id = $section . '_' . $field_id;
			}

			$type = isset( $_POST['new_field_type'] ) ? sanitize_text_field( wp_unslash( $_POST['new_field_type'] ) ) : 'text';
			$label = isset( $_POST['new_field_label'] ) ? sanitize_text_field( wp_unslash( $_POST['new_field_label'] ) ) : '';
			$placeholder = isset( $_POST['new_field_placeholder'] ) ? sanitize_text_field( wp_unslash( $_POST['new_field_placeholder'] ) ) : '';
			$class = isset( $_POST['new_field_class'] ) ? sanitize_text_field( wp_unslash( $_POST['new_field_class'] ) ) : '';
			$required = isset( $_POST['new_field_required'] ) ? 1 : 0;
			$priority = isset( $_POST['new_field_priority'] ) ? intval( $_POST['new_field_priority'] ) : 20;
			$options = '';

			// Process options for select, multiselect, radio, checkbox_group
			if ( in_array( $type, array( 'select', 'multiselect', 'radio', 'checkbox_group' ) ) && ! empty( $_POST['new_field_options'] ) ) {
				$options_array = array();
				$lines = explode( "\n", sanitize_textarea_field( wp_unslash( $_POST['new_field_options'] ) ) );

				foreach ( $lines as $line ) {
					$line = trim( $line );
					if ( ! empty( $line ) ) {
						$parts = explode( '|', $line );
						$key = trim( $parts[0] );
						$value = isset( $parts[1] ) ? trim( $parts[1] ) : $key;
						$options_array[ $key ] = $value;
					}
				}

				$options = json_encode( $options_array );
			}

			$validation = isset( $_POST['new_field_validation'] ) ?
				sanitize_text_field( implode( ',', array_map( 'sanitize_text_field', wp_unslash( $_POST['new_field_validation'] ) ) ) ) :
				'';
			$display_in_emails = isset( $_POST['new_field_display_in_emails'] ) ? 1 : 0;
			$display_in_order = isset( $_POST['new_field_display_in_order'] ) ? 1 : 0;

			// Check if field already exists
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM %s WHERE field_id = %s AND section = %s", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder -- Using %s instead of %i for compatibility, table name escaped with esc_sql()
				$table_name,
				$field_id,
				$section
			) );

			if ( $existing ) {
				$action_type = 'add';
				$action_result = 'error';
				$message = __( 'A field with this ID already exists in this section.', 'shopglut' );
			} else {
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$result = $wpdb->insert(
					$table_name,
					array(
						'section' => $section,
						'field_id' => $field_id,
						'type' => $type,
						'label' => $label,
						'placeholder' => $placeholder,
						'class' => $class,
						'required' => $required,
						'priority' => $priority,
						'options' => $options,
						'validation' => $validation,
						'enabled' => 1,
						'custom' => 1,
						'display_in_emails' => $display_in_emails,
						'display_in_order' => $display_in_order,
						'block_checkout' => 0
					),
					array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d' )
				);

				// Set action type and result for the redirect
				$action_type = 'add';
				$action_result = $result ? 'success' : 'error';
				$message = $result ?
					__( 'Checkout field added successfully.', 'shopglut' ) :
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					__( 'Error adding checkout field.', 'shopglut' ) . ' ' . $wpdb->last_error;
			}
			
			$redirect_args['section'] = $section;
			
			// Transient message storage removed
		}

		// Handle block field addition
		if ( isset( $_POST['add_block_field'] ) ) {
			$section = isset( $_POST['new_block_field_section'] ) ? sanitize_text_field( wp_unslash( $_POST['new_block_field_section'] ) ) : '';
			$field_id = isset( $_POST['new_block_field_id'] ) ? sanitize_key( wp_unslash( $_POST['new_block_field_id'] ) ) : '';

			// Add section prefix if not present
			if ( strpos( $field_id, $section . '_' ) !== 0 ) {
				$field_id = $section . '_' . $field_id;
			}

			$type = isset( $_POST['new_block_field_type'] ) ? sanitize_text_field( wp_unslash( $_POST['new_block_field_type'] ) ) : 'text';
			$label = isset( $_POST['new_block_field_label'] ) ? sanitize_text_field( wp_unslash( $_POST['new_block_field_label'] ) ) : '';
			$placeholder = isset( $_POST['new_block_field_placeholder'] ) ? sanitize_text_field( wp_unslash( $_POST['new_block_field_placeholder'] ) ) : '';
			$required = isset( $_POST['new_block_field_required'] ) ? 1 : 0;
			$priority = isset( $_POST['new_block_field_priority'] ) ? intval( $_POST['new_block_field_priority'] ) : 20;
			$options = '';

			// Process options for select, multiselect, radio, checkbox_group
			if ( in_array( $type, array( 'select', 'multiselect', 'radio', 'checkbox_group' ) ) && ! empty( $_POST['new_block_field_options'] ) ) {
				$options_array = array();
				$lines = explode( "\n", sanitize_textarea_field( wp_unslash( $_POST['new_block_field_options'] ) ) );

				foreach ( $lines as $line ) {
					$line = trim( $line );
					if ( ! empty( $line ) ) {
						$parts = explode( '|', $line );
						$key = trim( $parts[0] );
						$value = isset( $parts[1] ) ? trim( $parts[1] ) : $key;
						$options_array[ $key ] = $value;
					}
				}

				$options = json_encode( $options_array );
			}

			$display_in_emails = isset( $_POST['new_block_field_display_in_emails'] ) ? 1 : 0;
			$display_in_order = isset( $_POST['new_block_field_display_in_order'] ) ? 1 : 0;

			// Check if field already exists
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM %s WHERE field_id = %s AND section = %s", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder -- Using %s instead of %i for compatibility, table name escaped with esc_sql()
				$table_name,
				$field_id,
				$section
			) );

			if ( $existing ) {
				$action_type = 'add';
				$action_result = 'error';
				$message = __( 'A field with this ID already exists in this section.', 'shopglut' );
			} else {
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$result = $wpdb->insert(
					$table_name,
					array(
						'section' => $section,
						'field_id' => $field_id,
						'type' => $type,
						'label' => $label,
						'placeholder' => $placeholder,
						'class' => '',  // Block fields don't use class
						'required' => $required,
						'priority' => $priority,
						'options' => $options,
						'validation' => '',  // Block fields don't use validation
						'enabled' => 1,
						'custom' => 1,
						'display_in_emails' => $display_in_emails,
						'display_in_order' => $display_in_order,
						'block_checkout' => 1  // This is the key difference for block fields
					),
					array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d' )
				);

				// Set action type and result for the redirect
				$action_type = 'add';
				$action_result = $result ? 'success' : 'error';
				$message = $result ?
					__( 'Block checkout field added successfully.', 'shopglut' ) :
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					__( 'Error adding block checkout field.', 'shopglut' ) . ' ' . $wpdb->last_error;
			}

			$redirect_args['section'] = $section;

			// Transient message storage removed
		}


		// Add action type and result to redirect arguments
		if ( ! empty( $action_type ) ) {
			$redirect_args['action'] = $action_type;
			$redirect_args['result'] = $action_result;

			// Redirect to avoid form resubmission
			wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
			exit;
		}
	}

	/**
	 * Render welcome page
	 */
	public function render_welcome_page() {
		$welcome_page = new \CheckoutGlut\WelcomePage();
		$welcome_page->render_welcome_content();
	}

	/**
	 * Get singleton instance
	 *
	 * @return CheckoutFieldsManager
	 */
	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
