import Register from 'framework/common/utils/Register';

(function ($) {

    (new Register()).registerCallback(function ($container) {
        $container.filterAllNodes('.js-popup-image').magnificPopup({
            type: 'image'
        });
    });

})(jQuery);
