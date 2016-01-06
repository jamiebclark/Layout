(function($) {
	$.fn.elementList = function() {
		return this.each(function() {
			var $list = $(this),
				$items = $('.element-list-item', $list),
				$template = $('.element-list-template', $list),
				$button = $('.element-list-add', $list);

			function initItem($item) {
				if (!$item.data('element-list-item-init')) {
					var index = $items.index($item),
						$key = $('.element-list-key', $item);

					$item.data('element-list-item-index', index);
					$item.wrapInner('<div class="element-list-item-inner"></div>');
					$item.data('element-list-item-init', true);

					if ($key) {
						var name = $key.attr('name').replace('[id]', '[remove]');
						$button = $('<input/>')
							.attr('name', name)
							.attr('type', 'checkbox')
							.attr('tabindex', -1)
							.addClass('element-list-item-remove-input')
							.attr('value', index)
							.appendTo($item);

						$button
							.wrap('<label class="element-list-item-remove-label btn btn-default"></label>')
							.after('<i class="fa fa-times"></i>')
							.bind('change', function() {
								if ($(this).is(':checked')) {
									removeItem($item);
								} else {
									restoreItem($item);
								}
							})
							.trigger('change');
					}
				}
			}

			function templateReplace($element, attrName) {
				if ($element.attr(attrName)) {
					var v = $element.attr(attrName).replace('%TEMPLATE%', $items.length);
					$element.attr(attrName, v);
				}
			}

			function removeItem($item) {
				$item.addClass('removed');
				var $inner = $('.element-list-item-inner', $item).slideUp();				
				disableInputs($inner);
			}

			function restoreItem($item) {
				$item.removeClass('removed');
				var $inner = $('.element-list-item-inner', $item).slideDown();
				enableInputs($inner);
			}

			function disableInputs($el) {
				console.log("DISABLING INPUTS: " + $el.length);
				$(':input', $el).each(function() {
					var disabledVal = $(this).prop('disabled');
					$(this).prop('disabled', 'disabled').data('element-list-item-disabled', disabledVal);
				});
			}

			function enableInputs($el) {
				$(':input', $el).each(function() {
					if ($(this).data('element-list-item-disabled')) {
						$(this).prop('disabled', $(this).data('element-list-item-disabled'));
					} if ($(this).data('hide-disabled-set')) {
						$(this).prop('disabled', $(this).data('hide-disabled-set'));
					} else {
						$(this).prop('disabled', false);
					}
				});
			}

			function cloneTemplateItem() {
				var $item = $template
					.clone()
					.hide()
					.insertAfter($items.last())
					.removeClass('element-list-template')
					.addClass('element-list-item');

				enableInputs($item);

				$items = $('.element-list-item', $list);

				$(':input,label', $item).each(function() {
					templateReplace($(this), 'name');
					templateReplace($(this), 'id');
					templateReplace($(this), 'for');
				});
				initItem($item);
				$item.slideDown();
				$list.trigger('cloned');
				$(document).trigger('ajaxComplete');
			}

			if (!$list.data('element-list-init')) {
				console.log('Disabling templates')
				disableInputs($template);
				$button.click(function(e) {
					e.preventDefault();
					cloneTemplateItem();
				});
				$items.each(function() {
					initItem($(this));
				});

				$list.data('element-list-init', true);

				$(document).ajaxComplete(function() {
					disableInputs($template);
				});
			} else {
				console.log("ALREADY INITIALIZED");
			}
		});
	}
	$(document)
		.bind('ready ajaxComplete', function() {
			$('.element-list').elementList();
		});
})(jQuery);