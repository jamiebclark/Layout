// Table
(function($) {
	var lastCheckedIndex = 0;
	var nextLastCheckedIndex = 0;
	
	$.fn.tableCheckbox = function () {
		return this.each(function() {
			var $checkbox = $(this),
				$row = $checkbox.closest('tr'),
				shiftPress = false;
			
			function getIndex() {
				var name = $checkbox.attr('name'),
					reg = /\[table_checkbox\]\[([\d]+)\]/m,
					idKeyMatch = name.match(reg);
				return idKeyMatch ? idKeyMatch[1] : 0;
			}
			
			function shiftClick(start, stop, markChecked) {
				var $chk;
				if (start > stop) {
					var tmp = start;
					start = stop;
					stop = tmp;
				}
				for (var i = start; i <= stop; i++) {
					$chk = $('input[name="data[table_checkbox][' + i + ']"]');
					if ($chk.length) {
						if (markChecked) {
							$chk.prop('checked', true);
						} else {
							$chk.removeProp('checked');
						}
						$chk.trigger('afterClick');
					}
				}
			}
			
			function afterClick() {
				if ($checkbox.is(':checked')) {
					$row.addClass('active');
				} else {
					$row.removeClass('active');
				}
			}
			
			$(document)
				.keydown(function(e) {
					shiftPress = (e.keyCode == 16);
					return $(this);
				})
				.keyup(function() {
					shiftPress = false;
					return $(this);
				});
			$checkbox
				.data('index', getIndex())
				.click(function(e) {
					var index = $checkbox.data('index'),
						reclick = index == lastCheckedIndex,
						start = reclick ? nextLastCheckedIndex : lastCheckedIndex,
						stop = index,
						markChecked = $(this).is(':checked');
					if (shiftPress) {
						shiftClick(start, stop, markChecked);
					}
					if (!reclick) { 
						nextLastCheckedIndex = lastCheckedIndex;
						lastCheckedIndex = index;
					}
					afterClick();
					e.stopPropagation();
					return $(this);
				})
				.on('afterClick', function() {
					afterClick();
				});
			$row.hover(function() {
				$(this).toggleClass('row-hover');
			}).click(function(e) {
				$checkbox.click();
			});
			return $(this);
		});
	};
	
	$.fn.tableSortLink = function() {
		return this.each(function() {
			var $this = $(this),
				wrapClass = 'table-sort-links',
				linkClass = 'table-sort-links-toggle',
				dropdownClass = 'table-sort-links-dropdown';

			if (!$this.attr('href').match(/.*sort.*direction.*/)) {
				return $(this);
			}

			$this.addClass(linkClass).wrap($('<div></div>').addClass(wrapClass));
			$this.after(function() {
				var $link = $(this),
					url = $link.attr('href'),
					isAsc = $link.hasClass('asc'),
					isDesc = $link.hasClass('desc'),
					linkClass = '',
					label = $link.html();
				if (isAsc) {
					linkClass = 'asc';
				} else if (isDesc) {
					linkClass = 'desc';
				}
				if (isAsc || isDesc) {
					$link.addClass('active');
				}
				if (!url) {
					return '';
				}
				var $dropdown = $('<div></div>').addClass(dropdownClass)
					.append(function() {
						var linkClass = 'asc';
						if (isAsc) {
							linkClass += ' selected';
						}
						return $('<a>Ascending</a>')
							.attr({
								'href': url.replace('direction:desc','direction:asc'),
								'class': linkClass,
								'title': 'Sort the table by "' + label + '" in Ascending order'
							})
							.prepend($('<i class="pull-right glyphicon glyphicon-sort-by-attributes"></i>'));
						
					});
				$dropdown.append(function() {
					var linkClass = 'desc';
					if (isDesc) {
						linkClass += ' selected';
					}
					return $('<a>Descending</a>')
						.attr({
							'href': url.replace('direction:asc', 'direction:desc'),
							'class': linkClass,
							'title': 'Sort the table by this column in Descending order'
						})
						.prepend($('<i class="pull-right glyphicon glyphicon-sort-by-attributes-alt"></i>'));
				});
				return $dropdown.before('<br/>').hide();
			});

			var $wrap = $this.closest("." + wrapClass),
				$dropdown = $("." + dropdownClass, $wrap);
			$wrap.hover(function() {
					if ($wrap.not(':animated')) {
						$this.addClass('is-hovered');
						$dropdown.stop(true).delay(500).slideDown(100);
					}
				},
				function() {
					if ($wrap.not(':animated')) {
						$this.first().removeClass('is-hovered');
						$dropdown.stop(true).slideUp(100);
					}
				}
			);
			return $(this);
		});
	};
	
	$.fn.tableCheckboxes  = function() {
		return this.each(function() {
			var $table = $(this),
				$form = $table.closest('form'),
				$tableCheckboxes = $('input[name*="[table_checkbox]"]', $table),
				$formCheckboxes = $('input[name*="[table_checkbox]"]', $form),
				$checkedCheckboxes = $(':checked', $tableCheckboxes),
				$checkAllCheckboxes = $('th input.check-all', $form),
				$withChecked = $('.table-with-checked', $form);
			
			function setCheckedCheckboxes() {
				$checkedCheckboxes = $formCheckboxes.filter(function() { return $(this).is(':checked');});
			}
			
			function checkAll($checkboxes, setCheck) {
				if (setCheck !== false) {
					var setCheck = true;
				}
				$checkboxes.each(function() {
					$(this).prop('checked', !setCheck).click();
				});
			}
			
			function updateWithChecked() {
				var $withCheckedInfo = $('.table-with-checked-info', $withChecked);
				if ($checkedCheckboxes.length) {
					$withChecked.addClass('fixed');
				} else {
					$withChecked.removeClass('fixed');
				}
				if (!$withCheckedInfo.length) {
					$withCheckedInfo = $('<div class="table-with-checked-info"></div>').prependTo($withChecked);
				}
				$withCheckedInfo.html($checkedCheckboxes.length + ' Checked ');
				var allChecked = ($checkedCheckboxes.length == $formCheckboxes.length);
				$withCheckedInfo.append($('<a></a>', {
					'href': '#',
					'html': allChecked ? 'Uncheck All' : 'Check All',
					'click': function(e) {
						e.preventDefault();
						checkAll($formCheckboxes, !allChecked);
					}
				}));
			}
			
			$checkAllCheckboxes.click(function(e) {
				checkAll($tableCheckboxes, $(this).is(':checked'));
			});
			
			$tableCheckboxes.click(function(e) {
				setCheckedCheckboxes();
				if ($withChecked.length == 1) {
					updateWithChecked();
				}
			});
			return $table;
		});
	};

	$(document).ready(function() {
		$('th a').tableSortLink();
		$('.layout-table,.table-checkboxes').tableCheckboxes();
		$('input[name*="[table_checkbox]"]').tableCheckbox();
	});
})(jQuery);


//Action Menu Fit
(function($) {
	$.fn.actionMenuFit = function() {
		return this.each(function() {
			var $this = $(this),
				$parent = $this.parent('td'),
				$children = $('> a', $this),
				lft = $parent.css('padding-left'),
				rgt = $parent.css('padding-right'),
				w = 0;

			$children.each(function() {
				w += $(this).outerWidth();
			});

			if (lft) {
				w += parseFloat(lft);
			}
			if (rgt) {
				w += parseFloat(rgt);
			}			
			$parent.css('width', w);
			return $this;
		});
	};
	
	$(window).on('load', function() {
		$('.action-menu').actionMenuFit();
	});

})(jQuery);

