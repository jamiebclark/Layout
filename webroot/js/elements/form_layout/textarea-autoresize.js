(function($) {
	$.fn.textareaAutoresize = function() {
		return this.each(function() {
			var $textarea = $(this),
				h = 0,
				textarea = this;
			function resize() {
				//textarea.style.height = '0px';
				var newHeight = textarea.scrollHeight;
				if (newHeight != h) {
					$textarea.stop().animate({'height': (newHeight) + 'px'});
					h = newHeight;
				}
//				textarea.style.height = (textarea.scrollHeight + 10) + 'px';
			}
			if (!$textarea.data('textareaAutoresize-init')) {
				$textarea
					.css({'overflow': 'hidden', 'height': '0px'})
					.keyup(function() {
						resize();
					})
					.data('textareaAutoresize-init', true);
			}
			resize();
			return $textarea;
		});
	};

	documentReady(function() {
		$('textarea.textarea-autoresize').textareaAutoresize();
	});
	
})(jQuery);

