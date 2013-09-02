(function($) {
	var scrollToggle = 1;
	
	$.fn.galleryViewLink = function() {
		return this.each(function() {
			var $a = $(this),
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
			var marginPctWidth = 10,
				marginPctHeight = 10,
				screenW = $(window).width(),
				screenH = $(window).height(),
				marginW = screenW * (marginPctWidth / 100),
				marginH = screenH * (marginPctHeight / 100),
				imgW = $image[0].offsetWidth,
				imgH = $image[0].offsetHeight,
				minWidth = 600,
				maxWidth = 1020,
				minHeight = 500,
				maxHeight = 900,
				boxWidth = screenW - marginW * 2,
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
			$modal.css(css);
			var bodyPadding = $body.outerHeight() - $body.height();
			var bodyHeight = $modal.innerHeight() - $header.outerHeight() - bodyPadding;
			$image.height(bodyHeight);
		}
		
		function showInfo() {
			$infoControl.html('&raquo;');
			$('.gallery-view-infos-inner', $info).show();
			$info.animate({'width': infoWidth}).data('gallery-hidden', false);			
		}
		
		function hideInfo() {
			$infoControl.html('&laquo;');
			if (typeof $info.data('gallery-hidden') === 'undefined') {
				infoWidth = $info.outerWidth();
				infoWidthInner = $('.gallery-view-infos-inner', $info).outerWidth();
				$info.css({'width':0});
				
				afterHideInfo();
			} else {
				$info.animate({'width': 0}, {
					'complete': function() {
						afterHideInfo();
					}});
			}
		}
		
		function afterHideInfo() {
			$('.gallery-view-infos-inner', $info).css('width',infoWidthInner).hide();
			$info.data('gallery-hidden', true);
		}
		
		function hideThumbnails(delay) {
			if (typeof delay == 'undefined') {
				var delay = 0;
			}
			var duration = $thumbnails.data('hide-init') ? null : 0;
			$('> div', $thumbnails).delay(delay).stop().css({'position':'absolute','bottom':0}).slideUp(duration);
			$thumbnails.data('hide-init', true);
		}

		function showThumbnails() {
			$('> div', $thumbnails).stop().slideDown({
				'complete': function() {
					$(this).css({'position':'static'});
				}
			});
		}

		function init() {
			$modal = $('#gallery-modal');
			newModal = !$modal.length;
			$header = $('.modal-header', $modal).first();
			$headerTitle = $('.modal-header-title', $header).first();
			$body = $('.modal-body', $modal).first();
			if (newModal) {
				$modal = $('<div></div>', {
						'id': 'gallery-modal',
						'class': 'modal modal-wide hide fade',
						'aria-hidden': 'true'
					}).modal().appendTo($('body'));
				$header = $('<div class="modal-header"></div>').appendTo($modal);
				$header.append($('<button></button', {
					'type': 'button',
					'class': 'close',
					'data-dismiss': 'modal',
					'aria-hidden': 'true',
					'html': '&times;'
				}));				
				$headerTitle = $('<h3 class="modal-header-title"></h3>').appendTo($header);
				$body = $('<div class="modal-body"></div>').appendTo($modal);
			} else {
				$body.html('');
			}
			$title = $('.gallery-view-title', $data);
			$caption = $('.gallery-view-caption', $data);
			$imageHolder = $('.gallery-view-image', $data);
			$image = $('img', $imageHolder).first();
			$thumbnails = $('.gallery-view-thumbnails', $data);
			$info = $('.gallery-view-infos', $data);
			
			$caption
				.addClass('gallery-view-control')
				.click(function(e) {
					e.preventDefault();
					if ($info.data('gallery-hidden')) {
						showInfo();
					} else {
						hideInfo();
					}
				})
				.appendTo($imageHolder);
			$('a', $caption).click(function(e) {
				e.stopPropagation();
			});
			$view = $('<div class="gallery-view"></div>')
				.append($imageHolder)
				.append($thumbnails)
				.append($info)
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
			}
			$infoControl = $('<a></a>', {
				'href': '#',
				'html': '&laquo;',
				'class': 'gallery-view-info-control',
				'click': function(e) {
					e.preventDefault();
					if ($info.data('gallery-hidden')) {
						showInfo();
					} else {
						hideInfo();
					}
				}
			});
			$info.wrapInner($('<div></div>', {
				'class': 'gallery-view-infos-inner'
			})).prepend($infoControl);
			
			if (newModal) {
				$modal.on('shown', function() {
					hideInfo();	
				});
			}
			
			$thumbnails.hover(showThumbnails, hideThumbnails).css('min-height', '100px');
			hideThumbnails();
			
			$modal.modal('show').on('shown', function() {
				resize();
				showThumbnails();
				hideThumbnails(500);
			});
			if ($modal.is(':visible')) {
				hideInfo();	
			}

			$image.bind('load', function() {
				resize();
			});

			if (!$this.data('gallery-view-modal-init')) {
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
			$view;

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
