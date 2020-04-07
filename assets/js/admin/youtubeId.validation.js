import Register from 'framework/common/utils/Register';

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
