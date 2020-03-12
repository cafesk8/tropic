import Translator from 'bazinga-translator';
import Register from 'framework/common/utils/Register';

(function ($) {

    const Shopsys = window.Shopsys || {};
    Shopsys.promoCode = Shopsys.promoCode || {};

    Shopsys.promoCode.PromoCodeGroup = function ($promoCodeGroup) {
        const $firstRow = $promoCodeGroup.first().closest('.js-grid-row');
        const prefix = $firstRow.filterAllNodes('.js-promo-code-mass').attr('data-promo-code-prefix');

        this.init = function () {
            const deleteButtonHtml = $firstRow.find('.js-promocode-mass-delete').html();
            const unpackButtonHtml = '<span class="btn btn-success js-promo-code-group-unpack width-80 text-center" data-promo-code-prefix="' + prefix + '">' + Translator.trans('Rozbalit') + '</span>';
            const $parentRow = $('<tr class="table-grid__row js-grid-row background-color-ddd">'
                + '<td colspan="4" class="table-grid__cell">' + Translator.trans('Hromadné kupony s prefixem') + ' <b>' + prefix + '</b></td>'
                + '<td class="table-grid__cell text-right" colspan="4">' + deleteButtonHtml + unpackButtonHtml + '</td>'
                + '</tr>'
            );

            $parentRow.insertBefore($firstRow);

            const $rows = $promoCodeGroup.closest('.js-grid-row');
            $rows.addClass('display-none');

            $rows.each(function () {
                const $row = $(this);
                $row.filterAllNodes('td').first().css('padding-left', '40px');
            });

            $('.js-promo-code-group-unpack[data-promo-code-prefix="' + prefix + '"]').click(function () {
                $(this).text(function (i, text) {
                    const pack = Translator.trans('Sbalit');
                    const unpack = Translator.trans('Rozbalit');
                    return text === unpack ? pack : unpack;
                });
                $rows.toggleClass('display-none');
            });
        };
    };

    new Register().registerCallback(function ($container) {
        function arrayUnique (array) {
            return $.grep(array, function (el, index) {
                return index == $.inArray(el, array);
            });
        }

        let prefixJsClasses = [];

        $container.filterAllNodes('.js-promo-code-mass').each(function () {
            const prefixJsClass = $(this).attr('data-promo-code-prefix-js-class');
            if ($(this).attr('data-promo-code-group-enabled') === '1') {
                prefixJsClasses.push(prefixJsClass);
            }
        });

        prefixJsClasses = arrayUnique(prefixJsClasses);

        for (let i = 0; i < prefixJsClasses.length; i++) {
            const $promoCodeGroup = $('.' + prefixJsClasses[i]);
            const promoCodeGroup = new Shopsys.promoCode.PromoCodeGroup($promoCodeGroup);
            promoCodeGroup.init();
        }
    });

})(jQuery);
