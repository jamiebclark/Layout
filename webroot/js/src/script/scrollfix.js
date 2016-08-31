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
	/*
	$.fn.scrollfixOLD = function() {
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
	*/

	$.fn.scrollfix = function() {
		return this.each(function() {
			var $scrollbox = $(this),
				bodyOffset = 0,
				$parent = $scrollbox.parent().addClass('scrollfix-parent'),
				$container = $scrollbox.closest('.scrollfix-container,.row,.container,.container-fluid,body').addClass('scrollfix-container'),
				containerTop = 0,
				containerBottom = 0,
				containerHeight = 0,
				scrollboxWidth = 0,
				scrollboxHeight = 0,
				windowHeight = 0,
				topOffset = 0,

				timeoutTimer = 0;

		
			function setDimensionsTimeout() {
				clearTimeout(timeoutTimer);
				timeoutTimer = setTimeout(function() {
					setDimensions();
				}, 1000);
			}

			function setDimensions() {
				bodyOffset = parseInt($('body').css('padding-top'), 10);
				windowHeight = $(window).height();
				containerHeight = $container.outerHeight(true);
				containerTop = $container.offset().top;
				containerBottom = containerTop + containerHeight;
				scrollboxWidth = $parent.width();
				$scrollbox.width(scrollboxWidth - ($scrollbox.outerWidth() - $scrollbox.width()));
				scrollboxHeight = $scrollbox.outerHeight(true);

				topOffset = 0;

				$('.scrollfix-fixed:visible').each(function() {
					if ($(this).css('position') == "fixed") {
						var elementHeight = $(this).outerHeight();
						windowHeight -= elementHeight;
						topOffset += elementHeight;
					}
				});
			}
			
			function setScrollClass(currentScroll) {
				currentScroll += topOffset;
				/*
				$scrollMeter.css({top: currentScroll + "px"});
				$scrollMeterBottom.css({top: (currentScroll + windowHeight - 4) + "px"});
				*/

				var positions = ['scrollfix--top', 'scrollfix--bottom', 'scrollfix--fixed'],
					key,
					css = {top: 0},
					fixedTop = topOffset;

				if (scrollboxHeight > windowHeight) {
					fixedTop -= scrollboxHeight - windowHeight;
				}

				if (
					// Stick to top

					// Scroll is above the container
					(currentScroll < containerTop) || 

					// Bottom of scroll window isn't clearing the screen yet
					(windowHeight) < (containerTop + scrollboxHeight - currentScroll)
				) {
					key = 0;
				} else if ((currentScroll + scrollboxHeight) >= (containerBottom)) {
					// Bottom
					key = 1;
					css.top = (containerHeight - scrollboxHeight);
				} else {
					// Fixed
					css.top = fixedTop;
					key = 2;
				}
				return $scrollbox
					.css(css)
					.addClass(positions.splice(key,1)[0])
					.removeClass(positions.join(' '));
			}
			
			if (!$scrollbox.data('scrollfix-init')) {
				/*
				var $scrollMeter = $('<div class="scroll-meter"></div>').css({
					position: 'absolute',
					left: 0,
					right: 0,
					border: '2px solid red',
					zIndex: 9999
				}).appendTo($('body'));

				var $scrollMeterBottom = $('<div class="scroll-meter"></div>').css({
					position: 'absolute',
					left: 0,
					right: 0,
					border: '2px solid green',
					zIndex: 9999
				}).appendTo($('body'));
				*/


				setDimensions();

				$(window)
					.scroll(function() {
						setScrollClass($(window).scrollTop());
						setDimensionsTimeout();	
					})
					.resize(function() {
						setDimensionsTimeout();
					})
					.load(function() {
						setDimensionsTimeout();
					});
			}

		});
	};

	documentReady(function() {
		$('.affix-content').affixContent();
		$('.scrollfix').scrollfix();
	});

})(jQuery);
