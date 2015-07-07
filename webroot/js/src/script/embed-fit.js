// Embed Fit
(function($) {
	$.fn.embedFit = function() {
		return this.each(function() {
			var $container = $(this),
				$embedObject = $('object,iframe', $container).first(),
				$embedObjects = $('embed,object,iframe', $container),
				embedWidth = $embedObject.attr('width') ? $embedObject.attr('width') : $embedObject.width(),
				embedHeight = $embedObject.attr('height') ? $embedObject.attr('height') : $embedObject.height(),
				embedRatio = embedWidth / embedHeight;
			function fitEmbedObject() {
				var w = $container.width();
				$embedObjects.width(w).height(w / embedRatio);
			}
			if (!$container.data('embed-fit-init')) {
				fitEmbedObject();
				$(window).resize(function() {
					fitEmbedObject();
				});	
				$container.data('embed-fit-init', true);
			}
			$container.on('resize', function() {
				fitEmbedObject();
			});
		});
	};

	documentReady(function() {
		$('.embed-fit').embedFit();
	});
})(jQuery);