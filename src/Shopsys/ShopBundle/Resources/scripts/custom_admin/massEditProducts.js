(function ($) {

    Shopsys = Shopsys || {};
    Shopsys.massEdit = Shopsys.massEdit || {};

    Shopsys.massEdit.init = function ($container) {

        $container.filterAllNodes('#js-mass-edit-button').click(function () {
            $('#js-mass-edit').toggleClass('active');
        });

        var $form = $container.find('.js-mass-edit-form-wrapper form');
        var $partialWrapper = $form.find('.js-mass-edit-form-partial');

        var url = $partialWrapper.data('ajax-url');

        var replaceFormByAjax = function (data) {
            Shopsys.ajax({
                loaderElement: '.js-mass-edit-form-wrapper form',
                url: url,
                method: 'POST',
                data: data,
                success: function (responseData) {
                    $partialWrapper.html(responseData);
                    Shopsys.register.registerNewContent($partialWrapper);
                }
            });
        };

        $form.on('change', '.js-mass-edit-subject', function () {
            replaceFormByAjax({
                selectedSubjectName: $partialWrapper.find('.js-mass-edit-subject option:selected').attr('value')
            });
        });

        $form.on('change', '.js-mass-edit-operation', function () {
            replaceFormByAjax({
                selectedSubjectName: $partialWrapper.find('.js-mass-edit-subject option:selected').attr('value'),
                selectedOperationName: $partialWrapper.find('.js-mass-edit-operation option:selected').attr('value')
            });
        });
    };

    Shopsys.register.registerCallback(Shopsys.massEdit.init);

})(jQuery);
