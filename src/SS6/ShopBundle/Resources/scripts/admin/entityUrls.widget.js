(function ($) {

	SS6 = window.SS6 || {};
	SS6.entityUrls = SS6.entityUrls || {};

	SS6.entityUrls.Widget = function ($entityUrls) {
		var $buttonAdd = $entityUrls.find('.js-entity-urls-button-add-url');

		this.init = function () {
			$buttonAdd.click(loadWindow);
			$entityUrls.on('click', '.js-entity-urls-new-row-delete-button', onClickRemoveNewUrl);
		};

		var loadWindow = function () {
			$.ajax({
				url: $buttonAdd.data('window-content-ajax-url'),
				success: openWindow
			});
		};

		var openWindow = function (windowContentHtml) {
			var $window = SS6.window({
				content: windowContentHtml
			});
			var newUrlWindow = new SS6.entityUrls.NewUrlWindow($window, $entityUrls);
			newUrlWindow.init();
		};

		var onClickRemoveNewUrl = function () {
			$(this).closest('.js-entity-urls-new-row').remove();
		};
	};

	$(document).ready(function () {
		$('.js-entity-urls').each(function () {
			var entityUrlsWidget = new SS6.entityUrls.Widget($(this));
			entityUrlsWidget.init();
		});
	});

})(jQuery);
