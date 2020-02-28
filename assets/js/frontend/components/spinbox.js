import Repeater from './repeater';
import Register from 'framework/common/utils/Register';

export default class Spinbox {

    static bindSpinbox () {
        const $input = $(this).find('input.js-spinbox-input');
        const $plus = $(this).find('.js-spinbox-plus');
        const $minus = $(this).find('.js-spinbox-minus');
        const repeater = new Repeater();

        $input
            .on('spinbox.plus', Spinbox.plus)
            .on('spinbox.minus', Spinbox.minus)
            .on('change', Spinbox.checkValue);
        $input.ready(function () {
            $input.change();
        });

        $plus
            .on('mousedown.spinbox', function (e) {
                repeater.startAutorepeat($input, 'spinbox.plus');
            })
            .on('mouseup.spinbox mouseout.spinbox', function (e) {
                repeater.stopAutorepeat();
            });

        $minus
            .on('mousedown.spinbox', function (e) {
                repeater.startAutorepeat($input, 'spinbox.minus');
            })
            .on('mouseup.spinbox mouseout.spinbox', function (e) {
                repeater.stopAutorepeat();
            });

    }

    static checkValue (event) {
        let value = $(event.currentTarget).val();
        let min = $(event.currentTarget).data('spinbox-min');
        let step = $(event.currentTarget).data('spinbox-step');

        if (min < step) {
            min = step;
        }

        if (!value.match(/^\d+$/)) {
            value = min;
        }

        if (value % step !== 0) {
            value = Math.floor(value / step) * step;
        }

        if (min !== undefined && min > value) {
            value = min;
        }

        $(event.currentTarget).val(value);
    }

    static plus () {
        let value = $.trim($(this).val());
        let max = $(this).data('spinbox-max');
        const step = $(this).data('spinbox-step');

        if (value.match(/^\d+$/)) {
            value = parseInt(value) + step;
            if (max !== undefined && max < value) {
                value = max;
            }
            $(this).val(value);
            $(this).change();
        }
    }

    static minus () {
        let value = $.trim($(this).val());
        let min = $(this).data('spinbox-min');
        const step = $(this).data('spinbox-step');

        if (value.match(/^\d+$/)) {
            value = parseInt(value) - step;
            if (min !== undefined && min > value) {
                value = min;
            }
            $(this).val(value);
            $(this).change();
        }
    }

    static init ($container) {
        $container.filterAllNodes('.js-spinbox').each(Spinbox.bindSpinbox);
    }
}

(new Register()).registerCallback(Spinbox.init);
