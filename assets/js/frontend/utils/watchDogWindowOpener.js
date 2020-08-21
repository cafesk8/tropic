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
                    eventOnLoad: function () {
                        const $availabilityWatcher = $('#watch_dog_form_availabilityWatcher');
                        const $priceWatcher = $('#watch_dog_form_priceWatcher');
                        const $submitButton = $('.js-watch-dog-submit');
                        $priceWatcher.change(function () {
                            if ($(this).is(':checked')) {
                                $('.js-target-price-input-line').show();
                            } else {
                                $('.js-target-price-input-line').hide();
                            }
                        });
                        $('#watch_dog_form_priceWatcher, #watch_dog_form_availabilityWatcher').change(function () {
                            if ($availabilityWatcher.is(':checked') || $priceWatcher.is(':checked')) {
                                $submitButton.removeAttr('disabled');
                            } else {
                                $submitButton.attr('disabled', 'disabled');
                            }
                        });
                    }
                });
            }
        });

        return false;
    });
});
