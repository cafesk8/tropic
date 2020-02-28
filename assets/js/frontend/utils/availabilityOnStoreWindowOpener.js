import Register from 'framework/common/utils/Register';
import Window from './Window';

new Register().registerCallback(function ($container) {
    $container.filterAllNodes('.js-detail-availability-window-opener').on('click', function (event) {
        const $target = $(event.currentTarget);
        const productId = $target.data('product-id');
        const content = $('.js-availability-table_' + productId).html();
        /* eslint-disable no-new */
        new Window({
            content: content
        });

        return false;
    });
});
