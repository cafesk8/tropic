import Register from 'framework/common/utils/Register';
import Window from 'framework/admin/utils/Window';

new Register().registerCallback(function ($container) {
    $container.filterAllNodes('.js-window-opener-admin').on('click', function (event) {
        const $target = $(event.currentTarget);
        const content = $target.data('content');
        const textContinue = $target.data('text-continue');
        const urlContinue = $target.data('url-continue');
        /* eslint-disable no-new */
        new Window({
            content: content,
            buttonCancel: true,
            buttonContinue: true,
            textContinue: textContinue,
            urlContinue: urlContinue
        });

        return false;
    });
});
