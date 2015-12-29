(function($) {
	var disableTrigger = 'layout-disabled';
	$.fn.hideDisableChildren = function() {
		if ($(this).is(':visible')) {
			$(this).slideUp();
		}
		$(this).find(':input,select')
			.filter(function() {
				return $(this).prop('disabled') === false || !$(this).data('hide-disabled-set');
			})
			.each(function() {
				console.log({
					'Disabling': $(this).attr('name')
				});

				$(this)
					.data('stored-disabled', $(this).prop('disabled'))
					.data('hide-disabled-set', true)
					.prop('disabled', true)
					.trigger('layout-disabled');
			});
		return $(this);
	};
	$.fn.showEnableChildren = function(focusFirst) {
		if ($(this).is(':hidden')) {
			$(this).slideDown();
		}
		var $openInputs = $(this).find(':input').each(function() {
			var setDisabled = false;
			if ($(this).data('stored-disabled')) {
				setDisabled = $(this).data('stored-disabled');
			}

			console.log({
				'Enabling': $(this).attr('name')
			});

			$(this)
				.data('hide-disabled-set', false)
				.prop('disabled', false);
			if (!setDisabled) {
				$(this).trigger('layout-enabled');
			}
		});
		
		if (focusFirst) {
			$openInputs.first().select();
		}
		return $(this);
	};
})(jQuery);
