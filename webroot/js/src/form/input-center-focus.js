(function($) {
	$.fn.inputCenterFocus = function() {
		return this.each(function() {
			var $input = $(this),
				$label = $('<div></div>', {
					'class': 'input-centerfocus-label',
					'html': 'Click outside of the textbox to return to normal view'
				}),
				$placeholder = $('<div class="input-centerfocus-placeholder"></div>'),
				$bg = $('<div class="input-centerfocus-bg"></div>'),
				$focusElements = $([$label[0], $placeholder[0], $bg[0]]),
				inputOffset,
				oWidth = $input.css('width'),
				oHeight = $input.outerHeight(),
				oZIndex = $input.css('z-index'),
				oPos = $input.css('position'),
				screenW,
				screenH,
				boxStartLeft,
				boxStartTop,
				boxEndLeft,
				boxEndTop,
				boxEndWidth,
				boxEndHeight,
				boxPctWidth = 0.8,
				boxPctHeight = 0.8,
				isWindowFocused = true,
				isFocused = false;
				
			function grow() {
				if (!isWindowFocused) {
					return;
				}
				inputOffset = $input.offset();
				boxStartTop = inputOffset.top;
				boxStartLeft = inputOffset.left;

				screenW = $(window).width();
				screenH = $(window).height();
				
				boxEndHeight = screenH * boxPctHeight;
				boxEndWidth = screenW * boxPctWidth;
				boxEndTop = (screenH - boxEndHeight) / 2;
				boxEndLeft = (screenW - boxEndWidth) / 2;
				
				var boxTop = (boxStartTop - $(window).scrollTop()),
					boxLeft = (boxStartLeft - $(window).scrollLeft());
				$placeholder.css({
					'width': oWidth,
					'height': oHeight
				});
				$('body').addClass('input-centerfocus-open');
				$(this)
					.addClass('focused')
					.css({
						'width': oWidth,
						'height': oHeight,
						'left': boxLeft,
						'top': boxTop
					})
					.animate({
						'width': boxEndWidth,
						'height': boxEndHeight,
						'left': boxEndLeft,
						'top': boxEndTop
					}, {
						'complete': function() {
							$label.css({
								'top': boxEndTop + boxEndHeight,
								'left': boxEndLeft,
								'width': boxEndWidth
							});
							$focusElements.each(function() {
								$(this).addClass('focused').fadeIn();
							});	
						}
					});
					isFocused = true;
			}
			
			function shrink() {
				if (!isWindowFocused) {
					return;
				}
				$focusElements.each(function() {
					$(this).removeClass('focused').hide();
				});
				$('body').removeClass('input-centerfocus-open');
				$(this)
					.animate({
						'width': oWidth,
						'height': oHeight,
						'left': (boxStartLeft - $(window).scrollLeft()),
						'top': (boxStartTop - $(window).scrollTop())
					}, {
						complete: function() {
							$(this).removeClass('focused');
							isFocused = false;
						}
					});
			}
			
			if (!$input.data('centerfocus-init')) {
				$focusElements.each(function() {
					$(this).hide().insertAfter($input);
				});
				$(window).focus(function() {
					isWindowFocused = true;
					if (isFocused) {
						grow();
					} else {
						shrink();
					}
				}).blur(function() {
					isWindowFocused = false;
				});
				$input.focus(grow).blur(shrink).data('centerfocus-init', true);
			}				
		});
	};

	documentReady(function() {
		$('.input-centerfocus').inputCenterFocus();
	});
	
})(jQuery);
