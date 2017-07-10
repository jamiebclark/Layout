(function($) {
	$.fn.inputGroupCash = function() {
		function numberSanitize(val) {
			return val.replace(/[^0-9\.\-]/g,'');
		}
		return this.each(function() {
			var $input = $(this);
			$input.change(function() {
				$input.val(numberSanitize($input.val()));
			});
			return $input;
		});
	};
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
		$(':input.input-group-cash').inputGroupCash();
	});
	
})(jQuery);