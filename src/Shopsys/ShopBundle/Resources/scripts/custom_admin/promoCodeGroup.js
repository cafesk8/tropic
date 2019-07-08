(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.promoCode = Shopsys.promoCode || {};

    Shopsys.promoCode.PromoCodeGroup = function ($promoCodeGroup) {
        var $rows = $promoCodeGroup.closest('.js-grid-row');
        var $firstRow = $promoCodeGroup.first().closest('.js-grid-row');
        var prefix = $firstRow.filterAllNodes('.js-promo-code-mass').attr('data-promo-code-prefix');

        this.init = function () {
            $rows.addClass('display-none');

            var unpackButtonHtml = '<span class="btn btn-success js-promo-code-group-unpack width-80 text-center" data-promo-code-prefix="' + prefix + '">' + Shopsys.translator.trans('Rozbalit') + '</span>';
            var $parentRow = $('<tr class="table-grid__row js-grid-row background-color-ddd">'
                + '<td colspan="4" class="table-grid__cell">' + Shopsys.translator.trans('Hromadné kupony s prefixem') + ' <b>' + prefix + '</b></td>'
                + '<td colspan="2"></td>'
                + '<td class="table-grid__cell">' + unpackButtonHtml + '</td>'
                + '</tr>'
            );

            $parentRow.insertBefore($firstRow);

            $rows = $promoCodeGroup.closest('.js-grid-row');

            $rows.each(function () {
                $row = $(this);
                $row.filterAllNodes('td').first().css('padding-left', '40px');
            });

            $('.js-promo-code-group-unpack[data-promo-code-prefix="' + prefix + '"]').click(function () {
                $(this).text(function (i, text) {
                    var pack = Shopsys.translator.trans('Sbalit');
                    var unpack = Shopsys.translator.trans('Rozbalit');
                    return text === unpack ? pack : unpack;
                });
                $rows.toggleClass('display-none');
            });
        };
    };

    Shopsys.register.registerCallback(function ($container) {
        function arrayUnique (array) {
            return $.grep(array, function (el, index) {
                return index == $.inArray(el, array);
            });
        }

        var prefixJsClasses = [];

        $container.filterAllNodes('.js-promo-code-mass').each(function () {
            var prefixJsClass = $(this).attr('data-promo-code-prefix-js-class');
            if ($(this).attr('data-promo-code-group-enabled') === '1') {
                prefixJsClasses.push(prefixJsClass);
            }
        });

        prefixJsClasses = arrayUnique(prefixJsClasses);

        for (i = 0; i < prefixJsClasses.length; i++) {
            var $promoCodeGroup = $('.' + prefixJsClasses[i]);
            var promoCodeGroup = new Shopsys.promoCode.PromoCodeGroup($promoCodeGroup);
            promoCodeGroup.init();
        }
    });

})(jQuery);
