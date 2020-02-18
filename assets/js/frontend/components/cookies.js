import constant from '../utils/constant';
import Register from 'framework/common/utils/register';

const cookieName = constant('\\Shopsys\\FrameworkBundle\\Model\\Cookies\\CookiesFacade::EU_COOKIES_COOKIE_CONSENT_NAME');
const tenYears = 10 * 365;

export default function cookieInit () {
    $('.js-eu-cookies-consent-button').click(function () {
        const $cookiesFooterGap = $('.js-eu-cookies-consent-footer-gap');
        const $cookiesBlock = $('.js-eu-cookies');
        $.cookie(cookieName, true, { expires: tenYears, path: '/' });

        $cookiesBlock.addClass('box-cookies--closing');
        $cookiesFooterGap.removeClass('web__footer--with-cookies');
    });

    $('.js-eu-cookies-close-button').click(function () {
        const $cookiesBlock = $('.js-eu-cookies');
        $.cookie(cookieName, true, { expires: tenYears, path: '/' });

        $cookiesBlock.addClass('box-cookies--closing');
    });
}

(new Register()).registerCallback(cookieInit);
