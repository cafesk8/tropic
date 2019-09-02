/* TODO PRG */
(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.DropDown = Shopsys.DropDown || {};

    $footerLinkTitle = $('.js-footer-link-title');
    $footerLinkContent = $('.js-footer-link-content');

    $footerLinkTitle.on('click', function () {
        $(this).toggleClass('open');
        $(this).parent().find($footerLinkContent).slideToggle();
    });


})(jQuery);
