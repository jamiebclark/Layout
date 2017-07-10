(function($) {
	var toggleCount = 1;
	$.fn.layoutToggle = function() {
		return this.each(function() {
			var $toggle = $(this),
				$control = $toggle.find('.layout-toggle-control input[type*=checkbox]').first(),
				$content = $toggle.find('> .layout-toggle-content').first(),
				$offContent = $toggle.find('> .layout-toggle-off'),
				tc = toggleCount++;
			
			$toggle.addClass('toggle' + tc);
			
			function toggleOn() {
				$content.showEnableChildren();
				$offContent.hideDisableChildren();
			}
			function toggleOff() {
				$content.hideDisableChildren();
				$offContent.showEnableChildren();
			}
			function toggleCheck() {
				if (!$control.is(':disabled')) {
					if ($control.is(':checked')) {
						toggleOn();
					} else {
						toggleOff();
					}
				}
			}
			
			if (!$toggle.data('layout-toggle-init')) {
				$control.change(function() {
					toggleCheck();
				}).on('layout-enabled', function() {
					toggleCheck();
				});
				toggleCheck();
				$toggle.data('layout-toggle-init');
			}
			return $toggle;
		});
	};

	documentReady(function() {
		$('.layout-toggle').layoutToggle();
	});
})(jQuery);
