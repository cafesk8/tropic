import Ajax from 'framework/common/utils/Ajax';
import Register from 'framework/common/utils/Register';

export default function hoverIntentReload ($intentElement) {
    const reloadUrl = $intentElement.data('after-callback-url');
    const replaceSelector = $intentElement.data('after-replace-selector');
    const $replacedContent = $($intentElement.data('replaced-content-selector'));
    const alreadyLoaded = $replacedContent.length > 0;
    if (reloadUrl && (alreadyLoaded === false)) {
        Ajax.ajax({
            url: reloadUrl,
            loaderElement: replaceSelector,
            type: 'GET',
            success: function (html) {
                let $html = $($.parseHTML(html));

                $($intentElement.data('after-replace-selector')).html($html);
                (new Register()).registerNewContent($html);
            }
        });
    }
}
