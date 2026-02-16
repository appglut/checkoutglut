<?php
namespace CheckoutGlut;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to manage the display of custom checkout fields
 * across various WooCommerce interfaces
 */
class CheckoutFieldsDisplay {

	/**
	 * Field sections in WooCommerce checkout
	 *
	 * @var array
	 */
	private $field_sections = array( 'billing', 'shipping', 'additional', 'address', 'contact', 'order' );

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialize hooks for displaying custom fields
		$this->init_hooks();
	}

	/**
	 * Initialize hooks for displaying custom fields
	 */
	private function init_hooks() {

		// Add fields to emails
		add_action( 'woocommerce_email_after_order_table', array( $this, 'display_custom_fields_in_emails' ), 10, 4 );

		// REVISED: Add hardcoded content to the Order Complete Page order details table
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'add_content_to_order_details' ), 10, 1 );

		// REVISED: Add hardcoded content to the admin billing section
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'add_content_to_admin_billing' ), 20, 1 );

		// REVISED: Add hardcoded content to order emails in the order details section
		add_action( 'woocommerce_email_order_details', array( $this, 'add_content_to_email_order_details' ), 20, 4 );

	}


	/**
	 * REVISED: Add hardcoded content to order details table on Order Complete Page
	 */
	public function add_content_to_order_details( $order ) {
		// Only run if we have a valid order
		if ( ! $order )
			return;

		echo '<table class="woocommerce-table woocommerce-table--custom-fields shop_table custom-fields">';
		echo '<thead><tr><th colspan="2">Additional Order Informationdd</th></tr></thead>';
		echo '<tbody>';

		// Row 1
		echo '<tr>';
		echo '<th>Order Processing:</th>';
		echo '<td>Your order will be processed within 24-48 hours</td>';
		echo '</tr>';

		// Row 2
		echo '<tr>';
		echo '<th>Shipping Method:</th>';
		echo '<td>Standard Shipping (3-5 business days)</td>';
		echo '</tr>';

		// Row 3
		echo '<tr>';
		echo '<th>Customer Support:</th>';
		echo '<td>support@example.com or call 1-800-123-4567</td>';
		echo '</tr>';

		// Row 4
		echo '<tr>';
		echo '<th>Return Policy:</th>';
		echo '<td>30-day money back guarantee on all purchases</td>';
		echo '</tr>';

		echo '</tbody>';
		echo '</table>';
	}

	/**
	 * REVISED: Add hardcoded content to admin billing section
	 */
	public function add_content_to_admin_billing( $order ) {
		echo '<div class="billing-section-custom-notice" style="margin-top: 15px; padding: 10px; background-color: #e7f4ff; border: 1px solid #c5d9ed;">';
		echo '<h4 style="margin-top: 0; color: #2271b1; border-bottom: 1px solid #c5d9ed; padding-bottom: 5px;">Billing Section Information</h4>';

		echo '<table class="widefat" style="margin-top: 10px; border: none; background: transparent;">';
		echo '<tbody>';

		// Row 1
		echo '<tr>';
		echo '<td style="width: 200px;"><strong>Payment Verification:</strong></td>';
		echo '<td>Completed on ' . esc_html(gmdate( 'F j, Y' )) . '</td>';
		echo '</tr>';

		// Row 2
		echo '<tr>';
		echo '<td><strong>Fraud Check Status:</strong></td>';
		echo '<td>Passed - Low Risk</td>';
		echo '</tr>';

		// Row 3
		echo '<tr>';
		echo '<td><strong>Customer Tier:</strong></td>';
		echo '<td>Standard Account</td>';
		echo '</tr>';

		// Row 4
		echo '<tr>';
		echo '<td><strong>Sales Rep:</strong></td>';
		echo '<td>Online Order - No Representative</td>';
		echo '</tr>';

		echo '</tbody>';
		echo '</table>';
		echo '</div>';
	}

	/**
	 * REVISED: Add hardcoded content to order emails in the order details section
	 */
	public function add_content_to_email_order_details( $order, $sent_to_admin, $plain_text, $email ) {
		if ( $plain_text ) {
			echo "\n\n=== ORDER DETAILS ADDITIONAL INFORMATION ===\n\n";
			echo "Payment Verification: Completed\n";
			echo "Processing Status: In queue\n";
			echo "Delivery Information: Standard Shipping (3-5 business days)\n";
			echo "Order Notes: Thank you for your order! We'll process it right away.\n\n";
			echo "====================================\n\n";
		} else {
			echo '<h2 style="color: #96588a; display: block; font-size: 18px; font-weight: bold; line-height: 130%; margin: 16px 0 8px; text-transform: uppercase;">Order Details Additional Information</h2>';

			echo '<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: Helvetica, Roboto, Arial, sans-serif; color: #636363; border: 1px solid #e5e5e5; margin-bottom: 25px;">';
			echo '<thead>';
			echo '<tr>';
			echo '<th class="td" scope="col" style="text-align: left; color: #636363; border: 1px solid #e5e5e5; padding: 12px; background-color: #f8f8f8;">Information</th>';
			echo '<th class="td" scope="col" style="text-align: left; color: #636363; border: 1px solid #e5e5e5; padding: 12px; background-color: #f8f8f8;">Details</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			// Row 1
			echo '<tr>';
			echo '<td class="td" style="text-align: left; vertical-align: middle; border: 1px solid #e5e5e5; padding: 12px;"><strong>Payment Status</strong></td>';
			echo '<td class="td" style="text-align: left; vertical-align: middle; border: 1px solid #e5e5e5; padding: 12px;">Verified</td>';
			echo '</tr>';

			// Row 2
			echo '<tr>';
			echo '<td class="td" style="text-align: left; vertical-align: middle; border: 1px solid #e5e5e5; padding: 12px;"><strong>Processing Time</strong></td>';
			echo '<td class="td" style="text-align: left; vertical-align: middle; border: 1px solid #e5e5e5; padding: 12px;">24-48 hours</td>';
			echo '</tr>';

			// Row 3
			echo '<tr>';
			echo '<td class="td" style="text-align: left; vertical-align: middle; border: 1px solid #e5e5e5; padding: 12px;"><strong>Shipping Method</strong></td>';
			echo '<td class="td" style="text-align: left; vertical-align: middle; border: 1px solid #e5e5e5; padding: 12px;">Standard (3-5 business days)</td>';
			echo '</tr>';

			// Row 4
			echo '<tr>';
			echo '<td class="td" style="text-align: left; vertical-align: middle; border: 1px solid #e5e5e5; padding: 12px;"><strong>Customer Support</strong></td>';
			echo '<td class="td" style="text-align: left; vertical-align: middle; border: 1px solid #e5e5e5; padding: 12px;">support@example.com</td>';
			echo '</tr>';

			echo '</tbody>';
			echo '</table>';
		}
	}


	public function display_custom_fields_in_emails( $order, $sent_to_admin, $plain_text, $email ) {
		$order_id = $order->get_id();

		// Define fields to display
		$fields = array(
			'billing_company_size' => __( 'Company Size', 'shopglut' ),
			'shipping_delivery_notes' => __( 'Delivery Notes', 'shopglut' ),
			'order_notes' => __( 'Order Notes', 'shopglut' ),
			'gift_message' => __( 'Gift Message', 'shopglut' ),
			'is_gift' => __( 'Is Gift', 'shopglut' ),
			'contact_preferred_time' => __( 'Best Time to Contact', 'shopglut' ),
			'contact_preferred_times' => __( 'Best Time to Contact (Order)', 'shopglut' )
		);


		// $field_values = array();

		// foreach ( $fields as $field_key => $field_label ) {
		// 	// Try both with and without underscore prefix
		// 	$value = get_post_meta( $order_id, '_' . $field_key, true );
		// 	if ( empty( $value ) ) {
		// 		$value = get_post_meta( $order_id, $field_key, true );
		// 	}

		// 	// Also check if it's stored with the full field ID
		// 	if ( empty( $value ) ) {
		// 		$original_id = array_search( $field_key, $this->custom_fields );
		// 		if ( $original_id ) {
		// 			$value = get_post_meta( $order_id, $original_id, true );
		// 		}
		// 	}

		// 	if ( ! empty( $value ) ) {
		// 		$field_values[ $field_label ] = $value;
		// 	}
		// }

		// if ( ! empty( $field_values ) ) {
		// 	if ( $plain_text ) {
		// 		echo "\n" . esc_html__( 'ggg', 'shopglut' ) . "\n\n";

		// 		foreach ( $field_values as $label => $value ) {
		// 			echo $label . ': ' . $value . "\n";
		// 		}

		// 		echo "\n";
		// 	} else {
		// 		echo '<h2>' . esc_html__( 'Additional Order Informationsss', 'shopglut' ) . '</h2>';
		// 		echo '<ul>';

		// 		foreach ( $field_values as $label => $value ) {
		// 			echo '<li><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</li>';
		// 		}

		// 		echo '</ul>';
		// 	}
		// }
	}

	/**
	 * Get all custom field values for an order
	 *
	 * @param int $order_id Order ID
	 * @return array Custom field values
	 */
	private function get_custom_field_values( $order_id ) {
		// Get all post meta for this order
		$order_meta = get_post_meta( $order_id );

		// Filter and extract custom field values
		$custom_values = array();

		foreach ( $order_meta as $meta_key => $meta_value ) {
			// Check if this is a custom field (starts with underscore but not WC internal)
			if ( substr( $meta_key, 0, 1 ) === '_' &&
				substr( $meta_key, 0, 5 ) !== '_wc_' &&
				substr( $meta_key, 0, 11 ) !== '_woocommerce' ) {

				// Remove leading underscore for display
				$field_id = substr( $meta_key, 1 );

				// Get the actual value (meta values are stored in arrays)
				$value = isset( $meta_value[0] ) ? $meta_value[0] : '';

				// Skip empty values
				if ( ! empty( $value ) ) {
					$custom_values[ $field_id ] = $value;
				}
			}
		}

		return $custom_values;
	}

	/**
	 * Display custom fields on Order Complete Page
	 *
	 * @param WC_Order $order Order object
	 */
	public function display_custom_fields_in_thank_you( $order ) {
		$order_id = $order->get_id();

		// Get all custom field values (values entered by customer)
		$custom_values = $this->get_custom_field_values( $order_id );

		// If no values, return
		if ( empty( $custom_values ) ) {
			return;
		}

		// Get field definitions for better labels
		$custom_fields = $this->get_custom_fields();
		$block_fields = $this->get_block_custom_fields();
		$all_fields = array_merge_recursive( $custom_fields, $block_fields );

		//echo '<h2>' . esc_html__( 'Additional Information', 'shopglut' ) . '</h2>';
		echo '<table class="woocommerce-table shop_table order_details">';

		// Debug code removed for production

		foreach ( $custom_values as $field_id => $value ) {
			// Try to get field label
			$label = $this->get_field_label( $all_fields, $field_id );

			echo '<tr>';
			echo '<th>' . esc_html( $label ) . ':</th>';
			echo '<td>' . esc_html( $value ) . '</td>';
			echo '</tr>';
		}

		echo '</table>';
	}

	/**
	 * Display custom fields in admin order billing section
	 *
	 * @param WC_Order $order Order object
	 */
	public function display_custom_fields_in_admin_billing( $order ) {
		$order_id = $order->get_id();

		// Get all custom field values (values entered by customer)
		$custom_values = $this->get_custom_field_values( $order_id );

		// If no values, return
		if ( empty( $custom_values ) ) {
			return;
		}

		// Get field definitions for better labels
		$custom_fields = $this->get_custom_fields();
		$block_fields = $this->get_block_custom_fields();
		$all_fields = array_merge_recursive( $custom_fields, $block_fields );

		echo '<h3>' . esc_html__( 'Custom Fieldsddddd', 'shopglut' ) . '</h3>';
		echo '<div class="address">';

		foreach ( $custom_values as $field_id => $value ) {
			// Try to get field label
			$label = $this->get_field_label( $all_fields, $field_id );

			echo '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Display custom fields in email order details section
	 *
	 * @param WC_Order $order Order object
	 * @param bool $sent_to_admin Whether the email is sent to admin
	 * @param bool $plain_text Whether the email is plain text
	 * @param WC_Email $email Email object
	 */
	public function display_custom_fields_in_email_order_details( $order, $sent_to_admin, $plain_text, $email ) {
		$order_id = $order->get_id();

		// Get all custom field values (values entered by customer)
		$custom_values = $this->get_custom_field_values( $order_id );

		// If no values, return
		if ( empty( $custom_values ) ) {
			return;
		}

		// Get field definitions for better labels
		$custom_fields = $this->get_custom_fields();
		$block_fields = $this->get_block_custom_fields();
		$all_fields = array_merge_recursive( $custom_fields, $block_fields );

		// Display based on email format
		if ( $plain_text ) {
			echo "\n" . esc_html__( 'Order Details', 'shopglut' ) . ":\n";

			foreach ( $custom_values as $field_id => $value ) {
				// Try to get field label
				$label = $this->get_field_label( $all_fields, $field_id );

				echo esc_html($label) . ': ' . esc_html($value) . "\n";
			}
		} else {
			echo '<h2>' . esc_html__( 'Additional Details', 'shopglut' ) . '</h2>';
			echo '<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; margin-bottom: 20px;">';

			foreach ( $custom_values as $field_id => $value ) {
				// Try to get field label
				$label = $this->get_field_label( $all_fields, $field_id );

				echo '<tr>';
				echo '<th>' . esc_html( $label ) . ':</th>';
				echo '<td>' . esc_html( $value ) . '</td>';
				echo '</tr>';
			}

			echo '</table>';
		}
	}

	/**
	 * Get custom fields from database
	 *
	 * @return array Custom fields
	 */
	private function get_custom_fields() {
		$checkout_fields_manager = CheckoutFieldsManager::get_instance();
		return $checkout_fields_manager->getCustomFieldsFromDatabase();
	}

	/**
	 * Get block checkout fields from database
	 *
	 * @return array Block checkout fields
	 */
	private function get_block_custom_fields() {
		$checkout_fields_manager = CheckoutFieldsManager::get_instance();
		return $checkout_fields_manager->getBlockCheckoutFieldsFromDatabase();
	}

	/**
	 * Get field label from field definitions
	 * 
	 * @param array $all_fields All field definitions
	 * @param string $field_id Field ID
	 * @return string Field label
	 */
	private function get_field_label( $all_fields, $field_id ) {
		// Default to field ID if label not found
		$label = $field_id;

		// Search for field in all sections
		foreach ( $this->field_sections as $section ) {
			if ( isset( $all_fields[ $section ][ $field_id ]['label'] ) ) {
				$label = $all_fields[ $section ][ $field_id ]['label'];
				break;
			}
		}

		return $label;
	}

	/**
	 * Get instance of this class
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
}
