(function($) {
	var hoverCount = 0;
	
	$.fn.hoverContent = function() {
		var $this = $(this),
			$content = $this.find('.hover-content'),
			showWait = 600,
			hideWait = 250,
			isHovered = false,
			hoverLeft = $this.hasClass('hover-left');
		
		if (!$('#hover-content-holder').length) {
			$('body').append($('<div id="hover-content-holder"></div>').css('position', 'static'));
		}
		var $hoverContentHolder = $('#hover-content-holder');
		
		if (!$this.data('hover-init')) {
			$this.find('.hover-content,.hover-over').hover(function() {
				isHovered = true;
				$content.delay(showWait).queue(function(n) {
					if (isHovered) {
						$this.addClass('hovering');
						var $pos = $this.offset();
						$content.show();
						//Left-Right
						if (($pos.left + 30 + $content.width()) > $(window).width()) {
							$content.addClass('position-right');
							$pos.left = $pos.left - $content.width() + $this.width();
						} else {
							$content.removeClass('position-right');
						}
						var $css = {
							top : $pos.top + $this.height(),
							left : $pos.left,
							bottom : 'auto'
						};
						//Top-Bottom
						if (($pos.top + $content.height()) > ($(window).scrollTop() + $(window).height())) {
							$content.addClass('position-down');
							$css.top = $pos.top - $content.height();
						} else {
							$content.removeClass('position-down');
						}
						
						if (hoverLeft) {
							$css.top = $pos.top;
							$css.left = $pos.left - $content.width();
							$content.addClass('hover-left');
						} else {
							$content.removeClass('hover-left');
						}
						
						$content.css($css);
					}
					n();
				});
			}, function() {
				isHovered = false;
				$content.delay(hideWait).queue(function(n) {
					if (!isHovered) {
						$this.removeClass('hovering');
						$content.hide();
					}
					n();
				});
			});
			hoverCount++;
			$this.data('hoverId', hoverCount);
			$content.data('hoverId', hoverCount);
			$content.attr('id', 'hover-content' + hoverCount);
			
			$this.bind('remove', function() {
				$('#hover-content' + $this.data('hoverId')).remove();
			});
			$hoverContentHolder.append($content);
		}
		$this.data('hover-init', true);
	};
})(jQuery);

$(document).ajaxComplete(function() {
	$('.hover-layout').each(function() {$(this).hoverContent()});
});

