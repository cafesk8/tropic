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
        const $currentTarget = $(event.currentTarget);
        const $currentParent = $currentTarget.closest('.js-spinbox-parent');
        const $minus = $currentParent.find('.js-spinbox-minus');
        const $plus = $currentParent.find('.js-spinbox-plus');
        const $warning = $currentTarget.closest('.js-item-container').find('.js-maximum-amount-warning');

        let value = $currentTarget.val();
        let min = $currentTarget.data('spinbox-min');
        let step = $currentTarget.data('spinbox-step');
        let max = $currentTarget.data('spinbox-max');

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

        if (max !== undefined && max > 0 && max < value) {
            value = max;
            $currentTarget.closest('.js-item-container').find('.js-maximum-amount-warning').show();
        }

        if (max !== undefined && value < max) {
            $currentTarget.closest('.js-item-container').find('.js-maximum-amount-warning').hide();
        }

        if (value.toString() === max.toString()) {
            $plus.data('disabled', 'disabled').addClass('btn--disabled');
        } else {
            $plus.data('disabled', '').removeClass('btn--disabled');
            $warning.hide();
        }

        if (value.toString() === min.toString()) {
            $minus.data('disabled', 'disabled').addClass('btn--disabled');
        } else {
            $minus.data('disabled', '').removeClass('btn--disabled');
        }

        $currentTarget.val(value);
    }

    static plus (event) {
        const $currentTarget = $(event.currentTarget);
        const $currentParent = $currentTarget.closest('.js-spinbox-parent');
        const $plus = $currentParent.find('.js-spinbox-plus');
        const $warning = $currentTarget.closest('.js-item-container').find('.js-maximum-amount-warning');

        if ($plus.data('disabled') === 'disabled') {
            $warning.show();
        } else {
            let value = $.trim($currentTarget.val());
            let max = $currentTarget.data('spinbox-max');
            const step = $currentTarget.data('spinbox-step');

            if (value.match(/^\d+$/)) {
                value = parseInt(value) + step;
                if (max !== undefined && max < value) {
                    value = max;
                }

                if (value === max) {
                    $plus.data('disabled', 'disabled').addClass('btn--disabled');
                }

                $currentTarget.val(value);
                $currentTarget.change();
            }
        }
    }

    static minus (event) {
        const $currentTarget = $(event.currentTarget);

        let value = $.trim($currentTarget.val());
        let min = $currentTarget.data('spinbox-min');
        const step = $currentTarget.data('spinbox-step');

        if (value.match(/^\d+$/)) {
            value = parseInt(value) - step;
            if (min !== undefined && min > value) {
                value = min;
            }
            $currentTarget.val(value);
            $currentTarget.change();
        }
    }

    static init ($container) {
        $container.filterAllNodes('.js-spinbox').each(Spinbox.bindSpinbox);
    }
}

(new Register()).registerCallback(Spinbox.init);
