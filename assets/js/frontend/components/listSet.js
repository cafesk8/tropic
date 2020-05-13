import Register from 'framework/common/utils/Register';

export default function listSet () {
    const container = $('.js-list-set-container');
    const items = container.find('.js-list-set-item');

    $(items).click(function () {
        if ($(this).hasClass('active')) {
            return false;
        } else {
            items.removeClass('active');
            $(this).addClass('active');
        }
    });
};

(new Register()).registerCallback(listSet);
