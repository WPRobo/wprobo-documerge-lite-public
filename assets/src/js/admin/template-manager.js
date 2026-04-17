/**
 * WPRobo DocuMerge — Template Manager
 *
 * Handles the template card grid, slide-panel open/close,
 * DOCX drag-and-drop upload, merge-tag display,
 * template save (create/edit), and template deletion.
 *
 * @package WPRobo_DocuMerge
 * @since   1.0.0
 */

(function($) {
    'use strict';

    /**
     * WPRobo DocuMerge Template Manager module.
     *
     * @since 1.0.0
     * @type  {Object}
     */
    var WPRoboDocuMerge_TemplateManager = {

        /**
         * Maximum allowed file size in bytes (10 MB).
         *
         * @since 1.0.0
         * @type  {number}
         */
        maxFileSize: 10 * 1024 * 1024,

        /**
         * Initialize the module — bind all events and set up drag/drop.
         *
         * @since 1.0.0
         */
        init: function() {
            this.bindEvents();
            this.setupDragDrop();

            // Auto-open edit panel if ?edit=ID is in the URL.
            var urlParams = new URLSearchParams(window.location.search);
            var editId = urlParams.get('edit');
            if (editId && parseInt(editId, 10) > 0) {
                this.loadTemplate(parseInt(editId, 10));
            }
        },

        /**
         * Bind all UI event handlers.
         *
         * @since 1.0.0
         */
        bindEvents: function() {
            var self = this;

            // Open panel — new template (header button or empty-state button).
            $(document).on('click', '#wdm-upload-template-btn, .wdm-template-upload-btn', function(e) {
                e.preventDefault();
                self.openPanel();
            });

            // Show all tags on "+X more" click.
            $(document).on('click', '.wdm-tag-more', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $btn  = $(this);
                var tags  = $btn.data('tags');
                if (typeof tags === 'string') {
                    try { tags = JSON.parse(tags); } catch (ex) { tags = []; }
                }
                var $pills = $btn.closest('.wdm-template-tag-pills');
                // Save original HTML for "show less".
                if (!$pills.data('original-html')) {
                    $pills.data('original-html', $pills.html());
                }
                $pills.empty();
                $.each(tags, function(i, tag) {
                    $pills.append('<code class="wdm-tag-pill">' + $('<span>').text(tag).html() + '</code> ');
                });
                $pills.append('<button type="button" class="wdm-tag-less">Show less</button>');
            });

            // Collapse tags back on "Show less" click.
            $(document).on('click', '.wdm-tag-less', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $pills = $(this).closest('.wdm-template-tag-pills');
                var original = $pills.data('original-html');
                if (original) {
                    $pills.html(original);
                }
            });

            // Open panel — edit existing template.
            $(document).on('click', '.wdm-template-edit', function(e) {
                e.preventDefault();
                var templateId = $(this).data('id');
                self.loadTemplate(templateId);
            });

            // Close panel.
            $(document).on('click', '#wdm-panel-close, #wdm-panel-cancel, #wdm-overlay', function(e) {
                e.preventDefault();
                self.closePanel();
            });

            // File input change.
            $(document).on('change', '#wdm-template-file', function() {
                var files = this.files;
                if (files && files.length) {
                    self.handleFile(files[0]);
                }
            });

            // Click dropzone to trigger file input.
            $(document).on('click', '#wdm-dropzone', function(e) {
                if (!$(e.target).is('#wdm-template-file')) {
                    $('#wdm-template-file').trigger('click');
                }
            });

            // Save template.
            $(document).on('click', '#wdm-save-template', function(e) {
                e.preventDefault();
                self.saveTemplate();
            });

            // Delete template.
            $(document).on('click', '.wdm-template-delete', function(e) {
                e.preventDefault();
                var templateId = $(this).data('id');
                self.deleteTemplate(templateId, $(this));
            });
        },

        /**
         * Set up drag-and-drop on the dropzone element.
         *
         * @since 1.0.0
         */
        setupDragDrop: function() {
            var self      = this;
            var $dropzone = $('#wdm-dropzone');

            if (!$dropzone.length) {
                return;
            }

            $(document).on('dragover', '#wdm-dropzone', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('wdm-dropzone-active');
            });

            $(document).on('dragleave', '#wdm-dropzone', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('wdm-dropzone-active');
            });

            $(document).on('drop', '#wdm-dropzone', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('wdm-dropzone-active');

                var files = e.originalEvent.dataTransfer.files;
                if (files && files.length) {
                    self.handleFile(files[0]);
                }
            });
        },

        // ─────────────────────────────────────────────────────────
        //  Panel open / close
        // ─────────────────────────────────────────────────────────

        /**
         * Open the slide panel for a new template.
         *
         * @since 1.0.0
         */
        openPanel: function() {
            this.resetForm();
            $('#wdm-panel-title').text(wprobo_documerge_vars.i18n.upload_new_template || 'Upload New Template');
            $('#wdm-template-panel').addClass('wdm-panel-open');
            $('#wdm-overlay').fadeIn(200);
        },

        /**
         * Open the slide panel for editing an existing template.
         *
         * @since 1.0.0
         * @param {Object} data Template data object from the server.
         */
        openEditPanel: function(data) {
            this.resetForm();
            $('#wdm-panel-title').text(wprobo_documerge_vars.i18n.edit_template || 'Edit Template');
            $('#wdm-template-id').val(data.id);
            $('#wdm-template-name').val(data.name);
            $('#wdm-template-description').val(data.description);
            $('#wdm-template-file-path').val(data.file_path || '');

            // Output format.
            $('input[name="wdm_output_format"][value="' + data.output_format + '"]').prop('checked', true);

            // Show existing file name.
            if (data.file_name) {
                $('#wdm-uploaded-file-name').text(data.file_name);
                $('#wdm-uploaded-file').removeClass('wdm-hidden');
            }

            // Show merge tags if present.
            var mergeTags = data.merge_tags;
            if (typeof mergeTags === 'string') {
                try {
                    mergeTags = JSON.parse(mergeTags);
                } catch (e) {
                    mergeTags = [];
                }
            }
            if (mergeTags && mergeTags.length) {
                this.displayMergeTags(mergeTags);
            }

            $('#wdm-template-panel').addClass('wdm-panel-open');
            $('#wdm-overlay').fadeIn(200);
        },

        /**
         * Close the slide panel and reset all form fields.
         *
         * @since 1.0.0
         */
        closePanel: function() {
            $('#wdm-template-panel').removeClass('wdm-panel-open');
            $('#wdm-overlay').fadeOut(200);
            this.resetForm();
        },

        /**
         * Reset all form fields in the slide panel to their defaults.
         *
         * @since 1.0.0
         */
        resetForm: function() {
            $('#wdm-template-id').val('0');
            $('#wdm-template-file-path').val('');
            $('#wdm-template-name').val('');
            $('#wdm-template-description').val('');
            $('#wdm-template-file').val('');
            $('input[name="wdm_output_format"][value="pdf"]').prop('checked', true);
            $('#wdm-upload-progress').addClass('wdm-hidden');
            $('#wdm-uploaded-file').addClass('wdm-hidden');
            $('#wdm-uploaded-file-name').text('');
            $('#wdm-detected-tags').addClass('wdm-hidden');
            $('#wdm-tag-list').empty();
            $('#wdm-tag-count').text('');
            $('#wdm-progress-fill').css('width', '0%');
            $('#wdm-progress-text').text('0%');
            $('#wdm-dropzone').removeClass('wdm-dropzone-error');
            $('#wdm-dropzone .wdm-dropzone-error-msg').remove();
        },

        // ─────────────────────────────────────────────────────────
        //  File handling & upload
        // ─────────────────────────────────────────────────────────

        /**
         * Validate and upload the selected DOCX file.
         *
         * @since 1.0.0
         * @param {File} file The file object from the input or drop event.
         */
        handleFile: function(file) {
            var self = this;

            // Clear any previous error.
            $('#wdm-dropzone').removeClass('wdm-dropzone-error');
            $('#wdm-dropzone .wdm-dropzone-error-msg').remove();

            // Validate extension.
            var fileName = file.name || '';
            if (fileName.substring(fileName.lastIndexOf('.')).toLowerCase() !== '.docx') {
                this.showDropzoneError(wprobo_documerge_vars.i18n.invalid_file_type || 'Please upload a .docx file.');
                return;
            }

            // Validate size.
            if (file.size > this.maxFileSize) {
                this.showDropzoneError(wprobo_documerge_vars.i18n.file_too_large || 'File size exceeds 10 MB limit.');
                return;
            }

            // Build FormData and upload.
            var formData = new FormData();
            formData.append('action', 'wprobo_documerge_upload_template');
            formData.append('nonce', wprobo_documerge_vars.nonce);
            formData.append('template_file', file);

            $('#wdm-upload-progress').removeClass('wdm-hidden');

            $.ajax({
                url:         wprobo_documerge_vars.ajax_url,
                type:        'POST',
                dataType:    'json',
                data:        formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(evt) {
                        if (evt.lengthComputable) {
                            var pct = Math.round((evt.loaded / evt.total) * 100);
                            $('#wdm-progress-fill').css('width', pct + '%');
                            $('#wdm-progress-text').text(pct + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    $('#wdm-upload-progress').addClass('wdm-hidden');

                    if (response.success && response.data) {
                        // Store file info.
                        $('#wdm-template-file-path').val(response.data.file_path);
                        $('#wdm-uploaded-file-name').text(response.data.file_name);
                        $('#wdm-uploaded-file').removeClass('wdm-hidden');

                        // Display detected merge tags.
                        if (response.data.merge_tags && response.data.merge_tags.length) {
                            self.displayMergeTags(response.data.merge_tags);
                        }
                    } else {
                        var msg = (response.data && response.data.message)
                            ? response.data.message
                            : (wprobo_documerge_vars.i18n.upload_error || 'Upload failed.');
                        self.showDropzoneError(msg);
                    }
                },
                error: function() {
                    $('#wdm-upload-progress').addClass('wdm-hidden');
                    self.showDropzoneError(wprobo_documerge_vars.i18n.network_error || 'Network error. Please try again.');
                }
            });
        },

        /**
         * Display an error message inside the dropzone.
         *
         * @since 1.0.0
         * @param {string} message Error text to display.
         */
        showDropzoneError: function(message) {
            var $dropzone = $('#wdm-dropzone');
            $dropzone.addClass('wdm-dropzone-error');
            $dropzone.find('.wdm-dropzone-error-msg').remove();
            $dropzone.append(
                '<p class="wdm-dropzone-error-msg">' + $('<span>').text(message).html() + '</p>'
            );
        },

        // ─────────────────────────────────────────────────────────
        //  Merge tags display
        // ─────────────────────────────────────────────────────────

        /**
         * Populate and show the detected merge tags section.
         *
         * @since 1.0.0
         * @param {Array} tags Array of merge tag name strings.
         */
        displayMergeTags: function(tags) {
            var $list  = $('#wdm-tag-list');
            var $count = $('#wdm-tag-count');

            $list.empty();

            $.each(tags, function(i, tag) {
                $list.append(
                    '<li><code>' + $('<span>').text('{' + tag + '}').html() + '</code></li>'
                );
            });

            $count.text(
                tags.length + ' ' + (tags.length === 1
                    ? (wprobo_documerge_vars.i18n.tag_singular || 'merge tag detected')
                    : (wprobo_documerge_vars.i18n.tag_plural   || 'merge tags detected')
                )
            );

            $('#wdm-detected-tags').removeClass('wdm-hidden');
        },

        // ─────────────────────────────────────────────────────────
        //  CRUD operations
        // ─────────────────────────────────────────────────────────

        /**
         * Load an existing template via AJAX and open the edit panel.
         *
         * @since 1.0.0
         * @param {number} templateId The template ID to load.
         */
        loadTemplate: function(templateId) {
            var self = this;

            $.ajax({
                url:      wprobo_documerge_vars.ajax_url,
                type:     'POST',
                dataType: 'json',
                data: {
                    action:      'wprobo_documerge_get_template',
                    nonce:       wprobo_documerge_vars.nonce,
                    template_id: templateId
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.openEditPanel(response.data);
                    } else {
                        var msg = (response.data && response.data.message)
                            ? response.data.message
                            : (wprobo_documerge_vars.i18n.error || 'An error occurred.');
                        self.showNotice('error', msg);
                    }
                },
                error: function() {
                    self.showNotice('error', wprobo_documerge_vars.i18n.network_error || 'Network error. Please try again.');
                }
            });
        },

        /**
         * Save (create or update) a template via AJAX.
         *
         * @since 1.0.0
         */
        saveTemplate: function() {
            var self        = this;
            var templateId  = $('#wdm-template-id').val();
            var name        = $.trim($('#wdm-template-name').val());
            var description = $.trim($('#wdm-template-description').val());
            var filePath    = $('#wdm-template-file-path').val();
            var fileName    = $.trim($('#wdm-uploaded-file-name').text());
            var outputFmt   = $('input[name="wdm_output_format"]:checked').val();
            var isNew       = (!templateId || templateId === '0');

            // Validate required fields.
            if (!name) {
                self.showNotice('error', wprobo_documerge_vars.i18n.name_required || 'Template name is required.');
                return;
            }

            if (isNew && !filePath) {
                self.showNotice('error', wprobo_documerge_vars.i18n.file_required || 'Please upload a DOCX file.');
                return;
            }

            // Collect merge tags from the displayed list.
            var mergeTags = [];
            $('#wdm-tag-list li code').each(function() {
                var raw = $(this).text();
                // Strip surrounding braces.
                mergeTags.push(raw.replace(/^\{|\}$/g, ''));
            });

            var $btn = $('#wdm-save-template');

            $.ajax({
                url:      wprobo_documerge_vars.ajax_url,
                type:     'POST',
                dataType: 'json',
                data: {
                    action:        'wprobo_documerge_save_template',
                    nonce:         wprobo_documerge_vars.nonce,
                    id:            templateId,
                    name:          name,
                    description:   description,
                    file_path:     filePath,
                    file_name:     fileName,
                    output_format: outputFmt,
                    merge_tags:    JSON.stringify(mergeTags)
                },
                beforeSend: function() {
                    $btn.prop('disabled', true).addClass('wdm-loading');
                },
                success: function(response) {
                    if (response.success) {
                        self.closePanel();
                        self.showToast(
                            response.data && response.data.message
                                ? response.data.message
                                : (wprobo_documerge_vars.i18n.template_saved || 'Template saved.'),
                            'success'
                        );
                        window.location.reload();
                    } else {
                        self.showNotice('error',
                            response.data && response.data.message
                                ? response.data.message
                                : (wprobo_documerge_vars.i18n.error || 'An error occurred.')
                        );
                    }
                },
                error: function() {
                    self.showNotice('error', wprobo_documerge_vars.i18n.network_error || 'Network error. Please try again.');
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('wdm-loading');
                }
            });
        },

        /**
         * Delete a template after user confirmation.
         *
         * @since 1.0.0
         * @param {number} templateId The template ID to delete.
         * @param {jQuery} $btn       The clicked delete button element.
         */
        deleteTemplate: function(templateId, $btn) {
            var self = this;

            var confirmMsg = wprobo_documerge_vars.i18n.confirm_delete || 'Are you sure you want to delete this template?';
            if (!window.confirm(confirmMsg)) {
                return;
            }

            var $card = $btn.closest('.wdm-template-card');

            $.ajax({
                url:      wprobo_documerge_vars.ajax_url,
                type:     'POST',
                dataType: 'json',
                data: {
                    action:      'wprobo_documerge_delete_template',
                    nonce:       wprobo_documerge_vars.nonce,
                    template_id: templateId
                },
                beforeSend: function() {
                    $btn.prop('disabled', true).addClass('wdm-loading');
                },
                success: function(response) {
                    if (response.success) {
                        $card.fadeOut(300, function() {
                            $(this).remove();

                            // If no cards remain, reload to show empty state.
                            if (!$('.wdm-template-card').length) {
                                window.location.reload();
                            }
                        });
                        self.showToast(
                            response.data && response.data.message
                                ? response.data.message
                                : (wprobo_documerge_vars.i18n.template_deleted || 'Template deleted.'),
                            'success'
                        );
                    } else {
                        self.showNotice('error',
                            response.data && response.data.message
                                ? response.data.message
                                : (wprobo_documerge_vars.i18n.error || 'An error occurred.')
                        );
                    }
                },
                error: function() {
                    self.showNotice('error', wprobo_documerge_vars.i18n.network_error || 'Network error. Please try again.');
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
            // Show inside panel if panel is open, otherwise main page.
            var $panel = $('#wdm-panel-notices');
            var $container = ($panel.length && $panel.is(':visible')) ? $panel : $('#wdm-notices');
            var iconClass  = type === 'success' ? 'dashicons-yes-alt' : 'dashicons-warning';

            var $notice = $(
                '<div class="wdm-notice wdm-notice-' + type + '">' +
                    '<span class="dashicons ' + iconClass + '"></span> ' +
                    '<span class="wdm-notice-message">' + $('<span>').text(message).html() + '</span>' +
                    '<button type="button" class="wdm-notice-dismiss">&times;</button>' +
                '</div>'
            );

            $container.html($notice);

            // Scroll inside panel or page.
            if ($container.attr('id') === 'wdm-panel-notices') {
                $container.closest('.wdm-slide-panel').scrollTop(0);
            } else {
                $('html, body').animate({
                    scrollTop: $container.offset().top - 50
                }, 300);
            }

            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);

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

            setTimeout(function() {
                $toast.removeClass('wdm-toast-visible');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, 3000);
        }
    };

    $(document).ready(function() {
        WPRoboDocuMerge_TemplateManager.init();
    });

})(jQuery);
