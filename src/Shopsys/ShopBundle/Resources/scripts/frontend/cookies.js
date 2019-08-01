(function ($) {

    Shopsys = window.Shopsys || {};
    var cookieName = Shopsys.constant('\\Shopsys\\FrameworkBundle\\Model\\Cookies\\CookiesFacade::EU_COOKIES_COOKIE_CONSENT_NAME');
    var tenYears = 10 * 365;

    $(document).ready(function () {
        $('.js-eu-cookies-consent-button').click(function () {
            var $cookiesBlock = $('.js-eu-cookies');
            $.cookie(cookieName, true, { expires: tenYears, path: '/' });

            $cookiesBlock.addClass('box-cookies--closing');
        });

        $('.js-eu-cookies-close-button').click(function () {
            var $cookiesBlock = $('.js-eu-cookies');
            $cookiesBlock.addClass('box-cookies--closing');
        });
    });

})(jQuery);
