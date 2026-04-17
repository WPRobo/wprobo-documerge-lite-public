/**
 * WPRobo DocuMerge — Form Builder
 *
 * Handles the two-panel form builder UI: field type selection,
 * drag-and-drop canvas ordering, field settings editing,
 * merge tag generation, and form save/delete via AJAX.
 *
 * @package WPRobo_DocuMerge
 * @since   1.0.0
 */

(function($) {
    'use strict';

    /**
     * WPRobo DocuMerge Form Builder module.
     *
     * @since 1.0.0
     * @type  {Object}
     */
    var WPRoboDocuMerge_FormBuilder = {

        /**
         * Counter for generating unique field IDs.
         *
         * @since 1.0.0
         * @type  {number}
         */
        fieldCounter: 0,

        /**
         * Array of field configuration objects.
         *
         * @since 1.0.0
         * @type  {Array}
         */
        fields: [],

        /**
         * Map of field types to their human-readable labels.
         *
         * @since 1.0.0
         * @type  {Object}
         */
        typeLabels: {
            text:        (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_text) || 'Text',
            textarea:    (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_textarea) || 'Textarea',
            email:       (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_email) || 'Email',
            phone:       (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_phone) || 'Phone',
            number:      (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_number) || 'Number',
            date:        (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_date) || 'Date',
            dropdown:    (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_dropdown) || 'Dropdown',
            radio:       (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_radio) || 'Radio',
            checkbox:    (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_checkbox) || 'Checkbox',
            file_upload: (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_file_upload) || 'File Upload',
            address:     (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_address) || 'Address',
            name:        (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_name) || 'Name',
            hidden:      (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_hidden) || 'Hidden',
            html:             (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_html) || 'HTML Block',
            section_divider:  (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_section_divider) || 'Section Divider',
            url:              (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_url) || 'Website',
            ip_address:       (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_ip_address) || 'IP Address',
            tracking:         (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_tracking) || 'Tracking',
            signature:        (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_signature) || 'Signature',
            payment:          (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_payment) || 'Payment',
            captcha:          (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_captcha) || 'CAPTCHA',
            password:         (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_password) || 'Password',
            rating:           (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_rating) || 'Rating',
            repeater:         (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n.field_type_repeater) || 'Repeater'
        },

        /**
         * Field types that are Pro-only.
         *
         * Forms created in Pro may contain these fields; in Lite
         * they are rendered read-only with a PRO badge.
         *
         * @since 1.0.0
         * @type  {Array}
         */
        proFieldTypes: ['signature', 'payment', 'captcha', 'file_upload', 'repeater', 'rating', 'address', 'name', 'hidden', 'html', 'section_divider', 'password'],

        /**
         * Initialize the form builder module.
         *
         * Binds events, initializes sortable, and loads existing
         * fields when editing an existing form.
         *
         * @since 1.0.0
         */
        init: function() {
            this.bindEvents();
            this.initSortable();

            if ( typeof wprobo_documerge_form_fields !== 'undefined' && wprobo_documerge_form_fields ) {
                this.loadExistingFields();
            }

            // Restore active tab ONLY after a save redirect (has &saved=1).
            var urlParams  = new URLSearchParams(window.location.search);
            var wasSaved   = urlParams.get('saved') === '1';

            if ( wasSaved ) {
                var tabParam = urlParams.get('tab');
                if ( tabParam && tabParam !== 'fields' ) {
                    var $targetTab = $('.wdm-builder-main-tab[data-tab="' + tabParam + '"]');
                    if ( $targetTab.length ) {
                        $targetTab.trigger('click');
                    }
                }

                // Restore active settings sub-tab.
                if ( subtabParam ) {
                    var $targetSubtab = $('.wdm-settings-subtab[data-subtab="' + subtabParam + '"]');
                    if ( $targetSubtab.length ) {
                        $targetSubtab.trigger('click');
                    }
                }

                // Clean URL so tab params don't persist on manual navigation.
                if ( window.history && window.history.replaceState ) {
                    var cleanUrl = window.location.pathname + '?page=wprobo-documerge-forms&action=edit&id=' + $('#wdm-form-id').val();
                    window.history.replaceState( null, '', cleanUrl );
                }
            }
        },

        /**
         * Bind all UI event handlers.
         *
         * @since 1.0.0
         */
        bindEvents: function() {
            var self = this;

            // Add field from sidebar.
            $(document).on('click', '.wdm-field-type-btn', function(e) {
                e.preventDefault();
                self.addField.call(self, e, $(this));
            });

            // Toggle field settings panel.
            $(document).on('click', '.wdm-field-edit-btn', function(e) {
                e.preventDefault();
                self.toggleFieldSettings($(this));
            });

            // Delete field from canvas.
            $(document).on('click', '.wdm-field-delete-btn', function(e) {
                e.preventDefault();
                self.deleteField($(this));
            });

            // Save form.
            $(document).on('click', '#wdm-save-form', function(e) {
                e.preventDefault();
                self.saveForm();
            });

            // Preview document.
            $(document).on('click', '#wdm-preview-doc', function(e) {
                e.preventDefault();
                self.wprobo_documerge_preview_document();
            });

            // Copy shortcode.
            $(document).on('click', '.wdm-copy-shortcode', function(e) {
                e.preventDefault();
                self.copyShortcode($(this));
            });

            // Delete form.
            $(document).on('click', '.wdm-form-delete', function(e) {
                e.preventDefault();
                self.deleteForm($(this));
            });

            // Update field data on setting change.
            $(document).on('change', '.wdm-builder-setting-input', function(e) {
                self.updateFieldData($(this));
            });

            // Update merge tag on label input.
            $(document).on('input', '.wdm-builder-setting-input[data-setting="label"]', function(e) {
                self.updateMergeTag($(this));
            });

            // Options manager events.
            $(document).on('click', '.wdm-add-option', function(e) {
                e.preventDefault();
                self.addOption(e);
            });

            $(document).on('click', '.wdm-option-remove', function(e) {
                e.preventDefault();
                self.removeOption(e);
            });

            $(document).on('input', '.wdm-option-label', function(e) {
                self.updateOptionValue(e);
            });

            $(document).on('change', '.wdm-option-label, .wdm-option-value', function(e) {
                self.updateFieldOptions(e);
            });


            // Builder main tabs (Fields vs Settings).
            $(document).on('click', '.wdm-builder-main-tab', function(e) {
                e.preventDefault();
                var $tab = $(this);
                var tabName = $tab.data('tab');

                $('.wdm-builder-main-tab').removeClass('wdm-builder-main-tab-active');
                $tab.addClass('wdm-builder-main-tab-active');

                $('.wdm-builder-main-content').removeClass('wdm-builder-main-content-active');
                $('.wdm-builder-main-content[data-tab="' + tabName + '"]').addClass('wdm-builder-main-content-active');
            });

            // Create Page with shortcode.
            $(document).on('click', '#wdm-create-page', function(e) {
                e.preventDefault();
                var formId = $('#wdm-form-id').val();
                if ( ! formId || formId === '0' ) {
                    self.showNotice('error', wprobo_documerge_vars.i18n.save_form_first || 'Please save the form first.');
                    return;
                }

                var formTitle = $.trim($('#wdm-form-title').val()) || (wprobo_documerge_vars.i18n.documerge_form || 'DocuMerge Form');
                var $btn = $(this);
                $btn.prop('disabled', true).addClass('wdm-loading');

                $.ajax({
                    url:  wprobo_documerge_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action:    'wprobo_documerge_create_form_page',
                        nonce:     wprobo_documerge_vars.nonce,
                        form_id:   formId,
                        form_title: formTitle
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).removeClass('wdm-loading');
                        if ( response.success && response.data.edit_url ) {
                            self.showNotice('success', wprobo_documerge_vars.i18n.page_created || 'Page created! Opening in new tab...');
                            window.open(response.data.edit_url, '_blank');
                        } else {
                            self.showNotice('error', response.data ? response.data.message : (wprobo_documerge_vars.i18n.failed_create_page || 'Failed to create page.'));
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).removeClass('wdm-loading');
                        self.showNotice('error', wprobo_documerge_vars.i18n.network_error_retry || 'Network error.');
                    }
                });
            });

            // Settings sub-tab switching.
            $(document).on('click', '.wdm-settings-subtab', function(e) {
                e.preventDefault();
                var $stab = $(this);
                var subtab = $stab.data('subtab');

                $('.wdm-settings-subtab').removeClass('wdm-settings-subtab-active');
                $stab.addClass('wdm-settings-subtab-active');

                $('.wdm-settings-subtab-content').removeClass('wdm-settings-subtab-active');
                $('.wdm-settings-subtab-content[data-subtab="' + subtab + '"]').addClass('wdm-settings-subtab-active');
            });

            // Load external form fields via AJAX when the WPForms dropdown changes.
            $(document).on('change', '#wdm-external-form-id', function() {
                var extFormId = $(this).val();
                var $mapWrap  = $('#wdm-field-map-wrap');

                if ( ! extFormId ) {
                    $mapWrap.html('<p class="wdm-text-muted">' + $('<span>').text(wprobo_documerge_vars.i18n.select_external_form || 'Select an external form to see field mapping.').html() + '</p>');
                    return;
                }

                $mapWrap.html('<p class="wdm-text-muted">' + $('<span>').text(wprobo_documerge_vars.i18n.loading_fields || 'Loading fields...').html() + '</p>');

                $.ajax({
                    url:  wprobo_documerge_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action:           'wprobo_documerge_get_external_fields',
                        nonce:            wprobo_documerge_vars.nonce,
                        external_form_id: extFormId,
                        integration:      $('#wdm-form-integration').val() || '',
                        template_id:      $('#wdm-form-template').val() || ''
                    },
                    success: function(response) {
                        if ( response.success && response.data.html ) {
                            $mapWrap.html(response.data.html);
                        } else {
                            $mapWrap.html('<p class="wdm-text-muted">' + $('<span>').text(wprobo_documerge_vars.i18n.no_fields_found || 'No fields found.').html() + '</p>');
                        }
                    },
                    error: function() {
                        $mapWrap.html('<p class="wdm-text-muted">' + $('<span>').text(wprobo_documerge_vars.i18n.failed_load_fields || 'Failed to load fields.').html() + '</p>');
                    }
                });
            });

            // Alignment radio buttons — toggle active class for browsers without :has().
            $(document).on('change', '.wdm-align-option input[type="radio"]', function() {
                var $group = $(this).closest('.wdm-btn-align-selector');
                $group.find('.wdm-align-option').removeClass('wdm-align-active');
                $(this).closest('.wdm-align-option').addClass('wdm-align-active');
            });

            // Set initial active state on page load.
            $('.wdm-align-option input[type="radio"]:checked').closest('.wdm-align-option').addClass('wdm-align-active');


            // Click field label to open settings (same as edit button).
            $(document).on('click', '.wdm-field-label-preview', function(e) {
                e.preventDefault();
                var $card = $(this).closest('.wdm-field-card');
                if ($card.hasClass('wdm-field-card-pro')) { return; }
                $card.find('.wdm-field-card-settings').slideToggle(200);
            });

            // Field settings tab switching.
            $(document).on('click', '.wdm-field-tab', function(e) {
                e.preventDefault();
                var $tab = $(this);
                var $card = $tab.closest('.wdm-field-card');
                var tabName = $tab.data('tab');

                // Switch tab buttons.
                $card.find('.wdm-field-tab').removeClass('wdm-field-tab-active');
                $tab.addClass('wdm-field-tab-active');

                // Switch content panels.
                $card.find('.wdm-field-tab-content').removeClass('wdm-field-tab-active');
                $card.find('.wdm-field-tab-content[data-tab="' + tabName + '"]').addClass('wdm-field-tab-active');
            });
        },

        /**
         * Initialize jQuery UI Sortable on the builder canvas.
         *
         * @since 1.0.0
         */
        initSortable: function() {
            var self = this;

            // Make canvas sortable for reordering AND receiving drops.
            $('#wdm-builder-canvas').sortable({
                handle:      '.wdm-field-drag-handle',
                placeholder: 'wdm-field-placeholder',
                items:       '.wdm-field-card',
                distance:    3,
                tolerance:   'pointer',
                cursor:      'grabbing',
                revert:      false,
                scroll:      true,
                scrollSensitivity: 60,
                helper: function(e, el) {
                    var $clone = el.clone().css({ width: el.outerWidth(), opacity: 0.9 });
                    return $clone;
                },
                start: function(e, ui) {
                    ui.item.css('visibility', 'hidden');
                },
                stop: function(e, ui) {
                    ui.item.css('visibility', '');
                },
                update: function() {
                    self.updateFieldOrder();
                }
            });

            // Track drop insertion point when dragging from sidebar.
            self._dropIndex = -1;

            // Make sidebar field type buttons draggable.
            $('.wdm-field-type-btn').not('[disabled]').draggable({
                helper: function() {
                    var type  = $(this).data('type') || 'text';
                    var label = $.trim($(this).text());
                    var badge = (self.typeLabels[type] || type).toUpperCase();
                    return $('<div class="wdm-dragging-field-card">' +
                        '<span class="wdm-field-drag-handle dashicons dashicons-menu"></span>' +
                        '<span class="wdm-field-card-type">' + badge + '</span> ' +
                        '<span class="wdm-field-card-label">' + $('<span>').text(label).html() + '</span>' +
                    '</div>');
                },
                appendTo:       'body',
                zIndex:         100010,
                revert:         'invalid',
                revertDuration: 150,
                cursor:         'grabbing',
                cursorAt:       { left: 40, top: 18 },
                cancel:         '',
                distance:       5,
                start: function() {
                    $('#wdm-builder-canvas').addClass('wdm-canvas-drop-active');
                    self._dropIndex = -1;
                },
                stop: function() {
                    $('#wdm-builder-canvas').removeClass('wdm-canvas-drop-active');
                    $('.wdm-drop-indicator').remove();
                },
                drag: function(e) {
                    var mouseY = e.pageY;
                    var mouseX = e.pageX;
                    $('.wdm-drop-indicator').remove();

                    // Find the canvas the cursor is over.
                    var $target = null;
                    {
                        var $canvas = $('#wdm-builder-canvas');
                        var co = $canvas.offset();
                        if (mouseX >= co.left && mouseX <= co.left + $canvas.outerWidth() &&
                            mouseY >= co.top  && mouseY <= co.top + $canvas.outerHeight()) {
                            $target = $canvas;
                        }
                    }

                    if (!$target) { self._dropTarget = null; return; }

                    self._dropTarget = $target;
                    var $cards = $target.find('> .wdm-field-card');
                    var placed = false;

                    $cards.each(function(i) {
                        var $card  = $(this);
                        var top    = $card.offset().top;
                        var middle = top + ($card.outerHeight() / 2);

                        if (mouseY < middle) {
                            self._dropIndex = i;
                            $('<div class="wdm-drop-indicator"></div>').insertBefore($card);
                            placed = true;
                            return false;
                        }
                    });

                    if (!placed) {
                        self._dropIndex = $cards.length;
                        $target.append('<div class="wdm-drop-indicator"></div>');
                    }
                }
            });

            // Make canvas accept drops from sidebar.
            $('#wdm-builder-canvas').droppable({
                accept:     '.wdm-field-type-btn',
                hoverClass: 'wdm-canvas-drop-hover',
                tolerance:  'pointer',
                drop: function(e, ui) {
                    var $btn = ui.draggable;
                    $('.wdm-drop-indicator').remove();
                    self.addFieldAtIndex(e, $btn, self._dropIndex);
                    $(this).removeClass('wdm-canvas-drop-active wdm-canvas-drop-hover');
                }
            });
        },

        /**
         * Add a new field at a specific index in the canvas.
         *
         * @since 1.1.0
         * @param {Event}  e      The drop event.
         * @param {jQuery} $btn   The dragged field type button.
         * @param {number} index  Position to insert at (-1 or >= length = end).
         */
        addFieldAtIndex: function(e, $btn, index) {
            var type = $btn.data('type');
            if ($btn.prop('disabled')) { return; }
            if (this.proFieldTypes.indexOf(type) !== -1) { return; }

            // Singleton fields — only one allowed per form.
            if ( this.isSingletonField(type) && this.hasFieldType(type) ) {
                this.showNotice('error', this.typeLabels[type] + ' ' + (wprobo_documerge_vars.i18n.singleton_limit || 'field can only be added once per form.'));
                return;
            }

            this.fieldCounter++;
            var fieldId  = 'field_' + this.fieldCounter;
            var label    = this.typeLabels[type] || type;
            var mergeTag = label.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '');

            var field = {
                id: fieldId, type: type, label: label, name: mergeTag,
                placeholder: '', help_text: '', required: false, width: 'full',
                options: [], conditions: [], step: 1, error_message: '',
                min_length: '', max_length: '', min_value: '', max_value: '',
                step_value: '', date_format: 'Y-m-d',
                disable_past: false, max_future_months: '', searchable: false,
                css_class: '', css_id: '',
                label_position: 'top',
                show_country_code: true, default_country: 'GB',
                allowed_types: 'jpg,jpeg,png,gif,pdf,doc,docx',
                max_file_size: 5, multiple: false,
                show_line2: true, show_country: true,
                name_format: 'first_last',
                default_value: '', dynamic_value: 'none', custom_value: '',
                html_content: '<p>Add your content here.</p>',
                divider_style: 'line',
                show_title: true,
                title_alignment: 'left',
                track_utms: true,
                track_referrer: true,
                track_landing_page: true
            };

            if (type === 'dropdown' || type === 'radio' || type === 'checkbox') {
                field.options = [
                    {label: 'Option 1', value: 'option_1'},
                    {label: 'Option 2', value: 'option_2'},
                    {label: 'Option 3', value: 'option_3'}
                ];
            }

            var cardHtml = this.generateFieldCard(field);

            var $cards = $('#wdm-builder-canvas').find('.wdm-field-card');

            // Insert at the correct position.
            if (index >= 0 && index < $cards.length) {
                $(cardHtml).insertBefore($cards.eq(index));
            } else {
                $('#wdm-builder-canvas').append(cardHtml);
            }

            // Insert into fields array at the correct index.
            if (index >= 0 && index < this.fields.length) {
                this.fields.splice(index, 0, field);
            } else {
                this.fields.push(field);
            }

            // Hide placeholder.
            $('#wdm-canvas-placeholder').hide();

            // Refresh sortable.
            if ( $('#wdm-builder-canvas').hasClass('ui-sortable') ) {
                $('#wdm-builder-canvas').sortable('refresh');
            }
            this.updateSingletonButtons();
        },

        // ─────────────────────────────────────────────────────────
        //  Field management
        // ─────────────────────────────────────────────────────────

        /**
         * Add a new field to the canvas.
         *
         * Reads the field type from the clicked button, creates a
         * default configuration object, generates the card HTML,
         * and appends it to the canvas.
         *
         * @since 1.0.0
         * @param {Event}  e    The click event.
         * @param {jQuery} $btn The clicked field type button.
         */
        addField: function(e, $btn) {
            var type = $btn.data('type');

            // Bail if the field type is disabled.
            if ( $btn.prop('disabled') ) {
                return;
            }

            // Pro field types cannot be added in Lite.
            if ( this.proFieldTypes.indexOf(type) !== -1 ) {
                return;
            }

            // Singleton fields — only one allowed per form.
            if ( this.isSingletonField(type) && this.hasFieldType(type) ) {
                this.showNotice('error', this.typeLabels[type] + ' ' + (wprobo_documerge_vars.i18n.singleton_limit || 'field can only be added once per form.'));
                return;
            }

            this.fieldCounter++;

            var fieldId    = 'field_' + this.fieldCounter;
            var label      = this.typeLabels[type] || type;
            var mergeTag   = label.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '');

            var field = {
                id:            fieldId,
                type:          type,
                label:         label,
                name:          mergeTag,
                placeholder:   '',
                help_text:     '',
                required:      false,
                width:         'full',
                options:       [],
                conditions:    [],
                step:          1,
                error_message: '',
                min_length:    '',
                max_length:    '',
                min_value:     '',
                max_value:     '',
                step_value:    '',
                date_format:   'Y-m-d',
                css_class:     '',
                css_id:        '',
                label_position: 'top',
                show_country_code: true,
                default_country: 'GB',
                allowed_types: 'jpg,jpeg,png,gif,pdf,doc,docx',
                max_file_size: 5,
                multiple:      false,
                show_line2:    true,
                show_country:  true,
                name_format:   'first_last',
                default_value: '',
                dynamic_value: 'none',
                custom_value:  '',
                html_content:  '<p>Add your content here.</p>',
                divider_style: 'line',
                show_title:    true,
                title_alignment: 'left',
                track_utms:    true,
                track_referrer: true,
                track_landing_page: true
            };

            // Add default options for choice fields.
            if ( type === 'dropdown' || type === 'radio' || type === 'checkbox' ) {
                field.options = [
                    { label: 'Option 1', value: 'option_1' },
                    { label: 'Option 2', value: 'option_2' },
                    { label: 'Option 3', value: 'option_3' }
                ];
            }

            var cardHtml = this.generateFieldCard(field);

            $('#wdm-canvas-placeholder').hide();
            $('#wdm-builder-canvas').append(cardHtml);

            this.fields.push(field);
            this.updateSingletonButtons();
        },

        /**
         * Generate the HTML markup for a field card.
         *
         * @since 1.0.0
         * @param  {Object} field The field configuration object.
         * @return {string}       The field card HTML string.
         */
        generateFieldCard: function(field) {
            var typeLabel   = this.typeLabels[field.type] || field.type;
            var mergeTag    = '{' + field.name + '}';
            var checkedAttr = field.required ? ' checked' : '';
            var isPro       = this.proFieldTypes.indexOf(field.type) !== -1;

            // Pro field types: render read-only card with PRO badge, no settings panel.
            if (isPro) {
                var proHtml = '' +
                    '<div class="wdm-field-card wdm-field-card-pro" data-field-id="' + field.id + '" data-field-type="' + field.type + '">' +
                        '<div class="wdm-field-card-header">' +
                            '<span class="wdm-field-drag-handle dashicons dashicons-menu"></span>' +
                            '<span class="wdm-field-type-badge">' + $('<span>').text(typeLabel).html() + '</span>' +
                            '<span class="wdm-field-label-preview">' + $('<span>').text(field.label).html() + '</span>' +
                            '<span class="wdm-pro-badge" style="margin-left:auto;">PRO</span>' +
                            '<span class="dashicons dashicons-lock" style="color:#6b7280;margin-left:4px;"></span>' +
                        '</div>' +
                    '</div>';
                return proHtml;
            }

            var html = '' +
                '<div class="wdm-field-card" data-field-id="' + field.id + '" data-field-type="' + field.type + '">' +
                    '<div class="wdm-field-card-header">' +
                        '<span class="wdm-field-drag-handle dashicons dashicons-menu"></span>' +
                        '<span class="wdm-field-type-badge">' + $('<span>').text(typeLabel).html() + '</span>' +
                        '<span class="wdm-field-label-preview">' + $('<span>').text(field.label).html() + '</span>' +
                        '<div class="wdm-field-card-actions">' +
                            '<button type="button" class="wdm-field-edit-btn"><span class="dashicons dashicons-edit"></span></button>' +
                            '<button type="button" class="wdm-field-delete-btn"><span class="dashicons dashicons-trash"></span></button>' +
                        '</div>' +
                    '</div>' +
                    '<div class="wdm-field-card-settings" style="display:none;">' +

                        // Tab buttons.
                        '<div class="wdm-field-tabs">' +
                            '<button type="button" class="wdm-field-tab wdm-field-tab-active" data-tab="general">' + (wprobo_documerge_vars.i18n.general || 'General') + '</button>' +
                            '<button type="button" class="wdm-field-tab" data-tab="validation">' + (wprobo_documerge_vars.i18n.validation || 'Validation') + '</button>' +
                            '<button type="button" class="wdm-field-tab" data-tab="appearance">' + (wprobo_documerge_vars.i18n.appearance || 'Appearance') + '</button>' +
                        '</div>' +

                        // ── Tab 1: General ──────────────────────────
                        '<div class="wdm-field-tab-content wdm-field-tab-active" data-tab="general">';

            // IP Address fields: show Label + Merge Tag only.
            if ( field.type === 'ip_address' ) {
                html += '<div class="wdm-builder-field-setting">' +
                                '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.label_admin_reference || 'Label (admin reference)').html() + '</label>' +
                                '<input type="text" data-setting="label" class="wdm-builder-setting-input wdm-input" value="' + $('<span>').text(field.label).html() + '">' +
                                '<span class="wdm-description">' + $('<span>').text(wprobo_documerge_vars.i18n.admin_reference_desc || 'For admin reference only. IP is captured automatically.').html() + '</span>' +
                            '</div>' +
                            '<div class="wdm-builder-field-setting">' +
                                '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.merge_tag || 'Merge Tag').html() + '</label>' +
                                '<input type="text" class="wdm-input wdm-merge-tag-display" value="' + $('<span>').text(mergeTag).html() + '" readonly>' +
                            '</div>';

            // Tracking fields: show Label, Merge Tag, and tracking option checkboxes.
            } else if ( field.type === 'tracking' ) {
                html += '<div class="wdm-builder-field-setting">' +
                                '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.label_admin_reference || 'Label (admin reference)').html() + '</label>' +
                                '<input type="text" data-setting="label" class="wdm-builder-setting-input wdm-input" value="' + $('<span>').text(field.label).html() + '">' +
                                '<span class="wdm-description">' + $('<span>').text(wprobo_documerge_vars.i18n.not_shown_frontend || 'For admin reference only. Not shown on the frontend.').html() + '</span>' +
                            '</div>' +
                            '<div class="wdm-builder-field-setting">' +
                                '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.merge_tag || 'Merge Tag').html() + '</label>' +
                                '<input type="text" class="wdm-input wdm-merge-tag-display" value="' + $('<span>').text(mergeTag).html() + '" readonly>' +
                            '</div>' +
                            '<div class="wdm-builder-field-setting">' +
                                '<label><input type="checkbox" data-setting="track_utms" class="wdm-builder-setting-input"' + (field.track_utms !== false ? ' checked' : '') + '> ' + $('<span>').text(wprobo_documerge_vars.i18n.track_utm || 'Track UTM Parameters').html() + '</label>' +
                                '<span class="wdm-description">' + $('<span>').text(wprobo_documerge_vars.i18n.track_utm_desc || 'Captures utm_source, utm_medium, utm_campaign, utm_content, utm_term').html() + '</span>' +
                            '</div>' +
                            '<div class="wdm-builder-field-setting">' +
                                '<label><input type="checkbox" data-setting="track_referrer" class="wdm-builder-setting-input"' + (field.track_referrer !== false ? ' checked' : '') + '> ' + $('<span>').text(wprobo_documerge_vars.i18n.track_referrer || 'Track Referrer URL').html() + '</label>' +
                            '</div>' +
                            '<div class="wdm-builder-field-setting">' +
                                '<label><input type="checkbox" data-setting="track_landing_page" class="wdm-builder-setting-input"' + (field.track_landing_page !== false ? ' checked' : '') + '> ' + $('<span>').text(wprobo_documerge_vars.i18n.track_landing || 'Track Landing Page URL').html() + '</label>' +
                            '</div>';

            // Hidden fields: show Label + Merge Tag only (no placeholder, help text, required, width).
            } else if ( field.type === 'hidden' ) {
                html += '<div class="wdm-builder-field-setting">' +
                                '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.label_admin_reference || 'Label (admin reference)').html() + '</label>' +
                                '<input type="text" data-setting="label" class="wdm-builder-setting-input wdm-input" value="' + $('<span>').text(field.label).html() + '">' +
                            '</div>' +
                            '<div class="wdm-builder-field-setting">' +
                                '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.merge_tag || 'Merge Tag').html() + '</label>' +
                                '<input type="text" class="wdm-input wdm-merge-tag-display" value="' + $('<span>').text(mergeTag).html() + '" readonly>' +
                            '</div>' +
                            '<div class="wdm-builder-field-setting">' +
                                '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.default_value || 'Default Value').html() + '</label>' +
                                '<input type="text" data-setting="default_value" class="wdm-builder-setting-input wdm-input" value="' + $('<span>').text(field.default_value || '').html() + '">' +
                            '</div>' +
                            '<div class="wdm-builder-field-setting">' +
                                '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.dynamic_value || 'Dynamic Value').html() + '</label>' +
                                '<select data-setting="dynamic_value" class="wdm-builder-setting-input wdm-select">' +
                                    '<option value="none"' + (field.dynamic_value === 'none' ? ' selected' : '') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.dynamic_none || 'None (use default)').html() + '</option>' +
                                    '<option value="user_id"' + (field.dynamic_value === 'user_id' ? ' selected' : '') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.dynamic_user_id || 'Current User ID').html() + '</option>' +
                                    '<option value="user_email"' + (field.dynamic_value === 'user_email' ? ' selected' : '') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.dynamic_user_email || 'Current User Email').html() + '</option>' +
                                    '<option value="user_name"' + (field.dynamic_value === 'user_name' ? ' selected' : '') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.dynamic_user_name || 'Current User Name').html() + '</option>' +
                                    '<option value="page_url"' + (field.dynamic_value === 'page_url' ? ' selected' : '') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.dynamic_page_url || 'Current Page URL').html() + '</option>' +
                                    '<option value="page_title"' + (field.dynamic_value === 'page_title' ? ' selected' : '') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.dynamic_page_title || 'Page Title').html() + '</option>' +
                                    '<option value="referrer"' + (field.dynamic_value === 'referrer' ? ' selected' : '') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.dynamic_referrer || 'Referrer URL').html() + '</option>' +
                                    '<option value="custom"' + (field.dynamic_value === 'custom' ? ' selected' : '') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.dynamic_custom || 'Custom Value').html() + '</option>' +
                                '</select>' +
                            '</div>' +
                            '<div class="wdm-builder-field-setting">' +
                                '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.custom_value || 'Custom Value').html() + '</label>' +
                                '<input type="text" data-setting="custom_value" class="wdm-builder-setting-input wdm-input" value="' + $('<span>').text(field.custom_value || '').html() + '">' +
                            '</div>';

            // HTML block: show Label (admin reference) + HTML content only.
            } else if ( field.type === 'html' ) {
                html += '<div class="wdm-builder-field-setting">' +
                                '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.label_admin_reference || 'Label (admin reference)').html() + '</label>' +
                                '<input type="text" data-setting="label" class="wdm-builder-setting-input wdm-input" value="' + $('<span>').text(field.label).html() + '">' +
                            '</div>' +
                            '<div class="wdm-builder-field-setting">' +
                                '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.html_content || 'HTML Content').html() + '</label>' +
                                '<textarea data-setting="html_content" class="wdm-builder-setting-input wdm-textarea" rows="6">' + $('<span>').text(field.html_content || '<p>Add your content here.</p>').html() + '</textarea>' +
                                '<span class="wdm-description">' + $('<span>').text(wprobo_documerge_vars.i18n.html_content_desc || 'Supports HTML: headings, paragraphs, lists, links, images.').html() + '</span>' +
                            '</div>';

            // Section Divider: custom General tab — title, alignment, style.
            } else if ( field.type === 'section_divider' ) {
                var sel = function(current, val) { return current === val ? ' selected' : ''; };
                html += '<div class="wdm-builder-field-setting">' +
                                '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.title_text || 'Title Text').html() + '</label>' +
                                '<input type="text" data-setting="label" class="wdm-builder-setting-input wdm-input" value="' + $('<span>').text(field.label).html() + '">' +
                            '</div>' +
                            '<div class="wdm-builder-field-setting">' +
                                '<label><input type="checkbox" data-setting="show_title" class="wdm-builder-setting-input"' + (field.show_title !== false ? ' checked' : '') + '> ' + $('<span>').text(wprobo_documerge_vars.i18n.show_title || 'Show Title').html() + '</label>' +
                            '</div>' +
                            '<div class="wdm-builder-field-setting">' +
                                '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.title_alignment || 'Title Alignment').html() + '</label>' +
                                '<select data-setting="title_alignment" class="wdm-builder-setting-input wdm-select">' +
                                    '<option value="left"' + sel(field.title_alignment, 'left') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.align_left || 'Left').html() + '</option>' +
                                    '<option value="center"' + sel(field.title_alignment, 'center') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.align_center || 'Center').html() + '</option>' +
                                    '<option value="right"' + sel(field.title_alignment, 'right') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.align_right || 'Right').html() + '</option>' +
                                '</select>' +
                            '</div>' +
                            '<div class="wdm-builder-field-setting">' +
                                '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.divider_style || 'Divider Style').html() + '</label>' +
                                '<select data-setting="divider_style" class="wdm-builder-setting-input wdm-select">' +
                                    '<option value="line"' + sel(field.divider_style, 'line') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.divider_solid || 'Solid Line').html() + '</option>' +
                                    '<option value="dashed"' + sel(field.divider_style, 'dashed') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.divider_dashed || 'Dashed').html() + '</option>' +
                                    '<option value="dotted"' + sel(field.divider_style, 'dotted') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.divider_dotted || 'Dotted').html() + '</option>' +
                                    '<option value="double"' + sel(field.divider_style, 'double') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.divider_double || 'Double').html() + '</option>' +
                                    '<option value="none"' + sel(field.divider_style, 'none') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.divider_none || 'No Line').html() + '</option>' +
                                '</select>' +
                            '</div>';

            // All other field types: standard General tab fields.
            } else {
                html += '<div class="wdm-builder-field-setting">' +
                                '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.label || 'Label').html() + '</label>' +
                                '<input type="text" data-setting="label" class="wdm-builder-setting-input wdm-input" value="' + $('<span>').text(field.label).html() + '">' +
                            '</div>' +
                            '<div class="wdm-builder-field-setting">' +
                                '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.label_position || 'Label Position').html() + '</label>' +
                                '<select data-setting="label_position" class="wdm-builder-setting-input wdm-select">' +
                                    '<option value="top"' + (field.label_position === 'top' ? ' selected' : '') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.label_above_field || 'Above field').html() + '</option>' +
                                    '<option value="bottom"' + (field.label_position === 'bottom' ? ' selected' : '') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.label_below_field || 'Below field').html() + '</option>' +
                                    '<option value="hidden"' + (field.label_position === 'hidden' ? ' selected' : '') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.label_hidden_sr || 'Hidden (screen reader only)').html() + '</option>' +
                                '</select>' +
                            '</div>' +
                            '<div class="wdm-builder-field-setting">' +
                                '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.placeholder || 'Placeholder').html() + '</label>' +
                                '<input type="text" data-setting="placeholder" class="wdm-builder-setting-input wdm-input" value="' + $('<span>').text(field.placeholder).html() + '">' +
                            '</div>' +
                            '<div class="wdm-builder-field-setting">' +
                                '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.merge_tag || 'Merge Tag').html() + '</label>' +
                                '<input type="text" class="wdm-input wdm-merge-tag-display" value="' + $('<span>').text(mergeTag).html() + '" readonly>' +
                            '</div>' +
                            '<div class="wdm-builder-field-setting">' +
                                '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.help_text || 'Help Text').html() + '</label>' +
                                '<input type="text" data-setting="help_text" class="wdm-builder-setting-input wdm-input" value="' + $('<span>').text(field.help_text).html() + '">' +
                            '</div>' +
                            '<div class="wdm-builder-field-setting">';

                html += '<label><input type="checkbox" data-setting="required" class="wdm-builder-setting-input"' + checkedAttr + '> ' + $('<span>').text(wprobo_documerge_vars.i18n.required || 'Required').html() + '</label>';

                html += '</div>' +
                            '<div class="wdm-builder-field-setting">' +
                                '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.width || 'Width').html() + '</label>' +
                                '<div class="wdm-width-selector">' +
                                    '<label><input type="radio" name="width_' + field.id + '" data-setting="width" value="full" class="wdm-builder-setting-input"' + (field.width === 'full' ? ' checked' : '') + '> ' + $('<span>').text(wprobo_documerge_vars.i18n.width_full || 'Full').html() + '</label>' +
                                    '<label><input type="radio" name="width_' + field.id + '" data-setting="width" value="half" class="wdm-builder-setting-input"' + (field.width === 'half' ? ' checked' : '') + '> ' + $('<span>').text(wprobo_documerge_vars.i18n.width_half || 'Half').html() + '</label>' +
                                    '<label><input type="radio" name="width_' + field.id + '" data-setting="width" value="third" class="wdm-builder-setting-input"' + (field.width === 'third' ? ' checked' : '') + '> ' + $('<span>').text(wprobo_documerge_vars.i18n.width_third || 'Third').html() + '</label>' +
                                '</div>' +
                            '</div>';
            }

            // Date format selector (inside General tab, date fields only).
            if ( field.type === 'date' ) {
                var dateFormat = field.date_format || 'Y-m-d';
                var dateFormats = [
                    { value: 'Y-m-d',   preview: '2026-03-25' },
                    { value: 'd/m/Y',   preview: '25/03/2026' },
                    { value: 'm/d/Y',   preview: '03/25/2026' },
                    { value: 'd-m-Y',   preview: '25-03-2026' },
                    { value: 'd.m.Y',   preview: '25.03.2026' },
                    { value: 'F j, Y',  preview: 'March 25, 2026' },
                    { value: 'M j, Y',  preview: 'Mar 25, 2026' },
                    { value: 'j F Y',   preview: '25 March 2026' }
                ];

                html += '<div class="wdm-builder-field-setting">' +
                            '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.date_format || 'Date Format').html() + '</label>' +
                            '<select data-setting="date_format" class="wdm-builder-setting-input wdm-select">';

                $.each(dateFormats, function(i, fmt) {
                    var sel = (fmt.value === dateFormat) ? ' selected' : '';
                    html += '<option value="' + fmt.value + '"' + sel + '>' +
                                $('<span>').text(fmt.value + '  →  ' + fmt.preview).html() +
                            '</option>';
                });

                html += '</select>' +
                        '<span class="wdm-description">' + $('<span>').text(wprobo_documerge_vars.i18n.date_format_desc || 'How the date appears in the form and document.').html() + '</span>' +
                    '</div>';
            }

            // Options manager for choice fields (inside General tab).
            if ( field.type === 'dropdown' || field.type === 'radio' || field.type === 'checkbox' ) {
                var options = field.options || [];
                html += '<div class="wdm-builder-field-setting wdm-options-manager">' +
                            '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.options_label || 'Options').html() + '</label>' +
                            '<div class="wdm-options-list">';

                $.each(options, function(i, option) {
                    html += '<div class="wdm-option-row">' +
                                '<span class="wdm-option-drag dashicons dashicons-menu"></span>' +
                                '<input type="text" class="wdm-option-label wdm-input" placeholder="Label" value="' + $('<span>').text(option.label).html() + '">' +
                                '<input type="text" class="wdm-option-value wdm-input" placeholder="Value (auto)" value="' + $('<span>').text(option.value).html() + '">' +
                                '<button type="button" class="wdm-option-remove"><span class="dashicons dashicons-no-alt"></span></button>' +
                            '</div>';
                });

                html += '</div>' +
                        '<button type="button" class="wdm-add-option wdm-btn wdm-btn-sm">' + $('<span>').text(wprobo_documerge_vars.i18n.add_option || '+ Add Option').html() + '</button>' +
                    '</div>';

                // Searchable toggle — dropdown only.
                if ( field.type === 'dropdown' ) {
                    var searchChecked = field.searchable ? ' checked' : '';
                    html += '<div class="wdm-builder-field-setting">' +
                                '<label><input type="checkbox" data-setting="searchable" class="wdm-builder-setting-input"' + searchChecked + '> ' +
                                $('<span>').text(wprobo_documerge_vars.i18n.enable_search || 'Enable search (users can type to filter options)').html() +
                                '</label>' +
                                '<span class="wdm-description">' + $('<span>').text(wprobo_documerge_vars.i18n.enable_search_desc || 'Recommended for dropdowns with many options.').html() + '</span>' +
                            '</div>';
                }
            }

            // File Upload field settings (inside General tab).
            if ( field.type === 'file_upload' ) {
                html += '<div class="wdm-builder-field-setting">' +
                            '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.allowed_file_types || 'Allowed File Types').html() + '</label>' +
                            '<input type="text" data-setting="allowed_types" class="wdm-builder-setting-input wdm-input" value="' + $('<span>').text(field.allowed_types || 'jpg,jpeg,png,gif,pdf,doc,docx').html() + '" placeholder="jpg,png,pdf,docx">' +
                            '<span class="wdm-description">' + $('<span>').text(wprobo_documerge_vars.i18n.allowed_file_types_desc || 'Comma-separated file extensions').html() + '</span>' +
                        '</div>' +
                        '<div class="wdm-builder-field-setting">' +
                            '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.max_file_size || 'Max File Size (MB)').html() + '</label>' +
                            '<input type="number" data-setting="max_file_size" class="wdm-builder-setting-input wdm-input wdm-input-small" value="' + (field.max_file_size || 5) + '" min="1" max="50">' +
                        '</div>' +
                        '<div class="wdm-builder-field-setting">' +
                            '<label><input type="checkbox" data-setting="multiple" class="wdm-builder-setting-input"' + (field.multiple ? ' checked' : '') + '> ' + $('<span>').text(wprobo_documerge_vars.i18n.allow_multiple_files || 'Allow multiple files').html() + '</label>' +
                        '</div>';
            }

            // Address field settings (inside General tab).
            if ( field.type === 'address' ) {
                html += '<div class="wdm-builder-field-setting">' +
                            '<label><input type="checkbox" data-setting="show_line2" class="wdm-builder-setting-input"' + (field.show_line2 !== false ? ' checked' : '') + '> ' + $('<span>').text(wprobo_documerge_vars.i18n.show_address_line2 || 'Show Address Line 2').html() + '</label>' +
                        '</div>' +
                        '<div class="wdm-builder-field-setting">' +
                            '<label><input type="checkbox" data-setting="show_country" class="wdm-builder-setting-input"' + (field.show_country !== false ? ' checked' : '') + '> ' + $('<span>').text(wprobo_documerge_vars.i18n.show_country_field || 'Show Country field').html() + '</label>' +
                        '</div>' +
                        '<div class="wdm-builder-field-setting">' +
                            '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.default_country || 'Default Country').html() + '</label>' +
                            '<input type="text" data-setting="default_country" class="wdm-builder-setting-input wdm-input" value="' + $('<span>').text(field.default_country || '').html() + '" placeholder="e.g. United Kingdom">' +
                        '</div>';
            }

            // Name field settings (inside General tab).
            if ( field.type === 'name' ) {
                html += '<div class="wdm-builder-field-setting">' +
                            '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.name_format || 'Name Format').html() + '</label>' +
                            '<select data-setting="name_format" class="wdm-builder-setting-input wdm-select">' +
                                '<option value="first_last"' + (field.name_format === 'first_last' ? ' selected' : '') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.name_first_last || 'First + Last').html() + '</option>' +
                                '<option value="first_middle_last"' + (field.name_format === 'first_middle_last' ? ' selected' : '') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.name_first_middle_last || 'First + Middle + Last').html() + '</option>' +
                                '<option value="title_first_last"' + (field.name_format === 'title_first_last' ? ' selected' : '') + '>' + $('<span>').text(wprobo_documerge_vars.i18n.name_title_first_last || 'Title + First + Last').html() + '</option>' +
                            '</select>' +
                        '</div>';
            }


            // Close General tab content.
            html += '</div>';

            // ── Tab 2: Validation ───────────────────────────
            var errorMessage = field.error_message || '';
            var minValue     = field.min_value || '';
            var maxValue     = field.max_value || '';
            var stepVal      = field.step_value || '';
            var minLength    = field.min_length || '';
            var maxLength    = field.max_length || '';

            html += '<div class="wdm-field-tab-content" data-tab="validation">' +
                        '<div class="wdm-builder-field-setting">' +
                            '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.custom_error_message || 'Custom Error Message').html() + '</label>' +
                            '<input type="text" data-setting="error_message" class="wdm-builder-setting-input wdm-input" value="' + $('<span>').text(errorMessage).html() + '" placeholder="' + $('<span>').text(wprobo_documerge_vars.i18n.leave_blank_default || 'Leave blank for default').html() + '">' +
                        '</div>';

            // Number-specific validation fields.
            if ( field.type === 'number' ) {
                html += '<div class="wdm-builder-field-setting">' +
                            '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.min_value || 'Min Value').html() + '</label>' +
                            '<input type="number" data-setting="min_value" class="wdm-builder-setting-input wdm-input" value="' + $('<span>').text(minValue).html() + '">' +
                        '</div>' +
                        '<div class="wdm-builder-field-setting">' +
                            '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.max_value || 'Max Value').html() + '</label>' +
                            '<input type="number" data-setting="max_value" class="wdm-builder-setting-input wdm-input" value="' + $('<span>').text(maxValue).html() + '">' +
                        '</div>' +
                        '<div class="wdm-builder-field-setting">' +
                            '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.step || 'Step').html() + '</label>' +
                            '<input type="number" data-setting="step_value" class="wdm-builder-setting-input wdm-input" value="' + $('<span>').text(stepVal).html() + '" placeholder="e.g. 1, 0.01">' +
                        '</div>';
            }

            // Text-specific validation fields.
            if ( field.type === 'text' || field.type === 'textarea' || field.type === 'email' || field.type === 'phone' || field.type === 'url' ) {
                html += '<div class="wdm-builder-field-setting">' +
                            '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.min_length || 'Min Length').html() + '</label>' +
                            '<input type="number" data-setting="min_length" class="wdm-builder-setting-input wdm-input" value="' + $('<span>').text(minLength).html() + '">' +
                        '</div>' +
                        '<div class="wdm-builder-field-setting">' +
                            '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.max_length || 'Max Length').html() + '</label>' +
                            '<input type="number" data-setting="max_length" class="wdm-builder-setting-input wdm-input" value="' + $('<span>').text(maxLength).html() + '">' +
                        '</div>';
            }

            // Close Validation tab content.
            html += '</div>';


            // ── Tab 4: Appearance ──────────────────────────
            html += '<div class="wdm-field-tab-content" data-tab="appearance">' +
                        '<div class="wdm-builder-field-setting">' +
                            '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.css_class || 'CSS Class').html() + '</label>' +
                            '<input type="text" data-setting="css_class" class="wdm-builder-setting-input wdm-input" value="' + $('<span>').text(field.css_class || '').html() + '" placeholder="e.g. my-custom-field">' +
                            '<span class="wdm-description">' + $('<span>').text(wprobo_documerge_vars.i18n.css_class_desc || 'Add custom CSS class(es). Separate multiple with spaces.').html() + '</span>' +
                        '</div>' +
                        '<div class="wdm-builder-field-setting">' +
                            '<label>' + $('<span>').text(wprobo_documerge_vars.i18n.css_id || 'CSS ID').html() + '</label>' +
                            '<input type="text" data-setting="css_id" class="wdm-builder-setting-input wdm-input" value="' + $('<span>').text(field.css_id || '').html() + '" placeholder="e.g. my-field-id">' +
                            '<span class="wdm-description">' + $('<span>').text(wprobo_documerge_vars.i18n.css_id_desc || 'Optional unique HTML ID for this field.').html() + '</span>' +
                        '</div>' +
                    '</div>';

            // Close settings panel and card.
            html += '</div>' +
                '</div>';

            return html;
        },

        /**
         * Toggle the settings panel for a field card.
         *
         * @since 1.0.0
         * @param {jQuery} $btn The clicked edit button.
         */
        toggleFieldSettings: function($btn) {
            var $card     = $btn.closest('.wdm-field-card');

            // Pro fields cannot be edited in Lite.
            if ($card.hasClass('wdm-field-card-pro')) {
                return;
            }

            var $settings = $card.find('.wdm-field-card-settings');

            $settings.slideToggle(200);
        },

        /**
         * Delete a field from the canvas and fields array.
         *
         * Prompts for confirmation before removing.
         *
         * @since 1.0.0
         * @param {jQuery} $btn The clicked delete button.
         */
        deleteField: function($btn) {
            if ( ! window.confirm(wprobo_documerge_vars.i18n.confirm_remove_field || 'Are you sure you want to remove this field?') ) {
                return;
            }

            var $card   = $btn.closest('.wdm-field-card');
            var fieldId = $card.data('field-id');
            var self    = this;

            // Remove from fields array.
            this.fields = $.grep(this.fields, function(f) {
                return f.id !== fieldId;
            });

            var self = this;
            $card.fadeOut(200, function() {
                $(this).remove();

                // Show placeholder if canvas is empty.
                if ( ! $('#wdm-builder-canvas').find('.wdm-field-card').length ) {
                    $('#wdm-canvas-placeholder').show();
                }

                // Re-enable singleton buttons after deletion.
                self.updateSingletonButtons();
            });
        },

        /**
         * Update a field's data when a setting input changes.
         *
         * @since 1.0.0
         * @param {jQuery} $input The changed input element.
         */
        updateFieldData: function($input) {
            var $card   = $input.closest('.wdm-field-card');
            var fieldId = $card.data('field-id');
            var setting = $input.data('setting');
            var value;

            if ( ! setting ) {
                return;
            }

            // Determine value based on input type.
            if ( $input.is(':checkbox') ) {
                value = $input.is(':checked');
            } else if ( $input.is(':radio') ) {
                value = $input.val();
            } else {
                value = $input.val();
            }

            // Find and update the field in the array.
            $.each(this.fields, function(i, field) {
                if ( field.id === fieldId ) {
                    field[setting] = value;
                    return false;
                }
            });

            // Update label preview if label changed.
            if ( setting === 'label' ) {
                $card.find('.wdm-field-label-preview').text(value);
            }
        },

        /**
         * Update the merge tag display when the label changes.
         *
         * Converts the label to a lowercase, underscore-separated,
         * alphanumeric-only string for use as a merge tag.
         *
         * @since 1.0.0
         * @param {jQuery} $input The label input element.
         */
        updateMergeTag: function($input) {
            var $card    = $input.closest('.wdm-field-card');
            var fieldId  = $card.data('field-id');
            var label    = $input.val();
            var mergeTag = label.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '');

            // Update the readonly merge tag display.
            $card.find('.wdm-merge-tag-display').val('{' + mergeTag + '}');

            // Update the field name in the array.
            $.each(this.fields, function(i, field) {
                if ( field.id === fieldId ) {
                    field.name = mergeTag;
                    return false;
                }
            });
        },

        /**
         * Rebuild the fields array to match the current DOM order.
         *
         * Called after jQuery UI Sortable reorders the field cards.
         *
         * @since 1.0.0
         */
        updateFieldOrder: function() {
            var self      = this;
            var reordered = [];

            // Collect all field cards in DOM order (works for both flat and step modes).
            $('#wdm-builder-canvas').find('.wdm-field-card').each(function() {
                var fieldId = $(this).data('field-id');

                $.each(self.fields, function(i, field) {
                    if ( field.id === fieldId ) {
                        reordered.push(field);
                        return false;
                    }
                });
            });

            this.fields = reordered;
        },

        /**
         * Check if a field type is a singleton (only one allowed per form).
         *
         * @param  {string}  type Field type slug.
         * @return {boolean}
         */
        isSingletonField: function(type) {
            return false;
        },

        /**
         * Check if the form already contains a field of the given type.
         *
         * @param  {string}  type Field type slug.
         * @return {boolean}
         */
        hasFieldType: function(type) {
            var found = false;
            $.each(this.fields, function(i, field) {
                if (field.type === type) {
                    found = true;
                    return false;
                }
            });
            return found;
        },

        /**
         * Update sidebar button states (no singletons in Lite).
         */
        updateSingletonButtons: function() {
            // No singleton fields in Lite version.
        },

        // ─────────────────────────────────────────────────────────
        //  Save & load
        // ─────────────────────────────────────────────────────────

        /**
         * Save the form via AJAX.
         *
         * Collects all form data including fields, settings, and
         * metadata, validates the title, and sends to the server.
         *
         * @since 1.0.0
         */
        saveForm: function() {
            var self           = this;
            var formId         = $('#wdm-form-id').val();
            var title          = $.trim($('#wdm-form-title').val());
            var templateId     = $('#wdm-form-template').val();
            var outputFormat   = $('#wdm-form-output').val();
            var submitLabel    = $.trim($('#wdm-submit-label').val());
            var successMessage = $.trim($('#wdm-success-message').val());

            // Button appearance settings.
            var settingsObj = {
                btn_width:      $('#wdm-btn-width').val() || 'auto',
                btn_align:      $('input[name="wdm_btn_align"]:checked').val() || 'right',
                btn_style:      $('#wdm-btn-style').val() || 'filled',
                btn_size:       $('#wdm-btn-size').val() || 'medium',
                btn_radius:     $('#wdm-btn-radius').val() || '6',
                btn_bg_color:   $('#wdm-btn-bg-color').val() || '#042157',
                btn_text_color: $('#wdm-btn-text-color').val() || '#ffffff',
                btn_hover_bg:   $('#wdm-btn-hover-bg').val() || '#0a3d8f',
                btn_hover_text: $('#wdm-btn-hover-text').val() || '#ffffff',
                entry_limit:       $('#wdm-entry-limit').val() || '',
                limit_per_email:   $('#wdm-limit-per-email').val() || '0',
                limit_email_field: $('#wdm-limit-email-field').val() || '',
                limit_per_ip:      $('#wdm-limit-per-ip').val() || '0',
                limit_per_user:    $('#wdm-limit-per-user').val() || '0',
                closed_message:    $.trim($('#wdm-closed-message').val()) || ''
            };

            // Integration settings (external_form_id + field_map).
            if ( $('#wdm-external-form-id').length && $('#wdm-external-form-id').val() ) {
                settingsObj.external_form_id = $('#wdm-external-form-id').val();
            }
            var fieldMap = {};
            $('.wdm-field-map-select').each(function() {
                var tag = $(this).data('merge-tag');
                var val = $(this).val();
                if ( tag && val ) {
                    fieldMap[tag] = val;
                }
            });
            if ( Object.keys(fieldMap).length > 0 ) {
                settingsObj.field_map = fieldMap;
            }

            var settings = JSON.stringify(settingsObj);

            // Validate title.
            if ( ! title || title === (wprobo_documerge_vars.i18n.untitled_form || 'Untitled Form') ) {
                self.showNotice('error', wprobo_documerge_vars.i18n.enter_form_title || 'Please enter a form title.');
                return;
            }

            var $btn = $('#wdm-save-form');

            $.ajax({
                url:      wprobo_documerge_vars.ajax_url,
                type:     'POST',
                dataType: 'json',
                data: {
                    action:          'wprobo_documerge_save_form',
                    nonce:           wprobo_documerge_vars.nonce,
                    id:              formId,
                    title:           title,
                    template_id:     templateId,
                    fields:          JSON.stringify(self.fields),
                    output_format:   outputFormat,
                    submit_label:    submitLabel,
                    success_message: successMessage,
                    multistep:       0,
                    settings:        settings,
                    mode:            $('#wdm-form-mode').val() || 'standalone',
                    integration:     $('#wdm-form-integration').val() || '',
                    delivery_methods: JSON.stringify(
                        $('.wdm-delivery-method:checked').map(function() { return $(this).val(); }).get()
                    )
                },
                beforeSend: function() {
                    $btn.prop('disabled', true).addClass('wdm-loading');
                },
                success: function(response) {
                    if ( response.success ) {
                        var formId = response.data && (response.data.form_id || response.data.id) ? (response.data.form_id || response.data.id) : $('#wdm-form-id').val();

                        // Detect which tab is currently active.
                        var activeTab = 'fields';
                        var $activeMainTab = $('.wdm-builder-main-tab-active');
                        if ($activeMainTab.length) {
                            activeTab = $activeMainTab.data('tab') || 'fields';
                        }

                        // Detect which settings sub-tab is active.
                        var activeSubtab = '';
                        var $activeSubTab = $('.wdm-settings-subtab-active');
                        if ($activeSubTab.length) {
                            activeSubtab = $activeSubTab.data('subtab') || '';
                        }

                        // Redirect to the edit page — reloads integration fields, step containers, etc.
                        window.location.href = window.location.pathname +
                            '?page=wprobo-documerge-forms&action=edit&id=' + formId + '&saved=1&tab=' + activeTab +
                            (activeSubtab ? '&subtab=' + activeSubtab : '');
                        return;
                    } else {
                        self.showNotice('error',
                            response.data && response.data.message
                                ? response.data.message
                                : (wprobo_documerge_vars.i18n.save_error || 'An error occurred while saving the form.')
                        );
                    }
                },
                error: function() {
                    self.showNotice('error', wprobo_documerge_vars.i18n.network_error_retry || 'Network error. Please try again.');
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('wdm-loading');
                }
            });
        },

        /**
         * Generate a preview document with sample data.
         *
         * Sends the current form ID to the server, which fills the
         * template with sample data and returns a temporary PDF URL.
         * Opens the preview in a new browser tab.
         *
         * @since 1.4.0
         */
        wprobo_documerge_preview_document: function() {
            var self   = this;
            var formId = $('#wdm-form-id').val();

            if ( ! formId || formId === '0' ) {
                self.showNotice('error', wprobo_documerge_vars.i18n.save_form_first || 'Please save the form first.');
                return;
            }

            var $btn = $('#wdm-preview-doc');
            $btn.prop('disabled', true).addClass('wdm-loading');

            $.ajax({
                url:      wprobo_documerge_vars.ajax_url,
                type:     'POST',
                dataType: 'json',
                data: {
                    action:  'wprobo_documerge_preview_document',
                    nonce:   wprobo_documerge_vars.nonce,
                    form_id: formId
                },
                success: function(response) {
                    $btn.prop('disabled', false).removeClass('wdm-loading');
                    if ( response.success && response.data.url ) {
                        window.open(response.data.url, '_blank');
                    } else {
                        self.showNotice('error', response.data ? response.data.message : (wprobo_documerge_vars.i18n.preview_failed || 'Preview failed.'));
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).removeClass('wdm-loading');
                    self.showNotice('error', wprobo_documerge_vars.i18n.network_error_retry || 'Network error. Please try again.');
                }
            });
        },

        /**
         * Load existing fields into the canvas when editing a form.
         *
         * Reads the global wprobo_documerge_form_fields array, renders
         * each field card, and sets the field counter to avoid ID collisions.
         *
         * @since 1.0.0
         */
        loadExistingFields: function() {
            var self     = this;
            var existing = wprobo_documerge_form_fields;
            var maxNum   = 0;

            if ( ! $.isArray(existing) || ! existing.length ) {
                return;
            }

            $.each(existing, function(i, field) {
                self.addFieldFromData(field);

                // Track the highest field number for the counter.
                var match = field.id ? field.id.match(/field_(\d+)/) : null;
                if ( match ) {
                    var num = parseInt(match[1], 10);
                    if ( num > maxNum ) {
                        maxNum = num;
                    }
                }
            });

            this.fieldCounter = maxNum;
            this.updateSingletonButtons();
        },

        /**
         * Add a single field to the canvas from existing field data.
         *
         * Used by loadExistingFields to render pre-saved fields.
         *
         * @since 1.0.0
         * @param {Object} field The field data object.
         */
        addFieldFromData: function(field) {
            // Ensure defaults for any missing properties.
            // Preserve ALL saved properties — don't whitelist.
            var defaults = $.extend({
                id:            'field_0',
                type:          'text',
                label:         wprobo_documerge_vars.i18n.untitled || 'Untitled',
                name:          '',
                placeholder:   '',
                help_text:     '',
                required:      false,
                width:         'full',
                options:       [],
                conditions:    [],
                step:          1,
                error_message: '',
                min_length:    '',
                max_length:    '',
                min_value:     '',
                max_value:     '',
                step_value:    '',
                date_format:   'Y-m-d',
                css_class:     '',
                css_id:        ''
            }, field);

            var cardHtml = this.generateFieldCard(defaults);

            $('#wdm-canvas-placeholder').hide();
            $('#wdm-builder-canvas').append(cardHtml);

            this.fields.push(defaults);
        },

        // ─────────────────────────────────────────────────────────
        //  Form list actions
        // ─────────────────────────────────────────────────────────

        /**
         * Copy a shortcode string to the clipboard.
         *
         * @since 1.0.0
         * @param {jQuery} $btn The clicked copy button.
         */
        copyShortcode: function($btn) {
            var shortcode = $btn.data('shortcode');

            if ( ! shortcode ) {
                return;
            }

            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(shortcode).select();
            document.execCommand('copy');
            $temp.remove();

            this.showToast(wprobo_documerge_vars.i18n.copied || 'Copied!', 'success');
        },

        /**
         * Delete a form after user confirmation.
         *
         * @since 1.0.0
         * @param {jQuery} $btn The clicked delete button.
         */
        deleteForm: function($btn) {
            var self   = this;
            var formId = $btn.data('id');

            if ( ! window.confirm(wprobo_documerge_vars.i18n.confirm_delete_form || 'Are you sure you want to delete this form? This cannot be undone.') ) {
                return;
            }

            var $row = $btn.closest('tr, .wdm-form-card');

            $.ajax({
                url:      wprobo_documerge_vars.ajax_url,
                type:     'POST',
                dataType: 'json',
                data: {
                    action:  'wprobo_documerge_delete_form',
                    nonce:   wprobo_documerge_vars.nonce,
                    form_id: formId
                },
                beforeSend: function() {
                    $btn.prop('disabled', true).addClass('wdm-loading');
                },
                success: function(response) {
                    if ( response.success ) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                        });
                        self.showNotice('success',
                            response.data && response.data.message
                                ? response.data.message
                                : (wprobo_documerge_vars.i18n.form_deleted || 'Form deleted.')
                        );
                    } else {
                        self.showNotice('error',
                            response.data && response.data.message
                                ? response.data.message
                                : (wprobo_documerge_vars.i18n.an_error_occurred || 'An error occurred.')
                        );
                    }
                },
                error: function() {
                    self.showNotice('error', wprobo_documerge_vars.i18n.network_error_retry || 'Network error. Please try again.');
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('wdm-loading');
                }
            });
        },

        // ─────────────────────────────────────────────────────────
        //  Notices & toasts
        // ─────────────────────────────────────────────────────────

        /**
         * Display an admin notice in the notices container.
         *
         * @since 1.0.0
         * @param {string} type    Notice type: 'success' or 'error'.
         * @param {string} message The notice message text.
         */
        showNotice: function(type, message) {
            var $container = $('#wdm-notices');
            var iconClass  = type === 'success' ? 'dashicons-yes-alt' : 'dashicons-warning';

            var $notice = $(
                '<div class="wdm-notice wdm-notice-' + type + '">' +
                    '<span class="dashicons ' + iconClass + '"></span> ' +
                    '<span class="wdm-notice-message">' + $('<span>').text(message).html() + '</span>' +
                    '<button type="button" class="wdm-notice-dismiss">&times;</button>' +
                '</div>'
            );

            $container.append($notice);

            $('html, body').animate({
                scrollTop: $container.offset().top - 50
            }, 300);

            // Auto-dismiss after 5 seconds.
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);

            // Manual dismiss.
            $notice.on('click', '.wdm-notice-dismiss', function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Show a temporary toast notification.
         *
         * @since 1.0.0
         * @param {string} message The toast message text.
         * @param {string} type    Toast type: 'success' or 'error'.
         */
        showToast: function(message, type) {
            var typeClass = type ? 'wdm-toast-' + type : 'wdm-toast-success';

            var $toast = $(
                '<div class="wdm-toast ' + typeClass + '">' +
                    '<span class="wdm-toast-message">' + $('<span>').text(message).html() + '</span>' +
                '</div>'
            );

            $('body').append($toast);

            // Trigger reflow to enable CSS transition.
            $toast[0].offsetHeight;
            $toast.addClass('wdm-toast-visible');

            // Remove after 3 seconds.
            setTimeout(function() {
                $toast.removeClass('wdm-toast-visible');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, 3000);
        },

        // ─────────────────────────────────────────────────────────
        //  Options manager
        // ─────────────────────────────────────────────────────────

        /**
         * Add a new option row to the options list.
         *
         * @since 1.1.0
         * @param {Event} e The click event.
         */
        addOption: function(e) {
            var $btn  = $(e.target).closest('.wdm-add-option');
            var $card = $btn.closest('.wdm-field-card');
            var $list = $card.find('.wdm-options-list');

            var rowHtml = '<div class="wdm-option-row">' +
                '<span class="wdm-option-drag dashicons dashicons-menu"></span>' +
                '<input type="text" class="wdm-option-label wdm-input" placeholder="Label" value="">' +
                '<input type="text" class="wdm-option-value wdm-input" placeholder="Value (auto)" value="">' +
                '<button type="button" class="wdm-option-remove"><span class="dashicons dashicons-no-alt"></span></button>' +
            '</div>';

            $list.append(rowHtml);
        },

        /**
         * Remove an option row and update field data.
         *
         * @since 1.1.0
         * @param {Event} e The click event.
         */
        removeOption: function(e) {
            var $btn  = $(e.target).closest('.wdm-option-remove');
            var $card = $btn.closest('.wdm-field-card');

            $btn.closest('.wdm-option-row').remove();

            // Rebuild field options.
            this.rebuildFieldOptions($card);
        },

        /**
         * Auto-generate value from label if the value field is empty.
         *
         * @since 1.1.0
         * @param {Event} e The input event.
         */
        updateOptionValue: function(e) {
            var $label = $(e.target);
            var $row   = $label.closest('.wdm-option-row');
            var $value = $row.find('.wdm-option-value');

            // Only auto-generate if value field is empty or was auto-generated.
            if ( ! $value.val() || $value.data('auto-generated') ) {
                var generated = $label.val().toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '');
                $value.val(generated);
                $value.data('auto-generated', true);
            }
        },

        /**
         * Rebuild field.options[] from all option row inputs.
         *
         * @since 1.1.0
         * @param {Event} e The change event.
         */
        updateFieldOptions: function(e) {
            var $card = $(e.target).closest('.wdm-field-card');
            this.rebuildFieldOptions($card);
        },

        /**
         * Rebuild options array from DOM rows for a given card.
         *
         * @since 1.1.0
         * @param {jQuery} $card The field card element.
         */
        rebuildFieldOptions: function($card) {
            var fieldId = $card.data('field-id');
            var options = [];

            $card.find('.wdm-option-row').each(function() {
                var label = $(this).find('.wdm-option-label').val();
                var value = $(this).find('.wdm-option-value').val();
                options.push({ label: label, value: value });
            });

            $.each(this.fields, function(i, field) {
                if ( field.id === fieldId ) {
                    field.options = options;
                    return false;
                }
            });
        },


    };
    $(document).ready(function() {
        WPRoboDocuMerge_FormBuilder.init();
    });

})(jQuery);
