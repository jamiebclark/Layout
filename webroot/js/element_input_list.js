(function($) {
	$.fn.elementInputList = function() {
		return this.each(function() {
			console.log("ELEMENT INPUT LIST");

			var $list = $(this),
				$items = $('.element-input-list-item', $list),
				$template = $('.element-input-list-template', $list),
				$button = $('.element-input-list-add', $list);

			function initItem($item) {
				if (!$item.data('element-input-list-item-init')) {
					var index = $items.index($item),
						$key = $('.element-input-list-key', $item);

					$item.data('element-input-list-item-index', index);
					$item.wrapInner('<div class="element-input-list-item-inner"></div>');
					$item.data('element-input-list-item-init', true);

					if ($key.length) {
						var name = $key.attr('name').replace('[id]', '[remove]');
						$button = $('<input/>')
							.attr('name', name)
							.attr('type', 'checkbox')
							.attr('tabindex', -1)
							.addClass('element-input-list-item-remove-input')
							.attr('value', index)
							.appendTo($item);

						$button
							.wrap('<label class="element-input-list-item-remove-label btn btn-default"></label>')
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
				var $inner = $('.element-input-list-item-inner', $item).slideUp();				
				disableInputs($inner);
			}

			function restoreItem($item) {
				$item.removeClass('removed');
				var $inner = $('.element-input-list-item-inner', $item).slideDown();
				enableInputs($inner);
			}

			function disableInputs($el) {
				$(':input', $el).each(function() {
					var disabledVal = $(this).prop('disabled');
					$(this).prop('disabled', 'disabled').data('element-input-list-item-disabled', disabledVal);
				});
			}

			function enableInputs($el) {
				$(':input', $el).each(function() {
					if ($(this).data('element-input-list-item-disabled')) {
						$(this).prop('disabled', $(this).data('element-input-list-item-disabled'));
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
					.removeClass('element-input-list-template')
					.addClass('element-input-list-item');

				if ($items.length) {
					$item.insertAfter($items.last());
				} else {
					$item.prependTo($list);
				}

				enableInputs($item);

				$items = $('.element-input-list-item', $list);

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

			if (!$list.data('element-input-list-init')) {
				disableInputs($template);
				$button.click(function(e) {
					e.preventDefault();
					cloneTemplateItem();
				});
				$items.each(function() {
					initItem($(this));
				});

				$list.data('element-input-list-init', true);

				$(document).ajaxComplete(function() {
					disableInputs($template);
				});
			} else {
				// console.log("ALREADY INITIALIZED");
			}
		});
	}
	$(document)
		.bind('ready ajaxComplete', function() {
			$('.element-input-list').elementInputList();
		});
})(jQuery);