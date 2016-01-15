(function($) {
	$.fn.datepick = function () {
		return this.each(function() {
			var $input = $(this).datepicker(),
				$control = $input.closest('div'),
				$time = $('.timepicker', $input.closest('.date-time-input'));
			if (!$input.data('date-init')) {
				$('.today', $control).click(function(e) {
					e.preventDefault();
					$input.datepicker('setDate', 'now');
				});
				$('.clear', $control).click(function(e) {
					e.preventDefault();
					$input.datepicker('setDate');
				});
				if ($input.val()) {
					$input.datepicker('setDate', $input.val());
				}
				$input.change(function() {
					if ($time.length) {
						$time.focus();
					}
					return $(this);
				});
			}
			$input.data('date-init', true);
			return $input;
		});
	};
	$.fn.timepick = function () {
		return this.each(function() {
			var $input = $(this).timepicker(),
				$control = $input.closest('div');
			if (!$input.data('time-init')) {
				$('.today', $control).click(function(e) {
					e.preventDefault();
					$input.timepicker('setTime', new Date());
				});
				$('.clear', $control).click(function(e) {
					e.preventDefault();
					$input.val('');
				});
			}
			$input.data('time-init', true);
			return $input;
		});
	};

	$(document).bind('ready ajaxComplete', function() {
		$('.datepicker').datepick();
		$('.timepicker').timepick();
	});

})(jQuery);
