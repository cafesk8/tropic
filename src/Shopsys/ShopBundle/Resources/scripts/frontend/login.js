(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.login = Shopsys.login || {};

    Shopsys.login.Login = function () {

        this.init = function ($loginForm) {
            $loginForm.on('submit', onSubmit);
        };

        function onSubmit () {
            Shopsys.ajax({
                loaderElement: '.js-login-box-overlay',
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function (data) {
                    if (data.success === true) {
                        var $loaderOverlay = Shopsys.loaderOverlay.createLoaderOverlay('.js-login-box-overlay');
                        Shopsys.loaderOverlay.showLoaderOverlay($loaderOverlay);

                        document.location = data.urlToRedirect;
                    } else {
                        var $validationErrors = $('.js-login-validation-errors');
                        if ($validationErrors.hasClass('display-none')) {
                            $validationErrors
                                .text(Shopsys.translator.trans('This account doesn\'t exist or password is incorrect'))
                                .show();
                        }

                    }
                }
            });
            return false;
        }

    };

    Shopsys.register.registerCallback(function ($container) {
        $container.filterAllNodes('.js-front-login-window').each(function () {
            var $login = new Shopsys.login.Login();
            $login.init($(this));
        });
    });

})(jQuery);
