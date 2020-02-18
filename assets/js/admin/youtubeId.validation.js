import Register from 'framework/common/utils/register';

(function () {
    new Register().registerCallback(function ($container) {
        $container.filterAllNodes('.js-video-id').jsFormValidator({
            callbacks: {
                'validateYoutubeVideo': function () {
                }
            }
        });
    });

})();
