/**
 * WPRobo DocuMerge Lite — Admin Common Utilities
 *
 * Loaded on all DocuMerge admin pages.
 * Handles notices, toasts, dismiss actions, and shared UI helpers.
 *
 * @package WPRobo_DocuMerge
 * @since   1.0.0
 */

(function($) {
    'use strict';

    var WPRoboDocuMerge_Admin = {

        /**
         * Initialize.
         *
         * @since 1.0.0
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind event handlers.
         *
         * @since 1.0.0
         */
        bindEvents: function() {
            var self = this;

            // Dismiss notices.
            $(document).on('click', '.wdm-notice-dismiss', function(e) {
                e.preventDefault();
                $(this).closest('.wdm-notice').fadeOut(300, function() {
                    $(this).remove();
                });
            });

            // Dismiss Lite upgrade banner.
            $(document).on('click', '.wdm-lite-upgrade-banner .notice-dismiss, .wdm-lite-upgrade-banner .wdm-dismiss', function(e) {
                e.preventDefault();
                $(this).closest('.wdm-lite-upgrade-banner').fadeOut(300);
                $.post(wprobo_documerge_vars.ajax_url, {
                    action: 'wprobo_documerge_dismiss_lite_notice',
                    nonce:  wprobo_documerge_vars.nonce
                });
            });

            // Copy to clipboard.
            $(document).on('click', '.wdm-copy-btn, [data-copy]', function(e) {
                e.preventDefault();
                var text = $(this).data('copy') || $(this).prev('input, textarea').val() || '';
                if (text) {
                    self.copyToClipboard(text);
                    self.showToast(wprobo_documerge_vars.i18n.copied || 'Copied!', 'success');
                }
            });
        },

        /**
         * Show a page-level notice.
         *
         * @since 1.0.0
         * @param {string} type    Notice type: success, error, warning.
         * @param {string} message Notice text.
         */
        showNotice: function(type, message) {
            var icons = {
                success: 'dashicons-yes-alt',
                error:   'dashicons-warning',
                warning: 'dashicons-info'
            };
            var $notice = $(
                '<div class="wdm-notice wdm-notice-' + type + '" role="alert">' +
                    '<span class="wdm-notice-icon dashicons ' + (icons[type] || icons.success) + '"></span>' +
                    '<span class="wdm-notice-text">' + $('<div>').text(message).html() + '</span>' +
                    '<button class="wdm-notice-dismiss" aria-label="' + (wprobo_documerge_vars.i18n.dismiss || 'Dismiss') + '">&times;</button>' +
                '</div>'
            );
            $('#wdm-notices').html($notice);
            $('html, body').animate({ scrollTop: $('#wdm-notices').offset().top - 60 }, 300);
            setTimeout(function() {
                $notice.fadeOut(400, function() { $(this).remove(); });
            }, 5000);
        },

        /**
         * Show a toast notification (bottom right).
         *
         * @since 1.0.0
         * @param {string} message Toast text.
         * @param {string} type    Toast type: success, error, info.
         */
        showToast: function(message, type) {
            var $toast = $(
                '<div class="wdm-toast wdm-toast-' + (type || 'success') + '">' +
                    $('<span>').text(message).html() +
                '</div>'
            );
            $('body').append($toast);
            setTimeout(function() { $toast.addClass('wdm-toast-visible'); }, 10);
            setTimeout(function() {
                $toast.removeClass('wdm-toast-visible');
                setTimeout(function() { $toast.remove(); }, 300);
            }, 3000);
        },

        /**
         * Copy text to clipboard.
         *
         * @since 1.0.0
         * @param {string} text Text to copy.
         */
        copyToClipboard: function(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text);
            } else {
                var $temp = $('<textarea>').val(text).appendTo('body').select();
                document.execCommand('copy');
                $temp.remove();
            }
        }
    };

    $(document).ready(function() {
        WPRoboDocuMerge_Admin.init();
    });

})(jQuery);
