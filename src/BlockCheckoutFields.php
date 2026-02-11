<?php
namespace CheckoutGlut;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle WooCommerce Block Checkout Fields
 */
class BlockCheckoutFields {
	/**
	 * Initialize hooks
	 */
	public function __construct() {
		add_action( 'woocommerce_blocks_checkout_block_registration', array( $this, 'update_block_checkout_fields' ), 999 );

		// Create default block fields on plugin activation or init
		add_action( 'admin_init', array( $this, 'ensure_default_block_fields' ) );
	}

	/**
	 * Ensure default block checkout fields exist in database
	 */
	public function ensure_default_block_fields() {
		// Only run in admin
		if ( ! is_admin() ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'shopglut_checkout_fields';

		// Get the default fields with caching
		$cache_key = 'shopglut_default_block_fields';
		$default_fields = wp_cache_get( $cache_key );
		if ( false === $default_fields ) {
			$default_fields = $this->get_default_block_fields();
			wp_cache_set( $cache_key, $default_fields, '', 3600 ); // Cache for 1 hour
		}

		// For each section and field, check if it exists in the database
		foreach ( $default_fields as $section => $fields ) {
			foreach ( $fields as $field_id => $field ) {
				$cache_key = "shopglut_field_exists_{$field_id}_{$section}";
				$exists = wp_cache_get( $cache_key );

				if ( false === $exists ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for field existence check, using %i for table name
					$exists = $wpdb->get_var( $wpdb->prepare(
						"SELECT id FROM {$wpdb->prefix}shopglut_checkout_fields WHERE field_id = %s AND section = %s AND block_checkout = %d",
						$field_id,
						$section,
						1
					) );
					wp_cache_set( $cache_key, $exists, '', 300 ); // Cache for 5 minutes
				}

				// If field doesn't exist, add it
				if ( ! $exists ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Inserting default field configurations, not user data
					$wpdb->insert(
						$table_name,
						array(
							'section' => $section,
							'field_id' => $field_id,
							'type' => $field['type'],
							'label' => $field['label'],
							'placeholder' => $field['placeholder'],
							'class' => is_array( $field['class'] ) ? implode( ' ', $field['class'] ) : $field['class'],
							'required' => (int) $field['required'],
							'priority' => (int) $field['priority'],
							'options' => is_array( $field['options'] ) ? json_encode( $field['options'] ) : $field['options'],
							'validation' => is_array( $field['validation'] ) ? implode( ',', $field['validation'] ) : $field['validation'],
							'enabled' => 1,
							'custom' => 0, // Default fields are not custom
							'block_checkout' => 1,
							'display_in_emails' => 1,
							'display_in_order' => 1
						),
						array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d' )
					);

					// Clear the existence cache after insertion
					wp_cache_delete( "shopglut_field_exists_{$field_id}_{$section}" );
				}
			}
		}
	}

	/**
	 * Get default block checkout fields
	 * 
	 * @return array Default fields configuration
	 */
	public function get_default_block_fields() {
		return array(
			'contact' => array(
				'contact_email' => array(
					'type' => 'text',
					'label' => __( 'Email address', 'shopglut' ),
					'placeholder' => __( 'Enter your email address', 'shopglut' ),
					'class' => 'form-row-wide',
					'required' => true,
					'priority' => 10,
					'options' => '',
					'validation' => 'text',
				),
			),
			'address' => array(
				'address_first_name' => array(
					'type' => 'text',
					'label' => __( 'First name', 'shopglut' ),
					'placeholder' => '',
					'class' => 'form-row-first',
					'required' => true,
					'priority' => 10,
					'options' => '',
					'validation' => '',
				),
				'address_last_name' => array(
					'type' => 'text',
					'label' => __( 'Last name', 'shopglut' ),
					'placeholder' => '',
					'class' => 'form-row-last',
					'required' => true,
					'priority' => 20,
					'options' => '',
					'validation' => '',
				),
				'address_company' => array(
					'type' => 'text',
					'label' => __( 'Company', 'shopglut' ),
					'placeholder' => __( 'Company name (optional)', 'shopglut' ),
					'class' => 'form-row-wide',
					'required' => false,
					'priority' => 30,
					'options' => '',
					'validation' => '',
				),
				'address_address_1' => array(
					'type' => 'text',
					'label' => __( 'Street address', 'shopglut' ),
					'placeholder' => __( 'House number and street name', 'shopglut' ),
					'class' => 'form-row-wide',
					'required' => true,
					'priority' => 40,
					'options' => '',
					'validation' => '',
				),
				'address_address_2' => array(
					'type' => 'text',
					'label' => __( 'Apartment, suite, etc.', 'shopglut' ),
					'placeholder' => __( 'Apartment, suite, unit, etc. (optional)', 'shopglut' ),
					'class' => 'form-row-wide',
					'required' => false,
					'priority' => 50,
					'options' => '',
					'validation' => '',
				),
				'address_city' => array(
					'type' => 'text',
					'label' => __( 'City', 'shopglut' ),
					'placeholder' => '',
					'class' => 'form-row-wide',
					'required' => true,
					'priority' => 60,
					'options' => '',
					'validation' => '',
				),
				'address_state' => array(
					'type' => 'text',
					'label' => __( 'State / Province', 'shopglut' ),
					'placeholder' => '',
					'class' => 'form-row-wide',
					'required' => true,
					'priority' => 70,
					'options' => '',
					'validation' => '',
				),
				'address_postcode' => array(
					'type' => 'text',
					'label' => __( 'Postcode / ZIP', 'shopglut' ),
					'placeholder' => '',
					'class' => 'form-row-wide',
					'required' => true,
					'priority' => 80,
					'options' => '',
					'validation' => 'postcode',
				),
				'address_country' => array(
					'type' => 'select',
					'label' => __( 'Country / Region', 'shopglut' ),
					'placeholder' => '',
					'class' => 'form-row-wide',
					'required' => true,
					'priority' => 90,
					'options' => array(), // Country list would be populated dynamically by WooCommerce
					'validation' => '',
				),
				'address_phone' => array(
					'type' => 'text',
					'label' => __( 'Phone', 'shopglut' ),
					'placeholder' => __( 'Enter your phone number', 'shopglut' ),
					'class' => 'form-row-wide',
					'required' => false,
					'priority' => 100,
					'options' => '',
					'validation' => 'phone',
				),
			),
		);
	}

	public function update_block_checkout_fields() {
		if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Package' ) ||
			! class_exists( 'Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry' ) ||
			! class_exists( 'Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields' ) ) {
			return;
		}

		$checkout_fields = \Automattic\WooCommerce\Blocks\Package::container()->get( \Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class);
		$asset_data_registry = \Automattic\WooCommerce\Blocks\Package::container()->get( \Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry::class);

		// Get core fields and modify them
		$default_fields = $this->get_core_fields();

		foreach ( $default_fields as $key => &$field ) {
			$this->modify_core_field( $key, $field );
		}

		// Register additional fields from database
		$this->register_additional_fields();

		// Update the registry
		$asset_data_registry->add( 'defaultFields', array_merge( $default_fields, $checkout_fields->get_additional_fields() ) );
	}

	private function get_core_fields() {
		return array(
			'first_name' => array(
				'label' => __( 'First name', 'shopglut' ),
				'optionalLabel' => __( 'First name (optional)', 'shopglut' ),
				'required' => true,
				'hidden' => false,
				'index' => 10,
			),
			'last_name' => array(
				'label' => __( 'Last name', 'shopglut' ),
				'optionalLabel' => __( 'Last name (optional)', 'shopglut' ),
				'required' => true,
				'hidden' => false,
				'index' => 20,
			),
			'company' => array(
				'label' => __( 'Company', 'shopglut' ),
				'optionalLabel' => __( 'Company (optional)', 'shopglut' ),
				'required' => false,
				'hidden' => false,
				'index' => 30,
			),
			'country' => array(
				'label' => __( 'Country/Region', 'shopglut' ),
				'optionalLabel' => __( 'Country/Region (optional)', 'shopglut' ),
				'required' => true,
				'hidden' => false,
				'index' => 40,
			),
			'address_1' => array(
				'label' => __( 'Address', 'shopglut' ),
				'optionalLabel' => __( 'Address (optional)', 'shopglut' ),
				'required' => true,
				'hidden' => false,
				'index' => 50,
			),
			'address_2' => array(
				'label' => __( 'Apartment, suite, etc.', 'shopglut' ),
				'optionalLabel' => __( 'Apartment, suite, etc. (optional)', 'shopglut' ),
				'required' => false,
				'hidden' => false,
				'index' => 60,
			),
			'city' => array(
				'label' => __( 'City', 'shopglut' ),
				'optionalLabel' => __( 'City (optional)', 'shopglut' ),
				'required' => true,
				'hidden' => false,
				'index' => 70,
			),
			'state' => array(
				'label' => __( 'State/County', 'shopglut' ),
				'optionalLabel' => __( 'State/County (optional)', 'shopglut' ),
				'required' => true,
				'hidden' => false,
				'index' => 80,
			),
			'postcode' => array(
				'label' => __( 'Postcode', 'shopglut' ),
				'optionalLabel' => __( 'Postcode (optional)', 'shopglut' ),
				'required' => true,
				'hidden' => false,
				'index' => 90,
			),
			'phone' => array(
				'label' => __( 'Phone', 'shopglut' ),
				'optionalLabel' => __( 'Phone (optional)', 'shopglut' ),
				'required' => false,
				'hidden' => false,
				'index' => 100,
			),
			'email' => array(
				'label' => __( 'Email address', 'shopglut' ),
				'optionalLabel' => __( 'Email address (optional)', 'shopglut' ),
				'required' => true,
				'hidden' => false,
				'index' => 110,
			),
		);
	}

	private function modify_core_field( $key, &$field ) {
		// Map core field key to block checkout field ID
		$block_field_map = [ 
			'first_name' => 'address_first_name',
			'last_name' => 'address_last_name',
			'company' => 'address_company',
			'country' => 'address_country',
			'state' => 'address_state',
			'address_1' => 'address_address_1',
			'address_2' => 'address_address_2',
			'city' => 'address_city',
			'postcode' => 'address_postcode',
			'phone' => 'contact_phone',
			'email' => 'contact_email'
		];

		// Get block checkout fields from database
		$fields_manager = CheckoutFieldsManager::get_instance();

		// Look for the corresponding block field in database
		if ( isset( $block_field_map[ $key ] ) ) {
			$block_field_id = $block_field_map[ $key ];
			$section = '';

			// Determine section based on prefix
			if ( strpos( $block_field_id, 'address_' ) === 0 ) {
				$section = 'address';
			} elseif ( strpos( $block_field_id, 'contact_' ) === 0 ) {
				$section = 'contact';
			} elseif ( strpos( $block_field_id, 'order_' ) === 0 ) {
				$section = 'order';
			}

			// Get field data from database with caching
			if ( ! empty( $section ) ) {
				$cache_key = "shopglut_block_field_{$block_field_id}_{$section}";
				$db_field = wp_cache_get( $cache_key );

				if ( false === $db_field ) {
					global $wpdb;
					$table_name = $wpdb->prefix . 'shopglut_checkout_fields';

					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for field data lookup, using %i for table name
					$db_field = $wpdb->get_row( $wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}shopglut_checkout_fields WHERE field_id = %s AND section = %s AND block_checkout = %d",
						$block_field_id,
						$section,
						1
					), ARRAY_A );

					wp_cache_set( $cache_key, $db_field, '', 300 ); // Cache for 5 minutes
				}

				if ( $db_field ) {
					// Apply database values to core field
					$field['label'] = $db_field['label'];
					$field['optionalLabel'] = $db_field['label'] . ' ' . esc_html__( '(optional)', 'shopglut' );
					$field['required'] = (bool) $db_field['required'];
					$field['hidden'] = ! (bool) $db_field['enabled'];
					$field['index'] = (int) $db_field['priority'];
				}
			}
		}
	}

	private function register_additional_fields() {
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}

		// Get fields from database with caching
		$cache_key = 'shopglut_additional_block_fields';
		$fields = wp_cache_get( $cache_key );

		if ( false === $fields ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'shopglut_checkout_fields';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for additional fields lookup, using %i for table name
			$fields = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}shopglut_checkout_fields
				WHERE block_checkout = %d
				AND enabled = %d
				AND field_id NOT IN (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
				ORDER BY section, priority",
				1,
				1,
				'contact_email', 'contact_phone', 'address_first_name', 'address_last_name',
				'address_company', 'address_country', 'address_state', 'address_address_1', 'address_address_2',
				'address_city', 'address_postcode', 'order_comments'
			), ARRAY_A );

			wp_cache_set( $cache_key, $fields, '', 600 ); // Cache for 10 minutes
		}


		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				// Map section to location
				$location = $this->map_section_to_location( $field['section'] );

				// Format options for WC Blocks
				$options = array();
				if ( ! empty( $field['options'] ) ) {
					$opts = json_decode( $field['options'], true );
					if ( is_array( $opts ) ) {
						foreach ( $opts as $value => $label ) {
							$options[] = array(
								'value' => $value,
								'label' => $label
							);
						}
					}
				}

				// Create field config
				$field_config = array(
					'id' => 'shopglut/' . str_replace( '_', '-', $field['field_id'] ),
					'label' => $field['label'],
					'optionalLabel' => $field['label'] . ' ' . esc_html__( '(optional)', 'shopglut' ),
					'placeholder' => $field['placeholder'],
					'location' => $location,
					'type' => $this->map_field_type( $field['type'] ),
					'required' => (bool) $field['required'],
					'index' => (int) $field['priority'],
				);

				// Add options if needed
				if ( ! empty( $options ) ) {
					$field_config['options'] = $options;
				}

				woocommerce_register_additional_checkout_field( $field_config );
			}
		}
	}

	/**
	 * Map section to location for WC Blocks
	 */
	private function map_section_to_location( $section ) {
		$location_map = array(
			'billing' => 'address',
			'shipping' => 'address',
			'additional' => 'order'
		);

		return isset( $location_map[ $section ] ) ? $location_map[ $section ] : $section;
	}

	/**
	 * Map field type to WC Blocks compatible type
	 */
	private function map_field_type( $type ) {
		$type_map = array(
			'text' => 'text',
			'textarea' => 'textarea',
			'select' => 'select',
			'radio' => 'radio',
			'checkbox' => 'checkbox',
			'email' => 'email',
			'tel' => 'tel',
			'number' => 'number',
		);

		return isset( $type_map[ $type ] ) ? $type_map[ $type ] : 'text';
	}

	public static function get_instance() {
		static $instance;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}
}