/**
 * WPRobo DocuMerge — Auto-Save Draft to localStorage
 *
 * Saves form progress automatically so users don't lose data
 * on page refresh. Restores draft on page load with a dismiss/clear notice.
 *
 * @package WPRobo_DocuMerge
 * @since   1.3.0
 */

(function($) {
    'use strict';

    /**
     * WPRobo DocuMerge Autosave Handler
     *
     * @since 1.3.0
     */
    var WPRoboDocuMerge_Autosave = {

        /**
         * localStorage key prefix.
         *
         * @since 1.3.0
         * @type {string}
         */
        storagePrefix: 'wdm_draft_',

        /**
         * Initialize autosave for all forms on the page.
         *
         * @since 1.3.0
         */
        init: function() {
            var self = this;
            $('.wdm-form').each(function() {
                var $form = $(this);
                var formId = $form.closest('.wdm-form-wrap').data('form-id');
                if (!formId) {
                    return;
                }

                self.restoreDraft($form, formId);
                self.bindAutoSave($form, formId);
            });
        },

        /**
         * Get the localStorage key for a given form ID.
         *
         * @since 1.3.0
         * @param {number|string} formId The form ID.
         * @return {string}
         */
        getKey: function(formId) {
            return this.storagePrefix + formId;
        },

        /**
         * Bind autosave events to a form.
         *
         * @since 1.3.0
         * @param {jQuery} $form  The form element.
         * @param {number} formId The form ID.
         */
        bindAutoSave: function($form, formId) {
            var self = this;
            var key = self.getKey(formId);
            var saveTimeout;

            // Save on every input change, debounced 1 second.
            $form.on('input change', 'input, select, textarea', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(function() {
                    self.saveDraft($form, key);
                }, 1000);
            });

            // Clear draft on successful submission — also set a sessionStorage flag
            // so browser back/forward doesn't re-show the notice.
            $(document).on('wdm_form_submitted', function(e, submittedFormId) {
                if (parseInt(submittedFormId, 10) === parseInt(formId, 10)) {
                    try {
                        localStorage.removeItem(key);
                        sessionStorage.setItem('wdm_submitted_' + formId, '1');
                    } catch (ex) {
                        // Silently fail.
                    }
                    // Remove notice from DOM immediately.
                    $form.closest('.wdm-form-wrap').find('.wdm-draft-notice').remove();
                }
            });
        },

        /**
         * Save current form state to localStorage.
         *
         * @since 1.3.0
         * @param {jQuery} $form The form element.
         * @param {string} key   The localStorage key.
         */
        saveDraft: function($form, key) {
            var data = {};
            $form.find('input, select, textarea').each(function() {
                var $el = $(this);
                var name = $el.attr('name');
                if (!name || name === 'action' || name === 'nonce' || name === 'form_id' || name === 'wdm_trap') {
                    return;
                }
                if ($el.attr('type') === 'file') {
                    return;
                }
                if ($el.attr('type') === 'hidden') {
                    return;
                }

                if ($el.attr('type') === 'checkbox') {
                    data[name] = $el.is(':checked') ? $el.val() : '';
                } else if ($el.attr('type') === 'radio') {
                    if ($el.is(':checked')) {
                        data[name] = $el.val();
                    }
                } else {
                    data[name] = $el.val();
                }
            });

            try {
                localStorage.setItem(key, JSON.stringify(data));
            } catch (e) {
                // localStorage full or unavailable — silently fail.
            }
        },

        /**
         * Restore a saved draft and show a notice.
         *
         * @since 1.3.0
         * @param {jQuery} $form  The form element.
         * @param {number} formId The form ID.
         */
        restoreDraft: function($form, formId) {
            var key = this.getKey(formId);

            // Don't restore if this form was just submitted (handles browser back/forward).
            try {
                if (sessionStorage.getItem('wdm_submitted_' + formId)) {
                    localStorage.removeItem(key);
                    return;
                }
            } catch (ex) {
                // sessionStorage unavailable.
            }

            var raw;

            try {
                raw = localStorage.getItem(key);
            } catch (e) {
                return;
            }

            if (!raw) {
                return;
            }

            var data;
            try {
                data = JSON.parse(raw);
            } catch (e) {
                return;
            }

            if (!data || typeof data !== 'object') {
                return;
            }

            // Check if draft has any actual values.
            var hasValues = false;
            $.each(data, function(name, val) {
                if (val && val.trim && val.trim()) {
                    hasValues = true;
                    return false;
                }
            });

            if (!hasValues) {
                return;
            }

            // Show a restore notice.
            var $wrap = $form.closest('.wdm-form-wrap');
            var $notice = $('<div class="wdm-draft-notice">' +
                '<span class="dashicons dashicons-backup"></span> ' +
                '<span>' + WPRoboDocuMerge_Autosave.getI18nString('draft_restored', 'We restored your previous progress.') + '</span> ' +
                '<button type="button" class="wdm-draft-dismiss">' + WPRoboDocuMerge_Autosave.getI18nString('dismiss', 'Dismiss') + '</button> ' +
                '<button type="button" class="wdm-draft-clear">' + WPRoboDocuMerge_Autosave.getI18nString('start_fresh', 'Start Fresh') + '</button>' +
            '</div>');

            $wrap.prepend($notice);

            $notice.on('click', '.wdm-draft-dismiss', function() {
                $notice.slideUp(200, function() { $(this).remove(); });
            });

            $notice.on('click', '.wdm-draft-clear', function() {
                try {
                    localStorage.removeItem(key);
                    sessionStorage.removeItem('wdm_submitted_' + formId);
                } catch (ex) {
                    // Silently fail.
                }
                $form.find('input, select, textarea').each(function() {
                    var $el = $(this);
                    if ($el.attr('type') === 'hidden' || $el.attr('type') === 'file') {
                        return;
                    }
                    if ($el.attr('type') === 'checkbox' || $el.attr('type') === 'radio') {
                        $el.prop('checked', false);
                    } else {
                        $el.val('');
                    }
                });
                $notice.slideUp(200, function() { $(this).remove(); });
            });

            // Restore values.
            $.each(data, function(name, val) {
                var $el = $form.find('[name="' + name + '"]');
                if (!$el.length || !val) {
                    return;
                }

                if ($el.attr('type') === 'checkbox') {
                    $el.prop('checked', val === $el.val());
                } else if ($el.attr('type') === 'radio') {
                    $form.find('[name="' + name + '"][value="' + val + '"]').prop('checked', true);
                } else {
                    $el.val(val);
                }
            });
        },

        /**
         * Get an i18n string from localized vars, with fallback.
         *
         * @since 1.3.0
         * @param {string} key      The i18n key.
         * @param {string} fallback Fallback string.
         * @return {string}
         */
        getI18nString: function(key, fallback) {
            if (typeof wprobo_documerge_frontend_vars !== 'undefined' &&
                wprobo_documerge_frontend_vars.i18n &&
                wprobo_documerge_frontend_vars.i18n[key]) {
                return wprobo_documerge_frontend_vars.i18n[key];
            }
            return fallback;
        }
    };

    $(document).ready(function() {
        WPRoboDocuMerge_Autosave.init();
    });

    window.WPRoboDocuMerge_Autosave = WPRoboDocuMerge_Autosave;

})(jQuery);
