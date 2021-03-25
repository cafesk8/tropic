import Register from 'framework/common/utils/Register';

(function ($) {
    const Shopsys = window.Shopsys || {};
    Shopsys.luigisBox = Shopsys.luigisBox || {};

    Shopsys.luigisBox.init = function () {
        const $dataElement = $('#search-ui-data');

        if (typeof Luigis != 'undefined' && typeof Luigis.Search != 'undefined') {
            Luigis.Search({
                TrackerId: $dataElement.data('tracker-id'),
                Theme: 'luigis',
                Size: 24,
                Facets: ['price_amount', 'flags', 'brand'],
                UrlParamName: {
                    QUERY: 'q'
                },
                DefaultFilters: {
                    type: ['product']
                },
                QuicksearchTypes: ['category', 'set'],
                Locale: $dataElement.data('locale'),
                PriceFilter: {
                    decimals: 0,
                    symbol: $dataElement.data('currency-symbol')
                },
                OnDone: function () {
                    new Register().registerNewContent($('#lb-search-element'));
                }
            }, '[name="q"]', '#search-ui');
        }
    };

    new Register().registerCallback(Shopsys.luigisBox.init);
})(jQuery);
