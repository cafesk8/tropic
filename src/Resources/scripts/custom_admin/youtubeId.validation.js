(function () {
    Shopsys.register.registerCallback(function ($container) {
        $container.filterAllNodes('.js-video-id').jsFormValidator({
            callbacks: {
                'validateYoutubeVideo': function () {
                }
            }
        });
    });

})();
