import Register from 'framework/common/utils/register';
import 'selectric';

(function ($) {
    (new Register()).registerCallback(function ($container) {
        $container.filterAllNodes('select').selectric({
            arrowButtonMarkup: '<b class="button"><i class="svg svg-arrow-thin"></i></b>'
        });
    });
})(jQuery);