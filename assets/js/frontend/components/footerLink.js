import Register from 'framework/common/utils/Register';

(function ($) {

    const Shopsys = window.Shopsys || {};
    Shopsys.FooterLink = Shopsys.FooterLink || {};

    Shopsys.FooterLink.init = function () {
        const $footerLinkTitle = $('.js-footer-link-title');
        const footerLinkWrapperSelector = '.js-footer-link-wrapper';
        const footerLinkContentSelector = '.js-footer-link-content';

        $footerLinkTitle.unbind('click');

        const $firstFooterLinkTitle = $footerLinkTitle.first();

        const isFirstFooterLinkContentVisible = $firstFooterLinkTitle.closest(footerLinkWrapperSelector).find(footerLinkContentSelector).is(':visible');

        if (isFirstFooterLinkContentVisible === false) {
            $footerLinkTitle.on('click', function (event) {
                $(event.currentTarget).toggleClass('open');
                $(event.currentTarget).closest(footerLinkWrapperSelector).find(footerLinkContentSelector).slideToggle();
            });
        }
    };

    new Register().registerCallback(Shopsys.FooterLink.init);

    // In case of rotate screen on mobile and tablet
    $(window).resize(Shopsys.FooterLink.init);

})(jQuery);
