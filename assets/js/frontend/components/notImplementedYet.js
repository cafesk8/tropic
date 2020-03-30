import Register from 'framework/common/utils/Register';
import Translator from 'bazinga-translator';

(function ($) {

    (new Register()).registerCallback(function ($container) {
        $container.filterAllNodes('.js-not-implemented-yet')
            .attr('title', Translator.trans('Ještě nebylo implementováno.'))
            .tooltip();
    });

})(jQuery);
