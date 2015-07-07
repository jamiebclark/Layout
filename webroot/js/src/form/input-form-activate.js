(function($) {
	$.fn.inputFormActivate = function() {
		function updateForm($form, $input) {
			if ($input.is(':checked')) {
				$form.removeClass('form-inactive');
			} else {
				$form.addClass('form-inactive');
			}
		}

		return this.each(function() {
			var $input = $(this),
				$form = $input.closest('form');

			if (!$input.data('form-activate-init')) {
				$input.click(function(e) {
					updateForm($form, $input);
				});
				updateForm($form, $input);
				$input.data('form-activate-init');
			}
		});
	};

	documentReady(function() {
		$(':input[class*="form-activate"]').inputFormActivate();
	});
})(jQuery);
