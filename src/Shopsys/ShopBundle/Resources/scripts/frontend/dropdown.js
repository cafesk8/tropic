/* TODO PRG */
(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.DropDown = Shopsys.DropDown || {};

    Shopsys.DropDown.init = function ($container) {
        $container.filterAllNodes('.js-dropdown').each(Shopsys.DropDown.bindDropDown);
    };

    Shopsys.DropDown.bindDropDown = function () {
        $(this).click(function (event) {
            Shopsys.DropDown.hideAllSubmenus();
            $(this).toggleClass('open');

            event.stopPropagation();
        });

        $(document).on('click', function () {
            Shopsys.DropDown.hideAllSubmenus();
        });
    };

    Shopsys.DropDown.hideAllSubmenus = function () {
        $('.js-dropdown').each(function() {
            $(this).removeClass('open');
        });
    };

    Shopsys.register.registerCallback(Shopsys.DropDown.init);

})(jQuery);
