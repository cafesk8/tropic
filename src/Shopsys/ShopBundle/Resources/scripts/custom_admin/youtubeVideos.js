(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.youtubeVideos = Shopsys.youtubeVideos || {};

    Shopsys.youtubeVideos.NewProductVideo = function ($entitYoutubeVideo) {
        var $buttonAdd = $entitYoutubeVideo.find('.js-youtube-videos-new-btn');
        var $youtubeVideosContainer = $entitYoutubeVideo.find('.js-youtube-videos-list');
        var newVideoId = $youtubeVideosContainer.attr('id');

        this.init = function () {
            $buttonAdd.click(addNewProductVideo);
            $entitYoutubeVideo.on('click', '.js-youtube-video-delete-btn', onClickRemoveYoutubeVideo);
        };

        var addNewProductVideo = function () {
            var prototype = $youtubeVideosContainer.data('prototype');
            var index = getNextNewProductVideoIndex();
            var newProductVideo = prototype.replace(/__name__/g, index);
            var $newProductVideo = $($.parseHTML(newProductVideo));
            $newProductVideo.attr('data-index', index);
            $youtubeVideosContainer.append($newProductVideo);
            Shopsys.register.registerNewContent($newProductVideo);

            Shopsys.validation.addNewItemToCollection('#' + newVideoId, index);
        };

        var getNextNewProductVideoIndex = function () {
            var index = 0;
            while ($youtubeVideosContainer.find('.js-youtube-videos-list-row[data-index=' + index.toString() + ']').length > 0) {
                index++;
            }
            return index;
        };

        var onClickRemoveYoutubeVideo = function () {
            var $row = $(this).closest('.js-youtube-videos-list-row');
            var index = $row.data('index');
            Shopsys.validation.removeItemFromCollection('#' + newVideoId, index);
            $row.remove();
        };
    };

    Shopsys.register.registerCallback(function ($container) {
        $container.filterAllNodes('.js-youtube-videos-list').each(function () {
            var entityProductVideoNewProductVideo = new Shopsys.youtubeVideos.NewProductVideo($(this));
            entityProductVideoNewProductVideo.init();
        });
    });

})(jQuery);
