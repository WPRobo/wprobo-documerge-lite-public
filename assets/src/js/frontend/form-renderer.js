(function($) {
    'use strict';

    // ── Validator ────────────────────────────────────────────
    var WPRoboDocuMerge_Validator = {
        init: function() {
            this.bindBlurValidation();
            this.bindFocusClear();
        },

        bindBlurValidation: function() {
            $(document).on('blur', '.wdm-form input, .wdm-form select, .wdm-form textarea', function() {
                WPRoboDocuMerge_Validator.validateField($(this));
            });
        },

        bindFocusClear: function() {
            $(document).on('focus', '.wdm-form input, .wdm-form select, .wdm-form textarea', function() {
                WPRoboDocuMerge_Validator.clearFieldError($(this));
            });
        },

        validateField: function($field) {
            // Skip honeypot
            if ($field.attr('name') === 'wdm_trap') {
                return true;
            }

            // Skip fields that are display:none
            if ($field.closest('.wdm-field-group').is(':hidden')) {
                return true;
            }

            var $outerWrap   = $field.closest('[data-field-id]');
            var value        = $field.val() || '';
            var type         = $field.closest('.wdm-field-group').data('field-type') || $field.attr('type') || 'text';
            var required     = $field.prop('required') || $field.data('required');
            var customError  = $outerWrap.length ? $outerWrap.data('error-message') : '';
            var error        = '';

            // Required check
            if (required && !value.trim()) {
                error = customError || 'This field is required.';
            }

            // Format checks (only if has value)
            if (!error && value.trim()) {
                switch (type) {
                    case 'email':
                        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                            error = customError || 'Please enter a valid email address.';
                        }
                        break;
                    case 'phone':
                        // Use intl-tel-input validation if available.
                        if (typeof WPRoboDocuMerge_Phone !== 'undefined' && $field.hasClass('wdm-intl-phone')) {
                            if (!WPRoboDocuMerge_Phone.isValid($field[0])) {
                                error = customError || WPRoboDocuMerge_Phone.getError($field[0]);
                            }
                        } else {
                            // Fallback: basic regex.
                            if (!/^[+\d\s\-().]{4,}$/.test(value)) {
                                error = customError || 'Please enter a valid phone number.';
                            }
                        }
                        break;
                    case 'number':
                        if (isNaN(parseFloat(value)) || !isFinite(value)) {
                            error = customError || 'Please enter a valid number.';
                        }
                        break;
                    case 'url':
                        // Must start with http:// or https://
                        if (!/^https?:\/\/.+\..+/.test(value)) {
                            error = customError || 'Please enter a valid URL (e.g. https://example.com).';
                        }
                        break;
                    case 'date':
                        // Use format-aware parsing — Date.parse fails for d/m/Y etc.
                        var dateFmt = $outerWrap.data('date-format') || 'Y-m-d';
                        var parsed  = WPRoboDocuMerge_Validator.wprdmParseDate(value, dateFmt);
                        if (value && !parsed) {
                            error = customError || 'Please enter a valid date.';
                        }
                        // Min date check
                        var minDt = $outerWrap.data('min-date') || '';
                        if (!error && value && parsed && minDt) {
                            var minObj = new Date(minDt + 'T00:00:00');
                            if (parsed < minObj) {
                                error = customError || 'Date must be on or after ' + minDt + '.';
                            }
                        }
                        // Max date check
                        var maxDt = $outerWrap.data('max-date') || '';
                        if (!error && value && parsed && maxDt) {
                            var maxObj = new Date(maxDt + 'T00:00:00');
                            if (parsed > maxObj) {
                                error = customError || 'Date must be on or before ' + maxDt + '.';
                            }
                        }
                        break;
                    case 'checkbox':
                        var $checkboxes = $field.closest('.wdm-field-group').find('input[type="checkbox"]:checked');
                        var checkedCount = $checkboxes.length;
                        var minSel = parseInt($outerWrap.data('min-selections'), 10);
                        var maxSel = parseInt($outerWrap.data('max-selections'), 10);
                        if (required && checkedCount === 0) {
                            error = customError || 'Please select at least one option.';
                        }
                        if (!error && minSel && checkedCount < minSel) {
                            error = customError || 'Please select at least ' + minSel + ' option(s).';
                        }
                        if (!error && maxSel && checkedCount > maxSel) {
                            error = customError || 'Please select at most ' + maxSel + ' option(s).';
                        }
                        break;
                }
            }

            // Min/Max length checks (text, textarea)
            if (!error && value.trim() && $outerWrap.length) {
                var minLen = parseInt($outerWrap.data('min-length'), 10);
                var maxLen = parseInt($outerWrap.data('max-length'), 10);

                if (minLen && value.trim().length < minLen) {
                    error = customError || 'Minimum ' + minLen + ' characters required.';
                }
                if (!error && maxLen && value.trim().length > maxLen) {
                    error = customError || 'Maximum ' + maxLen + ' characters allowed.';
                }
            }

            // Min/Max value checks (number)
            if (!error && value.trim() && type === 'number' && $outerWrap.length) {
                var numVal  = parseFloat(value);
                var minVal  = $outerWrap.data('min-value');
                var maxVal  = $outerWrap.data('max-value');

                if (minVal !== undefined && minVal !== '' && numVal < parseFloat(minVal)) {
                    error = customError || 'Value must be at least ' + minVal + '.';
                }
                if (!error && maxVal !== undefined && maxVal !== '' && numVal > parseFloat(maxVal)) {
                    error = customError || 'Value must be at most ' + maxVal + '.';
                }
            }

            if (error) {
                this.showFieldError($field, error);
                return false;
            }
            this.clearFieldError($field);
            return true;
        },

        validateAll: function($form) {
            var valid = true;
            var self  = this;
            $form.find('.wdm-field-group:visible').each(function() {
                var $input = $(this).find('input, select, textarea').not('[type="hidden"]').first();
                if ($input.length && !self.validateField($input)) {
                    valid = false;
                }
            });
            return valid;
        },

        showFieldError: function($field, message) {
            var $group = $field.closest('.wdm-field-group');
            $group.addClass('wdm-field-has-error');
            if (!$group.find('.wdm-field-error-msg').length) {
                $group.append('<span class="wdm-field-error-msg" role="alert">' + $('<span>').text(message).html() + '</span>');
            }
        },

        /**
         * Parse a date string using a PHP-style format.
         * Returns a Date object or null if invalid.
         *
         * @param {string} value  The date string entered by user.
         * @param {string} format The PHP date format (Y-m-d, d/m/Y, etc.).
         * @return {Date|null}
         */
        wprdmParseDate: function(value, format) {
            if (!value) { return null; }
            var y, m, d, parts;

            switch (format) {
                case 'Y-m-d':
                    parts = value.split('-');
                    if (parts.length === 3) { y = parts[0]; m = parts[1]; d = parts[2]; }
                    break;
                case 'd/m/Y':
                    parts = value.split('/');
                    if (parts.length === 3) { d = parts[0]; m = parts[1]; y = parts[2]; }
                    break;
                case 'm/d/Y':
                    parts = value.split('/');
                    if (parts.length === 3) { m = parts[0]; d = parts[1]; y = parts[2]; }
                    break;
                case 'd-m-Y':
                    parts = value.split('-');
                    if (parts.length === 3) { d = parts[0]; m = parts[1]; y = parts[2]; }
                    break;
                case 'd.m.Y':
                    parts = value.split('.');
                    if (parts.length === 3) { d = parts[0]; m = parts[1]; y = parts[2]; }
                    break;
                default:
                    // F j, Y / M j, Y / j F Y — try native parsing as fallback.
                    var fallback = new Date(value);
                    return isNaN(fallback.getTime()) ? null : fallback;
            }

            if (y && m && d) {
                var yi = parseInt(y, 10);
                var mi = parseInt(m, 10);
                var di = parseInt(d, 10);
                if (mi < 1 || mi > 12 || di < 1 || di > 31 || yi < 1900) { return null; }
                var dateObj = new Date(yi, mi - 1, di);
                // Verify the date is valid (e.g. Feb 30 would roll over to Mar).
                if (dateObj.getFullYear() !== yi || dateObj.getMonth() !== mi - 1 || dateObj.getDate() !== di) {
                    return null;
                }
                return dateObj;
            }
            return null;
        },

        clearFieldError: function($field) {
            var $group = $field.closest('.wdm-field-group');
            $group.removeClass('wdm-field-has-error');
            $group.find('.wdm-field-error-msg').remove();
        }
    };

    // ── Form Controller ──────────────────────────────────────
    var WPRoboDocuMerge_Form = {

        init: function() {
            WPRoboDocuMerge_Validator.init();
            this.bindEvents();
            this.initCharacterCounters();
            this.initTooltips();
        },

        bindEvents: function() {
            var self = this;
            $(document).on('submit', '.wdm-form', function(e) {
                e.preventDefault();
                self.submitForm($(this));
            });
            $(document).on('click', '.wdm-try-again', function(e) {
                e.preventDefault();
                self.resetForm($(this));
            });
        },

        /**
         * Initialize character counters on fields with maxlength.
         *
         * @since 1.3.0
         */
        initCharacterCounters: function() {
            // Fields WITH maxlength — show "23/100" with warning colors.
            $('.wdm-form').find('input[maxlength], textarea[maxlength]').each(function() {
                var $field = $(this);
                var max = parseInt($field.attr('maxlength'), 10);
                if (!max || max <= 0) { return; }

                var $counter = $('<span class="wdm-char-count"><span class="wdm-char-current">0</span>/' + max + '</span>');
                $field.after($counter);

                $field.on('input', function() {
                    var len = $(this).val().length;
                    $counter.find('.wdm-char-current').text(len);
                    $counter.toggleClass('wdm-char-limit', len >= max);
                    $counter.toggleClass('wdm-char-warning', len >= max * 0.9 && len < max);
                });
                $field.trigger('input');
            });

            // Textareas WITHOUT maxlength — show simple character count.
            $('.wdm-form').find('textarea:not([maxlength])').filter(function() {
                return !$(this).closest('.wdm-hp').length;
            }).each(function() {
                var $field = $(this);
                var $counter = $('<span class="wdm-char-count"><span class="wdm-char-current">0</span> chars</span>');
                $field.after($counter);

                $field.on('input', function() {
                    $counter.find('.wdm-char-current').text($(this).val().length);
                });
                $field.trigger('input');
            });
        },

        /**
         * Convert help text paragraphs into tooltip icons next to labels.
         *
         * @since 1.3.0
         */
        initTooltips: function() {
            $('.wdm-form .wdm-help-text').each(function() {
                var $helpText = $(this);
                var text = $helpText.text().trim();
                if (!text) {
                    return;
                }

                var $label = $helpText.closest('.wdm-field-group').find('> label').first();
                if (!$label.length) {
                    // Try the inner wdm-field-group label.
                    $label = $helpText.closest('[data-field-id]').find('label').first();
                }
                if (!$label.length) {
                    return;
                }

                // Create tooltip icon and append to label.
                var $tooltip = $('<span class="wdm-tooltip-wrap">' +
                    '<span class="wdm-tooltip-icon dashicons dashicons-editor-help"></span>' +
                    '<span class="wdm-tooltip-text">' + $('<span>').text(text).html() + '</span>' +
                '</span>');

                $label.append($tooltip);

                // Remove the original help text paragraph.
                $helpText.remove();
            });
        },


        submitForm: function($form) {
            var self = this;

            // Validate all visible fields
            if (!WPRoboDocuMerge_Validator.validateAll($form)) {
                var $firstError = $form.find('.wdm-field-has-error').first();
                if ($firstError.length) {
                    $('html, body').animate({scrollTop: $firstError.offset().top - 80}, 300);
                }
                return;
            }

            // Show loading state + hide draft notice + lock submit button.
            var $wrap = $form.closest('.wdm-form-wrap');
            $wrap.find('.wdm-draft-notice').slideUp(200);

            var $btn = $form.find('.wdm-submit-btn');
            $btn.prop('disabled', true).addClass('wdm-btn-submitting');
            $btn.find('.wdm-submit-text').hide();
            $btn.find('.wdm-submit-spinner').show();

            var formData = new FormData($form[0]);
            formData.append('action', 'wprobo_documerge_submit_form');
                formData.append('page_url', window.location.href);
                formData.append('referrer', document.referrer || '');

                $.ajax({
                    url: (typeof wprobo_documerge_frontend_vars !== 'undefined') ? wprobo_documerge_frontend_vars.ajax_url : ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            self.showSuccess($form, response.data);
                        } else {
                            if (response.data && response.data.field_errors) {
                                self.showFieldErrors($form, response.data.field_errors);
                            } else {
                                self.showError($form, response.data ? response.data.message : '');
                            }
                        }
                    },
                    error: function() {
                        self.showError($form, '');
                    },
                    complete: function(jqXHR, textStatus) {
                        // Only reset button on failure — on success the form is hidden anyway.
                        var response = jqXHR.responseJSON;
                        var isSuccess = response && response.success && !( response.data && response.data.field_errors );
                        if ( !isSuccess ) {
                            $btn.prop('disabled', false).removeClass('wdm-btn-submitting');
                            $btn.find('.wdm-submit-text').show();
                            $btn.find('.wdm-submit-spinner').hide();
                        }
                    }
                });
        },

        showSuccess: function($form, data) {
            var $wrap = $form.closest('.wdm-form-wrap');
            var formId = $wrap.data('form-id');

            // Trigger custom event so autosave can clear the draft.
            if (formId) {
                $(document).trigger('wdm_form_submitted', [formId]);
            }

            $form.hide();
            $wrap.find('.wdm-form-nav').hide();
            var $success = $wrap.find('.wdm-form-success');

            // Always display the success message from the server
            if (data.message) {
                if (data.submitter_name) {
                    $success.find('#wdm-success-msg').text('Thank you, ' + data.submitter_name + '. ' + data.message);
                } else {
                    $success.find('#wdm-success-msg').text(data.message);
                }
            } else if (data.submitter_name) {
                $success.find('#wdm-success-msg').text('Thank you, ' + data.submitter_name + '. Your personalised document has been generated.');
            } else {
                $success.find('#wdm-success-msg').text('Your document has been generated successfully.');
            }

            if (data.download_url) {
                $success.find('#wdm-download-link').attr('href', data.download_url).show();
            } else {
                $success.find('#wdm-download-link').hide();
            }
            if (data.email_sent && data.submitter_email) {
                $success.find('#wdm-success-email').text('A copy has been sent to ' + data.submitter_email).show();
            }
            $success.fadeIn(300);
            $('html, body').animate({scrollTop: $wrap.offset().top - 60}, 300);
        },

        resetSubmitButton: function($form) {
            var $btn = $form.find('.wdm-submit-btn');
            $btn.prop('disabled', false);
            $btn.find('.wdm-submit-text').show();
            $btn.find('.wdm-submit-spinner').hide();
        },

        showError: function($form, message) {
            var $wrap = $form.closest('.wdm-form-wrap');
            $form.hide();
            $wrap.find('.wdm-form-nav').hide();
            if (message) {
                $wrap.find('.wdm-error-message').text(message);
            }
            $wrap.find('.wdm-form-error').show();
            $('html, body').animate({scrollTop: $wrap.offset().top - 60}, 300);
        },

        showFieldErrors: function($form, errors) {
            $.each(errors, function(fieldName, errorMsg) {
                var $field = $form.find('[name="' + fieldName + '"]');
                if ($field.length) {
                    WPRoboDocuMerge_Validator.showFieldError($field, errorMsg);
                }
            });
            var $firstError = $form.find('.wdm-field-has-error').first();
            if ($firstError.length) {
                $('html, body').animate({scrollTop: $firstError.offset().top - 80}, 300);
            }
        },

        resetForm: function($btn) {
            var $wrap = $btn.closest('.wdm-form-wrap');
            $wrap.find('.wdm-form-error').hide();
            $wrap.find('.wdm-form').show();
            $wrap.find('.wdm-form-nav').show();
        }
    };

    $(document).ready(function() {
        WPRoboDocuMerge_Form.init();
    });

    window.WPRoboDocuMerge_Form      = WPRoboDocuMerge_Form;
    window.WPRoboDocuMerge_Validator  = WPRoboDocuMerge_Validator;

})(jQuery);
