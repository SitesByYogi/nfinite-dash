(function ($) {
    'use strict';

    $(function () {
        $('.nfinite-projects-hero').each(function () {
            var $hero = $(this);
            $hero.on('click', '.nfinite-tab-btn', function () {
                var tab = $(this).data('nfinite-tab');
                $hero.find('.nfinite-tab-btn').removeClass('is-active');
                $hero.find('.nfinite-tab-pane').removeClass('is-active');
                $(this).addClass('is-active');
                $hero.find('[data-nfinite-pane="' + tab + '"]').addClass('is-active');
            });
        });
    });
})(jQuery);
