import Window from '../utils/Window';
import Translator from 'bazinga-translator';

(function ($) {

    const Shopsys = window.Shopsys || {};
    Shopsys.validation = Shopsys.validation || {};

    Shopsys.validation.showFormErrorsWindow = function (container) {
        if ($(container).hasClass('js-no-popup') === false) {
            /* eslint-disable no-new */
            new Window({
                content:
                      '        <div class="box-popup-add__content__title">'
                    + '            <span class="box-popup-add__content__title__text double-lined">'
                    + Translator.trans('Upozornění')
                    + '            </span>'
                    + '        </div>'
                    + '        <ul class="list-simple">'
                    + '            <li class="list-simple__item">'
                    + Translator.trans('Prosím, zkontrolujte zadané hodnoty')
                    + '            </li>'
                    + '        </ul>'
                    + '        <div class="box-popup-add__action__buttons__back">'
                    + '            <i class="svg svg-triangle"></i>'
                    + '            <a href="#" class="box-popup-add__action__buttons__back__link js-window-button-close">'
                    + Translator.trans('Zpět do e-shopu')
                    + '            </a>'
                    + '        </div>'
                    + '    </div>'
            });
        } else {
            $('.js-window-validation-errors').css('display', 'none');
        }
    };
})(jQuery);
