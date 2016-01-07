// Media
(function($) {
	$.fn.layoutMedia = function() {
		var fadeDuration = 250;
		var fadeEasing = "easeOutSine";
		return this.each(function() {
			var $this = $(this),
				$wrap = $this.closest('.media-wrap'),
				$actions = $('.media-actionmenu', $wrap),
				$hover = $wrap.length ? $wrap : $this;
				
			if ($wrap.length === 0) {
				$actions = $('.media-actionmenu', $this);
			}
			if (!$this.data('layout-media-init')) {
				$hover.hover(function() {
					$this.addClass('media-hover');
					$actions.stop().fadeIn(fadeDuration, fadeEasing);
				}, function() {
					$this.removeClass('media-hover');
					$actions.stop().fadeOut(fadeDuration, fadeEasing);
				});
				$this.data('layout-media-init', true);
			}
		});
	};
	documentReady(function() {
		$('.media').layoutMedia();
	});
})(jQuery);