import Register from 'framework/common/utils/Register';
import Ajax from 'framework/common/utils/Ajax';

export default function listSet () {
    const container = $('.js-list-set-container');
    const items = container.find('.js-list-set-item');

    $(items).click(function (event) {
        const $clickedItem = $(event.currentTarget);
        if ($clickedItem.hasClass('active')) {
            return false;
        } else {
            items.removeClass('active');
            $clickedItem.addClass('active');
            const itemId = $clickedItem.data('product-set-item-id');
            const $boxTabs = $('.js-box-tabs');

            Ajax.ajax({
                loaderElement: $boxTabs,
                url: '/product/box-tabs/' + itemId,
                dataType: 'html',
                success: function (html) {
                    const $html = $($.parseHTML(html));
                    $boxTabs.html($html);

                    (new Register()).registerNewContent($html);
                }
            });
        }
    });
};

(new Register()).registerCallback(listSet);
