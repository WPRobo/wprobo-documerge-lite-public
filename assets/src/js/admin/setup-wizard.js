/**
 * WPRobo DocuMerge — Setup Wizard
 *
 * Handles step navigation, form plugin detection display,
 * and AJAX saving of wizard configuration.
 *
 * @package WPRobo_DocuMerge
 * @since   1.0.0
 */

(function($) {
    'use strict';

    /**
     * WPRobo DocuMerge Setup Wizard
     *
     * @since 1.0.0
     */
    var WPRoboDocuMerge_SetupWizard = {

        /**
         * Current active step (1-4).
         *
         * @since 1.0.0
         * @type  {number}
         */
        currentStep: 1,

        /**
         * Total number of steps.
         *
         * @since 1.0.0
         * @type  {number}
         */
        totalSteps: 4,

        /**
         * Selected integration from step 2.
         *
         * @since 1.0.0
         * @type  {string}
         */
        selectedIntegration: '',

        /**
         * Initialize the wizard.
         *
         * @since 1.0.0
         */
        init: function() {
            this.bindEvents();
            this.wprobo_documerge_render_detect_results();
        },

        /**
         * Bind all event handlers.
         *
         * @since 1.0.0
         */
        bindEvents: function() {
            var self = this;

            $(document).on('click', '#wdm-wizard-continue', function(e) {
                e.preventDefault();
                self.wprobo_documerge_next_step();
            });

            $(document).on('click', '#wdm-wizard-skip', function(e) {
                e.preventDefault();
                self.wprobo_documerge_skip_wizard($(this).attr('href'));
            });

            $(document).on('change', 'input[name="wdm_integration"]', function() {
                self.selectedIntegration = $(this).val();
            });
        },

        /**
         * Navigate to the next step.
         *
         * On step 3, save settings via AJAX before proceeding.
         *
         * @since 1.0.0
         */
        wprobo_documerge_next_step: function() {
            var self = this;

            if ( self.currentStep === 3 ) {
                self.wprobo_documerge_save_settings(function() {
                    self.wprobo_documerge_go_to_step( self.currentStep + 1 );
                });
                return;
            }

            if ( self.currentStep < self.totalSteps ) {
                self.wprobo_documerge_go_to_step( self.currentStep + 1 );
            }
        },

        /**
         * Navigate to a specific step.
         *
         * @since 1.0.0
         * @param {number} step Target step number.
         */
        wprobo_documerge_go_to_step: function( step ) {
            if ( step < 1 || step > this.totalSteps ) {
                return;
            }

            this.currentStep = step;

            // Update step content visibility.
            $('.wdm-wizard-step').removeClass('wdm-step-active');
            $('.wdm-wizard-step[data-step="' + step + '"]').addClass('wdm-step-active');

            // Update progress dots.
            $('.wdm-wizard-step-dot').removeClass('wdm-active wdm-completed');
            $('.wdm-wizard-step-dot').each(function() {
                var dotStep = parseInt( $(this).data('step'), 10 );
                if ( dotStep < step ) {
                    $(this).addClass('wdm-completed');
                } else if ( dotStep === step ) {
                    $(this).addClass('wdm-active');
                }
            });

            // Update progress bar fill.
            var progress = ( ( step - 1 ) / ( this.totalSteps - 1 ) ) * 100;
            $('#wdm-progress-fill').css('width', progress + '%');

            // Update footer visibility.
            if ( step === this.totalSteps ) {
                $('#wdm-wizard-footer').hide();
            } else {
                $('#wdm-wizard-footer').show();
            }
        },

        /**
         * Render form plugin detection results in step 2.
         *
         * @since 1.0.0
         */
        wprobo_documerge_render_detect_results: function() {
            var $container = $('#wdm-detect-results');
            var plugins    = wprobo_documerge_wizard_vars.detected_plugins;

            var i18n = wprobo_documerge_wizard_vars.i18n;

            if ( ! plugins || plugins.length === 0 ) {
                $container.html(
                    '<div class="wdm-detect-card wdm-detect-none">' +
                        '<span class="wdm-detect-icon dashicons dashicons-info"></span>' +
                        '<div class="wdm-detect-text">' +
                            '<strong>' + this.wprobo_documerge_esc_html( i18n.no_plugins_title ) + '</strong>' +
                            '<p>' + this.wprobo_documerge_esc_html( i18n.no_plugins_desc ) + '</p>' +
                        '</div>' +
                    '</div>'
                );
                return;
            }

            var self = this;
            var html = '';
            $.each( plugins, function( index, plugin ) {
                var versionText = plugin.version ? ' (v' + plugin.version + ')' : '';
                var isFirst     = ( index === 0 );

                html += '<div class="wdm-detect-card wdm-detect-found">' +
                    '<span class="wdm-detect-icon dashicons dashicons-yes-alt"></span>' +
                    '<div class="wdm-detect-text">' +
                        '<strong>' + self.wprobo_documerge_esc_html( plugin.name + versionText + ' ' + i18n.detected_suffix ) + '</strong>' +
                        '<p>' + self.wprobo_documerge_esc_html( i18n.integrate_prefix + ' ' + plugin.name + '?' ) + '</p>' +
                        '<div class="wdm-detect-radios">' +
                            '<label class="wdm-wizard-radio">' +
                                '<input type="radio" name="wdm_integration" value="' + plugin.slug + '"' + ( isFirst ? ' checked' : '' ) + '>' +
                                '<span class="wdm-radio-mark"></span>' +
                                '<span class="wdm-radio-text">' + self.wprobo_documerge_esc_html( i18n.yes_use_prefix + ' ' + plugin.name + ' ' + i18n.yes_use_suffix ) + '</span>' +
                            '</label>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            });

            // Add standalone option.
            html += '<div class="wdm-detect-card">' +
                '<span class="wdm-detect-icon dashicons dashicons-edit-large"></span>' +
                '<div class="wdm-detect-text">' +
                    '<label class="wdm-wizard-radio">' +
                        '<input type="radio" name="wdm_integration" value="standalone">' +
                        '<span class="wdm-radio-mark"></span>' +
                        '<span class="wdm-radio-text">' + self.wprobo_documerge_esc_html( i18n.standalone_label ) + '</span>' +
                    '</label>' +
                '</div>' +
            '</div>';

            $container.html( html );

            // Set initial selection.
            this.selectedIntegration = plugins[0].slug;
        },

        /**
         * Save wizard settings via AJAX.
         *
         * @since    1.0.0
         * @param    {Function} callback Function to call on success.
         */
        wprobo_documerge_save_settings: function( callback ) {
            var self = this;
            var $btn = $('#wdm-wizard-continue');

            // Collect delivery methods.
            var deliveryMethods = [];
            $('input[name="wdm_delivery_methods[]"]:checked').each(function() {
                deliveryMethods.push( $(this).val() );
            });

            var data = {
                action:           'wprobo_documerge_wizard_save',
                nonce:            wprobo_documerge_wizard_vars.nonce,
                integration:      self.selectedIntegration,
                output_format:    $('input[name="wdm_output_format"]:checked').val(),
                doc_storage:      $('input[name="wdm_doc_storage"]:checked').val(),
                delivery_methods: deliveryMethods,
            };

            $.ajax({
                url:      wprobo_documerge_wizard_vars.ajax_url,
                type:     'POST',
                dataType: 'json',
                data:     data,
                beforeSend: function() {
                    $btn.prop('disabled', true).addClass('wdm-loading');
                },
                success: function( response ) {
                    if ( response.success ) {
                        if ( typeof callback === 'function' ) {
                            callback();
                        }
                    } else {
                        self.wprobo_documerge_show_error(
                            response.data && response.data.message
                                ? response.data.message
                                : wprobo_documerge_wizard_vars.i18n.error
                        );
                    }
                },
                error: function() {
                    self.wprobo_documerge_show_error( wprobo_documerge_wizard_vars.i18n.network_error );
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('wdm-loading');
                }
            });
        },

        /**
         * Skip the wizard — mark as complete and redirect.
         *
         * @since 1.0.0
         * @param {string} redirectUrl URL to redirect to.
         */
        wprobo_documerge_skip_wizard: function( redirectUrl ) {
            $.ajax({
                url:      wprobo_documerge_wizard_vars.ajax_url,
                type:     'POST',
                dataType: 'json',
                data: {
                    action:           'wprobo_documerge_wizard_save',
                    nonce:            wprobo_documerge_wizard_vars.nonce,
                    integration:      '',
                    output_format:    'pdf',
                    doc_storage:      'keep',
                    delivery_methods: ['download'],
                },
                complete: function() {
                    window.location.href = redirectUrl;
                }
            });
        },

        /**
         * Show an error message in the wizard.
         *
         * @since 1.0.0
         * @param {string} message Error message text.
         */
        wprobo_documerge_show_error: function( message ) {
            var $existing = $('.wdm-wizard-error');
            if ( $existing.length ) {
                $existing.remove();
            }

            var $error = $('<div class="wdm-wizard-error">' +
                '<span class="dashicons dashicons-warning"></span> ' + message +
            '</div>');

            $('.wdm-wizard-step.wdm-step-active .wdm-wizard-step-inner').prepend( $error );

            setTimeout(function() {
                $error.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Simple HTML entity escaping for dynamic content.
         *
         * @since  1.0.0
         * @param  {string} text Raw text.
         * @return {string}      Escaped text.
         */
        wprobo_documerge_esc_html: function( text ) {
            var div = document.createElement('div');
            div.appendChild( document.createTextNode( text ) );
            return div.innerHTML;
        }
    };

    $(document).ready(function() {
        WPRoboDocuMerge_SetupWizard.init();
    });

})(jQuery);
