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

	documentReady(function() {
		$('input.input-group-cash').inputGroupCash();
	});
});