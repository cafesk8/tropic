import Register from 'framework/common/utils/Register';
import Translator from 'bazinga-translator';
import AjaxMoreLoader from '../components/ajaxMoreLoader';

(function ($) {

    /* eslint-disable no-new */
    (new Register()).registerCallback(function ($container) {
        $container.filterAllNodes('.js-blog-list-with-paginator').each(function () {
            new AjaxMoreLoader($(this), {
                buttonTextCallback: function (loadNextCount) {
                    return Translator.transChoice(
                        '{1}Načíst další %loadNextCount% článek|[2,4]Načíst další %loadNextCount% články|[5,Inf]Načíst dalších %loadNextCount% článků',
                        loadNextCount,
                        { 'loadNextCount': loadNextCount }
                    );
                }
            });
        });
    });

})(jQuery);
