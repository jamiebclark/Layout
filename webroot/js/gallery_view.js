(function($) {
	var scrollToggle = 1;
	$.fn.viewToggleControl = function(passedOptions) {
		var defaults = {
			'controlDisplay': ['Show','Hide'],
			'id': 'toggle-control',
			'hidden': false
		};
		var options = $.extend([], defaults, passedOptions),
			$this = $(this),
			$control,
			$inner,
			id = options.id,
			isHidden = options.hidden,
			controlDisplay = options.controlDisplay,
			controlClass = id + '-control',
			innerClass = id + '-inner';
			
		
		function setControlDisplay() {
			var key = Math.round(!isHidden);
			$control.html(controlDisplay[key]);
		}
		
		if (!$this.data('toggle-control-init')) {
			$control = $('<a></a>', {
				'href': '#',
				'class': controlClass,
				'click': function(e) {
					e.preventDefault();
					e.stopPropagation();
					if (isHidden) {
						$this.trigger('show');
					} else {
						$this.trigger('hide');
					}
				}
			});
			$this
				.wrapInner($('<div></div>', {'class': innerClass}))
				.prepend($control)
				.on('hide', function() {
					isHidden = true;
					$this.data('hidden', true);
					if (options.hide) {
						options.hide;
					}
					$this.trigger('change');
				})
				.on('show', function() {
					isHidden = false;
					if (options.show) {
						options.show;
					}
					$this.trigger('change');
				})
				.on('change', function() {
					$this.data('hidden', isHidden);
					setControlDisplay();
				})
				.data('toggle-control-init', true);
		}
		$inner = $('.' + innerClass, $this);
		$control = $('.' + controlClass, $this);
		setControlDisplay();
		return $this;
	};
	
	$.fn.galleryViewLink = function() {
		return this.each(function() {
			var $this = $(this),
				$a = this.nodeName.toLowerCase() == 'a' ? $this : $('a', $this).first(),
				url = $a.attr('href');
			if (!$a.data('gallery-view-link-init')) {
				$a.click(function(e) {
					$('#gallery-modal').galleryViewModal('get', url);
					e.preventDefault();
				}).data('gallery-view-link-init', true).data('toggle', 'modal');
			}
		});
	};
	
	$.fn.galleryViewModal = function() {
		function get(url) {
			if (url != '#') {
				message('Loading...');
				imgUrl = url;
				$.get(url, function(data) {
					messageRemove();
					$data = $('<span></span>').append(data);
					init();
				})
				.fail(function() {
					message('Error loading', 'error', 500);
				});
			}
		}
		
		function message(text, className, delayHide) {
			if (typeof className == 'undefined') {
				var className = 'info';
			}
			var $msg = $('.ajax-loading', $this);
			if (!$msg.length) {
				$msg = $('<div class="ajax-loading"></div>').appendTo($this).hide();
			} else {
				$msg.html('');
			}
			$('<span></span>', {
				'class': className,
				'html': text
			}).appendTo($msg);

			$msg.fadeIn();
			
			if (typeof delayHide != 'undefined') {
				messageRemove(delayHide);
			}

		}
		function messageRemove(delay) {
			if (typeof delay == 'undefined') {
				delay = 0;
			}
			$('.ajax-loading', $modal).delay(delay).fadeOut();
		}
		function resize() {
			$image.removeAttr('width').removeAttr('height');
			var screenW = $(window).width(),
				screenH = $(window).height(),
				marginW = screenW * (marginPctWidth / 100),
				marginH = screenH * (marginPctHeight / 100),
				boxWidth = $image.parent().outerWidth(), //screenW - marginW * 2,
				boxHeight = screenH - marginH * 2;
			if (boxWidth > maxWidth) {
				boxWidth = maxWidth;
			} else if (boxWidth < minWidth) {
				boxWidth = minWidth;
			}
			if (boxHeight > maxHeight) {
				boxHeight = maxHeight;
			} else if (boxHeight < minHeight) {
				boxHeight = minHeight;
			}
			
			var boxWidthOuter = boxWidth + marginW * 2,
				boxHeightOuter = boxHeight + marginH * 2;
			var css = {
				'width': boxWidth,
				'height': boxHeight,
				'margin-left': -1 * boxWidth / 2,
				'margin-top': -1 * boxHeight / 2,
				'left': '50%',
				'top': '50%'
			};
			if (boxWidthOuter > screenW) {
				css['margin-left'] = 0;
				css['left'] = '10px';
			}
			if (boxHeight > screenH) {
				css['margin-top'] = 0;
				css['top'] = '10px';
			} 
			css = {'width': boxWidth, 'height' : boxHeight};
			//$dialog.css(css);
			var bodyPadding = $body.outerHeight() - $body.height();
			var bodyHeight = $modal.innerHeight() - $header.outerHeight() - $thumbnails.outerHeight() - bodyPadding;
			$('img,iframe,embed', $imageHolder).each(function() {
				var w, h, tag = this.nodeName.toLowerCase();
				if (tag == 'img') {
					var img = $(this)[0];
					
					w = this.offsetWidth;
					h = this.offsetHeight;
				} else {
					w = $(this).outerWidth();
					h = $(this).outerHeight();
				}
				if (w && h) {
					imageSizeRatio = w/h;
				}
				var	newHeight = bodyHeight,
					newWidth = imageSizeRatio * newHeight;
				if (newWidth > boxWidth) {
					newWidth = boxWidth;
					newHeight = newWidth / imageSizeRatio;
				}
				if (newWidth && newHeight) {
					$(this).show().stop().animate({'width':newWidth,'height':newHeight});
				}
			});
		}
		
		function showInfo() {
			$('.gallery-view-infos-inner', $info).show();
			$info.animate({'width': infoWidth}).data('gallery-hidden', false);			
		}
		
		function hideInfo() {
			var duration = null;
			if (typeof $info.data('gallery-hidden') === 'undefined') {
				infoWidth = $info.outerWidth();
				infoWidthInner = $('.gallery-view-infos-inner', $info).outerWidth();
				afterHideInfo();
				duration = 0;
			} 
			$info.animate({'width': 0}, {
				'duration': duration,
				'complete': function() {
					afterHideInfo();
				}});
		}
		
		function afterHideInfo() {
			$('.gallery-view-infos-inner', $info).css('width',infoWidthInner).hide();
			$info.data('gallery-hidden', true);
		}
		
		function hideThumbnails() {
			var duration = !$modal.data('thumbnails-hidden') && $thumbnails.data('hide-init') ? null : 0;
			var $inner = $('> div', $thumbnails);
			$inner.css({'position':'absolute','bottom':0,'left':0,'right':0});
			resize();
			$inner.stop().slideUp(duration);
			$modal.data('thumbnails-hidden', true);
			$thumbnails.data('hide-init', true);
		}

		function showThumbnails() {
			$('> div', $thumbnails).stop().slideDown({
				'complete': function() {
					$(this).css({'position':'static'});
					resize();
				}
			});
			$modal.data('thumbnails-hidden', false);
		}

		function stopVideo() {
			$embed.each(function() {
				var src = $(this).attr('src');
				$(this).attr('src', '').attr('src', src);
			});
		}
		
		function init() {
			$modal = $('#gallery-modal');
			$dialog = $('.modal-dialog', $modal);
			$content = $('.modal-content', $modal);
			
			newModal = !$modal.length;
			$header = $('.modal-header', $modal).first();
			$headerTitle = $('.modal-header-title', $header).first();
			$body = $('.modal-body', $modal).first();
			if (newModal) {
				$modal = $('<div></div>', {
						'id': 'gallery-modal',
						'class': 'modal fade',
						'aria-hidden': 'true'
					}).modal().appendTo($('body'));
				var $dialog = $('<div class="modal-dialog modal-lg"></div>').appendTo($modal),
					$content = $('<div class="modal-content"></div>').appendTo($dialog);
				
				$header = $('<div class="modal-header"></div>').appendTo($content);
				$header.append($('<button></button', {
					'type': 'button',
					'class': 'close',
					'data-dismiss': 'modal',
					'aria-hidden': 'true',
					'html': '&times;'
				}));				
				$headerTitle = $('<h3 class="modal-header-title"></h3>').appendTo($header);
				$body = $('<div class="modal-body"></div>').appendTo($content);
			} else {
				$body.html('');
			}
			$title = $('.gallery-view-title', $data);
			$caption = $('.gallery-view-caption', $data);
			$imageHolder = $('.gallery-view-image', $data);
			$image = $('img', $imageHolder).first().css({'max-height':'none','max-width':'none'});
			$embed = $('embed,iframe', $imageHolder);
			$thumbnails = $('.gallery-view-thumbnails', $data);
			$info = $('.gallery-view-infos', $data);
			nextUrl = $('.gallery-view-control.next').attr('href');
			prevUrl = $('.gallery-view-control.prev').attr('href');
			
			$caption
				.addClass('gallery-view-control')
				.click(function(e) {
					e.preventDefault();
					$info.trigger($info.data('gallery-hidden') ? 'show' : 'hide');
				})
				.appendTo($imageHolder);
			$('a', $caption).click(function(e) {
				e.stopPropagation();
			});
			$view = $('<div class="gallery-view"></div>')
				.append($imageHolder)
				.append($info)
				.append($thumbnails)
				.append($caption)
				.appendTo($body)
				.galleryView();
			$thumbnails = $('.gallery-view-thumbnails', $view);
			$info = $('.gallery-view-infos', $view);
			if ($title.length) {
				$headerTitle.html($title.html());
				if (!('a', $headerTitle).length && imgUrl) {
					$headerTitle.wrap($('<a></a>', {'href':imgUrl}));
				}
			} else {
				$headerTitle.html('');
			}
			
			/*
			$info.viewToggleControl({
				'id': 'gallery-view-infos',
				'controlDisplay': ['&laquo;', '&raquo;']
			})
				.on('show', function() {
					showInfo();
				})
				.on('hide', function() {
					hideInfo();
				});
			*/

			$thumbnails.viewToggleControl({
				'id': 'gallery-view-thumbnails',
				'controlDisplay': ['Show Thumbnails', 'Hide Thumbnails']
			})
				.on('show', function() {
					showThumbnails();
				})
				.on('hide', function() {
					hideThumbnails();
				});

			if ($modal.data('thumbnails-hidden')) {
				$thumbnails.trigger('hide');
			}
			if (newModal) {
				$modal.on('shown', function() {
					$info.trigger('hide');
				});
			}
			$modal
				.modal('show')
				.on('shown', function() {
					resize();
					$info.trigger('hide');
				})
				.on('hide', function() {
					stopVideo();
				});
			if ($modal.is(':visible')) {
				$info.trigger('hide');
			}
			$image.bind('load', function() {
				$("<img/>")
					.attr('src', $(this).attr('src'))
					.load(function() {
						naturalWidth = this.width;
						naturalHeight = this.height;
					});

				resize();
			});
			if (!$image.length) {
				resize();
			}
			if (!$this.data('gallery-view-modal-init')) {
				/***
				* Removed
				$('img', $imageHolder).bind('load', function() {
					$("<img/>")
						.attr('src', $(this).attr('src'))
						.load(function() {
							naturalWidth = this.width;
							naturalHeight = this.height;
						});
				});
				*/

				$(window).resize(function() {
					resize();
				});
				$this.data('gallery-view-modal-init', true);
			}
			$this.trigger('init');
		}
		
		var $this = $(this),
			$data = $this,
			$modal,
			$dialog,
			$content,
			newModal,
			$header,
			$headerTitle,
			$body,
			$title,
			$caption,
			$image,
			$imageHolder,
			img,
			$thumbnails,
			$info,
			$infoControl,
			infoWidth, infoWidthInner,
			imgUrl,
			$view,
			$embed,
			marginPctWidth = 5,
			marginPctHeight = 5,
			minWidth = 600,
			maxWidth = 1020,
			minHeight = 500,
			maxHeight = 900,
			imageSizeRatio,
			
			naturalWidth = 0,
			naturalHeight = 0;


		if (arguments && arguments[0] && arguments[0] == 'get') {
			get(arguments[1]);
		} else {
			init();
		}
		
		return $data;
	};

	$.fn.galleryView = function() {
		return this.each(function() {
			function hoverOn() {
				$controls.fadeIn();
			}
			function hoverOff() {
				$controls.fadeOut();
			}

			var $view = $(this),
				$image = $('.gallery-view-image', $view),
				$display = $('.gallery-view-image-display', $view),
				$info = $('.gallery-image-info', $view),
				$secondary = $('.gallery-image-secondary', $view),
				$thumbnails = $('.gallery-view-thumbnails', $view),
				$controls = $('.gallery-view-control', $view),
				$next = $('.gallery-view-control.next', $view),
				$prev = $('.gallery-view-control.prev', $view);

			if (!$view.data('gallery-view-init')) {
				$('img', $display).bind('load', function() {
					if ($next.length) {
						if (!$('a', $display).length) {
							$display.wrapInner($('<a></a>'));
						}
						var $a = $('a', $display).first()
							.addClass('gallery-view-link')
							.attr('href', $next.attr('href'));
					}
					$view.galleryViewLink();
				});

				$('.gallery-view-thumbnails a, a.gallery-view-control,.gallery-view-image a', $view).each(function() {
					$(this).addClass('gallery-view-link');
				});
				$view.data('gallery-view-init', true);
				$view.hover(hoverOn, hoverOff);
				hoverOff();
				
				var scroll = $('body').scrollTop();
				$('body').scrollTop(scroll + scrollToggle);
				scrollToggle *= -1;
			}
		});
	};
})(jQuery);

function galleryViewInit() {
	$('.gallery-view').galleryView();
	$('.gallery-view-link').galleryViewLink();
}

$(document)
	.ready(function() {
		galleryViewInit();
	})
	.ajaxComplete(function() {
		galleryViewInit();
	});
