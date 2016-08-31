//Scroll-fix
(function($) {
	$.fn.affixContent = function() {
		var $this = $(this),
			$content = $('#content'),
			off = $content.offset(),
			w = $(this).outerWidth(),
			t = function() {
				return (this.top = $this.parent().offset().top);
			},
			b = function() {
				return (this.bottom = $('#footer').outerHeight(true));
			},
			styles = {
				'position': '', 
				'top': 'auto',
				'width': w + "px"
			};
		
		function setStyles() {
			if ($('body').outerWidth() < 500) {
				styles.position = 'static';
				styles.width = '';
			}
			$this.css(styles);	
		}

		$this
			.affix({offset: {top: t, bottom: b}})
			.on('affixed.bs.affix', function() {
				styles.position = 'fixed';
				styles.top = 0;
				setStyles();
			})
			.on('affixed-top.bs.affix', function() {
				styles.position = 'relative';
				styles.top = 'auto';
				setStyles();
			})
			.on('affixed-bottom.affix', function() {
				styles.position = 'relative';
				setStyles();
			});
			
				
	};
	$.fn.scrollfix = function() {
		return this.each(function() {
			function checkIsFixed() {
				if (
					// Top clears the top of screen
					(scrollTop > scrollfixTop) && 
					// Bottom clears the bottom of screen
					((scrollfixTop + height) < (windowHeight + scrollTop))
				) {
					fix();
				} else {
					unfix();
				}
			}

			function fix() {
				var scrollOffset = (height > windowHeight) ? height - windowHeight : 0,
					overBorder = containerBottom && (scrollTop - scrollOffset + height) > containerBottom,
					addClass = '';
					removeClass = '',
					style = {width: width};

				if (overBorder) {
					addClass += 'scrollfix--bottom';
					removeClass += 'scrollfix-fixed';
				} else {
					removeClass += 'scrollfix--bottom';
					addClass += 'scrollfix--fixed';
					style.top = (topOffset - scrollOffset) + "px";
				}

				$scrollfix.addClass(addClass).removeClass(removeClass).css(style);
			}

			function unfix() {
				$scrollfix.removeClass('scrollfix--bottom scrollfix--fixed');
			}
			
			function setSizes() {
				unfix();

				height = $scrollfix.outerHeight(true);
				width = $scrollfix.outerWidth();
				scrollfixPos = $scrollfix.offset();
				scrollfixTop = scrollfixPos.top;

				windowHeight = $(window).height();
				topOffset = 10;

				$('.scrollfix-fixed:visible').each(function() {
					if ($(this).css('position') == "fixed") {
						var h = $(this).outerHeight();
						windowHeight -= h;
						topOffset += h;
					}
				});

				//containerBottom;
				if ($container.length) {
					containerPos = $container.offset();
					containerBottom = containerPos.top + $container.height();
					$container.css('min-height', height + "px");
				}
				checkIsFixed();
			}

			if (!$(this).data('scroll-init')) {
				var $scrollfix = $(this),
					containerClasses = new Array('.row', '.container'),
					$container;

				for (var i in containerClasses) {
					$container = $scrollfix.closest(containerClasses[i]);
					if ($container.length) {
						break;
					}
				}
				$container.addClass('scrollfix-container');

				var $parent = $scrollfix.parent();
				if ($parent !== $container) {
					$parent.addClass('scrollfix-parent');
				}

				var height,
					width,
					scrollfixPos,
					scrollfixTop,
					windowHeight,
					scrollTop,
					topOffset,
					containerBottom,
					containerPos;
				setSizes();
				
				$(window).on('scroll', function() {
					scrollTop = $(window).scrollTop() + topOffset;
					setSizes();
					checkIsFixed();
				}).resize(function() {
					setSizes();
				}).load(function() {
					setSizes();
					checkIsFixed();
				});

				$(this).data('scroll-init', true);
			}

		});
	};

	documentReady(function() {
		$('.affix-content').affixContent();
		$('.scrollfix').scrollfix();
	});

})(jQuery);
