(function($) {

	$.fn.formSubmittedOverlay = function() {
		return this.each(function() {
			var $form = $(this);
			if ($form.data('submitted-overlay-init')) {
				return $form;
			}

			$form.submit(function(e) {
				var padding = 20,
					$form = $(this).addClass('submitted-overlay-submitted'),
					$mask = $('<div class="submitted-overlay-mask"></div>')
						.css({
							'width': $form.outerWidth() + 2 * padding,
							'height': $form.outerHeight() + 2 * padding,
							'top': padding * -1,
							'left': padding * -1
						})
						.appendTo($form);

				var $content = $('<div class="submitted-overlay-mask-content"></div>')
						.append($('<h2><i class="fa fa-spinner fa-spin"></i> Loading</h2>'))
						.appendTo($mask);
						//.animatedEllipsis())

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
			});
			$form.data('submitted-overlay-init', true);

			return $form;
		});
	};

	documentReady(function() {
		$('form.submitted-overlay').formSubmittedOverlay();
	});

})(jQuery);
