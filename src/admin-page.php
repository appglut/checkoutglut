<?php
/**
 * Admin page template for Checkout Field Editor
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check for block checkout support in a compatible way
$blocks_checkout_enabled = false;
if ( function_exists( 'wc_get_feature_list' ) ) {
	$features = wc_get_feature_list();
	if ( isset( $features['blocks'] ) && isset( $features['blocks']['checkout'] ) && $features['blocks']['checkout'] ) {
		$blocks_checkout_enabled = true;
	}
} elseif ( class_exists( 'Automattic\WooCommerce\Blocks\Package' ) ) {
	// Alternative check for newer WC versions
	$blocks_checkout_enabled = true;
}


// Initialize message variables (transient functionality removed)
$message_type = '';
$message_text = '';

$field_sections = array( 'billing', 'shipping', 'additional', );
$block_field_sections = array( 'contact', 'address', 'order' );

?>

<div class="wrap shopglut-checkout-manager-wrap">
	<div class="shopglut-checkout-manager-header">
		<div class="shopglut-header-content">
			<div class="shopglut-title-section">
				<h1 style="text-align:center"><?php echo esc_html__( 'CheckoutGlut - Checkout Field Editor', 'checkoutglut' ); ?></h1>
			</div>
			<div class="shopglut-header-actions">
				<a href="<?php echo esc_url( admin_url() ); ?>" class="shopglut-back-to-admin">
					<span class="dashicons dashicons-admin-home"></span>
					<?php echo esc_html__( 'Back to admin', 'checkoutglut' ); ?>
				</a>
			</div>
		</div>
	</div>


	<div class="shopglut-checkout-manager-tabs-container">
		<nav class="shopglut-checkout-manager-tabs">
			<a href="?page=<?php echo esc_attr( $this->menu_slug ); ?>&tab=fields"
				class="shopglut-checkout-manager-tab <?php echo $current_tab === 'fields' ? 'active' : ''; ?>"><?php echo esc_html__( 'Classic Checkout Fields', 'checkoutglut' ); ?></a>
			<?php if ( $blocks_checkout_enabled ) : ?>
				<a href="?page=<?php echo esc_attr( $this->menu_slug ); ?>&tab=block_fields"
					class="shopglut-checkout-manager-tab <?php echo $current_tab === 'block_fields' ? 'active' : ''; ?>"><?php echo esc_html__( 'Block Checkout Fields', 'checkoutglut' ); ?></a>
			<?php endif; ?>
			<a href="?page=<?php echo esc_attr( $this->menu_slug ); ?>&tab=advanced"
				class="shopglut-checkout-manager-tab <?php echo $current_tab === 'advanced' ? 'active' : ''; ?>"><?php echo esc_html__( 'Advanced Settings', 'checkoutglut' ); ?></a>
			<a href="?page=<?php echo esc_attr( $this->menu_slug ); ?>&tab=import_export"
				class="shopglut-checkout-manager-tab <?php echo $current_tab === 'import_export' ? 'active' : ''; ?>"><?php echo esc_html__( 'Import/Export', 'checkoutglut' ); ?></a>
		</nav>

		<!-- Classic Checkout Fields Tab -->
		<div class="shopglut-checkout-manager-tab-content <?php echo $current_tab === 'fields' ? 'active' : ''; ?>">
			<?php if ( $current_tab === 'fields' ) : ?>
				<div class="shopglut-checkout-manager-section-tabs">
					<nav class="shopglut-checkout-manager-tabs">
						<?php
						$field_sections = array( 'billing', 'shipping', 'additional', );
						foreach ( $field_sections as $section ) : ?>
							<a href="#<?php echo esc_attr( $section ); ?>-fields"
								class="shopglut-checkout-manager-tab section-tab <?php echo ( $section === 'billing' ) ? 'active' : ''; ?>"><?php echo esc_html( ucfirst( $section ) ); ?>
								<?php echo esc_html__( 'Fields', 'checkoutglut' ); ?></a>
						<?php endforeach; ?>
						<a href="#add-new-field"
							class="shopglut-checkout-manager-tab section-tab add-tab"><?php echo esc_html__( 'Add New Field', 'checkoutglut' ); ?></a>
					</nav>
				</div>

				<!-- Field Sections Content -->
				<?php foreach ( $field_sections as $section ) : ?>
					<div id="<?php echo esc_attr( $section ); ?>-fields"
						class="shopglut-checkout-manager-section-content <?php echo ( $section === 'billing' ) ? 'active' : ''; ?>">
						<div class="shopglut-checkout-manager-card">
							<div class="shopglut-checkout-manager-card-header">
								<h3><?php echo esc_html( ucfirst( $section ) ); ?>
									<?php echo esc_html__( 'Fields', 'checkoutglut' ); ?>
									<div class="drag-instruction">
										<span class="dashicons dashicons-info"></span>
										<?php echo esc_html__( 'Drag and drop rows to reorder fields', 'checkoutglut' ); ?>
									</div>
								</h3>

								<div class="shopglut-checkout-manager-card-actions">
									<button type="button" class="shopglut-checkout-manager-button secondary enable-all-fields"
										data-section="<?php echo esc_attr( $section ); ?>"><?php echo esc_html__( 'Enable All', 'checkoutglut' ); ?></button>
									<button type="button" class="shopglut-checkout-manager-button secondary disable-all-fields"
										data-section="<?php echo esc_attr( $section ); ?>"><?php echo esc_html__( 'Disable All', 'checkoutglut' ); ?></button>
									<button type="button" class="shopglut-checkout-manager-button secondary reset-fields"
										data-section="<?php echo esc_attr( $section ); ?>"><?php echo esc_html__( 'Reset to Default', 'checkoutglut' ); ?></button>
								</div>
							</div>
							<div class="shopglut-checkout-manager-card-content">

								<!-- Add this right after the shopglut-checkout-manager-card-header div for each section -->
								<?php
								if ( strlen( $message_text ) > 0 ) :
									?>
									<div class="shopglut-checkout-manager-message <?php echo esc_attr( $message_type ); ?>">
										<p><?php echo esc_html( $message_text ); ?></p>
									</div>
								<?php endif; ?>
								<table class="shopglut-checkout-manager-table shopglut-checkout-manager-fields-table widefat">
									<thead>
										<tr>
											<th class="sort-column"></th>
											<th><?php echo esc_html__( 'Field ID', 'checkoutglut' ); ?></th>
											<th><?php echo esc_html__( 'Type', 'checkoutglut' ); ?></th>
											<th><?php echo esc_html__( 'Label', 'checkoutglut' ); ?></th>
											<th><?php echo esc_html__( 'Required', 'checkoutglut' ); ?></th>
											<th><?php echo esc_html__( 'Placeholder', 'checkoutglut' ); ?></th>
											<th><?php echo esc_html__( 'Display', 'checkoutglut' ); ?></th>
											<th><?php echo esc_html__( 'Actions', 'checkoutglut' ); ?></th>
										</tr>
									</thead>
									<tbody class="checkout-fields-list">
										<?php
										$section_fields = [];

										// Get default WooCommerce fields for this section
										if ( isset( $default_fields[ $section ] ) ) {
											foreach ( $default_fields[ $section ] as $field_id => $field ) {
												$field['default_field'] = true;
												$section_fields[ $field_id ] = $field;
											}
										}

										// Merge with custom fields
										if ( isset( $custom_fields[ $section ] ) ) {
											foreach ( $custom_fields[ $section ] as $field_id => $field ) {
												$field['custom_field'] = true;
												$section_fields[ $field_id ] = $field;
											}
										}

										// Sort by priority
										uasort( $section_fields, function ($a, $b) {
											$priority_a = isset( $a['priority'] ) ? $a['priority'] : 10;
											$priority_b = isset( $b['priority'] ) ? $b['priority'] : 10;
											return $priority_a - $priority_b;
										} );

										if ( ! empty( $section_fields ) ) :
											foreach ( $section_fields as $field_id => $field ) :
												$field_type = isset( $field['type'] ) ? $field['type'] : 'text';
												$field_label = isset( $field['label'] ) ? $field['label'] : '';
												$field_placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
												$field_required = isset( $field['required'] ) && $field['required'] ? true : false;
												$field_class = isset( $field['class'] ) ? ( is_array( $field['class'] ) ? implode( ' ', $field['class'] ) : $field['class'] ) : '';
												$field_priority = isset( $field['priority'] ) ? $field['priority'] : 10;
												$field_options = isset( $field['options'] ) ? $field['options'] : array();
												$field_validate = isset( $field['validate'] ) ? ( is_array( $field['validate'] ) ? implode( ',', $field['validate'] ) : $field['validate'] ) : '';
												$is_custom = isset( $field['custom'] ) && $field['custom'] ? true : false;
												$display_in_emails = isset( $field['display_in_emails'] ) ? $field['display_in_emails'] : true;
												$display_in_order = isset( $field['display_in_order'] ) ? $field['display_in_order'] : true;
												$field_enabled = isset( $field['enabled'] ) ? ( $field['enabled'] == 1 || $field['enabled'] === true ) : true;

												// Get the database ID if available with caching
												global $wpdb;
												$table_name = $wpdb->prefix . 'shopglut_checkout_fields';
												$cache_key = "shopglut_field_id_{$field_id}_{$section}";

												$db_id = wp_cache_get( $cache_key );
												if ( false === $db_id ) {
													// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for field lookup, using %i for table name
													$db_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}shopglut_checkout_fields WHERE field_id = %s AND section = %s", $field_id, $section ) );
													wp_cache_set( $cache_key, $db_id, '', 300 ); // Cache for 5 minutes
												}
												?>
												<tr class="field-row sortable-row <?php echo ! $field_enabled ? 'disabled' : ''; ?>"
													data-field-id="<?php echo esc_attr( $db_id ? $db_id : $field_id ); ?>"
													data-field-key="<?php echo esc_attr( $field_id ); ?>"
													data-section="<?php echo esc_attr( $section ); ?>">
													<td class="sort-handle">
														<span class="dashicons dashicons-menu" title="<?php echo esc_attr__( 'Drag to reorder', 'checkoutglut' ); ?>"></span>
													</td>
													<td><?php echo esc_html( $field_id ); ?></td>
													<td><?php echo esc_html( isset( $this->field_types[ $field_type ] ) ? $this->field_types[ $field_type ] : ucfirst( $field_type ) ); ?>
													</td>
													<td><?php echo esc_html( $field_label ); ?></td>
													<td class="field-required">
														<?php echo $field_required ? '<span class="dashicons dashicons-yes"></span>' : '—'; ?>
													</td>
													<td><?php echo esc_html( $field_placeholder ); ?></td>
													<td>
														<?php if ( $display_in_emails ) : ?>
															<span class="dashicons dashicons-email"
																title="<?php echo esc_attr__( 'Display in Emails', 'checkoutglut' ); ?>"></span>
														<?php endif; ?>
														<?php if ( $display_in_order ) : ?>
															<span class="dashicons dashicons-clipboard"
																title="<?php echo esc_attr__( 'Display in Order', 'checkoutglut' ); ?>"></span>
														<?php endif; ?>
													</td>
													<td class="field-actions">
														<a href="#" class="edit-field" data-field-id="<?php echo esc_attr( $db_id ); ?>"
															data-field-key="<?php echo esc_attr( $field_id ); ?>"
															data-section="<?php echo esc_attr( $section ); ?>"
															data-type="<?php echo esc_attr( $field_type ); ?>"
															data-label="<?php echo esc_attr( $field_label ); ?>"
															data-placeholder="<?php echo esc_attr( $field_placeholder ); ?>"
															data-class="<?php echo esc_attr( $field_class ); ?>"
															data-required="<?php echo $field_required ? '1' : '0'; ?>"
															data-priority="<?php echo esc_attr( $field_priority ); ?>"
															data-options="<?php echo esc_attr( is_array( $field_options ) ? json_encode( $field_options ) : $field_options ); ?>"
															data-validation="<?php echo esc_attr( $field_validate ); ?>"
															data-display-emails="<?php echo esc_attr( $display_in_emails ? '1' : '0' ); ?>"
															data-display-order="<?php echo esc_attr( $display_in_order ? '1' : '0' ); ?>"
															data-enabled="<?php echo esc_attr( $field_enabled ? '1' : '0' ); ?>"
															data-custom="<?php echo esc_attr( $is_custom ? '1' : '0' ); ?>">
															<span class="dashicons dashicons-edit"></span>
															<?php echo esc_html__( 'Edit', 'checkoutglut' ); ?>
														</a>

														<?php if ( $is_custom ) : ?>
															<a href="#" class="delete-field delete-action"
																data-field-id="<?php echo esc_attr( $db_id ); ?>">
																<span class="dashicons dashicons-trash"></span>
																<?php echo esc_html__( 'Delete', 'checkoutglut' ); ?>
															</a>
														<?php endif; ?>

														<a href="#" class="toggle-field"
															data-field-id="<?php echo esc_attr( $db_id ); ?>"
															data-enabled="<?php echo esc_attr( $field_enabled ? '1' : '0' ); ?>">
															<?php if ( $field_enabled ) : ?>
																<span class="dashicons dashicons-no-alt"></span>
																<?php echo esc_html__( 'Disable', 'checkoutglut' ); ?>
															<?php else : ?>
																<span class="dashicons dashicons-yes-alt"></span>
																<?php echo esc_html__( 'Enable', 'checkoutglut' ); ?>
															<?php endif; ?>
														</a>
													</td>
												</tr>
												<?php
											endforeach;
										else :
											?>
											<tr>
												<td colspan="7">
													<?php echo esc_html__( 'No fields found for this section.', 'checkoutglut' ); ?>
												</td>
											</tr>
										<?php endif; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				<?php endforeach; ?>

				<!-- Add New Field Section -->
				<div id="add-new-field" class="shopglut-checkout-manager-section-content">
					<div class="shopglut-checkout-manager-card">
						<div class="shopglut-checkout-manager-card-header">
							<h3><?php echo esc_html__( 'Add New Checkout Field', 'checkoutglut' ); ?></h3>
							<div class="drag-instruction">
								<span class="dashicons dashicons-info"></span>
								<?php echo esc_html__( 'Drag and drop rows to reorder fields', 'checkoutglut' ); ?>
							</div>
						</div>

						<div class="shopglut-checkout-manager-card-content">
							<form method="post" action="" id="add-field-form">
								<?php wp_nonce_field( 'shopglut_checkout_fields_action', 'shopglut_checkout_fields_nonce' ); ?>

								<table class="shopglut-checkout-manager-form-table">
									<tr>
										<th scope="row">
											<label
												for="new_field_section"><?php echo esc_html__( 'Section', 'checkoutglut' ); ?></label>
										</th>
										<td>
											<select name="new_field_section" id="new_field_section" required>
												<?php
												$field_sections = array( 'billing', 'shipping', 'additional' );

												foreach ( $field_sections as $section ) : ?>
													<option value="<?php echo esc_attr( $section ); ?>">
														<?php echo esc_html( ucfirst( $section ) ); ?>
													</option>
												<?php endforeach; ?>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label
												for="new_field_id"><?php echo esc_html__( 'Field ID', 'checkoutglut' ); ?></label>
										</th>
										<td>
											<input type="text" name="new_field_id" id="new_field_id" class="regular-text"
												required>
											<p class="description">
												<?php echo esc_html__( 'Unique identifier for the field (e.g. custom_field). The section prefix will be added automatically.', 'checkoutglut' ); ?>
											</p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label
												for="new_field_type"><?php echo esc_html__( 'Field Type', 'checkoutglut' ); ?></label>
										</th>
										<td>
											<select name="new_field_type" id="new_field_type" required>
												<?php foreach ( $this->field_types as $type => $label ) : ?>
													<option value="<?php echo esc_attr( $type ); ?>">
														<?php echo esc_html( $label ); ?>
													</option>
												<?php endforeach; ?>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label
												for="new_field_label"><?php echo esc_html__( 'Label', 'checkoutglut' ); ?></label>
										</th>
										<td>
											<input type="text" name="new_field_label" id="new_field_label"
												class="regular-text" required>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label
												for="new_field_placeholder"><?php echo esc_html__( 'Placeholder', 'checkoutglut' ); ?></label>
										</th>
										<td>
											<input type="text" name="new_field_placeholder" id="new_field_placeholder"
												class="regular-text">
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label
												for="new_field_class"><?php echo esc_html__( 'CSS Class', 'checkoutglut' ); ?></label>
										</th>
										<td>
											<input type="text" name="new_field_class" id="new_field_class"
												class="regular-text" value="form-row-wide">
											<p class="description">
												<?php echo esc_html__( 'Space-separated list of CSS classes. Common values: form-row-first, form-row-last, form-row-wide', 'checkoutglut' ); ?>
											</p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<?php echo esc_html__( 'Required', 'checkoutglut' ); ?>
										</th>
										<td>
											<label for="new_field_required">
												<input type="checkbox" name="new_field_required" id="new_field_required"
													value="1">
												<?php echo esc_html__( 'Make this field required', 'checkoutglut' ); ?>
											</label>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label
												for="new_field_priority"><?php echo esc_html__( 'Priority', 'checkoutglut' ); ?></label>
										</th>
										<td>
											<input type="number" name="new_field_priority" id="new_field_priority"
												class="small-text" value="100">
											<p class="description">
												<?php echo esc_html__( 'Controls the display order. Lower numbers display first.', 'checkoutglut' ); ?>
											</p>
										</td>
									</tr>
									<tr class="field-options" style="display:none;">
										<th scope="row">
											<label
												for="new_field_options"><?php echo esc_html__( 'Options', 'checkoutglut' ); ?></label>
										</th>
										<td>
											<textarea name="new_field_options" id="new_field_options" rows="5"
												cols="50"></textarea>
											<p class="description">
												<?php echo esc_html__( 'Enter each option on a new line. Format: value|label (e.g. option_1|Option 1). If no label is specified, the value will be used as the label.', 'checkoutglut' ); ?>
											</p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<?php echo esc_html__( 'Validation', 'checkoutglut' ); ?>
										</th>
										<td>
											<fieldset>
												<legend class="screen-reader-text">
													<?php echo esc_html__( 'Validation', 'checkoutglut' ); ?>
												</legend>
												<label for="new_field_validation_email">
													<input type="checkbox" name="new_field_validation[]"
														id="new_field_validation_email" value="email">
													<?php echo esc_html__( 'Email', 'checkoutglut' ); ?>
												</label><br>
												<label for="new_field_validation_phone">
													<input type="checkbox" name="new_field_validation[]"
														id="new_field_validation_phone" value="phone">
													<?php echo esc_html__( 'Phone', 'checkoutglut' ); ?>
												</label><br>
												<label for="new_field_validation_postcode">
													<input type="checkbox" name="new_field_validation[]"
														id="new_field_validation_postcode" value="postcode">
													<?php echo esc_html__( 'Postcode', 'checkoutglut' ); ?>
												</label><br>
												<label for="new_field_validation_state">
													<input type="checkbox" name="new_field_validation[]"
														id="new_field_validation_state" value="state">
													<?php echo esc_html__( 'State', 'checkoutglut' ); ?>
												</label><br>
												<label for="new_field_validation_number">
													<input type="checkbox" name="new_field_validation[]"
														id="new_field_validation_number" value="number">
													<?php echo esc_html__( 'Number', 'checkoutglut' ); ?>
												</label>
											</fieldset>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<?php echo esc_html__( 'Display Options', 'checkoutglut' ); ?>
										</th>
										<td>
											<fieldset>
												<legend class="screen-reader-text">
													<?php echo esc_html__( 'Display Options', 'checkoutglut' ); ?>
												</legend>
												<label for="new_field_display_in_emails">
													<input type="checkbox" name="new_field_display_in_emails"
														id="new_field_display_in_emails" value="1" checked>
													<?php echo esc_html__( 'Display in emails', 'checkoutglut' ); ?>
												</label><br>
												<label for="new_field_display_in_order">
													<input type="checkbox" name="new_field_display_in_order"
														id="new_field_display_in_order" value="1" checked>
													<?php echo esc_html__( 'Display in order details', 'checkoutglut' ); ?>
												</label>
											</fieldset>
										</td>
									</tr>
								</table>

								<div class="shopglut-checkout-manager-card-footer">
									<input type="submit" name="add_field" class="shopglut-checkout-manager-button"
										value="<?php echo esc_attr__( 'Add Field', 'checkoutglut' ); ?>">
								</div>
							</form>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<!-- Block Checkout Fields Tab -->
		<!-- Block Checkout Fields Tab -->
		<div
			class="shopglut-checkout-manager-tab-content <?php echo $current_tab === 'block_fields' ? 'active' : ''; ?>">
			<?php if ( $current_tab === 'block_fields' ) : ?>
				<div class="shopglut-checkout-manager-card">
					<div class="shopglut-checkout-manager-card-header">
						<h3><?php echo esc_html__( 'Block Checkout Fields', 'checkoutglut' ); ?>
							<div class="drag-instruction">
								<span class="dashicons dashicons-info"></span>
								<?php echo esc_html__( 'Drag and drop rows to reorder fields', 'checkoutglut' ); ?>
							</div>
						</h3>

					</div>
					<div class="shopglut-checkout-manager-card-content">
						<div class="shopglut-checkout-manager-info-box info compact">
							<h4><span class="dashicons dashicons-info"></span> <?php echo esc_html__( 'WooCommerce Blocks Support', 'checkoutglut' ); ?></h4>
							<p><?php echo esc_html__( 'Block checkout supports a limited set of field types.', 'checkoutglut' ); ?><br>
							<strong><?php echo esc_html__( 'Supported types:', 'checkoutglut' ); ?></strong> <?php echo esc_html__( 'Text, Select, Checkbox', 'checkoutglut' ); ?></p>
						</div>

						<div class="shopglut-checkout-manager-section-tabs">
							<nav class="shopglut-checkout-manager-tabs">
								<?php $block_field_sections = array( 'contact', 'address', 'order' );

								foreach ( $block_field_sections as $section ) : ?>
									<a href="#block-<?php echo esc_attr( $section ); ?>-fields"
										class="shopglut-checkout-manager-tab section-tab <?php echo ( $section === 'contact' ) ? 'active' : ''; ?>"><?php echo esc_html( ucfirst( $section ) ); ?>
										<?php echo esc_html__( 'Fields', 'checkoutglut' ); ?></a>
								<?php endforeach; ?>
								<a href="#block-add-new-field"
									class="shopglut-checkout-manager-tab section-tab add-tab"><?php echo esc_html__( 'Add New Field', 'checkoutglut' ); ?></a>
							</nav>
						</div>

						<!-- Block Field Sections Content -->
						<?php
						$block_fields = array();

						// Get block checkout fields from database with caching
						global $wpdb;
						$table_name = $wpdb->prefix . 'shopglut_checkout_fields';
						$cache_key = 'shopglut_block_checkout_fields';

						$results = wp_cache_get( $cache_key );
						if ( false === $results ) {
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for block fields lookup, using %i for table name
							$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}shopglut_checkout_fields WHERE block_checkout = %d ORDER BY section, priority", 1 ), ARRAY_A );
							wp_cache_set( $cache_key, $results, '', 600 ); // Cache for 10 minutes
						}

						if ( $results ) {
							foreach ( $results as $field ) {
								$section = $field['section'];
								if ( ! isset( $block_fields[ $section ] ) ) {
									$block_fields[ $section ] = array();
								}
								$block_fields[ $section ][] = $field;
							}
						}


						$block_field_sections = array( 'contact', 'address', 'order' );


						foreach ( $block_field_sections as $section ) :
							?>
							<div id="block-<?php echo esc_attr( $section ); ?>-fields"
								class="shopglut-checkout-manager-section-content <?php echo ( $section === 'contact' ) ? 'active' : ''; ?>">
								<div class="shopglut-checkout-manager-card-header">
									<h3><?php echo esc_html( ucfirst( $section ) ); ?>
										<?php echo esc_html__( 'Fields', 'checkoutglut' ); ?>
										<div class="drag-instruction">
											<span class="dashicons dashicons-info"></span>
											<?php echo esc_html__( 'Drag and drop rows to reorder fields', 'checkoutglut' ); ?>
										</div>
									</h3>


									<div class="shopglut-checkout-manager-card-actions">
										<button type="button"
											class="shopglut-checkout-manager-button secondary enable-all-block-fields"
											data-section="<?php echo esc_attr( $section ); ?>"><?php echo esc_html__( 'Enable All', 'checkoutglut' ); ?></button>
										<button type="button"
											class="shopglut-checkout-manager-button secondary disable-all-block-fields"
											data-section="<?php echo esc_attr( $section ); ?>"><?php echo esc_html__( 'Disable All', 'checkoutglut' ); ?></button>
										<button type="button" class="shopglut-checkout-manager-button secondary reset-fields"
											data-section="<?php echo esc_attr( $section ); ?>"><?php echo esc_html__( 'Reset to Default', 'checkoutglut' ); ?></button>
									</div>
								</div>
								<table class="shopglut-checkout-manager-table shopglut-checkout-manager-fields-table widefat">
									<?php
									if ( strlen( $message_text ) > 0 ) :
										?>
										<div class="shopglut-checkout-manager-message <?php echo esc_attr( $message_type ); ?>">
											<p><?php echo esc_html( $message_text ); ?></p>
										</div>
									<?php endif; ?>
									<thead>
										<tr>
											<th class="sort-column"></th>
											<th><?php echo esc_html__( 'Field ID', 'checkoutglut' ); ?></th>
											<th><?php echo esc_html__( 'Type', 'checkoutglut' ); ?></th>
											<th><?php echo esc_html__( 'Label', 'checkoutglut' ); ?></th>
											<th><?php echo esc_html__( 'Required', 'checkoutglut' ); ?></th>
											<th><?php echo esc_html__( 'Display', 'checkoutglut' ); ?></th>
											<th><?php echo esc_html__( 'Actions', 'checkoutglut' ); ?></th>
										</tr>
									</thead>
									<tbody>

										<?php

										if ( isset( $block_fields[ $section ] ) && ! empty( $block_fields[ $section ] ) ) : ?>
											<?php foreach ( $block_fields[ $section ] as $field ) : ?>
												<tr class="field-row sortable-row block-field-row <?php echo ! $field['enabled'] ? 'disabled' : ''; ?>"
													data-field-id="<?php echo esc_attr( $field['id'] ); ?>"
													data-field-key="<?php echo esc_attr( $field['field_id'] ); ?>"
													data-section="<?php echo esc_attr( $field['section'] ); ?>">
													<td class="sort-handle">
														<span class="dashicons dashicons-menu" title="<?php echo esc_attr__( 'Drag to reorder', 'checkoutglut' ); ?>"></span>
													</td>
													<td><?php echo esc_html( $field['field_id'] ); ?></td>
													<td><?php echo esc_html( $this->getBlockFieldTypes()[ $field['type'] ] ?? ucfirst( $field['type'] ) ); ?>
													</td>
													<td><?php echo esc_html( $field['label'] ); ?></td>
													<td class="field-required">
														<?php echo $field['required'] ? '<span class="dashicons dashicons-yes"></span>' : '—'; ?>
													</td>
													<td>
														<?php if ( $field['display_in_emails'] ) : ?>
															<span class="dashicons dashicons-email"
																title="<?php echo esc_attr__( 'Display in Emails', 'checkoutglut' ); ?>"></span>
														<?php endif; ?>
														<?php if ( $field['display_in_order'] ) : ?>
															<span class="dashicons dashicons-clipboard"
																title="<?php echo esc_attr__( 'Display in Order', 'checkoutglut' ); ?>"></span>
														<?php endif; ?>
													</td>
													<td class="field-actions">
														<a href="#" class="edit-block-field"
															data-field-id="<?php echo esc_attr( $field['id'] ); ?>"
															data-field-key="<?php echo esc_attr( $field['field_id'] ); ?>"
															data-section="<?php echo esc_attr( $field['section'] ); ?>"
															data-type="<?php echo esc_attr( $field['type'] ); ?>"
															data-label="<?php echo esc_attr( $field['label'] ); ?>"
															data-placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
															data-class="<?php echo esc_attr( $field['class'] ); ?>"
															data-required="<?php echo $field['required'] ? '1' : '0'; ?>"
															data-priority="<?php echo esc_attr( $field['priority'] ); ?>"
															data-options="<?php echo esc_attr( $field['options'] ); ?>"
															data-display-emails="<?php echo esc_attr( $field['display_in_emails'] ? '1' : '0' ); ?>"
															data-display-order="<?php echo esc_attr( $field['display_in_order'] ? '1' : '0' ); ?>"
															data-enabled="<?php echo esc_attr( $field['enabled'] ? '1' : '0' ); ?>"
															data-custom="<?php echo esc_attr( $field['custom'] ? '1' : '0' ); ?>">
															<span class="dashicons dashicons-edit"></span>
															<?php echo esc_html__( 'Edit', 'checkoutglut' ); ?>
														</a>

														<?php if ( $field['custom'] ) : ?>
															<a href="#" class="delete-block-field delete-action"
																data-field-id="<?php echo esc_attr( $field['id'] ); ?>">
																<span class="dashicons dashicons-trash"></span>
																<?php echo esc_html__( 'Delete', 'checkoutglut' ); ?>
															</a>
														<?php endif; ?>

														<a href="#" class="toggle-block-field"
															data-field-id="<?php echo esc_attr( $field['id'] ); ?>"
															data-enabled="<?php echo esc_attr( $field['enabled'] ? '1' : '0' ); ?>">
															<?php if ( $field['enabled'] ) : ?>
																<span class="dashicons dashicons-no-alt"></span>
																<?php echo esc_html__( 'Disable', 'checkoutglut' ); ?>
															<?php else : ?>
																<span class="dashicons dashicons-yes-alt"></span>
																<?php echo esc_html__( 'Enable', 'checkoutglut' ); ?>
															<?php endif; ?>
														</a>
													</td>
												</tr>
											<?php endforeach; ?>
										<?php else : ?>
											<tr>
												<td colspan="6">
													<?php echo esc_html__( 'No block checkout fields found for this section. Add your first block checkout field using the "Add New Field" tab.', 'checkoutglut' ); ?>
												</td>
											</tr>
										<?php endif; ?>
									</tbody>
								</table>
							</div>
						<?php endforeach; ?>

						<!-- Block Add New Field Section -->
						<div id="block-add-new-field" class="shopglut-checkout-manager-section-content">
							<div class="shopglut-checkout-manager-card">
								<div class="shopglut-checkout-manager-card-header">
									<h3><?php echo esc_html__( 'Add New Block Checkout Field', 'checkoutglut' ); ?></h3>
								</div>
								<div class="shopglut-checkout-manager-card-content">
									<form method="post" action="" id="add-block-field-form">
										<?php wp_nonce_field( 'shopglut_checkout_fields_action', 'shopglut_checkout_fields_nonce' ); ?>
										<input type="hidden" name="block_checkout" value="1">

										<table class="shopglut-checkout-manager-form-table">
											<tr>
												<th scope="row">
													<label
														for="new_block_field_section"><?php echo esc_html__( 'Section', 'checkoutglut' ); ?></label>
												</th>
												<td>
													<select name="new_block_field_section" id="new_block_field_section"
														required>
														<?php foreach ( $block_field_sections as $section ) : ?>
															<option value="<?php echo esc_attr( $section ); ?>">
																<?php echo esc_html( ucfirst( $section ) ); ?>
															</option>
														<?php endforeach; ?>
													</select>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label
														for="new_block_field_id"><?php echo esc_html__( 'Field ID', 'checkoutglut' ); ?></label>
												</th>
												<td>
													<input type="text" name="new_block_field_id" id="new_block_field_id"
														class="regular-text" required>
													<p class="description">
														<?php echo esc_html__( 'Unique identifier for the field (e.g. custom_field). The section prefix will be added automatically.', 'checkoutglut' ); ?>
													</p>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label
														for="new_block_field_type"><?php echo esc_html__( 'Field Type', 'checkoutglut' ); ?></label>
												</th>
												<td>
													<select name="new_block_field_type" id="new_block_field_type" required>
														<?php foreach ( $this->getBlockFieldTypes() as $type => $label ) : ?>
															<option value="<?php echo esc_attr( $type ); ?>">
																<?php echo esc_html( $label ); ?>
															</option>
														<?php endforeach; ?>
													</select>
													<p class="description">
														<?php echo esc_html__( 'Only these field types are currently supported in block checkout.', 'checkoutglut' ); ?>
													</p>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label
														for="new_block_field_label"><?php echo esc_html__( 'Label', 'checkoutglut' ); ?></label>
												</th>
												<td>
													<input type="text" name="new_block_field_label"
														id="new_block_field_label" class="regular-text" required>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label
														for="new_block_field_placeholder"><?php echo esc_html__( 'Placeholder', 'checkoutglut' ); ?></label>
												</th>
												<td>
													<input type="text" name="new_block_field_placeholder"
														id="new_block_field_placeholder" class="regular-text">
												</td>
											</tr>
											<tr>
												<th scope="row">
													<?php echo esc_html__( 'Required', 'checkoutglut' ); ?>
												</th>
												<td>
													<label for="new_block_field_required">
														<input type="checkbox" name="new_block_field_required"
															id="new_block_field_required" value="1">
														<?php echo esc_html__( 'Make this field required', 'checkoutglut' ); ?>
													</label>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<label
														for="new_block_field_priority"><?php echo esc_html__( 'Priority', 'checkoutglut' ); ?></label>
												</th>
												<td>
													<input type="number" name="new_block_field_priority"
														id="new_block_field_priority" class="small-text" value="100">
													<p class="description">
														<?php echo esc_html__( 'Controls the display order. Lower numbers display first.', 'checkoutglut' ); ?>
													</p>
												</td>
											</tr>
											<tr class="block-field-options" style="display:none;">
												<th scope="row">
													<label
														for="new_block_field_options"><?php echo esc_html__( 'Options', 'checkoutglut' ); ?></label>
												</th>
												<td>
													<textarea name="new_block_field_options" id="new_block_field_options"
														rows="5" cols="50"></textarea>
													<p class="description">
														<?php echo esc_html__( 'Enter each option on a new line. Format: value|label (e.g. option_1|Option 1).', 'checkoutglut' ); ?>
													</p>
												</td>
											</tr>
											<tr>
												<th scope="row">
													<?php echo esc_html__( 'Display Options', 'checkoutglut' ); ?>
												</th>
												<td>
													<fieldset>
														<legend class="screen-reader-text">
															<?php echo esc_html__( 'Display Options', 'checkoutglut' ); ?>
														</legend>
														<label for="new_block_field_display_in_emails">
															<input type="checkbox" name="new_block_field_display_in_emails"
																id="new_block_field_display_in_emails" value="1" checked>
															<?php echo esc_html__( 'Display in emails', 'checkoutglut' ); ?>
														</label><br>
														<label for="new_block_field_display_in_order">
															<input type="checkbox" name="new_block_field_display_in_order"
																id="new_block_field_display_in_order" value="1" checked>
															<?php echo esc_html__( 'Display in order details', 'checkoutglut' ); ?>
														</label>
													</fieldset>
												</td>
											</tr>
										</table>

										<div class="shopglut-checkout-manager-card-footer">
											<input type="submit" name="add_block_field"
												class="shopglut-checkout-manager-button"
												value="<?php echo esc_attr__( 'Add Block Field', 'checkoutglut' ); ?>">
										</div>
									</form>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<!-- Edit Block Field Dialog -->
		<div class="shopglut-checkout-manager-overlay" id="edit-block-field-overlay"></div>
		<div class="shopglut-checkout-manager-dialog" id="edit-block-field-dialog">
			<div class="shopglut-checkout-manager-dialog-header">
				<h2><?php echo esc_html__( 'Edit Block Field', 'checkoutglut' ); ?></h2>
				<span class="shopglut-checkout-manager-dialog-close">&times;</span>
			</div>
			<div class="shopglut-checkout-manager-dialog-content">
				<form method="post" action="" id="edit-block-field-form">
					<?php wp_nonce_field( 'shopglut_checkout_fields_action', 'shopglut_checkout_fields_nonce' ); ?>
					<input type="hidden" name="field_id" id="edit_block_field_id_hidden">
					<input type="hidden" name="block_checkout" value="1">

					<table class="shopglut-checkout-manager-form-table">
						<tr>
							<th scope="row">
								<label
									for="edit_block_field_section"><?php echo esc_html__( 'Section', 'checkoutglut' ); ?></label>
							</th>
							<td>
								<select name="edit_block_field_section" id="edit_block_field_section" required>
									<?php foreach ( $block_field_sections as $section ) : ?>
										<option value="<?php echo esc_attr( $section ); ?>">
											<?php echo esc_html( ucfirst( $section ) ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label
									for="edit_block_field_id"><?php echo esc_html__( 'Field ID', 'checkoutglut' ); ?></label>
							</th>
							<td>
								<input type="text" name="edit_block_field_id" id="edit_block_field_id"
									class="regular-text" required>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label
									for="edit_block_field_type"><?php echo esc_html__( 'Field Type', 'checkoutglut' ); ?></label>
							</th>
							<td>
								<select name="edit_block_field_type" id="edit_block_field_type" required>
									<?php foreach ( $this->getBlockFieldTypes() as $type => $label ) : ?>
										<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label
									for="edit_block_field_label"><?php echo esc_html__( 'Label', 'checkoutglut' ); ?></label>
							</th>
							<td>
								<input type="text" name="edit_block_field_label" id="edit_block_field_label"
									class="regular-text" required>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label
									for="edit_block_field_placeholder"><?php echo esc_html__( 'Placeholder', 'checkoutglut' ); ?></label>
							</th>
							<td>
								<input type="text" name="edit_block_field_placeholder" id="edit_block_field_placeholder"
									class="regular-text">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<?php echo esc_html__( 'Required', 'checkoutglut' ); ?>
							</th>
							<td>
								<label for="edit_block_field_required">
									<input type="checkbox" name="edit_block_field_required"
										id="edit_block_field_required" value="1">
									<?php echo esc_html__( 'Make this field required', 'checkoutglut' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label
									for="edit_block_field_priority"><?php echo esc_html__( 'Priority', 'checkoutglut' ); ?></label>
							</th>
							<td>
								<input type="number" name="edit_block_field_priority" id="edit_block_field_priority"
									class="small-text" value="10">
							</td>
						</tr>
						<tr class="block-field-options-edit" style="display:none;">
							<th scope="row">
								<label
									for="edit_block_field_options"><?php echo esc_html__( 'Options', 'checkoutglut' ); ?></label>
							</th>
							<td>
								<textarea name="edit_block_field_options" id="edit_block_field_options" rows="5"
									cols="50"></textarea>
								<p class="description">
									<?php echo esc_html__( 'Enter each option on a new line. Format: value|label (e.g. option_1|Option 1).', 'checkoutglut' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<?php echo esc_html__( 'Display Options', 'checkoutglut' ); ?>
							</th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<?php echo esc_html__( 'Display Options', 'checkoutglut' ); ?>
									</legend>
									<label for="edit_block_field_display_in_emails">
										<input type="checkbox" name="edit_block_field_display_in_emails"
											id="edit_block_field_display_in_emails" value="1">
										<?php echo esc_html__( 'Display in emails', 'checkoutglut' ); ?>
									</label><br>
									<label for="edit_block_field_display_in_order">
										<input type="checkbox" name="edit_block_field_display_in_order"
											id="edit_block_field_display_in_order" value="1">
										<?php echo esc_html__( 'Display in order details', 'checkoutglut' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
					</table>
				</form>
			</div>
			<div class="shopglut-checkout-manager-dialog-footer">
				<button type="button"
					class="shopglut-checkout-manager-button secondary cancel-button"><?php echo esc_html__( 'Cancel', 'checkoutglut' ); ?></button>
				<button type="button"
					class="shopglut-checkout-manager-button update-block-field-button"><?php echo esc_html__( 'Update Field', 'checkoutglut' ); ?></button>
			</div>
		</div>

		<!-- Advanced Settings Tab -->
		<div class="shopglut-checkout-manager-tab-content <?php echo $current_tab === 'advanced' ? 'active' : ''; ?>">
			<?php if ( $current_tab === 'advanced' ) : ?>
				<form method="post" action="">
					<?php wp_nonce_field( 'shopglut_checkout_fields_action', 'shopglut_checkout_fields_nonce' ); ?>

					<div class="shopglut-checkout-manager-card">
						<div class="shopglut-checkout-manager-card-header">
							<h3><?php echo esc_html__( 'Locale Override Settings', 'checkoutglut' ); ?></h3>
						</div>

						<div class="shopglut-checkout-manager-card-content">
							<p class="description">
								<?php echo esc_html__( 'These settings control whether your field customizations override the default WooCommerce locale settings.', 'checkoutglut' ); ?>
							</p>

							<div class="shopglut-checkout-manager-settings-section">
								<div class="shopglut-checkout-manager-settings-row">
									<input type="checkbox" id="enable_label_override" name="enable_label_override" value="1"
										<?php checked( isset( $settings['enable_label_override'] ) && $settings['enable_label_override'] ); ?>>
									<label
										for="enable_label_override"><?php echo esc_html__( 'Enable label override for address fields', 'checkoutglut' ); ?></label>
								</div>

								<div class="shopglut-checkout-manager-settings-row">
									<input type="checkbox" id="enable_placeholder_override"
										name="enable_placeholder_override" value="1" <?php checked( isset( $settings['enable_placeholder_override'] ) && $settings['enable_placeholder_override'] ); ?>>
									<label
										for="enable_placeholder_override"><?php echo esc_html__( 'Enable placeholder override for address fields', 'checkoutglut' ); ?></label>
								</div>

								<div class="shopglut-checkout-manager-settings-row">
									<input type="checkbox" id="enable_class_override" name="enable_class_override" value="1"
										<?php checked( isset( $settings['enable_class_override'] ) && $settings['enable_class_override'] ); ?>>
									<label
										for="enable_class_override"><?php echo esc_html__( 'Enable class override for address fields', 'checkoutglut' ); ?></label>
								</div>

								<div class="shopglut-checkout-manager-settings-row">
									<input type="checkbox" id="enable_priority_override" name="enable_priority_override"
										value="1" <?php checked( isset( $settings['enable_priority_override'] ) && $settings['enable_priority_override'] ); ?>>
									<label
										for="enable_priority_override"><?php echo esc_html__( 'Enable priority override for address fields', 'checkoutglut' ); ?></label>
								</div>

								<div class="shopglut-checkout-manager-settings-row">
									<input type="checkbox" id="enable_required_override" name="enable_required_override"
										value="1" <?php checked( isset( $settings['enable_required_override'] ) && $settings['enable_required_override'] ); ?>>
									<label
										for="enable_required_override"><?php echo esc_html__( 'Enable required validation override for address fields', 'checkoutglut' ); ?></label>
								</div>
							</div>
						</div>
						<div class="shopglut-checkout-manager-card-footer">
							<input type="submit" name="save_settings" class="shopglut-checkout-manager-button"
								value="<?php echo esc_attr__( 'Save Changes', 'checkoutglut' ); ?>">
							<input type="submit" name="reset_settings" class="shopglut-checkout-manager-button secondary"
								value="<?php echo esc_attr__( 'Reset to Default', 'checkoutglut' ); ?>"
								onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to reset to default settings? All your changes will be lost.', 'checkoutglut' ) ); ?>')">
						</div>
					</div>
				</form>
			<?php endif; ?>
		</div>

		<!-- Import/Export Tab -->
		<div
			class="shopglut-checkout-manager-tab-content <?php echo $current_tab === 'import_export' ? 'active' : ''; ?>">
			<?php if ( $current_tab === 'import_export' ) : ?>
				<div class="shopglut-checkout-manager-card">
					<div class="shopglut-checkout-manager-card-header">
						<h3><?php echo esc_html__( 'Import/Export Settings', 'checkoutglut' ); ?></h3>
					</div>
					<div class="shopglut-checkout-manager-card-content">
						<div class="shopglut-checkout-manager-info-box info">
							<div class="shopglut-checkout-manager-info-box-icon">
								<span class="dashicons dashicons-info"></span>
							</div>
							<div class="shopglut-checkout-manager-info-box-content">
								<p><?php echo esc_html__( 'You can transfer the saved settings data between different installs by copying the text inside the box below. To import settings, paste the exported data in the import box and click "Import Settings".', 'checkoutglut' ); ?>
								</p>
							</div>
						</div>

						<div class="shopglut-checkout-manager-import-export">
							<h4><?php echo esc_html__( 'Export Settings', 'checkoutglut' ); ?></h4>
							<p><?php echo esc_html__( 'Copy the contents of this field to save your settings data.', 'checkoutglut' ); ?>
							</p>
							<textarea readonly onclick="this.focus();this.select()"
								rows="10"><?php echo esc_textarea( $this->getExportSettings() ); ?></textarea>
						</div>

						<div class="shopglut-checkout-manager-import-export">
							<h4><?php echo esc_html__( 'Import Settings', 'checkoutglut' ); ?></h4>
							<p><?php echo esc_html__( 'Paste your exported settings data here and click "Import Settings" to restore your settings.', 'checkoutglut' ); ?>
							</p>
							<form method="post" action="">
								<?php wp_nonce_field( 'shopglut_checkout_fields_action', 'shopglut_checkout_fields_nonce' ); ?>
								<textarea name="import_data" rows="10"></textarea>
								<p>
									<input type="submit" name="import_settings" class="shopglut-checkout-manager-button"
										value="<?php echo esc_attr__( 'Import Settings', 'checkoutglut' ); ?>"
										onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to import these settings? All current settings will be replaced.', 'checkoutglut' ) ); ?>')">
								</p>
							</form>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<!-- Edit Field Dialog -->
<div class="shopglut-checkout-manager-overlay" id="edit-overlay"></div>
<div class="shopglut-checkout-manager-dialog" id="edit-field-dialog">
	<div class="shopglut-checkout-manager-dialog-header">
		<h2><?php echo esc_html__( 'Edit Field', 'checkoutglut' ); ?></h2>
		<span class="shopglut-checkout-manager-dialog-close">&times;</span>
	</div>
	<div class="shopglut-checkout-manager-dialog-content">
		<form method="post" action="" id="edit-field-form">
			<?php wp_nonce_field( 'shopglut_checkout_fields_action', 'shopglut_checkout_fields_nonce' ); ?>
			<input type="hidden" name="field_id" id="edit_field_id_hidden">

			<table class="shopglut-checkout-manager-form-table">
				<tr>
					<th scope="row">
						<label for="edit_field_section"><?php echo esc_html__( 'Section', 'checkoutglut' ); ?></label>
					</th>
					<td>
						<select name="edit_field_section" id="edit_field_section" required>
							<?php foreach ( $field_sections as $section ) : ?>
								<option value="<?php echo esc_attr( $section ); ?>">
									<?php echo esc_html( ucfirst( $section ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="edit_field_id"><?php echo esc_html__( 'Field ID', 'checkoutglut' ); ?></label>
					</th>
					<td>
						<input type="text" name="edit_field_id" id="edit_field_id" class="regular-text" required>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="edit_field_type"><?php echo esc_html__( 'Field Type', 'checkoutglut' ); ?></label>
					</th>
					<td>
						<select name="edit_field_type" id="edit_field_type" required>
							<?php foreach ( $this->field_types as $type => $label ) : ?>
								<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="edit_field_label"><?php echo esc_html__( 'Label', 'checkoutglut' ); ?></label>
					</th>
					<td>
						<input type="text" name="edit_field_label" id="edit_field_label" class="regular-text" required>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label
							for="edit_field_placeholder"><?php echo esc_html__( 'Placeholder', 'checkoutglut' ); ?></label>
					</th>
					<td>
						<input type="text" name="edit_field_placeholder" id="edit_field_placeholder"
							class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="edit_field_class"><?php echo esc_html__( 'CSS Class', 'checkoutglut' ); ?></label>
					</th>
					<td>
						<input type="text" name="edit_field_class" id="edit_field_class" class="regular-text">
						<p class="description">
							<?php echo esc_html__( 'Space-separated list of CSS classes. Common values: form-row-first, form-row-last, form-row-wide', 'checkoutglut' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php echo esc_html__( 'Required', 'checkoutglut' ); ?>
					</th>
					<td>
						<label for="edit_field_required">
							<input type="checkbox" name="edit_field_required" id="edit_field_required" value="1">
							<?php echo esc_html__( 'Make this field required', 'checkoutglut' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="edit_field_priority"><?php echo esc_html__( 'Priority', 'checkoutglut' ); ?></label>
					</th>
					<td>
						<input type="number" name="edit_field_priority" id="edit_field_priority" class="small-text"
							value="10">
						<p class="description">
							<?php echo esc_html__( 'Controls the display order. Lower numbers display first.', 'checkoutglut' ); ?>
						</p>
					</td>
				</tr>
				<tr class="field-options-edit" style="display:none;">
					<th scope="row">
						<label for="edit_field_options"><?php echo esc_html__( 'Options', 'checkoutglut' ); ?></label>
					</th>
					<td>
						<textarea name="edit_field_options" id="edit_field_options" rows="5" cols="50"></textarea>
						<p class="description">
							<?php echo esc_html__( 'Enter each option on a new line. Format: value|label (e.g. option_1|Option 1). If no label is specified, the value will be used as the label.', 'checkoutglut' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php echo esc_html__( 'Validation', 'checkoutglut' ); ?>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php echo esc_html__( 'Validation', 'checkoutglut' ); ?>
							</legend>
							<label for="edit_field_validation_email">
								<input type="checkbox" name="edit_field_validation[]" id="edit_field_validation_email"
									value="email">
								<?php echo esc_html__( 'Email', 'checkoutglut' ); ?>
							</label><br>
							<label for="edit_field_validation_phone">
								<input type="checkbox" name="edit_field_validation[]" id="edit_field_validation_phone"
									value="phone">
								<?php echo esc_html__( 'Phone', 'checkoutglut' ); ?>
							</label><br>
							<label for="edit_field_validation_postcode">
								<input type="checkbox" name="edit_field_validation[]"
									id="edit_field_validation_postcode" value="postcode">
								<?php echo esc_html__( 'Postcode', 'checkoutglut' ); ?>
							</label><br>
							<label for="edit_field_validation_state">
								<input type="checkbox" name="edit_field_validation[]" id="edit_field_validation_state"
									value="state">
								<?php echo esc_html__( 'State', 'checkoutglut' ); ?>
							</label><br>
							<label for="edit_field_validation_number">
								<input type="checkbox" name="edit_field_validation[]" id="edit_field_validation_number"
									value="number">
								<?php echo esc_html__( 'Number', 'checkoutglut' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php echo esc_html__( 'Display Options', 'checkoutglut' ); ?>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text">
								<?php echo esc_html__( 'Display Options', 'checkoutglut' ); ?>
							</legend>
							<label for="edit_field_display_in_emails">
								<input type="checkbox" name="edit_field_display_in_emails"
									id="edit_field_display_in_emails" value="1">
								<?php echo esc_html__( 'Display in emails', 'checkoutglut' ); ?>
							</label><br>
							<label for="edit_field_display_in_order">
								<input type="checkbox" name="edit_field_display_in_order"
									id="edit_field_display_in_order" value="1">
								<?php echo esc_html__( 'Display in order details', 'checkoutglut' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
			</table>
		</form>
	</div>
	<div class="shopglut-checkout-manager-dialog-footer">
		<button type="button"
			class="shopglut-checkout-manager-button secondary cancel-button"><?php echo esc_html__( 'Cancel', 'checkoutglut' ); ?></button>
		<button type="button"
			class="shopglut-checkout-manager-button update-field-button"><?php echo esc_html__( 'Update Field', 'checkoutglut' ); ?></button>
	</div>
</div>

<!-- Delete Field Dialog -->
<div class="shopglut-checkout-manager-overlay" id="delete-overlay"></div>
<div class="shopglut-checkout-manager-dialog" id="delete-field-dialog">
	<div class="shopglut-checkout-manager-dialog-header">
		<h2><?php echo esc_html__( 'Delete Field', 'checkoutglut' ); ?></h2>
		<span class="shopglut-checkout-manager-dialog-close">&times;</span>
	</div>
	<div class="shopglut-checkout-manager-dialog-content">
		<p><?php echo esc_html__( 'Are you sure you want to delete this field? This action cannot be undone.', 'checkoutglut' ); ?>
		</p>
		<form method="post" action="" id="delete-field-form">
			<?php wp_nonce_field( 'shopglut_checkout_fields_action', 'shopglut_checkout_fields_nonce' ); ?>
			<input type="hidden" name="field_id" id="delete_field_id">
		</form>
	</div>
	<div class="shopglut-checkout-manager-dialog-footer">
		<button type="button"
			class="shopglut-checkout-manager-button secondary cancel-button"><?php echo esc_html__( 'Cancel', 'checkoutglut' ); ?></button>
		<button type="button"
			class="shopglut-checkout-manager-button danger delete-field-button"><?php echo esc_html__( 'Delete Field', 'checkoutglut' ); ?></button>
	</div>
</div>

<?php
// JavaScript functionality has been moved to /assets/admin-script.js
// CSS styling has been moved to /assets/admin-style.css
// Assets are automatically loaded via the assets.php file
?>