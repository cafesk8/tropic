import Register from 'framework/common/utils/Register';

export default function opener () {
    const opener = $('.js-opener-container');

    opener.each(function () {
        const items = $(this).find('.js-opener-item');
        const itemsWrapper = $(this).find('.js-opener-wrap');
        const openedItems = itemsWrapper.data("opened-items");
        const wrapperFullHeight = itemsWrapper.height();
        const openerButton = $(this).find('.js-opener-show-more-button');
        const itemsHeight = [];

        // Check if is adequate counf od products
        if (items.length <= openedItems) {
            openerButton.hide();
        } else {
            items.each(function () {
                const height = $(this).outerHeight();
                const marginOfItem = parseFloat($(this).css('margin-bottom'), 10);
                const finalHeight = height + marginOfItem;
                itemsHeight.push(finalHeight);
            })

            function wrapperHeight () {
                const cuttedItems = itemsHeight.slice(0, openedItems);
                const finalHeight = cuttedItems.reduce(function(accumulator, currentValue){
                    return accumulator + currentValue;
                });

                return finalHeight;
            }

            itemsWrapper.css('height', wrapperHeight());

            openerButton.click(function () {
                if($(this).hasClass('open')){
                    hideItems($(this))
                } else {
                    showItems($(this))
                }
            })

            function hideItems (item) {
                itemsWrapper.css('height', wrapperHeight());
                item.removeClass('open');
            }

            function showItems (item) {
                itemsWrapper.css('height', wrapperFullHeight);
                item.addClass('open');
            }
        }
    })
};

(new Register()).registerCallback(opener);
