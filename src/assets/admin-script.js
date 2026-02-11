jQuery(document).ready(function($) {
    'use strict';

    /**
     * Checkout Field Manager
     */
    var CheckoutFieldManager = {

        /**
         * Initialize the manager
         */
        init: function() {
            this.bindEvents();
            this.initSortable();
            this.initTooltips();
            this.initDialogs();
            this.setupFormValidation();
            this.initTabs();
        },

        /**
         * Bind all event handlers
         */
        bindEvents: function() {
            // Tab navigation
            this.bindTabNavigation();

            // Field type changes
            this.bindFieldTypeChanges();

            // Edit buttons
            this.bindEditButtons();

            // Individual field toggles
            this.bindIndividualToggles();

            // Bulk action buttons
            this.bindBulkActions();

            // Form submissions
            this.bindFormSubmissions();

            // Modal dialogs
            this.bindModalEvents();
        },

        /**
         * Bind tab navigation events
         */
        bindTabNavigation: function() {
            // Main tabs - ensure they work properly
            $('.shopglut-checkout-manager-tab').not('.section-tab').off('click').on('click', function(e) {
                var href = $(this).attr('href');
                if (href && href.indexOf('?') !== -1) {
                    window.location.href = href;
                    return false;
                }
                return true;
            });

            // Section tabs
            $('.shopglut-checkout-manager-tab.section-tab').off('click').on('click', function(e) {
                e.preventDefault();

                // Remove active class from all section tabs
                $('.shopglut-checkout-manager-tab.section-tab').removeClass('active');
                $(this).addClass('active');

                // Show/hide section content with smooth transition
                var targetId = $(this).attr('href');
                $('.shopglut-checkout-manager-section-content').removeClass('active').fadeOut(150);

                setTimeout(function() {
                    $(targetId).addClass('active').fadeIn(200);
                }, 150);
            });
        },

        /**
         * Handle field type changes
         */
        bindFieldTypeChanges: function() {
            // New field type change
            $(document).on('change', '#new_field_type, #new_block_field_type', function() {
                var fieldType = $(this).val();
                var isBlockField = $(this).attr('id') === 'new_block_field_type';
                var prefix = isBlockField ? 'new_block_field_' : 'new_field_';

                if (['select', 'multiselect', 'radio', 'checkbox_group'].includes(fieldType)) {
                    $('.field-options-' + (isBlockField ? 'block' : 'new')).show();
                } else {
                    $('.field-options-' + (isBlockField ? 'block' : 'new')).hide();
                }
            });

            // Edit field type change
            $(document).on('change', '#edit_field_type, #edit_block_field_type', function() {
                var fieldType = $(this).val();
                var isBlockField = $(this).attr('id') === 'edit_block_field_type';

                if (['select', 'multiselect', 'radio', 'checkbox_group'].includes(fieldType)) {
                    $('.field-options-edit').show();
                } else {
                    $('.field-options-edit').hide();
                }
            });
        },

        /**
         * Bind edit button events
         */
        bindEditButtons: function() {
            var self = this;

            $(document).on('click', '.edit-field, .edit-block-field', function(e) {
                e.preventDefault();

                var $this = $(this);
                var isBlockField = $this.hasClass('edit-block-field');
                var prefix = isBlockField ? 'edit_block_field_' : 'edit_field_';
                var dialogId = isBlockField ? '#edit-block-field-dialog' : '#edit-field-dialog';
                var overlayId = isBlockField ? '#edit-block-field-overlay' : '#edit-overlay';

                // Populate form fields
                var fieldData = {
                    id: $this.data('field-id'),
                    key: $this.data('field-key'),
                    section: $this.data('section'),
                    type: $this.data('type'),
                    label: $this.data('label'),
                    placeholder: $this.data('placeholder'),
                    class: $this.data('class'),
                    required: $this.data('required'),
                    priority: $this.data('priority'),
                    options: $this.data('options'),
                    validation: $this.data('validation'),
                    displayEmails: $this.data('display-emails'),
                    displayOrder: $this.data('display-order'),
                    enabled: $this.data('enabled'),
                    custom: $this.data('custom')
                };

                // Check if modal elements exist
                if ($(dialogId).length === 0) {
                    console.error('Modal dialog not found:', dialogId);
                    self.showNotification('Edit dialog not found. Please refresh the page.', 'error');
                    return;
                }

                // Populate form
                self.populateEditForm(fieldData, prefix, isBlockField);

                // Show dialog
                $(overlayId + ', ' + dialogId).fadeIn(200);
            });
        },

        /**
         * Populate edit form with field data
         */
        populateEditForm: function(data, prefix, isBlockField) {
            // Basic fields
            $('#' + prefix + 'section').val(data.section);

            var fieldIdValue = data.key ? data.key.replace(data.section + '_', '') : '';
            $('#' + prefix + 'id').val(fieldIdValue);

            $('#' + prefix + 'type').val(data.type).trigger('change');
            $('#' + prefix + 'label').val(data.label);
            $('#' + prefix + 'placeholder').val(data.placeholder);
            $('#' + prefix + 'required').prop('checked', data.required == '1');
            $('#' + prefix + 'priority').val(data.priority);
            $('#' + prefix + 'id_hidden').val(data.id);

            if (!isBlockField) {
                $('#' + prefix + 'class').val(data.class);
            }

            // Handle options for select/radio/checkbox fields
            if (['select', 'multiselect', 'radio', 'checkbox_group'].includes(data.type)) {
                this.populateOptionsField(data.options, prefix + 'options');
            }

            // Validation (non-block fields only)
            if (!isBlockField && data.validation) {
                var validations = data.validation.split(',');
                $('[name="' + prefix + 'validation[]"]').prop('checked', false);
                validations.forEach(function(validation) {
                    $('#' + prefix + 'validation_' + validation.trim()).prop('checked', true);
                });
            }

            // Display settings
            $('#' + prefix + 'display_in_emails').prop('checked', data.displayEmails == '1');
            $('#' + prefix + 'display_in_order').prop('checked', data.displayOrder == '1');
            $('#' + prefix + 'enabled').prop('checked', data.enabled == '1');
        },

        /**
         * Populate options field from JSON data
         */
        populateOptionsField: function(optionsJson, fieldId) {
            if (!optionsJson) return;

            try {
                var options = JSON.parse(optionsJson);
                var optionsText = '';

                for (var key in options) {
                    if (options.hasOwnProperty(key)) {
                        if (key === options[key]) {
                            optionsText += key + '\n';
                        } else {
                            optionsText += key + '|' + options[key] + '\n';
                        }
                    }
                }

                $('#' + fieldId).val(optionsText.trim());
            } catch (e) {
                $('#' + fieldId).val('');
            }
        },

        /**
         * Bind individual field toggle events
         */
        bindIndividualToggles: function() {
            var self = this;

            $(document).on('click', '.toggle-field, .toggle-block-field', function(e) {
                e.preventDefault();

                var $this = $(this);
                var fieldId = $this.data('field-id');
                var currentlyEnabled = $this.data('enabled') == '1';
                var isBlockField = $this.hasClass('toggle-block-field');

                if (!fieldId) {
                    self.showNotification('Field ID not found.', 'error');
                    return;
                }

                // Toggle the field
                self.toggleField(fieldId, !currentlyEnabled, isBlockField);
            });
        },

        /**
         * Toggle field via AJAX or form submission
         */
        toggleField: function(fieldId, enable, isBlockField) {
            // Use the localized nonce from wp_localize_script
            var nonce = '';
            if (typeof shopglut_checkout_fields !== 'undefined' && shopglut_checkout_fields.nonce) {
                nonce = shopglut_checkout_fields.nonce;
            } else {
                // Fallback to form field nonce
                var nonceField = $('input[name="shopglut_checkout_fields_nonce"]').first();
                if (nonceField.length > 0) {
                    nonce = nonceField.val();
                }
            }

            if (!nonce) {
                this.showNotification('Security token not found. Please refresh the page.', 'error');
                return;
            }

            var formData = {
                'field_id': fieldId,
                'enable': enable ? '1' : '0',
                'shopglut_checkout_fields_nonce': nonce
            };

            var actionName = isBlockField ? 'toggle_block_field' : 'toggle_field';
            formData[actionName] = '1';

            // Use AJAX for individual field toggle
            if (typeof shopglut_checkout_fields !== 'undefined' && shopglut_checkout_fields.ajax_url) {
                this.submitViaAjax(formData, actionName);
            } else {
                // Fallback to form submission
                this.submitForm(formData);
            }
        },

        /**
         * Bind bulk action events
         */
        bindBulkActions: function() {
            var self = this;

            // Enable/Disable All buttons
            $(document).on('click', '.enable-all-fields, .disable-all-fields, .enable-all-block-fields, .disable-all-block-fields', function(e) {
                e.preventDefault();

                var $this = $(this);
                var section = $this.data('section');
                var enable = $this.hasClass('enable-all-fields') || $this.hasClass('enable-all-block-fields');
                var isBlockField = $this.hasClass('enable-all-block-fields') || $this.hasClass('disable-all-block-fields');

                // Add loading state
                $this.addClass('shopglut-checkout-manager-loading').prop('disabled', true);

                self.toggleAllFields(section, enable, isBlockField);
            });

            // Reset fields
            $(document).on('click', '.reset-fields', function(e) {
                e.preventDefault();

                var $this = $(this);
                var section = $this.data('section');

                if (confirm('Are you sure you want to reset all fields in this section to their default settings? All customizations will be lost.')) {
                    // Add loading state
                    $this.addClass('shopglut-checkout-manager-loading').prop('disabled', true);
                    self.resetFields(section);
                }
            });
        },

        /**
         * Toggle all fields in a section
         */
        toggleAllFields: function(section, enable, isBlockField) {
            var actionName = isBlockField ? 'toggle_all_block_fields' : 'toggle_all_fields';

            // Use the localized nonce from wp_localize_script
            var nonce = '';
            if (typeof shopglut_checkout_fields !== 'undefined' && shopglut_checkout_fields.nonce) {
                nonce = shopglut_checkout_fields.nonce;
            } else {
                // Fallback to form field nonce
                var nonceField = $('input[name="shopglut_checkout_fields_nonce"]').first();
                if (nonceField.length > 0) {
                    nonce = nonceField.val();
                }
            }

            if (!nonce) {
                this.showNotification('Security token not found. Please refresh the page.', 'error');
                return;
            }

            var formData = {
                'section': section,
                'enable': enable ? '1' : '0',
                'shopglut_checkout_fields_nonce': nonce
            };
            formData[actionName] = '1';

            // Try AJAX first if available, otherwise fall back to form submission
            if (typeof shopglut_checkout_fields !== 'undefined' && shopglut_checkout_fields.ajax_url) {
                this.submitViaAjax(formData, actionName);
            } else {
                this.submitForm(formData);
            }
        },

        /**
         * Reset fields in a section
         */
        resetFields: function(section) {
            // Use the localized nonce from wp_localize_script
            var nonce = '';
            if (typeof shopglut_checkout_fields !== 'undefined' && shopglut_checkout_fields.nonce) {
                nonce = shopglut_checkout_fields.nonce;
            } else {
                // Fallback to form field nonce
                var nonceField = $('input[name="shopglut_checkout_fields_nonce"]').first();
                if (nonceField.length > 0) {
                    nonce = nonceField.val();
                }
            }

            if (!nonce) {
                this.showNotification('Security token not found. Please refresh the page.', 'error');
                return;
            }

            var formData = {
                'section': section,
                'reset_fields': '1',
                'shopglut_checkout_fields_nonce': nonce
            };

            // Try AJAX first if available, otherwise fall back to form submission
            if (typeof shopglut_checkout_fields !== 'undefined' && shopglut_checkout_fields.ajax_url) {
                this.submitViaAjax(formData, 'reset_fields');
            } else {
                this.submitForm(formData);
            }
        },

        /**
         * Submit via AJAX
         */
        submitViaAjax: function(formData, action) {
            var self = this;

            // Map our action names to the correct PHP handler names
            var actionMap = {
                'toggle_all_fields': 'shopglut_toggle_checkout_fields',
                'toggle_all_block_fields': 'shopglut_toggle_checkout_fields',
                'toggle_field': 'shopglut_toggle_checkout_field',
                'toggle_block_field': 'shopglut_toggle_checkout_field',
                'reset_fields': 'shopglut_reset_checkout_fields',
                'update_field': 'shopglut_save_checkout_field',
                'update_block_field': 'shopglut_save_checkout_field',
                'reorder_fields': 'shopglut_reorder_checkout_fields'
            };

            var ajaxAction = actionMap[action] || 'shopglut_' + action;
            var ajaxData = $.extend({}, formData);
            delete ajaxData.shopglut_checkout_fields_nonce; // Remove from data, add separately

            $.ajax({
                url: shopglut_checkout_fields.ajax_url,
                type: 'POST',
                data: $.extend(ajaxData, {
                    action: ajaxAction,
                    nonce: formData.shopglut_checkout_fields_nonce
                }),
                success: function(response) {
                    if (response && response.success) {
                        // Extract the message properly from the response
                        var message = 'Operation completed successfully.';
                        if (response.data) {
                            if (typeof response.data === 'string') {
                                message = response.data;
                            } else if (response.data.message) {
                                message = response.data.message;
                            }
                        }

                        self.showNotification(message, 'success');

                        // Auto-reload after successful operation to show changes
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);

                    } else {
                        var errorMsg = 'Operation failed. Please try again.';
                        if (response && response.data) {
                            if (typeof response.data === 'string') {
                                errorMsg = response.data;
                            } else if (response.data.message) {
                                errorMsg = response.data.message;
                            }
                        }
                        console.error('AJAX operation failed:', errorMsg);
                        self.showNotification(errorMsg, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error details:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status,
                        url: shopglut_checkout_fields.ajax_url,
                        action: ajaxAction
                    });

                    self.showNotification('AJAX request failed. Check console for details.', 'error');
                }
            });
        },

        /**
         * Form submission handling
         */
        bindFormSubmissions: function() {
            var self = this;

            // Update field buttons
            $(document).on('click', '.update-field-button, .update-block-field-button', function(e) {
                e.preventDefault();

                var $button = $(this);
                var isBlockField = $button.hasClass('update-block-field-button');
                var formId = isBlockField ? '#edit-block-field-form' : '#edit-field-form';
                var actionName = isBlockField ? 'update_block_field' : 'update_field';

                // Get form data
                var $form = $(formId);
                if ($form.length === 0) {
                    console.error('Form not found:', formId);
                    self.showNotification('Form not found. Please refresh the page.', 'error');
                    return;
                }

                // Serialize form data
                var formData = {};
                var serializedArray = $form.serializeArray();

                serializedArray.forEach(function(item) {
                    formData[item.name] = item.value;
                });

                // Handle checkboxes separately (they don't appear in serializeArray if unchecked)
                $form.find('input[type="checkbox"]').each(function() {
                    var $checkbox = $(this);
                    var name = $checkbox.attr('name');
                    if (name) {
                        formData[name] = $checkbox.is(':checked') ? '1' : '0';
                    }
                });

                // Add action and nonce
                formData[actionName] = '1';

                // Use the localized nonce
                if (typeof shopglut_checkout_fields !== 'undefined' && shopglut_checkout_fields.nonce) {
                    formData['shopglut_checkout_fields_nonce'] = shopglut_checkout_fields.nonce;
                } else {
                    // Fallback to form nonce if available
                    var formNonce = $form.find('input[name="shopglut_checkout_fields_nonce"]').val();
                    if (formNonce) {
                        formData['shopglut_checkout_fields_nonce'] = formNonce;
                    }
                }

                // Validate required fields
                var missingFields = [];
                var requiredFields = $form.find('[required]');

                requiredFields.each(function() {
                    var $field = $(this);
                    var name = $field.attr('name');
                    var value = formData[name];
                    var fieldLabel = $field.closest('tr').find('th').text().trim() || name;

                    if (!value || (typeof value === 'string' && value.trim() === '')) {
                        missingFields.push(fieldLabel);
                        $field.addClass('error');
                    } else {
                        $field.removeClass('error');
                    }
                });

                if (missingFields.length > 0) {
                    console.error('Missing required fields:', missingFields);
                    self.showNotification('Please fill in all required fields: ' + missingFields.join(', '), 'error');
                    return;
                }

                // Use AJAX if available, otherwise fallback to form submission
                if (typeof shopglut_checkout_fields !== 'undefined' && shopglut_checkout_fields.ajax_url) {
                    self.submitViaAjax(formData, actionName);
                } else {
                    // Fallback to traditional form submission
                    $form.append('<input type="hidden" name="' + actionName + '" value="1">');
                    $form.submit();
                }
            });
        },

        /**
         * Modal event handling
         */
        bindModalEvents: function() {
            // Close modal when clicking outside or on close button
            $(document).on('click', '.shopglut-checkout-manager-dialog-overlay, .close-dialog', function(e) {
                if (e.target === this || $(this).hasClass('close-dialog')) {
                    $('.shopglut-checkout-manager-dialog-overlay, .shopglut-checkout-manager-dialog').fadeOut(200);
                }
            });

            // Prevent modal from closing when clicking inside dialog
            $(document).on('click', '.shopglut-checkout-manager-dialog', function(e) {
                e.stopPropagation();
            });

            // Handle escape key
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27) { // Escape key
                    $('.shopglut-checkout-manager-dialog-overlay, .shopglut-checkout-manager-dialog').fadeOut(200);
                }
            });
        },

        /**
         * Initialize sortable functionality
         */
        initSortable: function() {
            var self = this;

            if (typeof $.fn.sortable !== 'undefined') {
                // Find all table bodies that contain field rows
                var $sortableTables = $('.shopglut-checkout-manager-table tbody');

                $sortableTables.each(function() {
                    var $tbody = $(this);
                    var $rows = $tbody.find('tr.sortable-row');

                    if ($rows.length > 0) {
                        // Initialize sortable for each table tbody
                        $tbody.sortable({
                            handle: '.sort-handle .dashicons',
                            placeholder: 'ui-sortable-placeholder',
                            helper: function(e, tr) {
                                // Get field name (label or ID)
                                var fieldLabel = tr.find('td:nth-child(5)').text().trim();
                                var fieldId = tr.find('td:nth-child(3)').text().trim();
                                var fieldName = fieldLabel || fieldId || 'Field';
                                var $simpleHelper = $('<div class="drag-helper-simple">' + fieldName + '</div>');
                                return $simpleHelper;
                            },
                            distance: 5,
                            delay: 100,
                            start: function(e, ui) {
                                // Set placeholder dimensions
                                ui.placeholder.height(ui.item.height());

                                // Get the field name and add it to placeholder
                                var fieldLabel = ui.item.find('td:nth-child(5)').text().trim();
                                var fieldId = ui.item.find('td:nth-child(3)').text().trim();
                                var fieldName = fieldLabel || fieldId;
                                ui.placeholder.attr('data-field-name', fieldName);

                                // Add sorting class to the item
                                ui.item.addClass('ui-sortable-sorting');

                                // Disable hover effects on all rows during drag
                                $tbody.addClass('dragging-active');
                            },
                            stop: function(e, ui) {
                                // Remove sorting class
                                ui.item.removeClass('ui-sortable-sorting');

                                // Re-enable hover effects
                                $tbody.removeClass('dragging-active');
                            },
                            update: function(e, ui) {
                                // Get the new field order
                                var fieldOrder = [];
                                var section = '';
                                var isBlockField = false;

                                $(this).find('tr').each(function() {
                                    var $row = $(this);
                                    var fieldId = $row.data('field-id');
                                    if (fieldId) {
                                        fieldOrder.push(fieldId);

                                        if (!section) {
                                            section = $row.data('section') || self.getCurrentSection();
                                            isBlockField = $row.hasClass('block-field-row') ||
                                                          $row.find('.toggle-block-field, .edit-block-field').length > 0;
                                        }
                                    }
                                });

                                if (fieldOrder.length > 0) {
                                    // Save the new order
                                    self.saveFieldOrder(fieldOrder, section, isBlockField);
                                }
                            }
                        });
                    }
                });

            } else {
                console.warn('jQuery UI Sortable not available. Drag and drop functionality disabled.');
                // Hide drag handles if sortable is not available
                $('.sort-handle').hide();
            }
        },

        /**
         * Initialize tab behavior
         */
        initTabs: function() {
            // Hide all section content except active one
            $('.shopglut-checkout-manager-section-content').hide();
            $('.shopglut-checkout-manager-section-content.active').show();
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            // Add tooltips to icons and buttons
            $('[title]').each(function() {
                var $this = $(this);
                var title = $this.attr('title');

                if (title) {
                    $this.removeAttr('title').on('mouseenter', function() {
                        var tooltip = $('<div class="shopglut-tooltip">' + title + '</div>');
                        $('body').append(tooltip);

                        var offset = $this.offset();
                        tooltip.css({
                            position: 'absolute',
                            top: offset.top - tooltip.outerHeight() - 5,
                            left: offset.left + ($this.outerWidth() / 2) - (tooltip.outerWidth() / 2)
                        }).fadeIn(200);

                    }).on('mouseleave', function() {
                        $('.shopglut-tooltip').fadeOut(200, function() {
                            $(this).remove();
                        });
                    });
                }
            });
        },

        /**
         * Initialize dialog functionality
         */
        initDialogs: function() {
            // Initialize any existing dialog elements
            if (typeof $.fn.dialog !== 'undefined') {
                $('.shopglut-checkout-manager-dialog').each(function() {
                    var $dialog = $(this);
                    if (!$dialog.hasClass('ui-dialog-content')) {
                        $dialog.dialog({
                            autoOpen: false,
                            modal: true,
                            width: 600,
                            height: 'auto',
                            resizable: false,
                            dialogClass: 'shopglut-checkout-manager-modal'
                        });
                    }
                });
            }
        },

        /**
         * Setup form validation
         */
        setupFormValidation: function() {
            // Add validation classes to required fields
            $('input[required], select[required], textarea[required]').addClass('shopglut-required');

            // Real-time validation
            $(document).on('blur', '.shopglut-required', function() {
                var $field = $(this);
                if ($field.val().trim() === '') {
                    $field.addClass('error');
                } else {
                    $field.removeClass('error');
                }
            });
        },

        /**
         * Submit form data
         */
        submitForm: function(data) {
            var $form = $('<form>', {
                method: 'post',
                action: '',
                style: 'display: none;'
            });

            for (var key in data) {
                if (data.hasOwnProperty(key)) {
                    $form.append($('<input>', {
                        type: 'hidden',
                        name: key,
                        value: data[key]
                    }));
                }
            }

            $('body').append($form);
            $form.submit();
        },

        /**
         * Get current section from URL or page context
         */
        getCurrentSection: function() {
            // Try to get section from URL parameters
            var urlParams = new URLSearchParams(window.location.search);
            var section = urlParams.get('section');

            if (section) {
                return section;
            }

            // Try to get from active tab
            var $activeTab = $('.shopglut-checkout-manager-tab.section-tab.active');
            if ($activeTab.length > 0) {
                var href = $activeTab.attr('href');
                if (href && href.indexOf('#') > -1) {
                    return href.split('#')[1];
                }
            }

            // Default to billing
            return 'billing';
        },

        /**
         * Save field order via AJAX
         */
        saveFieldOrder: function(fieldOrder, section, isBlockField) {
            var self = this;

            // Use the localized nonce
            var nonce = '';
            if (typeof shopglut_checkout_fields !== 'undefined' && shopglut_checkout_fields.nonce) {
                nonce = shopglut_checkout_fields.nonce;
            } else {
                // Fallback to form field nonce
                var nonceField = $('input[name="shopglut_checkout_fields_nonce"]').first();
                if (nonceField.length > 0) {
                    nonce = nonceField.val();
                }
            }

            if (!nonce) {
                self.showNotification('Security token not found. Please refresh the page.', 'error');
                return;
            }

            var formData = {
                'field_order': JSON.stringify(fieldOrder),
                'section': section,
                'is_block_field': isBlockField ? '1' : '0',
                'shopglut_checkout_fields_nonce': nonce,
                'reorder_fields': '1'
            };

            // Show loading notification
            self.showNotification('Reordering fields...', 'info');

            // Use AJAX if available, otherwise fallback to form submission
            if (typeof shopglut_checkout_fields !== 'undefined' && shopglut_checkout_fields.ajax_url) {
                self.submitViaAjax(formData, 'reorder_fields');
            } else {
                // Create a hidden form and submit
                var $form = $('<form>', {
                    method: 'post',
                    action: '',
                    style: 'display: none;'
                });

                for (var key in formData) {
                    if (formData.hasOwnProperty(key)) {
                        $form.append($('<input>', {
                            type: 'hidden',
                            name: key,
                            value: formData[key]
                        }));
                    }
                }

                $('body').append($form);
                $form.submit();
            }
        },

        /**
         * Show notification to user - uses centralized ShopGlutNotification utility
         */
        showNotification: function(message, type) {
            type = type || 'info';
            if (typeof ShopGlutNotification !== 'undefined') {
                ShopGlutNotification.show(message, type, { duration: 5000 });
            } else {
                // Fallback if centralized utility not loaded
                var className = 'shopglut-notification shopglut-notification-' + type;
                $('.shopglut-notification').remove();
                var $notification = $('<div class="' + className + '">' + message + '</div>');
                $('body').append($notification);
                $notification.css({
                    position: 'fixed',
                    top: '20px',
                    right: '20px',
                    zIndex: 99999,
                    padding: '12px 20px',
                    backgroundColor: type === 'error' ? '#dc3545' : type === 'success' ? '#28a745' : '#17a2b8',
                    color: '#ffffff',
                    borderRadius: '4px',
                    fontSize: '14px',
                    boxShadow: '0 2px 5px rgba(0,0,0,0.2)'
                }).fadeIn(300);
                setTimeout(function() {
                    $notification.fadeOut(300, function() { $(this).remove(); });
                }, 5000);
                $notification.on('click', function() {
                    $(this).fadeOut(300, function() { $(this).remove(); });
                });
            }
        }
    };

    // Initialize when document is ready
    CheckoutFieldManager.init();
});