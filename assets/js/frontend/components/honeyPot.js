import Register from 'framework/common/utils/Register';

(new Register()).registerCallback(function ($container) {
    $container.filterAllNodes('.js-honey').hide();
    $container.filterAllNodes('.js-honey').parents('.form-line').hide();
});
