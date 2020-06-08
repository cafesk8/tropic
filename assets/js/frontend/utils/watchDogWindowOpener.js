import Register from 'framework/common/utils/Register';
import Window from './Window';
import Ajax from 'framework/common/utils/Ajax';

new Register().registerCallback(function ($container) {
    $container.filterAllNodes('.js-watch-dog-window-opener').on('click', function (event) {
        const $target = $(event.currentTarget);
        Ajax.ajax({
            type: 'GET',
            url: $target.data('url'),
            success: function (data) {
                /* eslint-disable no-new */
                new Window({
                    content: data.substring(data.indexOf('</h2>')),
                    cssClass: 'window-popup--wide',
                    textHeading: $($.parseHTML(data)).filterAllNodes('.js-watch-dog-heading').text()
                });
            }
        });

        return false;
    });
});
