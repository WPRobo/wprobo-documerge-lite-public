/**
 * WPRobo DocuMerge — Tracking Parameter Handler
 *
 * Populates hidden tracking inputs with UTM parameters,
 * document referrer, and current landing page URL.
 *
 * @package WPRobo_DocuMerge
 * @since   1.4.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        var params;

        try {
            params = new URLSearchParams(window.location.search);
        } catch (e) {
            params = null;
        }

        $('.wdm-tracking-input').each(function() {
            var $input = $(this);
            var param  = $input.data('param');

            if ( ! param ) {
                return;
            }

            if ( param === 'referrer' ) {
                $input.val(document.referrer || '');
            } else if ( param === 'landing_page' ) {
                $input.val(window.location.href);
            } else if ( params ) {
                $input.val(params.get(param) || '');
            }
        });
    });

})(jQuery);
