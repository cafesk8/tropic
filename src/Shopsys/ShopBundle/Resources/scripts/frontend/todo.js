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

    // search auto focus on popup activation
    $searchButtonOpener = $('.js-search-opener');
    $searchButtonOpener.on('mouseover', function () {
        setTimeout(function () {
            $searchButtonOpener.find('input[type=text]').focus();
            console.log('e');
        }, 500
        );
    });

})(jQuery);
