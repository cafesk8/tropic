(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.windowInfoTooltip = Shopsys.windowInfoTooltip || {};

    var tooltipElement = '.js-window-info-tooltip';

    Shopsys.windowInfoTooltip.init = function ($container) {
        $container.filterAllNodes(tooltipElement).each(Shopsys.windowInfoTooltip.show);
    };

    Shopsys.windowInfoTooltip.show = function () {
        $(this).click(function (event) {
            Shopsys.windowInfoTooltip.hideAllTooltips();

            $(this).toggleClass('active');
            event.stopPropagation();
            return false;
        });

        $(document).on('click', function () {
            Shopsys.windowInfoTooltip.hideAllTooltips();
        });
    };

    Shopsys.windowInfoTooltip.hideAllTooltips = function () {
        $(tooltipElement).each(function () {
            $(this).removeClass('active');
        });
    };

    Shopsys.register.registerCallback(Shopsys.windowInfoTooltip.init);

})(jQuery);
