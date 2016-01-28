(function($) {

	var maskPadding = 0;

	$.fn.formSubmittedOverlay = function() {
		return this.each(function() {
			var $form = $(this);

			if ($form.data('submitted-overlay-init')) {
				return $form;
			}

			$form.submit(function(e) {
				var $form = $(this).addClass('submitted-overlay-submitted'),
					$mask = $('<div class="submitted-overlay-mask"></div>')
						.css({
							'width': $form.outerWidth() + 2 * maskPadding,
							'height': $form.outerHeight() + 2 * maskPadding,
							'top': maskPadding * -1,
							'left': maskPadding * -1
						})
						.appendTo($form),
					$message = $('<div class="submitted-overlay-mask-message"></div>').appendTo($mask),
					$icon = $('<div class="submitted-overlay-mask-message-icon"><i class="fa fa-spinner fa-spin"></i></div>')
						.appendTo($message),
					$title = $('<div class="submitted-overlay-mask-message-title"></div>').appendTo($message),
					$content = $('<div class="submitted-overlay-mask-message-content"></div>').appendTo($message);

				$message.append('<h2>Loading</h2>');

				function getOverlayUrl(getUrl, refresh) {
					$.ajax({
						//type: 'POST',
						url: getUrl
					}).done(function(data) {
						if (data !== '') {
							$content.html(data);
						}
						if (refresh) {
							setTimeout(function() {
								getOverlayUrl(getUrl, refresh)
							}, refresh);
						}
					});
				}

				if ($form.data('submitted-overlay-url')) {
					getOverlayUrl($form.data('submitted-overlay-url'), $form.data('submitted-overlay-refresh'));
				}
								
				$(':submit', $form).each(function() {
					$(this).prop('disabled', true).html('Loading...');
				});
				return false;
			});
			$form.data('submitted-overlay-init', true);

			return $form;
		});
	};

	$(document).bind('ready ajaxComplete', function() {
		$('form.submitted-overlay').formSubmittedOverlay();
	});
})(jQuery);