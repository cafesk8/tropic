import Register from 'framework/common/utils/register';
import 'jquery.cookie';

(function ($) {

    const Shopsys = window.Shopsys || {};
    Shopsys.infoRow = Shopsys.infoRow || {};

    const cookieExpires = 10 * 365; // 10 years
    const cookieName = 'infoRow_closedAt';

    Shopsys.infoRow.InfoRow = function ($infoRow) {

        const $infoRowCloseButton = $infoRow.find('.js-info-row__close');
        this.init = function () {
            $infoRowCloseButton.click(closeAndRememberIt);

        };
        function closeAndRememberIt () {
            // current datetime can be different on server and client. It has to use time from server
            $.cookie(cookieName, $infoRow.data('now'), { expires: cookieExpires, path: '/' });

            $infoRow.slideUp('fast', function () {
                $infoRow.remove();
            });
        }
    };

    new Register().registerCallback(function ($container) {
        $container.filterAllNodes('#js-info-row').each(function () {
            const infoRow = new Shopsys.infoRow.InfoRow($(this));
            infoRow.init();
        });
    });

})(jQuery);
