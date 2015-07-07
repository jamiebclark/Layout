// Animated Ellipsis
(function($) {

	$.fn.animateText = function(opts) {
		opts = jQuery.extend({}, opts, {
			refreshInterval: 300,
			step: function(text, key) {}
		});

		return this.each(function() {
			var $container = $(this),
				animationKey = 0,
				animationInterval = false;
			animationInterval = setInterval(function() {
				var txt = opts.step($container.html(), animationKey);
				animationKey++;
				$container.html(txt);
			}, opts.refreshInterval);
		});
	};

	$.fn.animatedEllipsis = function() {
		function animatedEllipsisStep(text, key) {
			var	outputText = '',
				pt = key % 3;
			for (var i = 1; i <= pt; i++) {
				outputText += ".";
			}
			return outputText;
		}

		return this.animateText({
			refreshInteval: 300,
			step: function (text, key) {
				var	outputText = '',
					pt = key % 3;
				for (var i = 1; i <= pt; i++) {
					outputText += ".";
				}
				return outputText;
			}
		});
	};

	documentReady(function() {
		$('.animated-ellipsis').animatedEllipsis();
	});
})(jQuery);
