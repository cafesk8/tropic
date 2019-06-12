(function ($) {

    Shopsys = Shopsys || {};
    Shopsys.massEditConfirm = Shopsys.massEditConfirm || {};

    var isConfirmed = false;

    Shopsys.massEditConfirm.init = function ($container) {
        $container.filterAllNodes('.js-mass-edit-submit').click(function () {
            var $button = $(this);
            if (!isConfirmed) {
                var selectType = $('.js-mass-edit-select-type').val();
                var count;
                switch (selectType) {
                    case Shopsys.constant('\\Shopsys\\ShopBundle\\Model\\Product\\MassEdit\\MassEditFacade::SELECT_TYPE_CHECKED'):
                        count = $('.js-grid-mass-action-select-row:checked').length;
                        break;
                    case Shopsys.constant('\\Shopsys\\ShopBundle\\Model\\Product\\MassEdit\\MassEditFacade::SELECT_TYPE_ALL_RESULTS'):
                        count = $('.js-grid').data('total-count');
                        break;
                }

                var subject = $('.js-mass-edit-subject option:selected').text().toLowerCase();
                var operation = $('.js-mass-edit-operation option:selected').text().toLowerCase();

                Shopsys.window({
                    content: Shopsys.translator.trans(
                        'Opravdu chcete %operation% (%subject%) u %count% produktů?',
                        { '%operation%': operation, '%subject%': subject, '%count%': count }
                    ),
                    buttonCancel: true,
                    buttonContinue: true,
                    eventContinue: function () {
                        isConfirmed = true;
                        $button.trigger('click');
                    }
                });

                return false;
            }
        });

    };

    Shopsys.register.registerCallback(Shopsys.massEditConfirm.init);

})(jQuery);
