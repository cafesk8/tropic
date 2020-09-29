import Register from 'framework/common/utils/Register';

export default function opener () {
    let opener = $('.js-opener-container');
    console.log('test');

    opener.each(function () {
        let items = $(this).find('.js-opener-item');
        let itemsWrapper = $(this).find('.js-opener-wrap');
        let openedItems = itemsWrapper.data('opened-items');
        let wrapperFullHeight = itemsWrapper.height();
        let wrapperWrappedHeight = 0;
        let openerButton = $(this).find('.js-opener-show-more-button');
        let itemsHeight = [];

        if (items.length <= openedItems) {
            openerButton.hide();
        } else {
            items.each(function () {
                let height = $(this).outerHeight();
                let marginOfItem = parseFloat($(this).css('margin-bottom'));
                let finalHeight = height + marginOfItem;
                itemsHeight.push(finalHeight);
            });

            wrapperFullHeight = itemsHeight.reduce(function (total, current) {
                return total + current;
            });

            wrapperWrappedHeight = itemsHeight.slice(0, openedItems).reduce(function (accumulator, currentValue) {
                return accumulator + currentValue;
            });

            itemsWrapper.css('height', openerButton.hasClass('open') ? wrapperFullHeight : wrapperWrappedHeight);

            openerButton.unbind('click');
            openerButton.bind('click', function () {
                if ($(this).hasClass('open')) {
                    itemsWrapper.css('height', wrapperWrappedHeight);
                    $(this).removeClass('open');
                } else {
                    itemsWrapper.css('height', wrapperFullHeight);
                    $(this).addClass('open');
                }
            });
        };
    });
}

(new Register()).registerCallback(opener);

$(window).resize(opener);
