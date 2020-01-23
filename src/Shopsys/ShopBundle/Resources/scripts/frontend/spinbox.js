(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.spinbox = Shopsys.spinbox || {};

    Shopsys.spinbox.init = function ($container) {
        $container.filterAllNodes('.js-spinbox').each(Shopsys.spinbox.bindSpinbox);
    };

    Shopsys.spinbox.bindSpinbox = function () {
        var $input = $(this).find('input.js-spinbox-input');
        var $plus = $(this).find('.js-spinbox-plus');
        var $minus = $(this).find('.js-spinbox-minus');

        $input
            .bind('spinbox.plus', Shopsys.spinbox.plus)
            .bind('spinbox.minus', Shopsys.spinbox.minus);

        $input.change(function () {
            var value = $(this).val();
            var min = $(this).data('spinbox-min');
            var max = $(this).data('spinbox-max');
            var step = $(this).data('spinbox-step');

            if (min < step) {
                min = step;
            }

            if (!value.match(/^\d+$/)) {
                value = min;
            }

            if (step === undefined || Math.abs(step) < 1) {
                step = 1;
            }

            if (value % step !== 0) {
                value = Math.floor(value / step) * step;
            }

            if (min !== undefined && min > value) {
                value = min;
            }

            if (max !== undefined && max < value) {
                value = max;
            }

            $(this).val(value);
        }).change();

        $plus
            .bind('mousedown.spinbox', function (e) {
                repeater.startAutorepeat($input, 'spinbox.plus');
            })
            .bind('mouseup.spinbox mouseout.spinbox', function (e) {
                repeater.stopAutorepeat();
            });

        $minus
            .bind('mousedown.spinbox', function (e) {
                repeater.startAutorepeat($input, 'spinbox.minus');
            })
            .bind('mouseup.spinbox mouseout.spinbox', function (e) {
                repeater.stopAutorepeat();
            });
    };

    Shopsys.spinbox.plus = function () {
        var value = $.trim($(this).val());
        var step = $(this).data('spinbox-step');
        if (step === undefined || Math.abs(step) < 1) {
            step = 1;
        }
        if (value.match(/^\d+$/)) {
            value = parseInt(value) + step;
            $(this).val(value);
            $(this).change();
        }
    };

    Shopsys.spinbox.minus = function () {
        var value = $.trim($(this).val());
        var step = $(this).data('spinbox-step');
        if (step === undefined || Math.abs(step) < 1) {
            step = 1;
        }
        if (value.match(/^\d+$/)) {
            value = parseInt(value) - step;
            $(this).val(value);
            $(this).change();
        }
    };

    var repeater = {
        timerDelay: null,
        timerRepeat: null,

        startAutorepeat: function ($input, eventString) {
            $input.trigger(eventString);
            repeater.stopAutorepeat();
            repeater.timerDelay = setTimeout(function () {
                $input.trigger(eventString);
                repeater.timerRepeat = setInterval(function () {
                    $input.trigger(eventString);
                }, 100);
            }, 500);
        },

        stopAutorepeat: function () {
            clearTimeout(repeater.timerDelay);
            clearInterval(repeater.timerRepeat);
        }
    };

    Shopsys.register.registerCallback(Shopsys.spinbox.init);

})(jQuery);
