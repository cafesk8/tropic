(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.hoverIntent = Shopsys.hoverIntent || {};

    Shopsys.hoverIntent.HoverIntent = function ($hoverIntentParent) {

        var interval = 250;
        var timeout = 250;
        var classForOpen = 'open';

        this.init = function () {

            if ($hoverIntentParent.data('hover-intent-interval')) {
                interval = parseInt($hoverIntentParent.data('hover-intent-interval'));
            }

            if ($hoverIntentParent.data('hover-intent-timeout')) {
                timeout = parseInt($hoverIntentParent.data('hover-intent-timeout'));
            }

            if ($hoverIntentParent.data('hover-intent-class-for-open')) {
                classForOpen = $hoverIntentParent.data('hover-intent-class-for-open');
            }

            $hoverIntentParent.hoverIntent({
                interval: interval,
                timeout: timeout,
                over: function () {
                    $(this).addClass(classForOpen);
                },
                out: function () {
                    if ($(this).find('input:focus').size() === 0) {
                        $(this).removeClass(classForOpen);
                    }
                }
            });
        };
    };

    Shopsys.register.registerCallback(function ($container) {
        $container.filterAllNodes('.js-hover-intent').each(function () {
            var hoverIntent = new Shopsys.hoverIntent.HoverIntent($(this));
            hoverIntent.init();
        });
    });

})(jQuery);
