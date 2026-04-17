/**
 * WPRobo DocuMerge — Phone Field Handler
 *
 * Initializes intl-tel-input on phone fields with country flags,
 * search, and auto-formatting.
 *
 * @package WPRobo_DocuMerge
 * @since   1.1.0
 */

(function($) {
    'use strict';

    var WPRoboDocuMerge_Phone = {

        instances: {},

        init: function() {
            var self = this;
            $('.wdm-intl-phone').each(function() {
                self.initPhone(this);
            });
        },

        initPhone: function(input) {
            if (typeof intlTelInput === 'undefined') {
                return;
            }

            var $input         = $(input);
            var defaultCountry = $input.data('default-country') || 'gb';
            var showCountry    = $input.data('show-country');

            var utilsUrl = (typeof wprobo_documerge_frontend_vars !== 'undefined' && wprobo_documerge_frontend_vars.intl_tel_utils_url)
                ? wprobo_documerge_frontend_vars.intl_tel_utils_url
                : '';

            var opts = {
                initialCountry:        defaultCountry,
                countryOrder:          ['gb', 'us', 'ca', 'au', 'de', 'fr', 'in', 'pk', 'ae'],
                separateDialCode:      true,
                nationalMode:          false,
                autoPlaceholder:       'aggressive',
                formatOnDisplay:       true,
                countrySearch:         true,
                showFlags:             true,
                containerClass:        'wdm-iti-container',
                dropdownContainer:     document.body,
                i18n: {}
            };

            // intl-tel-input v25+ loads utils via dynamic ES-module import.
            if (utilsUrl) {
                opts.loadUtils = function () { return import(/* webpackIgnore: true */ utilsUrl); };
            }

            // If country code is disabled, just use simple mode.
            if (showCountry === 0 || showCountry === '0') {
                opts.showFlags        = false;
                opts.separateDialCode = false;
                opts.allowDropdown    = false;
            }

            var iti = intlTelInput(input, opts);
            this.instances[input.id] = iti;

            // On blur, copy the full international number to hidden input and validate.
            $input.on('blur change', function() {
                var fullNum = iti.getNumber();
                var $hidden = $('#' + input.id + '-full');
                if ($hidden.length) {
                    $hidden.val(fullNum);
                }
                // Also update the visible input value to the formatted number.
                if (fullNum) {
                    input.value = iti.getNumber(1); // national format
                }
            });
        },

        /**
         * Validate a phone field using intl-tel-input's built-in validation.
         *
         * @param {HTMLElement} input The phone input element.
         * @return {boolean} True if valid or no instance found.
         */
        isValid: function(input) {
            var iti = this.instances[input.id];
            if (!iti) { return true; }
            // isValidNumber uses Google's libphonenumber under the hood.
            return iti.isValidNumber();
        },

        /**
         * Get the validation error message.
         *
         * @param {HTMLElement} input The phone input element.
         * @return {string} Error message.
         */
        getError: function(input) {
            var iti = this.instances[input.id];
            if (!iti) { return ''; }
            var i18n = (typeof wprobo_documerge_vars !== 'undefined' && wprobo_documerge_vars.i18n) ? wprobo_documerge_vars.i18n : {};
            var errorMap = [
                i18n.phone_invalid || 'Invalid number',
                i18n.phone_invalid_country || 'Invalid country code',
                i18n.phone_too_short || 'Too short',
                i18n.phone_too_long || 'Too long',
                i18n.phone_invalid || 'Invalid number'
            ];
            var errorCode = iti.getValidationError();
            return errorMap[errorCode] || (i18n.phone_invalid_number || 'Invalid phone number');
        }
    };

    $(document).ready(function() {
        WPRoboDocuMerge_Phone.init();
    });

    window.WPRoboDocuMerge_Phone = WPRoboDocuMerge_Phone;

})(jQuery);
