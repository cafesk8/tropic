/* TODO PRG */
(function ($) {

    const Shopsys = window.Shopsys || {};
    Shopsys.DropDown = Shopsys.DropDown || {};

    const $footerLinkTitle = $('.js-footer-link-title');
    const $footerLinkContent = $('.js-footer-link-content');

    $footerLinkTitle.on('click', function (event) {
        $(event.currentTarget).toggleClass('open');
        $(event.currentTarget).parent().find($footerLinkContent).slideToggle();
    });

    // search auto focus on popup activation
    const $searchButtonOpener = $('.js-search-opener');
    $searchButtonOpener.on('mouseover', function () {
        setTimeout(function () {
            $searchButtonOpener.find('input[type=text]').focus();
        }, 500
        );
    });

})(jQuery);
