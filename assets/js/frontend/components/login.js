import Ajax from 'framework/common/utils/Ajax';
import Register from 'framework/common/utils/Register';
import { createLoaderOverlay, showLoaderOverlay } from 'framework/common/utils/loaderOverlay';

(function ($) {

    const Shopsys = window.Shopsys || {};
    Shopsys.login = Shopsys.login || {};

    Shopsys.login.Login = function () {

        this.init = function ($loginForm) {
            $loginForm.on('submit', onSubmit);
        };

        function onSubmit () {
            Ajax.ajax({
                loaderElement: '.js-login-box-overlay',
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function (data) {
                    if (data.success === true) {
                        const $loaderOverlay = createLoaderOverlay('.js-login-box-overlay');
                        showLoaderOverlay($loaderOverlay);

                        document.location = data.urlToRedirect;
                    } else {
                        const $validationErrors = $('.js-login-validation-errors');
                        if ($validationErrors.hasClass('display-none')) {
                            $validationErrors
                                .html(data.error_message)
                                .show();
                        }

                    }
                }
            });
            return false;
        }

    };

    new Register().registerCallback(function ($container) {
        $container.filterAllNodes('.js-front-login-window').each(function () {
            const $login = new Shopsys.login.Login();
            $login.init($(this));
        });
    });

})(jQuery);
