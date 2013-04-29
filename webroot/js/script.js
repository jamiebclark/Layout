(function($) {
	$.fn.datepick = function () {
		return this.each(function() {
			var $input = $(this).datepicker(),
				$control = $input.closest('div'),
				$time = $('.timepicker', $input.closest('.date-time-input'));
			if (!$input.data('date-init')) {
				$('.today', $control).click(function(e) {
					e.preventDefault();
					$input.datepicker('setDate', 'now');
				});
				$('.clear', $control).click(function(e) {
					e.preventDefault();
					$input.datepicker('setDate');
				});
				if ($input.val()) {
					$input.datepicker('setDate', $input.val());
				}
				$input.change(function() {
					if ($time.length) {
						$time.focus();
					}
					return $(this);
				});
			}
			$input.data('date-init', true);
			return $input;
		});
	};
	$.fn.timepick = function () {
		return this.each(function() {
			var $input = $(this).timepicker(),
				$control = $input.closest('div');
			if (!$input.data('time-init')) {
				$('.today', $control).click(function(e) {
					e.preventDefault();
					$input.timepicker('setTime', new Date());
				});
				$('.clear', $control).click(function(e) {
					e.preventDefault();
					$input.val('');
				});
			}
			$input.data('time-init', true);
			return $input;
		});
	};
})(jQuery);

function initLayout() {
	$('.datepicker').datepick();
	$('.timepicker').timepick();
}

//Table
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
					reg = /\[table_checkbox\]\[([\d]+)\]/m
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
							$chk.attr('checked', true);
						} else {
							$chk.removeAttr('checked');
						}
					}
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
					if (markChecked) {
						$row.addClass('active');
					} else {
						$row.removeClass('active');
					}
					if (shiftPress) {
						shiftClick(start, stop, markChecked);
					}
					if (!reclick) { 
						nextLastCheckedIndex = lastCheckedIndex;
						lastCheckedIndex = index;
					}
					return $(this);
				});
			return $(this);
		});
	};
	
	$.fn.tableSortLink = function() {
		var $sortLinks = $(this).filter(function() {
			return $(this).attr('href').match(/.*sort.*direction.*/);
		});
		$sortLinks.addClass('sort-select').wrap('<div class="table-sort-links"></div>');
		$sortLinks.after(function() {
			var $link = $(this),
				url = $link.attr('href')
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
				$link.addClass('selected');
			}
			if (!url) {
				return '';
			}
			var $div = $('<div class="table-sort"></div>')
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
						.prepend($('<i class="icon-caret-up"></i>'));
					
				});
			$div.append(function() {
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
					.prepend($('<i class="icon-caret-down"></i>'));
			});
			return $div.before('<br/>').hide();
		});
		$sortLinks.closest('.table-sort-links').hover(function() {
				if ($(this).not(':animated')) {
					$(this).find('a').first().addClass('hover');
					$(this).find('.table-sort').stop(true).delay(500).slideDown(100);
				}
			},
			function() {
				if ($(this).not(':animated')) {
					$(this).find('a').first().removeClass('hover');
					$(this).find('.table-sort').stop(true).slideUp(100);
				}
			}
		);
		return $(this);
	};
})(jQuery);

$(document).ready(function() {
	$('th a').tableSortLink();
	$('input[name*="[table_checkbox]"]').tableCheckbox();
	$('th input.check-all').click(function(e) {
		var $check = $(this);
		$('input[name*=table_checkbox]', $check.closest('table')).each(function() {
			$(this).prop('checked', $check.is(':checked'));
		});
	});
});

$(document)
	.ready(function() {initLayout();})
	.ajaxComplete(function() {initLayout();});