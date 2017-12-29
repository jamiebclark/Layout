(function($) {
	$.fn.inputList = function() {
		return this.each(function() {
			var $list = $(this),
				$listItems = $('> .input-list-inner > .input-list-item', $list),
				$control = $('> .input-list-control', $list),
				$addLink = $('a', $control);

			if ($(this).data('input-list-init')) {
				return $(this);
			}

			function addRemoveBox($listItem) {
				var $ids = $listItem.getIdInputs(),
					$id = $ids.first();

				// console.log($ids);

				if (!$ids.length) {
					return false;
				}
				var removeClass = 'input-list-item-remove',
					removeCommand = 'remove',
					$checkbox = $listItem.find('.' + removeClass + ' input[type=checkbox]'),
					$content = $listItem.children(':not(.' + removeClass + ')');

				if ($list.data('input-list-remove-command')) {
					removeCommand = $list.data('input-list-remove-command');
				}

				if (!$checkbox.length) {
					$listItem.wrapInner('<div class="input-list-item-inner"></div>');
					var removeName = $id.attr('name').substr(0, $id.data('id-input-after-key-index')) + "[" + removeCommand + "]";

					// console.log(removeName);
					var	removeBoxId = removeName.replace(/(\[([^\]]+)\])/g, '_$2'),
						$checkbox = $('<input/>', {
							'type' : 'checkbox',
							'name' : removeName,
							'value' : 1,
							'id' : removeBoxId
						}).attr('name', removeName).val(1).attr('id',removeBoxId);
					$checkbox
						.attr('tabindex', -1)
						.appendTo($listItem)
						.wrap($('<div></div>', {'class' : removeClass}))
						.wrap($('<label></label>', {'html': '&times;'}));

					$checkbox.insertBefore($('<input/>', {
						'type': 'hidden',
						'name': removeName,
						'value': '0'
					}));
				}
				$checkbox.change(function() {
					if ($(this).is(':checked')) {
						$(this).parent().addClass('active');
						$listItem.addClass('remove').find(':input').filter(function() {
							var name = $(this).attr('name');
							return name != $checkbox.attr('name') && (
								!(name.match(/\[id\]/)) || $(this).is('select')
							);
						}).prop('disabled',true);
						//$content.slideUp();
					} else {
						$(this).parent().removeClass('active');
						$listItem.removeClass('remove').find(':input').prop('disabled',false);
						$content.slideDown();
					}
				}).prop('checked', false).change();
			}
			$listItems.each(function() {
				$(this).addClass('row');
				return $(this);
			});

			$list.on('cloned', function (e, $cloned) {
				addRemoveBox($cloned);
				$listItems = $('> .input-list-inner > .input-list-item', $list);
			});
			if (!$addLink || !$addLink.length) {
				$addLink = $('<a class="btn btn-default btn-sm" href="#" tabindex="-1">Add</a>').appendTo($control);
			}

			$addLink.click(function(e) {
				e.preventDefault();
				$list.trigger('add');
			});

			$listItems.filter(':visible').each(function() {
				addRemoveBox($(this));
				return $(this);
			});
			$list.on('add', function(e) {
				$listItems.cloneNumbered($list).trigger('inputListAdd').last();
				e.stopPropagation();
			}).data('input-list-init', true);
			return $(this);
		});
	};

	$.fn.renumberInput = function(newIdKey, keyIndex) {
		if (typeof keyIndex === "undefined") {
			var keyIndex = -1;
		}

		if (!$(this).attr('name')) {
			return $(this);
		}
		var	name = $(this).attr('name'),
			id = $(this).attr('id');

		if (keyIndex != -1) {
			var key = getNameKey(name, keyIndex);
			// console.log([name, keyIndex, key.index]);
			if (key === false) {
				return $(this);
			}
			var idKey = key.key,
				idKeyP = "[" + idKey + "]",
				newNameStart = name.substr(0, key.index),
				newNameIndexEnd = name.indexOf("[", newNameStart.length + idKeyP.length),
				newNameEnd = (newNameIndexEnd > -1) ? name.substr(newNameIndexEnd) : '',
				newName = newNameStart + "[" + newIdKey + "]" + newNameEnd;

			if (newNameEnd != ']') {
				$(this).attr('name', newName);
			}
		} else {
			var idKeyMatch = name.match(/\[(\d+)\]/);
			if (idKeyMatch) {
				var idKeyP = idKeyMatch[0],
					idKey = idKeyMatch[1];
				$(this).attr('name', name.replace(idKeyP, "[" + newIdKey + "]"));
			}
		}

		if (id) {
			var oldId = id,
				$labels = $('label').filter(function() { return $(this).attr('for') == oldId;}),
				newId = id.replace(idKey, newIdKey);
			$(this).attr('id', newId);
			$(this).parents().last().find('label[for="' + oldId + '"]').attr('for',newId);
			/*
			$(this).parent('label[for="'+oldId+'"]').attr('for', newId);
			$(this).closest('.form-group').find('label[for="'+oldId+'"]').attr('for',newId);
			$(this).next('label[for="'+oldId+'"]').attr('for',newId);
			$(this).prev('label[for="'+oldId+'"]').attr('for',newId);
			*/
		}
		return $(this);
	};

	function getNumericKey(name, search, forward) {
		i = name.indexOf(search);
		do {
			key = getNameKey(name, i, forward);
			i = forward ? key.endIndex : key.index;
		} while (isNaN(parseFloat(key.key)));
		return key;
	}

	function getNameKey(name, startIndex, forward) {
		if (typeof forward === 'undefined') {
			var forward = true;
		}
		var i = startIndex,
			k = '',
			endIndex,
			len = name.length,
			success = true;
		if (forward) {
			while (success && name.charAt(i) != '[') {
				i++;
				if (i > len) {
					success = false;
				}
			}
			while (success && name.charAt(++i) != ']') {
				k += name.charAt(i);
				if (k > len) {
					success = false;
				}
			}
			endIndex = i;
		} else {
			while (success && name.charAt(i) != ']') {
				i--;
				if (i < 0) {
					success = false;
				}
			}
			while (success && name.charAt(--i) != '[') {
				k = name.charAt(i) + k;
				if (k < 0) {
					success = false;
				}
			}
			endIndex = startIndex;
			startIndex = i;
		}
		if (success) {
			return {index: startIndex, key: k, endIndex: endIndex};
		} else {
			return false;
		}
	}

	// Finds all inputs that are valid ID inputs
	$.fn.getIdInputs = function() {
		var $this = $(this),
			idKeyReg = /(\[([\d]+)\])[\[id\]]*$/,
			keyIndex,
			afterKeyIndex
			$ids = $(':input:enabled', $this).filter(function() {
				// Filters only those elements with an id element
				var n = $(this).attr('name');
				var matches = idKeyReg.exec(n);
				if (!matches) {
					return false;
				} else {
					if (!keyIndex) {
						var i = n.indexOf(matches[0]);
						keyIndex = n.length - matches[0].length;
					}
				}
				$(this).data('id-input-key-index', keyIndex);
				$(this).data('id-input-after-key-index', keyIndex + matches[1].length);
				return $(this);
			});
		$ids.data('id-inputs-key-index', keyIndex);
		return $ids;
	};

	// Clones an element, incrementing any numeric indexes
	$.fn.cloneNumbered = function($parent) {
		if ($parent.data('cloning')) {
			return $(this);
		}
		$parent.data('cloning', true);

		var $ids = $(this).getIdInputs(),
			keyIndex = $ids.data('id-inputs-key-index');

		if (!keyIndex) {
			console.log('Key not found');
			//return false;
		}

		var	$id = $ids.last(),
			name = $id.attr('name');

		if ($id.length) {
			var $entry = $(this).last();
			var $cloned = $entry.clone();
			var newIdKey = $ids.length;

			$('input', $cloned).removeClass('hasDatepicker');
			$('input[name*="[id]"]', $cloned).val('').trigger('reset');
			$('.clone-numbered-index', $cloned).html((newIdKey + 1));	//Re-numbers from 0 index

			$cloned.find(':input').each(function() {
				return $(this).renumberInput(newIdKey, keyIndex).removeAttr('disabled');//.removeAttr('checked');
			});
			$cloned.insertAfter($entry);

			$(':checkbox,:radio', $cloned).each(function() {
				if (typeof($(this).data('clone-numbered-default')) !== 'undefined' && $(this).data('clone-numbered-default') == $(this).val()) {
					$(this).prop('checked', true);
				}
			});
			$('.no-clone', $cloned).remove();
			$(':input', $cloned).not(':hidden,:checkbox,:radio,:submit,:reset').each(function() {
				var v = '';
				if ($(this).data('clone-numbered-default')) {
					v = $(this).data('clone-numbered-default');
				} else if ($(this).attr('default')) {
					v = $(this).attr('default');
				}
				$(this).val(v);
				$(this).trigger('reset');
			});

			$cloned
				.slideDown()
				.data('added', true)
				.effect('highlight');

			$cloned.find(':input:visible').first().focus();
			$cloned.data('id-key', newIdKey);

			$parent.trigger('cloned', [$cloned]);
			$entry.trigger('entry-cloned');
			//formLayoutInit();
		}
		$parent.data('cloning', false);
		$(document).trigger('ajaxComplete');
		return $(this);
	};

	documentReady(function () {
		$('.input-list').inputList();
	});

})(jQuery);
