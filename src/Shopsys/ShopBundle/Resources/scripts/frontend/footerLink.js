(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.FooterLink = Shopsys.FooterLink || {};

    Shopsys.FooterLink.init = function () {
        $footerLinkTitle = $('.js-footer-link-title');
        var footerLinkWrapperSelector = '.js-footer-link-wrapper';
        var footerLinkContentSelector = '.js-footer-link-content';

        $footerLinkTitle.unbind('click');

        $firstFooterLinkTitle = $footerLinkTitle.first();

        var isFirstFooterLinkContentVisible = $firstFooterLinkTitle.closest(footerLinkWrapperSelector).find(footerLinkContentSelector).is(':visible');

        if (isFirstFooterLinkContentVisible === false) {
            $footerLinkTitle.on('click', function () {
                $(this).toggleClass('open');
                $(this).closest(footerLinkWrapperSelector).find(footerLinkContentSelector).slideToggle();
            });
        }
    };

    Shopsys.register.registerCallback(Shopsys.FooterLink.init);

    // In case of rotate screen on mobile and tablet
    $(window).resize(Shopsys.FooterLink.init);

})(jQuery);
