(function ($) {

    /*
     1. add to button class 'js-responsive-toggle'
     2. set data-element to ID of target element to show/hide only on non desktop version
     3. set hide-on-click-out to true, if it should hide after click on page or different element
     4. switch to desktop version reset visibility of data-element from 2.
     */

    Shopsys = window.Shopsys || {};

    var activeButtonClass = 'open';
    var instanceCoutner = 0;

    Shopsys.ResponsiveToggle = function ($button, $elementToHide, hideOnClickOut) {
        var defaultActive = null;
        var instanceNumber = instanceCoutner;
        instanceCoutner++;

        this.init = function () {
            defaultActive = isActive();
            $button.click(function () {
                toggle(!isActive());
                return false;
            });

            if (hideOnClickOut) {
                $(document).click(onClickOut);
            }

            $(window).resize(function () {
                Shopsys.timeout.setTimeoutAndClearPrevious('ResponsiveToggle.window.resize.' + instanceNumber, onWindowResize, 200);
            });
        };

        function isActive () {
            if ($button.data('parent-give-class')) {
                return $button.parent().hasClass(activeButtonClass);
            } else {
                return $button.hasClass(activeButtonClass);
            };
        }

        function toggle (show) {
            if ($button.data('parent-give-class')) {
                $button.parent().toggleClass(activeButtonClass, show);
            } else {
                $button.toggleClass(activeButtonClass, show);
            };

            if (!$button.data('slide-toggle-disabled')) {
                $elementToHide.slideToggle(show);
            }
        }

        function onClickOut (event) {
            if (
                isActive()
                && $(event.target).closest($button).length === 0
                && $(event.target).closest($elementToHide).length === 0
            ) {
                toggle(false);
            }
        }

        function onWindowResize () {
            if (Shopsys.responsive.isDesktopVersion()) {
                if ($elementToHide.is(':animated')) {
                    $elementToHide.stop(true, true);
                }

                if ($button.data('parent-give-class')) {
                    $button.parent().toggleClass(activeButtonClass, defaultActive);
                } else {
                    $button.toggleClass(activeButtonClass, defaultActive);
                }
                $elementToHide.css('display', '');
            }
        }

    };

    $(document).ready(function () {

        $('.js-responsive-toggle').each(function () {
            var $button = $(this);
            var $elementToHide = $('#' + $button.data('element'));
            var hideOnClickOut = $button.data('hide-on-click-out');

            var responsiveToggle = new Shopsys.ResponsiveToggle($button, $elementToHide, hideOnClickOut);
            responsiveToggle.init();
        });

    });
})(jQuery);
