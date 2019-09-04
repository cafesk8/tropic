(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.hoverIntent = Shopsys.hoverIntent || {};

    Shopsys.hoverIntent.HoverIntentSetting = function ($hoverIntentParent) {
        var interval = 250;
        var timeout = 250;
        var classForOpen = 'open';
        var $selector = $hoverIntentParent;

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
        };

        this.getInterval = function () {
            return interval;
        };

        this.getTimeout = function () {
            return timeout;
        };

        this.getClassForOpen = function () {
            return classForOpen;
        };

        this.getSelector = function () {
            return $selector;
        };
    };

    Shopsys.hoverIntent.hoverIntent = function (hoverIntentSettings) {
        hoverIntentSettings.forEach(function (hoverIntentSetting) {
            hoverIntentSetting.getSelector().hoverIntent({
                interval: hoverIntentSetting.getInterval(),
                timeout: hoverIntentSetting.getTimeout(),
                over: function () {
                    hideAllOpenedIntent();
                    $(this).addClass(hoverIntentSetting.getClassForOpen());
                },
                out: function () {
                    if ($(this).find('input:focus').size() === 0) {
                        $(this).removeClass(hoverIntentSetting.getClassForOpen());
                    }
                }
            });
        });

        function hideAllOpenedIntent () {
            hoverIntentSettings.forEach(function (hoverIntentSetting) {
                hoverIntentSetting.getSelector().removeClass(hoverIntentSetting.getClassForOpen());
            });
        }

        // hide all opened intent after click wherever instead of element with hover intent
        $('body').click(function (event) {
            if ($(event.target).closest('.js-hover-intent').length === 0) {
                hideAllOpenedIntent();
            }
        });
    };

    Shopsys.register.registerCallback(function ($container) {
        var hoverIntentSettings = [];
        $container.filterAllNodes('.js-hover-intent').each(function () {
            var hoverIntentSetting = new Shopsys.hoverIntent.HoverIntentSetting($(this));
            hoverIntentSetting.init();
            hoverIntentSettings.push(hoverIntentSetting);
        });

        Shopsys.hoverIntent.hoverIntent(hoverIntentSettings);
    });

})(jQuery);
