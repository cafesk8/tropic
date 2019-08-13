(function ($) {

    Shopsys = Shopsys || {};
    Shopsys.youtubeUrlParser = Shopsys.youtubeUrlParser || {};

    Shopsys.youtubeUrlParser.YoutubeUrlParser = function ($container) {

        this.init = function () {
            var $youtubeVideoIdElement = $container.filterAllNodes('.js-video-id');
            $youtubeVideoIdElement.on('change', this.parseUrl);
        };

        this.parseUrl = function () {
            var youtubeId = $(this).val();
            if (youtubeId.startsWith('http') === true) {
                var youtubeUrlId = null;

                // eslint-disable-next-line
                var urlMatches = youtubeId.match(/\?v=([^&]*)/); // https://regex101.com/r/SyNE0I/3
                if (urlMatches !== null && urlMatches.length > 0) {
                    youtubeUrlId = urlMatches[1];
                }

                if (youtubeUrlId !== null) {
                    $(this).val(youtubeUrlId);
                }
            }
        };
    };

    Shopsys.register.registerCallback(function ($container) {
        var youtubeUrlParser = new Shopsys.youtubeUrlParser.YoutubeUrlParser($container);
        youtubeUrlParser.init();
    });

})(jQuery);
