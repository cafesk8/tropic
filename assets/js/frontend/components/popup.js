import Register from 'framework/common/utils/register';

(function ($) {

    (new Register()).registerCallback(function ($container) {
        $container.filterAllNodes('.js-popup-image').magnificPopup({
            type: 'image'
        });
    });

})(jQuery);
