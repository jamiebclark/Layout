(function($) {
	$.fn.inputChoices = function() {
		return this.each(function() {
			var $list = $(this), $choices, $controls, $contents, $checkedControl;
			
			function setVars() {
				$choices = $('.input-choice', $list);
				$controls = $('.input-choice-control input', $choices);
				$contents = $('.input-choice-content', $choices);
				$checkedControl = $controls.filter(':checked');
			}
				
			function clickRadio() {
				setVars();
				if (!$checkedControl.length) {
					$checkedControl = $controls.first();
				}

				if (!$checkedControl.is(':disabled')) {
					$checkedControl.prop('checked', true);
					var $choice = $checkedControl.closest('.input-choice');
					$choices.not($choice).removeClass('input-choice-active');
					$choice.addClass('input-choice-active');

					$(':input', $contents).each(function() {
						var $parent = $(this).closest('.input-choice'),
							isActive = $parent.hasClass('input-choice-active');
						//Removes required props from hidden elements
						if (isActive && $(this).data('is-required')) {
							$(this).prop('required', true);
						} else if (!isActive && $(this).prop('required')) {
							$(this).data('is-required', true).removeAttr('required');
						}
					});
					
					$contents
						.filter(function() {
							return !$(this).closest('.input-choice').hasClass('input-choice-active');
						})
						.each(function() {
							$(this).hideDisableChildren();
						});
					$('.input-choice-content', $choice).showEnableChildren();
				}
			}

			setVars();
			if (!$list.data('input-choice-init')) {
				$controls.each(function() {
					$(this)
						.hover(function() {
								$(this).toggleClass('input-choice-hover');
							})
						.click(function(e) {
							$checkedControl = $(this);
							clickRadio();
						})
						.on('layout-enabled', function() {
							clickRadio();
						});
				});
				$list.data('input-choice-init', true);

				$(document).ready(clickRadio);
				$(document).on('ajaxComplete', clickRadio);
				$(window).on('load unload', clickRadio);
			}
			clickRadio();
			return $list;
		});
	};
	documentReady(function() {
		$('.input-choices').inputChoices();
	});
})(jQuery);
