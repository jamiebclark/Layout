(function($) {
/**
 * Overlay Message queue
 * handles animated sliding text over loading screen
 *
 **/
	var MessageCarousel = function(element, options) {
		this.$element = $(element);
		this.$content = $('<div class="message-carousel-wrap"></div>')
			.appendTo(this.$element);
		this.options = options;

		this.$messages = $('.message-carousel-message').hide();
		this.$currentItem;
	
		this.key = 0;
		this.transitionInterval;

		this.transitionTime 		= 5000;
		this.fadeDuration 			= 500;
		this.fadeEasing				= "easeOutQuad";

		this.fadeOptions = {
			queue: false,
			duration: this.fadeDuration,
			easing: this.fadeEasing
		};
	}

	MessageCarousel.prototype.createItem = function (key) {
		if (this.$messages.eq(key).length) {
			var $message = this.$messages.eq(key).clone().html();
			var $item = $('<div class="message-carousel-item"></div>').appendTo(this.$content);
			var h = $item.append($message).outerHeight();
			$item.css({
				position: 'absolute',
				top: 0,
				left: 0,
				right: 0
			}).hide();

			this.$content.animate({
				height: h
			}, {duration: this.fadeDuration});

			return $item;
		}
		return false;
	}

	MessageCarousel.prototype.loadMessage = function() {
		var $item = this.createItem(this.key);
		if (!$item) {
			this.key = 0;
			$item = this.createItem(this.key);
		}

		if (this.$currentItem) {
			var $oldItem = this.$currentItem;
			this.$currentItem.fadeOut($.extend(this.fadeOptions, {
				complete: function() {
					$oldItem.remove();
				}
			}));
			this.$currentItem = $item.fadeIn(this.fadeOptions);
		} else {
			this.$currentItem = $item.show();
		}

		this.key++;
	}

	MessageCarousel.prototype.start = function() {
		this.loadMessage();

		var that = this;
		this.transitionInterval = setInterval(function() {
			that.loadMessage();
		}, this.transitionTime);
	}

	MessageCarousel.prototype.pause = function() {
		clearInterval(this.transitionInterval);
	}


	$.fn.messageCarousel = function(option) {
		return this.each(function() {
			var $this = $(this);
			var data = $this.data('messageCarousel');
			var options = $.extend({}, $this.data(), typeof option == 'object' && options);
			var action = typeof option == 'string' ? option : 'start';
			if (!data) $this.data('messageCarousel', (data = new MessageCarousel(this, options)));
			if (action) {
				data[action]();
			}
		});
	}
	$.fn.messageCarousel.Constructor = MessageCarousel;

	$.fn.formSubmittedOverlay = function() {
		return this.each(function() {
			var $form = $(this);

			if ($form.data('submitted-overlay-init')) {
				return $form;
			}

			$form.submit(function(e) {
				// Builds the mask
				var $form = $(this).addClass('submitted-overlay-submitted'),
					maskPadding = 0,
					loadingIcon = '<i class="fa fa-spinner fa-spin"></i>',
					$mask = $('<div class="submitted-overlay-mask"></div>')
						.css({
							'width': 	$form.outerWidth() + 2 * maskPadding,
							'height': 	$form.outerHeight() + 2 * maskPadding,
							'top': 		maskPadding * -1,
							'left': 	maskPadding * -1
						})
						.appendTo($form),
					$message = $('<div class="submitted-overlay-mask-message"></div>').appendTo($mask),
					$icon = $('<div class="submitted-overlay-mask-message-icon">' + loadingIcon + '</div>')
						.appendTo($message),
					$title = $('<div class="submitted-overlay-mask-message-title">Loading</div>').appendTo($message),
					$content = $('<div class="submitted-overlay-mask-message-content"></div>').appendTo($message);

				$content.messageCarousel();
								
				$(':submit', $form).each(function() {
					$(this).prop('disabled', true).html(loadingIcon + ' Loading');
				});
				// return false;
			});
			$form.data('submitted-overlay-init', true);
			return $form;
		});
	};

	$(document).bind('ready ajaxComplete', function() {
		$('form.submitted-overlay').formSubmittedOverlay();
	});
})(jQuery);