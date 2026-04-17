/**
 * WPRobo DocuMerge — Datepicker Handler
 *
 * Initializes Flatpickr on date fields with format, min/max date,
 * disable past dates, and max future months constraints.
 *
 * @package WPRobo_DocuMerge
 * @since   1.1.0
 */

(function($) {
    'use strict';

    var WPRoboDocuMerge_Datepicker = {

        /**
         * PHP to Flatpickr format map.
         */
        formatMap: {
            'Y-m-d':  'Y-m-d',
            'd/m/Y':  'd/m/Y',
            'm/d/Y':  'm/d/Y',
            'd-m-Y':  'd-m-Y',
            'd.m.Y':  'd.m.Y',
            'F j, Y': 'F j, Y',
            'M j, Y': 'M j, Y',
            'j F Y':  'j F Y'
        },

        init: function() {
            var self = this;
            $('.wdm-datepicker').each(function() {
                self.initPicker(this);
            });
        },

        initPicker: function(input) {
            if (typeof flatpickr === 'undefined') {
                return;
            }

            var $input          = $(input);
            var phpFormat       = $input.data('date-format') || 'Y-m-d';
            var fpFormat        = this.formatMap[phpFormat] || 'Y-m-d';
            var disablePast     = $input.data('disable-past');
            var maxFutureMonths = parseInt($input.data('max-future-months'), 10) || 0;
            var minDateAttr     = $input.data('min-date') || '';
            var maxDateAttr     = $input.data('max-date') || '';

            var config = {
                dateFormat:   fpFormat,
                allowInput:   true,
                disableMobile: false,
                altInput:     false,
                animate:      true
            };

            // Disable past dates — set minDate to today.
            if (disablePast === 1 || disablePast === '1' || disablePast === true) {
                config.minDate = 'today';
            }

            // Explicit min date overrides disable_past.
            if (minDateAttr) {
                config.minDate = minDateAttr;
            }

            // Max future months — calculate the max date.
            if (maxFutureMonths > 0) {
                var maxDate = new Date();
                maxDate.setMonth(maxDate.getMonth() + maxFutureMonths);
                config.maxDate = maxDate;
            }

            // Explicit max date overrides max_future_months.
            if (maxDateAttr) {
                config.maxDate = maxDateAttr;
            }

            flatpickr(input, config);
        }
    };

    $(document).ready(function() {
        WPRoboDocuMerge_Datepicker.init();
    });

    window.WPRoboDocuMerge_Datepicker = WPRoboDocuMerge_Datepicker;

})(jQuery);
