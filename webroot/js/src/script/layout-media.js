// Media
(function($) {
	$.fn.layoutMedia = function() {
		var fadeDuration = 100;
		return this.each(function() {
			var $this = $(this),
				$wrap = $this.closest('.media-wrap'),
				$actions = $('.media-actionmenu', $wrap),
				$hover = $wrap.length ? $wrap : $this;
				
			if ($wrap.length === 0) {
				$actions = $('.media-actionmenu', $this);
			}
			$hover.hover(function() {
				$this.addClass('media-hover');
				$actions.fadeIn(fadeDuration);
			}, function() {
				$this.removeClass('media-hover');
				$actions.fadeOut(fadeDuration);
			});
		});
	};
	documentReady(function() {
		$('.media').layoutMedia();
	});

})(jQuery);