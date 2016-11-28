function getUuid() {
	return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
		var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
		return v.toString(16);
	});
}

(function($) {
	function getElementOrCreate(tag, target, props, $parent) {
		if (typeof $parent === "undefined") {
			var $parent = $('body');
		}
		var $el = $(target, $parent);
		if (!$el.length) {
			$el = $("<" + tag + "></" + tag + ">", props).appendTo($parent);
		}
		return $el;
	}

	$.fn.selectCollapseHoverTrack = function() {
		$(this).hover(
			function() {$(this).data('hovering', true);}, 
			function() {$(this).data('hovering', false);}
		);
		return $(this);
	};

	$.fn.selectCollapse = function() {
		return this.each(function() {
			var $select = $(this),
				$selectedA = false,
				uuid = $select.data('uuid') ? $select.data('uuid') : getUuid(),
				id = $select.attr('id') ? $select.attr('id') : 'select-collapse-' + uuid,
				windowId = 'select-collapse-window-' + uuid,
				maskId = 'select-collapse-mask-' + uuid;
			
			$select.data('uuid', uuid).attr('id', id);
			if (!$select.data('optionVals')) {
				$select.data('optionVals', []);
			}

			var $window = getElementOrCreate("div", "#" + windowId, {id: windowId, class: 'select-collapse-window'}),
				$search = getElementOrCreate('input', '.select-collapse-search', {
					class: 'select-collapse-search',
					type: 'text'
				}, $window),
				$ul = getElementOrCreate("ul", '.select-collapse-options', {class: 'select-collapse-options'}, $window),
				$searchResults = getElementOrCreate('ul', '.select-collapse-search-results', {class: 'select-collapse-search-results'}, $window),
				$mask = getElementOrCreate('div', '#' + maskId, {
					id: maskId,
					class: 'select-collapse-mask'
				});

			var $scrollables = $select.parents().add($(window)),
				$modalParent = $select.closest('.modal'),
				$scrollParent = $select.scrollParent(),
				bulletRepeat = ' - ',
				initName = 'collapse-init',
				isDisabled = $select.is(':disabled');
				
			function setLink($a) {
				$window.find('.active').removeClass('active');
				var $li = $a.closest('span.select-collapse-option').addClass('active').closest('li');
				collapseAll();
				expandUp($li);
				set($a.data('val'));
				hide();

				if ($select.data(initName)) {
					// This is causing unpredictable results when other elements are making AJAX calls
					// $select.focus();
				}
			}

			function set(val) {
				$select.val(val).trigger('change');
			}
			function toggle() {
				return $select.data('expanded') ? hide() : show();
			}
			function show() {
				if ($window.is(':hidden')) {
					$search.focus().val('');
					searchUpdate();
				}
				positionMask();
				$select.data('expanded', true).attr('disabled', 'disabled');
				var pos = $select.offset(),
					h = $select.outerHeight(),
					w = $select.outerWidth(),
					zIndex = $select.zIndex();
					
				$window.show().css({
					'top' : pos.top + h, 
					'left' : pos.left, 
					'width' : w
				//	'z-index' : zIndex + 1
				});
				$('.expanded > ul', $window).show();

				$search.focus();
				return true;
			}
			function hide() {
				positionMask();
				$select.data('expanded', false);
				if (!isDisabled) {
					$select.removeAttr('disabled');
				}
				$window.hide();
				return true;
			}
			function expand($li, recursive) {
				var recursive = typeof recursive !== 'undefined' ? recursive : true;
				$li.addClass('expanded').find('.select-collapse-bullet').first().html('-');
				var $ul = $li.find('ul').first();
				if ($ul.is(':hidden')) {
					$ul.slideDown();
				}
				if (recursive) {
					expandUp($li, false);
				}
			}
			function expandUp($li) {
				$li.parentsUntil('.select-collapse-window', 'li').each(function() {
					expand($(this), false);
				});
			}
			function collapse($li, recursive) {
				var recursive = typeof recursive !== 'undefined' ? recursive : true;
				$li.removeClass('expanded').find('.select-collapse-bullet').first().html('+');
				var $ul = $li.find('ul').first();
				if (!$ul.is(':hidden')) {
					$ul.slideUp();
				}
				if (recursive) {
					collapseAll($li);
				}
			}
			
			function collapseAll($li) {
				var $li = typeof $li !== 'undefined' ? $li : $window;
				$li.find('li.expanded').each(function() {
					collapse($(this), false);
				});
			}
			
			function positionMask() {
				var offset, pos, w, h;
				if ($select.is(':visible')) {
					offset = $select.offset();
					pos = $select.position();
					h = $select.outerHeight();
					w = $select.outerWidth();
				} else {
					offset = {top: 0, left: 0};
					pos = offset;
					w = 0;
					h = 0;
				}
				
				if ($scrollParent.length && pos.top > $scrollParent.height()) {
					h -= pos.top - $scrollParent.height();
					if (h < 0) {
						h = 0;
					}
				}
				
				$mask.css({
					'position' : 'absolute',
					'top' : offset.top,
					'left' : offset.left,
					'right' : offset.left + w,
					'bottom' : offset.top + h,
					'width' : w,
					'height' : h
				});
			}

			function searchUpdate() {
				var val = $search.val(),
					valLower = val.toLowerCase(),
					optionVals = $select.data('optionVals');
				if (val === '') {
					$ul.show();
					$searchResults.hide();
					return $(this);
				}
				$ul.hide();
				$searchResults.show().empty();
				for (var i = 0; i < optionVals.length; i++) {
					var label = optionVals[i].label,
						index = label.toLowerCase().indexOf(valLower);

					if (index == -1) {
						continue;
					}
					label = label.replace(val, '<strong>' + val + '</strong>');
					$('<a href="#"></a>')
						.appendTo($searchResults)
						.data('target', optionVals[i].target)
						.wrap('<li><span class="select-collapse-option"></span></li>')
						.html(label)
						.click(function(e) {
							e.preventDefault();
							e.stopPropagation();
							setLink($($(this).data('target')));
						});
				}
			}

			function buildList() {
				var valLabelPath = [],
					$options = $('option', $select),
					$lastLi = false,
					childIndex = 0,
					lastChildIndex = 0,
					$subUl = $ul,
					optionVals = [];

				$selectedA = false;

				$select.data('optionVals', []);
				$ul.empty();
				$options.each(function(optionIndex) {
					var $option = $(this),
						$li = $('<li></li>').addClass('no-child'),
						$a = $('<a class="select-collapse-link" href="#"></a>')
							.appendTo($li)
							.wrap('<span class="select-collapse-option"></span>')
							.data('val', $option.val())
							.attr('id', 'select-collapse-' + $select.attr('id') + '-' + optionIndex),
						title = $option.html(),
						titlePre = title.match(/^[^A-Za-z0-9]*/),
						i = 0;
					if (titlePre) {
						titlePre = titlePre[0];
						title = title.substring(titlePre.length);
						if (titlePre.substr(0,1) == '_') {
							bulletRepeat = '_';
						}
						childIndex = titlePre.split(bulletRepeat).length - 1;
						var bulletIndexLength = childIndex * bulletRepeat.length;
						if (bulletIndexLength < titlePre.length) {
							title = titlePre.substring(bulletIndexLength) + title;
						}
					}
					if (childIndex > lastChildIndex) {
						if ($lastLi) {
							$subUl = $('<ul></ul>').appendTo($lastLi);
							$lastLi.removeClass('no-child').find('a').first().before($('<a class="select-collapse-bullet" href="#">+</a>')
								.click(function(e) {
									e.preventDefault();
									var $li = $(this).closest('li');
									if ($li.hasClass('expanded')) {
										collapse($li);
									} else {
										expand($li);
									}
								})
							);
							collapse($lastLi);
						}
					} else if (childIndex < lastChildIndex) {
						for (i = childIndex; i < lastChildIndex; i++) {
							$subUl = $subUl.closest('li').closest('ul');
							valLabelPath.pop();
						}
						valLabelPath.pop();
					} else {
						valLabelPath.pop();
					}

					lastChildIndex = childIndex;
					$li.appendTo($subUl);
					$a.html(title);
					if ($option.is(':selected')) {
						$selectedA = $a;
					}
					if ($option.attr('disabled')) {
						$a.addClass('disabled').click(function(e) {e.preventDefault();});
					} else {
						$a.click(function(e) {
							e.preventDefault();
							setLink($a);
						});
					}
					$lastLi = $li;
					if ($(this).val()) {
						var valLabel = "";
						valLabelPath.push(title);
						for (i = 0; i < valLabelPath.length; i++) {
							valLabel += "/" + valLabelPath[i];
						}
						optionVals.push({label: valLabel, value: $(this).val(), target: '#' + $a.attr('id')});
					}
				});
				if ($selectedA.length) {
					setLink($selectedA);
				}
				$select.data('optionVals', optionVals);
			}

			if (!$select.data(initName)) {
				positionMask();
				
				$mask.selectCollapseHoverTrack()
					.hover(function() {$(this).css('cursor','pointer');})
					.click(function() {toggle();});
				
				$(document).click(function() {
					if (
						$select.data('expanded') && 
						!$select.data('hovering') && 
						!$mask.data('hovering') && 
						!$window.data('hovering')
					) {
						hide();
					}
				});
				$select
					.selectCollapseHoverTrack()
					.click(function(e) {
						$select.data('expanded', true).attr('disabled', 'disabled');

						e.stopPropagation();
						e.preventDefault();
						toggle();
					})
					.hover(function(e) {
						positionMask();
					});
				
				$window.selectCollapseHoverTrack();

				$search.keyup(function() {
					searchUpdate();
				});

				$scrollables.each(function() {
					$(this).scroll(function() {
						if ($select.length) {
							positionMask();
						}
						if ($select.data('expanded')) {
							show();	//Re-positions
						}
					});
				});

				$modalParent.on('hide', function() {
					hide();
				}).on('shown', function() {
					positionMask();
				});
				
				if ($select.is(':disabled')) {
					isDisabled = true;
				}
				$select
					.on('layout-disabled', function() {
						isDisabled = true;
					})
					.on('layout-enabled', function() {
						isDisabled = false;
					})
					.on('refresh', function() {
						buildList();
					});
				$select.data(initName, true);
			}

			$(document).ajaxComplete(function() {
				buildList();
			});

			buildList();
			return $(this);
		});
	};
	$(document).bind('ajaxComplete ready', function() {
		$('select.select-collapse').selectCollapse();
	});


})(jQuery);
