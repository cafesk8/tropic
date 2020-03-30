import Register from 'framework/common/utils/Register';
import { addNewItemToCollection, removeItemFromCollection } from 'framework/admin/validation/customization/customizeCollectionBundle';

(function ($) {

    const Shopsys = window.Shopsys || {};
    Shopsys.youtubeVideos = Shopsys.youtubeVideos || {};

    Shopsys.youtubeVideos.NewProductVideo = function ($entitYoutubeVideo) {
        const $buttonAdd = $entitYoutubeVideo.find('.js-youtube-videos-new-btn');
        const $youtubeVideosContainer = $entitYoutubeVideo.find('.js-youtube-videos-list');
        const newVideoId = $youtubeVideosContainer.attr('id');

        this.init = function () {
            $buttonAdd.click(addNewProductVideo);
            $entitYoutubeVideo.on('click', '.js-youtube-video-delete-btn', onClickRemoveYoutubeVideo);
        };

        const addNewProductVideo = function () {
            const prototype = $youtubeVideosContainer.data('prototype');
            const index = getNextNewProductVideoIndex();
            const newProductVideo = prototype.replace(/__name__/g, index);
            const $newProductVideo = $($.parseHTML(newProductVideo));
            $newProductVideo.attr('data-index', index);
            $youtubeVideosContainer.append($newProductVideo);
            new Register().registerNewContent($newProductVideo);

            addNewItemToCollection('#' + newVideoId, index);
        };

        const getNextNewProductVideoIndex = function () {
            let index = 0;
            while ($youtubeVideosContainer.find('.js-youtube-videos-list-row[data-index=' + index.toString() + ']').length > 0) {
                index++;
            }
            return index;
        };

        const onClickRemoveYoutubeVideo = function (event) {
            const $row = $(event.currentTarget).closest('.js-youtube-videos-list-row');
            const index = $row.data('index');
            removeItemFromCollection('#' + newVideoId, index);
            $row.remove();
        };
    };

    new Register().registerCallback(function ($container) {
        $container.filterAllNodes('.js-youtube-videos-list').each(function () {
            const entityProductVideoNewProductVideo = new Shopsys.youtubeVideos.NewProductVideo($(this));
            entityProductVideoNewProductVideo.init();
        });
    });

})(jQuery);
