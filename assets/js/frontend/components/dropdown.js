/* TODO PRG */
import Register from 'framework/common/utils/Register';

(function ($) {

    const Shopsys = window.Shopsys || {};
    Shopsys.DropDown = Shopsys.DropDown || {};

    Shopsys.DropDown.init = function ($container) {
        $container.filterAllNodes('.js-dropdown').each(Shopsys.DropDown.bindDropDown);
    };

    Shopsys.DropDown.bindDropDown = function (event) {
        $(event.currentTarget).click(function (e) {
            Shopsys.DropDown.hideAllSubmenus();

            if ($(e.currentTarget).data('type') == 'link-on-mobile') {
                if ($(window).width() < Shopsys.responsive.SM) {
                    event.stopPropagation();
                }
            } else if ($(e.currentTarget).data('type') == 'link') {
                event.stopPropagation();
            } else {
                $(e.currentTarget).toggleClass('open');
                event.stopPropagation();
            }
        });

        $(document).on('click', function () {
            Shopsys.DropDown.hideAllSubmenus();
        });
    };

    Shopsys.DropDown.hideAllSubmenus = function () {
        $('.js-dropdown').each(function () {
            $(this).removeClass('open');
        });
    };

    new Register().registerCallback(Shopsys.DropDown.init);

})(jQuery);
