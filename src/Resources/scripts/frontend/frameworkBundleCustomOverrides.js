(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.validation = Shopsys.validation || {};

    Shopsys.validation.showFormErrorsWindow = function (container) {
        if ($(container).hasClass('js-no-popup') === false) {
            Shopsys.window({
                content:
                      '        <div class="box-popup-add__content__title">'
                    + '            <span class="box-popup-add__content__title__text double-lined">'
                    + Shopsys.translator.trans('Upozornění')
                    + '            </span>'
                    + '        </div>'
                    + '        <ul class="list-simple">'
                    + '            <li class="list-simple__item">'
                    + Shopsys.translator.trans('Prosím, zkontrolujte zadané hodnoty')
                    + '            </li>'
                    + '        </ul>'
                    + '        <div class="box-popup-add__action__buttons__back">'
                    + '            <i class="svg svg-triangle"></i>'
                    + '            <a href="#" class="box-popup-add__action__buttons__back__link underline-small js-window-button-close">'
                    + Shopsys.translator.trans('Zpět do e-shopu')
                    + '            </a>'
                    + '        </div>'
                    + '    </div>'
            });
        } else {
            $('.js-window-validation-errors').css('display', 'none');
        }
    };
})(jQuery);
