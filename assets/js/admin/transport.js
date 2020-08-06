(function ($) {
    const Shopsys = window.Shopsys || {};
    Shopsys.transport = Shopsys.transport || {};

    Shopsys.transport.init = function () {
        const $actionActiveCB = $('.js-action-cb');

        const onActionChange = function (event) {
            let $currentCheckbox = $(event.target);
            let $targetedElement = $('.js-action-price-wrapper-' + $currentCheckbox.data('domain'));

            $currentCheckbox.is(':checked') ? $targetedElement.removeClass('display-none') : $targetedElement.addClass('display-none');
        };

        $actionActiveCB.on('change', onActionChange);
        $actionActiveCB.change();
    };

    $(document).ready(function () {
        Shopsys.transport.init();
    });
})(jQuery);
