(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.hoverIntent = Shopsys.hoverIntent || {};

    Shopsys.hoverIntent.HoverIntent = function ($hoverIntentParent) {

        var interval = null;
        var timeout = null;
        var classForOpen = null;

        this.init = function () {
            interval = parseInt($hoverIntentParent.data('hover-intent-interval'));
            timeout = parseInt($hoverIntentParent.data('hover-intent-timeout'));
            classForOpen = $hoverIntentParent.data('hover-intent-class-for-open');

            $hoverIntentParent.hoverIntent({
                interval: interval,
                timeout: timeout,
                over: function () {
                    $(this).addClass(classForOpen);
                },
                out: function () {
                    $(this).removeClass(classForOpen);
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
