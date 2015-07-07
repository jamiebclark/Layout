(function($) {
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
				$options = $select.find('option'),
				$div = $('<div class="select-collapse-window"></div>'),
				$ul = $('<ul class="select-collapse-options"></ul>'),
				$searchResults = $('<ul class="select-collapse-search-results"></ul>'),
				$mask = $('<div class="select-collapse-mask"></div>'),
				$search = $('<input class="select-collapse-search" type="text"/>'),
				$lastLi = false,
				$scrollables = $select.parents().add($(window)),
				$modalParent = $select.closest('.modal'),
				$scrollParent = $select.scrollParent(),
				bulletRepeat = ' - ',
				childIndex = 0,
				lastChildIndex = 0,
				initName = 'collapse-init',
				isDisabled = $select.is(':disabled'),
				vals = [];
				
			function setLink($a) {
				$div.find('.active').removeClass('active');
				var $li = $a.closest('span.select-collapse-option').addClass('active').closest('li');
				collapseAll();
				expandUp($li);
				set($a.data('val'));
				hide();
				if ($select.data(initName)) {
					$select.focus();
				}
			}
			function set(val) {
				$select.val(val).trigger('change');
			}
			function toggle() {
				return $select.data('expanded') ? hide() : show();
			}
			function show() {
				if ($div.is(':hidden')) {
					$search.focus().val('');
					searchUpdate();
				}
				positionMask();
				$select.data('expanded', true).attr('disabled', 'disabled');
				var pos = $select.offset(),
					h = $select.outerHeight(),
					w = $select.outerWidth(),
					zIndex = $select.zIndex();
					
				$div.show().css({
					'top' : pos.top + h, 
					'left' : pos.left, 
					'width' : w
				//	'z-index' : zIndex + 1
				});
				$('.expanded > ul', $div).show();

				$search.focus();
				return true;
			}
			function hide() {
				positionMask();
				$select.data('expanded', false);
				if (!isDisabled) {
					$select.removeAttr('disabled');
				}
				$div.hide();
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
				var $li = typeof $li !== 'undefined' ? $li : $div;
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
				var val = $search.val();
				if (val === '') {
					$('.select-collapse-options', $div).show();
					$searchResults.hide();
					return $(this);
				}
				$('.select-collapse-options', $div).hide();
				$searchResults.show().empty();
				for (var i = 0; i < vals.length; i++) {
					var label = vals[i].label,
						index = label.indexOf(val);

					if (index == -1) {
						continue;
					}
					label = label.replace(val, '<strong>' + val + '</strong>');
					$('<a href="#"></a>')
						.appendTo($searchResults)
						.data('target', vals[i].target)
						.wrap('<li><span class="select-collapse-option"></span></li>')
						.html(label)
						.click(function(e) {
							e.preventDefault();
							e.stopPropagation();
							setLink($($(this).data('target')));
						});
				}
			}
			
			if (!$select.data(initName)) {
				$div.append($search).append($ul).append($searchResults).appendTo($('body'));
				$mask.appendTo($('body'));
				positionMask();
				var $selectedA = false;
				
				$mask.selectCollapseHoverTrack()
					.hover(function() {$(this).css('cursor','pointer');})
					.click(function() {toggle();});
				
				$(document).click(function() {
					if (
						$select.data('expanded') && 
						!$select.data('hovering') && 
						!$mask.data('hovering') && 
						!$div.data('hovering')
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
				
				$div.selectCollapseHoverTrack();
				
				vals = [];
				var valLabelPath = [];
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

					if (childIndex > lastChildIndex && $lastLi) {
						$ul = $('<ul></ul>').appendTo($lastLi);
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
					} else if (childIndex < lastChildIndex) {
						for (i = childIndex; i < lastChildIndex; i++) {
							$ul = $ul.closest('li').closest('ul');
							valLabelPath.pop();
						}
					} else {
						valLabelPath.pop();
						valLabelPath.pop();
					}

					lastChildIndex = childIndex;
					$li.appendTo($ul);
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
						vals.push({label: valLabel, value: $(this).val(), target: '#' + $a.attr('id')});
					}
				});
				
				$search.keyup(function() {
					searchUpdate();
				});

				if ($selectedA.length) {
					setLink($selectedA);
				}
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
					.data(initName, true);
			}
			return $(this);
		});
	};
	
	documentReady(function() {
		$('select.select-collapse').selectCollapse();
	});

})(jQuery);
