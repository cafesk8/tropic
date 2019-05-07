(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.infoRow = Shopsys.infoRow || {};

    var cookieExpires = 10 * 365; // 10 years
    var cookieName = Shopsys.constant('Shopsys\\ShopBundle\\Component\\InfoRow\\InfoRowFacade::COOKIE_CLOSED_AT');

    Shopsys.infoRow.InfoRow = function ($infoRow) {

        $infoRowCloseButton = $infoRow.find('.js-info-row__close');
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

    Shopsys.register.registerCallback(function ($container) {
        $container.filterAllNodes('#js-info-row').each(function () {
            var infoRow = new Shopsys.infoRow.InfoRow($(this));
            infoRow.init();
        });
    });

})(jQuery);
