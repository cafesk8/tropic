import 'jquery-ui/slider';

import { parseNumber, formatDecimalNumber } from 'framework/common/utils/number';
import Register from 'framework/common/utils/register';

export default class RangeSlider {

    constructor ($sliderElement) {
        this.$sliderElement = $sliderElement;
        this.$minimumInput = $('#' + this.$sliderElement.data('minimumInputId'));
        this.$maximumInput = $('#' + this.$sliderElement.data('maximumInputId'));
        this.minimalValue = parseNumber(this.$sliderElement.data('minimalValue'));
        this.maximalValue = parseNumber(this.$sliderElement.data('maximalValue'));
        this.steps = 100;
        this.$formattedMinimumInput = $('#' + this.$sliderElement.data('minimumInputId') + 'Formatted');
        this.$formattedMaximumInput = $('#' + this.$sliderElement.data('maximumInputId') + 'Formatted');
        this.$formattedMinimumInput.val(RangeSlider.getFormattedInt(this.$minimumInput.val()));
        this.$formattedMaximumInput.val(RangeSlider.getFormattedInt(this.$maximumInput.val()));
    }

    static updateSliderMinimum (rangeSlider) {
        const value = parseNumber(rangeSlider.$minimumInput.val()) || rangeSlider.minimalValue;
        const step = rangeSlider.getStepFromValue(value);
        rangeSlider.$sliderElement.slider('values', 0, step);
    }

    static updateSliderMaximum (rangeSlider) {
        const value = parseNumber(rangeSlider.$maximumInput.val()) || rangeSlider.maximalValue;
        const step = rangeSlider.getStepFromValue(value);
        rangeSlider.$sliderElement.slider('values', 1, step);
    }

    getStepFromValue (value) {
        return Math.round((value - this.minimalValue) / (this.maximalValue - this.minimalValue) * this.steps);
    }

    getValueFromStep (step) {
        return this.minimalValue + (this.maximalValue - this.minimalValue) * step / this.steps;
    }

    static getIntFromLocaleString (text) {
        switch (window.currentDomainLocale) {
            case 'de':
                return parseInt(text.replace('.', '').replace(/\s/g, '').replace(',', '.'));
            case 'en':
                return parseInt(text.replace(',', '').replace(/\s/g, ''));
            case 'cs':
            case 'sk':
            default:
                return parseInt(text.replace(',', '.').replace(/\s/g, ''));
        }
    }

    static getFormattedInt (text) {
        if (text) {
            return parseInt(text).toLocaleString(window.currentDomainLocale);
        }

        return '';
    }

    static init ($container) {
        $container.filterAllNodes('.js-range-slider').each(function () {
            let lastMinimumInputValue;
            let lastMaximumInputValue;

            const rangeSlider = new RangeSlider($(this));

            rangeSlider.$sliderElement.slider({
                range: true,
                min: 0,
                max: rangeSlider.steps,
                start: function () {
                    lastMinimumInputValue = rangeSlider.$minimumInput.val();
                    lastMaximumInputValue = rangeSlider.$maximumInput.val();
                },
                slide: function (event, ui) {
                    const minimumSliderValue = rangeSlider.getValueFromStep(ui.values[0]);
                    const maximumSliderValue = rangeSlider.getValueFromStep(ui.values[1]);
                    rangeSlider.$minimumInput.val(minimumSliderValue !== rangeSlider.minimalValue ? formatDecimalNumber(minimumSliderValue) : '');
                    rangeSlider.$formattedMinimumInput.val(RangeSlider.getFormattedInt(rangeSlider.$minimumInput.val()));
                    rangeSlider.$maximumInput.val(maximumSliderValue !== rangeSlider.maximalValue ? formatDecimalNumber(maximumSliderValue) : '');
                    rangeSlider.$formattedMaximumInput.val(RangeSlider.getFormattedInt(rangeSlider.$maximumInput.val()));
                },
                stop: function () {
                    if (lastMinimumInputValue !== rangeSlider.$minimumInput.val()) {
                        rangeSlider.$minimumInput.change();
                    }
                    if (lastMaximumInputValue !== rangeSlider.$maximumInput.val()) {
                        rangeSlider.$maximumInput.change();
                    }
                }
            });

            rangeSlider.$minimumInput.change(() => RangeSlider.updateSliderMinimum(rangeSlider));
            rangeSlider.$formattedMinimumInput.change(() => {
                let minValue = RangeSlider.getIntFromLocaleString(rangeSlider.$formattedMinimumInput.val());
                console.log(minValue);
                rangeSlider.$formattedMinimumInput.val(RangeSlider.getFormattedInt(minValue));
                rangeSlider.$minimumInput.val(minValue);
                RangeSlider.updateSliderMinimum(rangeSlider);
            });
            RangeSlider.updateSliderMinimum(rangeSlider);

            rangeSlider.$maximumInput.change(() => RangeSlider.updateSliderMaximum(rangeSlider));
            rangeSlider.$formattedMaximumInput.change(() => {
                let maxValue = RangeSlider.getIntFromLocaleString(rangeSlider.$formattedMaximumInput.val());
                rangeSlider.$formattedMaximumInput.val(RangeSlider.getFormattedInt(maxValue));
                rangeSlider.$maximumInput.val(maxValue);
                RangeSlider.updateSliderMaximum(rangeSlider);
            });
            RangeSlider.updateSliderMaximum(rangeSlider);
        });
    }
}

(new Register()).registerCallback(RangeSlider.init);
