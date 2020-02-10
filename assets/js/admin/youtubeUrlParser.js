import Register from 'framework/common/utils/register';

(function ($) {

    const Shopsys = Shopsys || {};
    Shopsys.youtubeUrlParser = Shopsys.youtubeUrlParser || {};

    Shopsys.youtubeUrlParser.YoutubeUrlParser = function ($container) {

        this.init = function () {
            const $youtubeVideoIdElement = $container.filterAllNodes('.js-video-id');
            $youtubeVideoIdElement.on('change', this.parseUrl);
        };

        this.parseUrl = function () {
            const youtubeId = $(this).val();
            if (youtubeId.startsWith('http') === true) {
                let youtubeUrlId = null;

                // eslint-disable-next-line
                const urlMatches = youtubeId.match(/\?v=([^&]*)/); // https://regex101.com/r/SyNE0I/3
                if (urlMatches !== null && urlMatches.length > 0) {
                    youtubeUrlId = urlMatches[1];
                }

                if (youtubeUrlId !== null) {
                    $(this).val(youtubeUrlId);
                }
            }
        };
    };

    new Register().registerCallback(function ($container) {
        const youtubeUrlParser = new Shopsys.youtubeUrlParser.YoutubeUrlParser($container);
        youtubeUrlParser.init();
    });

})(jQuery);
