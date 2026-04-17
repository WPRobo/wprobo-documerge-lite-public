/**
 * WPRobo DocuMerge — Submissions Page
 *
 * Handles submission listing, filtering, pagination,
 * detail panel, bulk actions, and CSV export.
 *
 * @package WPRobo_DocuMerge
 * @since   1.0.0
 */

(function($) {
    'use strict';

    /**
     * WPRobo DocuMerge Submissions
     *
     * @since 1.0.0
     */
    var WPRoboDocuMerge_Submissions = {

        /**
         * Current page number for pagination.
         *
         * @since 1.0.0
         * @type  {number}
         */
        currentPage: 1,

        /**
         * Initialize the submissions module.
         *
         * @since 1.0.0
         */
        init: function() {
            // Only load via AJAX if the old custom table exists (not WP_List_Table).
            if ( $('#wdm-submissions-tbody').length ) {
                this.loadSubmissions();
            }
            this.bindEvents();
        },

        /**
         * Bind all event handlers.
         *
         * @since 1.0.0
         */
        bindEvents: function() {
            var self = this;

            // Filter submissions.
            $(document).on('click', '#wdm-filter-btn', function(e) {
                e.preventDefault();
                self.filterSubmissions();
            });

            // Pagination.
            $(document).on('click', '.wdm-pagination-btn', function(e) {
                e.preventDefault();
                self.changePage($(this));
            });

            // Open submission detail — old AJAX table rows.
            $(document).on('click', '#wdm-submissions-tbody tr', function() {
                self.openDetail($(this));
            });

            // Open submission detail — WP_List_Table links.
            $(document).on('click', '.wdm-view-submission', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                if ( id ) {
                    self.openDetailById(id);
                }
            });

            // Close detail panel.
            $(document).on('click', '#wdm-detail-close, #wdm-overlay', function() {
                self.closeDetail();
            });

            // Bulk delete.
            $(document).on('click', '#wdm-bulk-delete', function(e) {
                e.preventDefault();
                self.bulkDelete();
            });

            // Export CSV.
            $(document).on('click', '#wdm-export-csv', function(e) {
                e.preventDefault();
                self.exportCSV();
            });

            // Select all checkboxes.
            $(document).on('change', '#wdm-select-all', function() {
                self.toggleSelectAll($(this));
            });

            // Delete from detail panel.
            $(document).on('click', '#wdm-detail-delete', function(e) {
                e.preventDefault();
                self.deleteFromDetail();
            });

            // Prevent detail panel opening when clicking download links.
            $(document).on('click', '.wdm-doc-download', function(e) {
                e.stopPropagation();
            });

            // Delete from single submission view page.
            $(document).on('click', '.wdm-delete-submission-single', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                if ( ! id || ! confirm( wprobo_documerge_vars.i18n.confirm_delete || 'Delete this submission? This cannot be undone.' ) ) {
                    return;
                }

                $.ajax({
                    url:  wprobo_documerge_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wprobo_documerge_delete_submission',
                        nonce:  wprobo_documerge_vars.nonce,
                        ids:    [id]
                    },
                    success: function(response) {
                        if ( response.success ) {
                            window.location.href = wprobo_documerge_vars.admin_url || ( wprobo_documerge_vars.ajax_url.replace( 'admin-ajax.php', 'admin.php' ) + '?page=wprobo-documerge-submissions' );
                        }
                    }
                });
            });

            // Save admin note on a submission.
            $(document).on('click', '.wdm-save-note', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var id   = $btn.data('id');
                var note = $('#wdm-admin-note-' + id).val();

                $btn.prop('disabled', true).text(wprobo_documerge_vars.i18n.saving || 'Saving...');

                $.ajax({
                    url:  wprobo_documerge_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wprobo_documerge_save_submission_note',
                        nonce:  wprobo_documerge_vars.nonce,
                        id:     id,
                        note:   note
                    },
                    success: function(response) {
                        $btn.prop('disabled', false);
                        if ( response.success ) {
                            $btn.text(wprobo_documerge_vars.i18n.saved || 'Saved!');
                            setTimeout(function() {
                                $btn.text((wprobo_documerge_vars.i18n.save_note || 'Save Note'));
                            }, 2000);
                        } else {
                            $btn.text((wprobo_documerge_vars.i18n.save_note || 'Save Note'));
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).text((wprobo_documerge_vars.i18n.save_note || 'Save Note'));
                    }
                });
            });
        },

        /**
         * Load submissions via AJAX.
         *
         * @since 1.0.0
         * @param {number} [page=1] The page number to load.
         */
        loadSubmissions: function(page) {
            var self = this;

            self.currentPage = page || 1;

            var data = {
                action:    'wprobo_documerge_get_submissions',
                nonce:     wprobo_documerge_vars.nonce,
                page:      self.currentPage,
                form_id:   $('#wdm-filter-form').val(),
                status:    $('#wdm-filter-status').val(),
                date_from: $('#wdm-filter-from').val(),
                date_to:   $('#wdm-filter-to').val()
            };

            $.ajax({
                url:      wprobo_documerge_vars.ajax_url,
                type:     'POST',
                dataType: 'json',
                data:     data,
                beforeSend: function() {
                    $('#wdm-submissions-tbody').html(
                        '<tr><td colspan="7" class="wdm-loading-row">' +
                            '<span class="spinner is-active"></span>' +
                        '</td></tr>'
                    );
                },
                success: function(response) {
                    if ( response.success ) {
                        self.renderTable(response.data.submissions);
                        self.renderPagination(response.data);
                    } else {
                        self.showNotice('error', response.data && response.data.message
                            ? response.data.message
                            : wprobo_documerge_vars.i18n.error
                        );
                    }
                },
                error: function() {
                    self.showNotice('error', wprobo_documerge_vars.i18n.network_error);
                }
            });
        },

        /**
         * Render the submissions table body.
         *
         * @since 1.0.0
         * @param {Array} submissions Array of submission objects.
         */
        renderTable: function(submissions) {
            var $tbody = $('#wdm-submissions-tbody');

            $tbody.empty();

            if ( ! submissions || submissions.length === 0 ) {
                $tbody.html(
                    '<tr><td colspan="7" class="wdm-empty-row">' +
                        wprobo_documerge_vars.i18n.no_submissions +
                    '</td></tr>'
                );
                return;
            }

            var statusClasses = {
                completed:       'success',
                processing:      'info',
                pending_payment: 'pending',
                error:           'error',
                payment_failed:  'error'
            };

            var self = this;

            $.each(submissions, function(i, sub) {
                var statusClass = statusClasses[sub.status] || 'info';
                var paymentHtml = self.buildPaymentLabel(sub);
                var docsHtml    = self.buildDocButtons(sub);

                var row =
                    '<tr data-id="' + sub.id + '">' +
                        '<td><input type="checkbox" class="wdm-row-check" value="' + sub.id + '"></td>' +
                        '<td>' + $('<span>').text(sub.date_formatted).html() + '</td>' +
                        '<td>' + $('<span>').text(sub.form_title).html() + '</td>' +
                        '<td>' + $('<span>').text(sub.submitter_email).html() + '</td>' +
                        '<td><span class="wdm-badge wdm-badge-' + statusClass + '">' +
                            $('<span>').text(sub.status_label).html() +
                        '</span></td>' +
                        '<td>' + paymentHtml + '</td>' +
                        '<td>' + docsHtml + '</td>' +
                    '</tr>';

                $tbody.append(row);
            });
        },

        /**
         * Build the payment label HTML for a submission.
         *
         * @since 1.0.0
         * @param  {Object} sub Submission object.
         * @return {string} HTML string.
         */
        buildPaymentLabel: function(sub) {
            var paymentStatus = sub.payment_status || 'none';
            var label  = '';
            var cls    = '';

            switch ( paymentStatus ) {
                case 'paid':
                    label = (wprobo_documerge_vars.i18n.paid || 'Paid') + ' ' + $('<span>').text(sub.currency_symbol || '').html() + $('<span>').text(sub.payment_amount || '').html();
                    cls   = 'success';
                    break;
                case 'none':
                case 'free':
                    label = wprobo_documerge_vars.i18n.status_free || 'Free';
                    cls   = 'info';
                    break;
                case 'pending':
                    label = wprobo_documerge_vars.i18n.status_pending || 'Pending';
                    cls   = 'pending';
                    break;
                case 'failed':
                    label = wprobo_documerge_vars.i18n.status_failed || 'Failed';
                    cls   = 'error';
                    break;
                case 'refunded':
                    label = wprobo_documerge_vars.i18n.status_refunded || 'Refunded';
                    cls   = 'pending';
                    break;
                default:
                    label = $('<span>').text(paymentStatus).html();
                    cls   = 'info';
                    break;
            }

            return '<span class="wdm-badge wdm-badge-' + cls + '">' + label + '</span>';
        },

        /**
         * Build document download buttons for a submission.
         *
         * @since 1.0.0
         * @param  {Object} sub Submission object.
         * @return {string} HTML string.
         */
        buildDocButtons: function(sub) {
            var buttons = '';

            if ( sub.doc_path_pdf ) {
                buttons += '<a class="wdm-btn wdm-btn-sm wdm-doc-download" href="' +
                    wprobo_documerge_vars.ajax_url +
                    '?action=wprobo_documerge_download_document&submission_id=' + sub.id +
                    '&format=pdf&nonce=' + encodeURIComponent(wprobo_documerge_vars.nonce) +
                    '" target="_blank">PDF</a> ';
            }

            if ( sub.doc_path_docx ) {
                buttons += '<a class="wdm-btn wdm-btn-sm wdm-doc-download" href="' +
                    wprobo_documerge_vars.ajax_url +
                    '?action=wprobo_documerge_download_document&submission_id=' + sub.id +
                    '&format=docx&nonce=' + encodeURIComponent(wprobo_documerge_vars.nonce) +
                    '" target="_blank">DOCX</a> ';
            }

            return buttons || '&mdash;';
        },

        /**
         * Render pagination controls.
         *
         * @since 1.0.0
         * @param {Object} data Response data with total, per_page, current_page.
         */
        renderPagination: function(data) {
            var $container = $('#wdm-pagination');
            var total      = parseInt(data.total, 10) || 0;
            var perPage    = parseInt(data.per_page, 10) || 20;
            var current    = parseInt(data.current_page, 10) || 1;
            var totalPages = Math.ceil(total / perPage);

            $container.empty();

            if ( total === 0 ) {
                return;
            }

            var startItem = ((current - 1) * perPage) + 1;
            var endItem   = Math.min(current * perPage, total);

            var html = '<span class="wdm-pagination-info">' + (wprobo_documerge_vars.i18n.showing || 'Showing') + ' ' + startItem + '&ndash;' + endItem + ' ' + (wprobo_documerge_vars.i18n.of || 'of') + ' ' + total + '</span>';

            html += '<span class="wdm-pagination-buttons">';

            // Previous button.
            if ( current > 1 ) {
                html += '<button type="button" class="wdm-btn wdm-btn-sm wdm-pagination-btn" data-page="' + (current - 1) + '">&laquo; ' + (wprobo_documerge_vars.i18n.prev || 'Prev') + '</button>';
            }

            // Numbered page buttons.
            for ( var p = 1; p <= totalPages; p++ ) {
                if ( p === current ) {
                    html += '<button type="button" class="wdm-btn wdm-btn-sm wdm-pagination-btn wdm-btn-primary" data-page="' + p + '">' + p + '</button>';
                } else {
                    html += '<button type="button" class="wdm-btn wdm-btn-sm wdm-pagination-btn" data-page="' + p + '">' + p + '</button>';
                }
            }

            // Next button.
            if ( current < totalPages ) {
                html += '<button type="button" class="wdm-btn wdm-btn-sm wdm-pagination-btn" data-page="' + (current + 1) + '">' + (wprobo_documerge_vars.i18n.next || 'Next') + ' &raquo;</button>';
            }

            html += '</span>';

            $container.html(html);
        },

        /**
         * Filter submissions — reset to page 1 and reload.
         *
         * @since 1.0.0
         */
        filterSubmissions: function() {
            this.loadSubmissions(1);
        },

        /**
         * Handle pagination button click.
         *
         * @since 1.0.0
         * @param {jQuery} $btn The clicked pagination button.
         */
        changePage: function($btn) {
            var page = parseInt($btn.data('page'), 10);

            if ( page && page > 0 ) {
                this.loadSubmissions(page);
            }
        },

        /**
         * Open the detail panel for a submission.
         *
         * @since 1.0.0
         * @param {jQuery} $row The clicked table row.
         */
        /**
         * Open detail panel by submission ID (for WP_List_Table).
         *
         * @since 1.4.0
         * @param {number} id The submission ID.
         */
        openDetailById: function(id) {
            var self = this;

            if ( ! id ) { return; }

            $.ajax({
                url:      wprobo_documerge_vars.ajax_url,
                type:     'POST',
                dataType: 'json',
                data: {
                    action: 'wprobo_documerge_get_submission_detail',
                    nonce:  wprobo_documerge_vars.nonce,
                    id:     id
                },
                beforeSend: function() {
                    $('#wdm-detail-body').html('<div class="wdm-loading-panel"><span class="spinner is-active"></span></div>');
                    $('#wdm-detail-title').text((wprobo_documerge_vars.i18n.submission_prefix || 'Submission #') + id);
                    $('#wdm-submission-panel').addClass('wdm-panel-open').data('submission-id', id);
                    $('#wdm-overlay').show();
                },
                success: function(response) {
                    if ( response.success ) {
                        $('#wdm-detail-body').html(response.data.html);
                    } else {
                        $('#wdm-detail-body').html(
                            '<p class="wdm-error">' +
                                (response.data && response.data.message ? response.data.message : (wprobo_documerge_vars.i18n.error_loading_submission || 'Error loading submission.')) +
                            '</p>'
                        );
                    }
                },
                error: function() {
                    self.closeDetail();
                }
            });
        },

        openDetail: function($row) {
            var self = this;
            var id   = $row.data('id');

            if ( ! id ) {
                return;
            }

            $.ajax({
                url:      wprobo_documerge_vars.ajax_url,
                type:     'POST',
                dataType: 'json',
                data: {
                    action: 'wprobo_documerge_get_submission_detail',
                    nonce:  wprobo_documerge_vars.nonce,
                    id:     id
                },
                beforeSend: function() {
                    $('#wdm-detail-body').html('<div class="wdm-loading-panel"><span class="spinner is-active"></span></div>');
                    $('#wdm-detail-title').text((wprobo_documerge_vars.i18n.submission_prefix || 'Submission #') + id);
                    $('#wdm-submission-panel').addClass('wdm-panel-open').data('submission-id', id);
                    $('#wdm-overlay').show();
                },
                success: function(response) {
                    if ( response.success ) {
                        $('#wdm-detail-body').html(response.data.html);
                    } else {
                        $('#wdm-detail-body').html(
                            '<p class="wdm-error">' +
                                $('<span>').text(response.data && response.data.message ? response.data.message : wprobo_documerge_vars.i18n.error).html() +
                            '</p>'
                        );
                    }
                },
                error: function() {
                    self.showNotice('error', wprobo_documerge_vars.i18n.network_error);
                    self.closeDetail();
                }
            });
        },

        /**
         * Close the detail panel.
         *
         * @since 1.0.0
         */
        closeDetail: function() {
            $('#wdm-submission-panel').removeClass('wdm-panel-open');
            $('#wdm-overlay').hide();
        },

        /**
         * Bulk-delete selected submissions.
         *
         * @since 1.0.0
         */
        bulkDelete: function() {
            var self = this;
            var ids  = [];

            $('#wdm-submissions-tbody .wdm-row-check:checked').each(function() {
                ids.push($(this).val());
            });

            if ( ids.length === 0 ) {
                self.showNotice('error', wprobo_documerge_vars.i18n.select_submissions_delete || 'Select submissions to delete');
                return;
            }

            if ( ! confirm((wprobo_documerge_vars.i18n.delete_selected_confirm || 'Delete %d selected submissions?').replace('%d', ids.length)) ) {
                return;
            }

            $.ajax({
                url:      wprobo_documerge_vars.ajax_url,
                type:     'POST',
                dataType: 'json',
                data: {
                    action: 'wprobo_documerge_delete_submission',
                    nonce:  wprobo_documerge_vars.nonce,
                    ids:    ids
                },
                success: function(response) {
                    if ( response.success ) {
                        self.showNotice('success', response.data && response.data.message
                            ? response.data.message
                            : wprobo_documerge_vars.i18n.deleted
                        );
                        self.loadSubmissions(self.currentPage);
                    } else {
                        self.showNotice('error', response.data && response.data.message
                            ? response.data.message
                            : wprobo_documerge_vars.i18n.error
                        );
                    }
                },
                error: function() {
                    self.showNotice('error', wprobo_documerge_vars.i18n.network_error);
                }
            });
        },

        /**
         * Delete a submission from the detail panel.
         *
         * @since 1.0.0
         */
        deleteFromDetail: function() {
            var self = this;
            var id   = $('#wdm-submission-panel').data('submission-id');

            if ( ! id ) {
                return;
            }

            if ( ! confirm(wprobo_documerge_vars.i18n.delete_this_submission || 'Delete this submission?') ) {
                return;
            }

            $.ajax({
                url:      wprobo_documerge_vars.ajax_url,
                type:     'POST',
                dataType: 'json',
                data: {
                    action: 'wprobo_documerge_delete_submission',
                    nonce:  wprobo_documerge_vars.nonce,
                    ids:    [id]
                },
                success: function(response) {
                    if ( response.success ) {
                        self.showNotice('success', response.data && response.data.message
                            ? response.data.message
                            : wprobo_documerge_vars.i18n.deleted
                        );
                        self.closeDetail();
                        self.loadSubmissions(self.currentPage);
                    } else {
                        self.showNotice('error', response.data && response.data.message
                            ? response.data.message
                            : wprobo_documerge_vars.i18n.error
                        );
                    }
                },
                error: function() {
                    self.showNotice('error', wprobo_documerge_vars.i18n.network_error);
                }
            });
        },

        /**
         * Export submissions as CSV using current filters.
         *
         * @since 1.0.0
         */
        exportCSV: function() {
            var params = {
                action:    'wprobo_documerge_export_submissions',
                nonce:     wprobo_documerge_vars.nonce,
                form_id:   $('#wdm-filter-form').val(),
                status:    $('#wdm-filter-status').val(),
                date_from: $('#wdm-filter-from').val(),
                date_to:   $('#wdm-filter-to').val()
            };

            var queryString = $.param(params);
            window.location.href = wprobo_documerge_vars.ajax_url + '?' + queryString;
        },

        /**
         * Toggle all row checkboxes.
         *
         * @since 1.0.0
         * @param {jQuery} $checkbox The select-all checkbox.
         */
        toggleSelectAll: function($checkbox) {
            var checked = $checkbox.is(':checked');

            $('#wdm-submissions-tbody .wdm-row-check').prop('checked', checked);
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
        WPRoboDocuMerge_Submissions.init();
    });

})(jQuery);
