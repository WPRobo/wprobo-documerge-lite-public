/**
 * WPRobo DocuMerge — Settings Page
 *
 * Handles tab switching, settings saving via AJAX,
 * password visibility toggling, clipboard copy actions,
 * and integration field visibility.
 *
 * @package WPRobo_DocuMerge
 * @since   1.0.0
 */

(function($) {
    'use strict';

    /**
     * WPRobo DocuMerge Settings
     *
     * @since 1.0.0
     */
    var WPRoboDocuMerge_Settings = {

        /**
         * Map of tab slugs to their AJAX save actions.
         *
         * @since 1.0.0
         * @type  {Object}
         */
        tabActions: {
            general:   'wprobo_documerge_save_general',
            advanced:  'wprobo_documerge_save_advanced'
        },

        /**
         * Initialize the settings module.
         *
         * @since 1.0.0
         */
        init: function() {
            this.bindEvents();

            // Restore active tab from URL hash.
            var hash = window.location.hash.replace('#', '');
            if (hash) {
                var $tab = $('.wdm-settings-tab[data-tab="' + hash + '"]');
                if ($tab.length) {
                    this.switchTab($tab);
                }
            }

            // Handle highlight param — scroll to and pulse a specific card.
            var urlParams = new URLSearchParams(window.location.search);
            var highlight = urlParams.get('highlight');
            if ( 'form-mode' === highlight ) {
                var $card = $('#wdm-form-mode-card');
                if ( $card.length ) {
                    // Scroll to the card.
                    setTimeout(function() {
                        $('html, body').animate({ scrollTop: $card.offset().top - 60 }, 400, function() {
                            // Add pulse animation.
                            $card.addClass('wdm-highlight-pulse');
                            // Remove after animation completes.
                            setTimeout(function() {
                                $card.removeClass('wdm-highlight-pulse');
                            }, 3000);
                        });
                    }, 300);

                    // Clean URL.
                    if ( window.history && window.history.replaceState ) {
                        var cleanUrl = window.location.pathname + '?page=wprobo-documerge-settings';
                        window.history.replaceState(null, '', cleanUrl);
                    }
                }
            }
        },

        /**
         * Bind all event handlers.
         *
         * @since 1.0.0
         */
        bindEvents: function() {
            var self = this;

            // Tab switching.
            $(document).on('click', '.wdm-settings-tab', function(e) {
                e.preventDefault();
                self.switchTab($(this));
            });

            // Save settings.
            $(document).on('click', '.wdm-settings-save', function(e) {
                e.preventDefault();
                self.saveSettings($(this));
            });

            // Toggle password visibility.
            $(document).on('click', '.wdm-toggle-password', function(e) {
                e.preventDefault();
                self.togglePassword($(this));
            });

            // Copy system info.
            $(document).on('click', '#wdm-copy-system-info', function(e) {
                e.preventDefault();
                var value = $('#wdm-system-info-text').val();
                self.copyToClipboard(value);
                self.showToast(wprobo_documerge_vars.i18n.copied, 'success');
            });


            // Danger Zone actions.
            $(document).on('click', '.wdm-danger-action', function(e) {
                e.preventDefault();
                var actionName = $(this).data('action');
                var $btn = $(this);

                var messages = {
                    delete_submissions: 'DELETE ALL SUBMISSIONS and their generated documents? This cannot be undone.',
                    delete_forms:       'DELETE ALL FORMS? All form configurations will be permanently removed.',
                    delete_templates:   'DELETE ALL TEMPLATES and their uploaded DOCX files?',
                    delete_documents:   'DELETE ALL GENERATED DOCUMENTS (PDF/DOCX files)? Submission records are kept but downloads will stop working.',
                    reset_settings:     'RESET ALL SETTINGS to factory defaults? Forms, templates, and submissions are not affected.',
                    factory_reset:      'FULL FACTORY RESET — This will DELETE EVERYTHING: submissions, forms, templates, documents, settings, and logs. The plugin will return to a freshly-installed state.\n\nType "RESET" to confirm.'
                };

                if (actionName === 'factory_reset') {
                    var typed = prompt(messages[actionName]);
                    if (typed !== 'RESET') { return; }
                } else {
                    if (!confirm(messages[actionName])) { return; }
                }

                $btn.prop('disabled', true).addClass('wdm-loading');

                $.ajax({
                    url:  wprobo_documerge_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action:       'wprobo_documerge_danger_zone',
                        nonce:        wprobo_documerge_vars.settings_nonce,
                        danger_action: actionName
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).removeClass('wdm-loading');
                        if (response.success) {
                            self.showNotice('success', response.data.message || 'Done.');
                            if (actionName === 'factory_reset') {
                                setTimeout(function() { window.location.reload(); }, 1500);
                            } else {
                                setTimeout(function() { window.location.reload(); }, 1000);
                            }
                        } else {
                            self.showNotice('error', response.data ? response.data.message : 'An error occurred.');
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).removeClass('wdm-loading');
                        self.showNotice('error', 'Network error.');
                    }
                });
            });

            // Integration dropdown visibility based on form mode.
            $(document).on('change', 'input[name="wprobo_documerge_form_mode"]', function() {
                self.toggleIntegrationField($(this).val());
            });

            // Re-run Setup Wizard button.
            $(document).on('click', '#wdm-rerun-wizard', function(e) {
                e.preventDefault();
                if ( ! confirm(wprobo_documerge_vars.i18n.confirm_rerun_wizard || 'Re-run the setup wizard? You will be redirected to the wizard screen.') ) {
                    return;
                }
                var $btn = $(this);
                $btn.prop('disabled', true).addClass('wdm-loading');

                $.ajax({
                    url:  wprobo_documerge_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wprobo_documerge_reset_wizard',
                        nonce:  wprobo_documerge_vars.nonce
                    },
                    success: function(response) {
                        if ( response.success && response.data.redirect ) {
                            window.location.href = response.data.redirect;
                        } else {
                            $btn.prop('disabled', false).removeClass('wdm-loading');
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).removeClass('wdm-loading');
                    }
                });
            });

            // ── Import / Export ──────────────────────────────────────

            // Export Selected.
            $(document).on('click', '#wdm-export-selected', function(e) {
                e.preventDefault();
                var types = [];
                $('.wdm-export-checkbox:checked').each(function() {
                    types.push($(this).val());
                });
                if ( ! types.length ) {
                    self.showNotice('error', wprobo_documerge_vars.i18n.export_none || 'Please select at least one data type to export.');
                    return;
                }
                self.wproboDocuMergeRunExport($(this), types);
            });

            // Export All.
            $(document).on('click', '#wdm-export-all', function(e) {
                e.preventDefault();
                var types = [];
                $('.wdm-export-checkbox').each(function() {
                    types.push($(this).val());
                });
                self.wproboDocuMergeRunExport($(this), types);
            });

            // Import: browse button triggers file input.
            $(document).on('click', '#wdm-import-browse', function(e) {
                e.preventDefault();
                $('#wdm-import-file').trigger('click');
            });

            // Import: file selected.
            $(document).on('change', '#wdm-import-file', function() {
                var file = this.files[0];
                if ( file ) {
                    self.wproboDocuMergeHandleImportFile(file);
                }
            });

            // Import: drag and drop.
            $(document).on('dragover', '#wdm-import-dropzone', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('wdm-dropzone-active');
            });
            $(document).on('dragleave', '#wdm-import-dropzone', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('wdm-dropzone-active');
            });
            $(document).on('drop', '#wdm-import-dropzone', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('wdm-dropzone-active');
                var file = e.originalEvent.dataTransfer.files[0];
                if ( file ) {
                    self.wproboDocuMergeHandleImportFile(file);
                }
            });

            // Import: clear file.
            $(document).on('click', '#wdm-import-clear', function(e) {
                e.preventDefault();
                self.wproboDocuMergeParsedData = null;
                $('#wdm-import-file').val('');
                $('#wdm-import-preview').slideUp(200);
                $('#wdm-import-result').slideUp(200);
                $('#wdm-import-dropzone').slideDown(200);
                $('#wdm-import-run').prop('disabled', true);
            });

            // Import: run import.
            $(document).on('click', '#wdm-import-run', function(e) {
                e.preventDefault();
                self.wproboDocuMergeRunImport($(this));
            });

            // Import: enable/disable import button based on checkboxes.
            $(document).on('change', '.wdm-import-type-checkbox', function() {
                var anyChecked = $('.wdm-import-type-checkbox:checked').length > 0;
                $('#wdm-import-run').prop('disabled', !anyChecked);
            });

        },

        /**
         * Switch to the selected tab.
         *
         * @since 1.0.0
         * @param {jQuery} $tab The clicked tab element.
         */
        switchTab: function($tab) {
            var tabId = $tab.data('tab');

            // Update tab active state.
            $tab.siblings('.wdm-settings-tab').removeClass('wdm-tab-active');
            $tab.addClass('wdm-tab-active');

            // Update panel visibility.
            $('.wdm-settings-panel').removeClass('wdm-panel-active');
            $('.wdm-settings-panel[data-tab="' + tabId + '"]').addClass('wdm-panel-active');

            // Save tab to URL hash so page refresh restores it.
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, '', window.location.pathname + window.location.search + '#' + tabId);
            }
        },

        /**
         * Save settings for the given tab via AJAX.
         *
         * @since 1.0.0
         * @param {jQuery} $btn The clicked save button.
         */
        saveSettings: function($btn) {
            var self   = this;
            var tab    = $btn.data('tab');
            var action = self.tabActions[tab];

            if ( ! action ) {
                return;
            }

            var $panel = $('.wdm-settings-panel[data-tab="' + tab + '"]');
            var data   = {
                action: action,
                nonce:  wprobo_documerge_vars.settings_nonce
            };

            // Collect all inputs from the panel (skip inputs inside hidden containers).
            $panel.find(':input').each(function() {
                var $input = $(this);
                var name   = $input.attr('name');

                if ( ! name ) {
                    return;
                }

                // Skip inputs inside hidden containers.
                var $parent = $input.closest('.wdm-integration-field-group');
                if ( $parent.length && $parent.is(':hidden') ) {
                    return;
                }

                if ( $input.is(':checkbox') ) {
                    data[name] = $input.is(':checked') ? '1' : '0';
                } else if ( $input.is(':radio') ) {
                    if ( $input.is(':checked') ) {
                        data[name] = $input.val();
                    }
                } else {
                    data[name] = $input.val();
                }
            });

            $.ajax({
                url:      wprobo_documerge_vars.ajax_url,
                type:     'POST',
                dataType: 'json',
                data:     data,
                beforeSend: function() {
                    $btn.prop('disabled', true).addClass('wdm-loading');
                },
                success: function(response) {
                    if ( response.success ) {
                        self.showNotice('success', response.data && response.data.message
                            ? response.data.message
                            : wprobo_documerge_vars.i18n.settings_saved
                        );
                    } else {
                        self.showNotice('error', response.data && response.data.message
                            ? response.data.message
                            : wprobo_documerge_vars.i18n.error
                        );
                    }
                },
                error: function() {
                    self.showNotice('error', wprobo_documerge_vars.i18n.network_error);
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('wdm-loading');
                }
            });
        },

        /**
         * Toggle password field visibility.
         *
         * @since 1.0.0
         * @param {jQuery} $btn The toggle button.
         */
        togglePassword: function($btn) {
            var $input = $btn.siblings('input');
            var $icon  = $btn.find('.dashicons');

            if ( $input.attr('type') === 'password' ) {
                $input.attr('type', 'text');
                $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
        },

        /**
         * Copy text to clipboard using a temporary textarea.
         *
         * @since 1.0.0
         * @param {string} text The text to copy.
         */
        copyToClipboard: function(text) {
            var $temp = $('<textarea>');

            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
        },

        /**
         * Show or hide the integration field group based on form mode.
         *
         * @since 1.0.0
         * @param {string} mode The selected form mode value.
         */
        toggleIntegrationField: function(mode) {
            var $group = $('.wdm-integration-field-group');

            if ( 'standalone' === mode ) {
                $group.slideUp(200);
            } else {
                $group.slideDown(200);
            }
        },

        /**
         * Parsed import data stored temporarily.
         *
         * @since 1.6.0
         * @type {Object|null}
         */
        wproboDocuMergeParsedData: null,

        /**
         * Run the export AJAX request and trigger a file download.
         *
         * @since 1.6.0
         * @param {jQuery} $btn  The clicked button.
         * @param {Array}  types Array of type strings to export.
         */
        wproboDocuMergeRunExport: function($btn, types) {
            var self = this;

            $btn.prop('disabled', true).addClass('wdm-loading');

            $.ajax({
                url:      wprobo_documerge_vars.ajax_url,
                type:     'POST',
                dataType: 'json',
                data: {
                    action:       'wprobo_documerge_export_data',
                    nonce:        wprobo_documerge_vars.settings_nonce,
                    export_types: types
                },
                success: function(response) {
                    if ( response.success && response.data && response.data.json ) {
                        // Create a downloadable file from the JSON.
                        var json_str = JSON.stringify(response.data.json, null, 2);
                        var blob     = new Blob([json_str], { type: 'application/json' });
                        var url      = URL.createObjectURL(blob);
                        var $link    = $('<a>').attr({
                            href:     url,
                            download: response.data.filename || 'documerge-export.json'
                        });
                        $('body').append($link);
                        $link[0].click();
                        $link.remove();
                        URL.revokeObjectURL(url);

                        self.showToast(wprobo_documerge_vars.i18n.export_success || 'Export downloaded.', 'success');
                    } else {
                        self.showNotice('error', response.data ? response.data.message : 'Export failed.');
                    }
                },
                error: function() {
                    self.showNotice('error', wprobo_documerge_vars.i18n.network_error);
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('wdm-loading');
                }
            });
        },

        /**
         * Handle an import file — read, parse, validate, and display preview.
         *
         * @since 1.6.0
         * @param {File} file The JSON file from the file input or drag-and-drop.
         */
        wproboDocuMergeHandleImportFile: function(file) {
            var self = this;

            // Validate file type.
            if ( file.type && file.type !== 'application/json' && ! file.name.match(/\.json$/i) ) {
                self.showNotice('error', wprobo_documerge_vars.i18n.import_invalid_type || 'Please select a valid JSON file.');
                return;
            }

            // Validate file size (max 50MB).
            if ( file.size > 52428800 ) {
                self.showNotice('error', wprobo_documerge_vars.i18n.import_too_large || 'File is too large. Maximum size is 50 MB.');
                return;
            }

            var reader = new FileReader();
            reader.onload = function(e) {
                var data;
                try {
                    data = JSON.parse(e.target.result);
                } catch (err) {
                    self.showNotice('error', wprobo_documerge_vars.i18n.import_invalid_json || 'Could not parse JSON file.');
                    return;
                }

                // Validate structure.
                if ( ! data.plugin || data.plugin !== 'wprobo-documerge' ) {
                    self.showNotice('error', wprobo_documerge_vars.i18n.import_wrong_plugin || 'This file is not a valid DocuMerge export.');
                    return;
                }

                if ( ! data.data || typeof data.data !== 'object' ) {
                    self.showNotice('error', wprobo_documerge_vars.i18n.import_no_data || 'Export file contains no data.');
                    return;
                }

                // Store parsed data.
                self.wproboDocuMergeParsedData = data;

                // Show file name.
                $('#wdm-import-filename').text(file.name);

                // Build summary.
                var summaryParts = [];
                var version_info = '';
                if ( data.version ) {
                    version_info = '<span class="wdm-import-meta">' +
                        (wprobo_documerge_vars.i18n.import_version || 'Version') + ': ' + $('<span>').text(data.version).html() +
                    '</span>';
                }
                if ( data.exported_at ) {
                    version_info += ' <span class="wdm-import-meta">' +
                        (wprobo_documerge_vars.i18n.import_date || 'Exported') + ': ' + $('<span>').text(data.exported_at).html() +
                    '</span>';
                }
                if ( data.site_url ) {
                    version_info += ' <span class="wdm-import-meta">' +
                        (wprobo_documerge_vars.i18n.import_site || 'Site') + ': ' + $('<span>').text(data.site_url).html() +
                    '</span>';
                }
                $('#wdm-import-summary').html(version_info);

                // Build selectable items.
                var itemsHtml = '';
                var type_labels = {
                    templates:   wprobo_documerge_vars.i18n.templates   || 'Templates',
                    forms:       wprobo_documerge_vars.i18n.forms       || 'Forms',
                    submissions: wprobo_documerge_vars.i18n.submissions || 'Submissions',
                    settings:    wprobo_documerge_vars.i18n.settings_label || 'Settings'
                };

                $.each(data.data, function(key, val) {
                    var count = '';
                    if ( $.isArray(val) ) {
                        count = val.length;
                    } else if ( typeof val === 'object' ) {
                        count = Object.keys(val).length;
                    }
                    var label = type_labels[key] || key;
                    itemsHtml += '<label class="wdm-checkbox-label">' +
                        '<input type="checkbox" class="wdm-import-type-checkbox" value="' + $('<span>').text(key).html() + '" checked> ' +
                        $('<span>').text(label).html() + ' (' + count + ')' +
                    '</label>';
                });

                $('#wdm-import-select-items').html(itemsHtml);
                $('#wdm-import-run').prop('disabled', false);

                // Show preview, hide drop zone.
                $('#wdm-import-dropzone').slideUp(200);
                $('#wdm-import-result').slideUp(200);
                $('#wdm-import-preview').slideDown(200);
            };

            reader.readAsText(file);
        },

        /**
         * Run the import AJAX request.
         *
         * @since 1.6.0
         * @param {jQuery} $btn The import button.
         */
        wproboDocuMergeRunImport: function($btn) {
            var self = this;
            var data = self.wproboDocuMergeParsedData;

            if ( ! data ) {
                self.showNotice('error', 'No file loaded.');
                return;
            }

            // Collect selected types.
            var types = [];
            $('.wdm-import-type-checkbox:checked').each(function() {
                types.push($(this).val());
            });

            if ( ! types.length ) {
                self.showNotice('error', wprobo_documerge_vars.i18n.import_none || 'Please select at least one data type to import.');
                return;
            }

            var mode = $('input[name="wdm_import_mode"]:checked').val() || 'merge';

            // Confirm if replace mode.
            if ( 'replace' === mode ) {
                if ( ! confirm(wprobo_documerge_vars.i18n.import_replace_confirm || 'Replace mode will DELETE all existing data for the selected types before importing. Continue?') ) {
                    return;
                }
            }

            $btn.prop('disabled', true).addClass('wdm-loading');

            $.ajax({
                url:      wprobo_documerge_vars.ajax_url,
                type:     'POST',
                dataType: 'json',
                data: {
                    action:       'wprobo_documerge_import_data',
                    nonce:        wprobo_documerge_vars.settings_nonce,
                    import_json:  JSON.stringify(data),
                    import_mode:  mode,
                    import_types: types
                },
                success: function(response) {
                    if ( response.success ) {
                        // Build result summary.
                        var resultHtml = '<div class="wdm-notice wdm-notice-success" role="alert">' +
                            '<span class="dashicons dashicons-yes-alt"></span> ' +
                            '<span class="wdm-notice-message">' + $('<span>').text(response.data.message).html() + '</span>' +
                        '</div>';

                        if ( response.data.results ) {
                            resultHtml += '<div class="wdm-import-result-details">';
                            var result_labels = {
                                templates:   wprobo_documerge_vars.i18n.templates   || 'Templates',
                                forms:       wprobo_documerge_vars.i18n.forms       || 'Forms',
                                submissions: wprobo_documerge_vars.i18n.submissions || 'Submissions',
                                settings:    wprobo_documerge_vars.i18n.settings_label || 'Settings'
                            };
                            $.each(response.data.results, function(key, count) {
                                var label = result_labels[key] || key;
                                resultHtml += '<div class="wdm-import-result-row">' +
                                    '<span class="wdm-import-result-label">' + $('<span>').text(label).html() + '</span>' +
                                    '<span class="wdm-badge wdm-badge-success">' + count + ' ' +
                                        (wprobo_documerge_vars.i18n.imported || 'imported') +
                                    '</span>' +
                                '</div>';
                            });
                            resultHtml += '</div>';
                        }

                        $('#wdm-import-result').html(resultHtml).slideDown(200);
                        $('#wdm-import-preview').slideUp(200);
                        self.wproboDocuMergeParsedData = null;
                        $('#wdm-import-file').val('');
                    } else {
                        self.showNotice('error', response.data ? response.data.message : 'Import failed.');
                    }
                },
                error: function() {
                    self.showNotice('error', wprobo_documerge_vars.i18n.network_error);
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('wdm-loading');
                }
            });
        },

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

            // Scroll to notice.
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
        }
    };

    $(document).ready(function() {
        WPRoboDocuMerge_Settings.init();
    });

})(jQuery);
