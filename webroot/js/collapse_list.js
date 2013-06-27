(function($) {
	$.fn.collapseList = function() {
		return this.each(function() {
			var $collapseList = $(this),
				$items = $('li.collapse-list-item', $collapseList),
				$titles = $('.collapse-list-item-title', $collapseList),
				$lists = $('ul.collapse-list-list', $collapseList),
				$rootList = $lists.first(),
				$rootItems = $rootList.find('> li'),
				$selected = $titles.filter('.selected').first(),
				itemCount = 0,
				isDraggable = $collapseList.hasClass('draggable'),
				collapseTimeout = false,
				hash = window.location.hash ? window.location.hash.substring(1) : false,
				$body = $('html,body');

			function selectListItem(id) {
				var qName = 'collapse-list-queue';
				
				$collapseList
					.clearQueue(qName)
					.queue(qName, function(next) {
						updateRootList(true);
						next();
					})
					.queue(qName, function(next) {
						$titles.removeClass('selected');
						next();
					});
				console.log($selected.length);
				if (id) {
					$selected = $rootList.find('#' + id + ' .collapse-list-item-title').first();
				}
				console.log($selected.length);
				if ($selected.length) {
					$collapseList
						.queue(qName, function(next) {
							$selected.parentsUntil('.collapse-list', 'ul.collapse-list-list').each(function() {
								updateList($(this), false, false, qName);
								console.log('Displaying Collapse List Level');
							});
							next();
						})
						.queue(qName, function(next) {
							updateList($selected, false, false, qName);
							next();
						})
						.queue(qName, function(next) {
							$selected.addClass('selected');
							console.log('Selecting Current Item');
							next();
						})
						.delay(2000, qName)
						.queue(qName, function(next) {
							scrollToSelected();
							next();
						});
				}
				$collapseList.dequeue(qName);
			}

			function scrollToSelected() {
				var scrT = $selected.offset().top - 200;
				$('html,body').scrollTop(scrT);
			}
			
			function updateRootList(hide) {
				$rootList.find('> li.collapse-list-item').each(function() {
					if ($(this).find('> ul.collapse-list-list').length) {
						updateList($(this).find('> ul.collapse-list-list'), hide);
					}
				});
			}
			
			function updateList($list, hide, sub, qName) {
				if (!$list) {
					var $list = $rootList;
				}
				if (!qName) {
					var qName = false;
				}
				
				var $checkbox = $list.closest('li').find('> label.collapse-list-item-toggle input');
				if (typeof hide != "undefined") {
					if ($checkbox.is(':checked') != hide) {
						$checkbox.attr('checked', hide).change();
					}
				} else {
					hide = $checkbox.is(':checked');
				}
				
				if (hide) {
					$list.slideUp();
				} else {
					$list.slideDown();
				}

				if (!sub && hide) {
					$list.find('ul.collapse-list-list').each(function() {
						updateList($(this), hide, true, qName);
					});
				}
			}

			$lists.filter(function() {
				if ($(this).data('init')) {
					return false;
				}
				return true;
			}).each(function() {
				$(this).data('init', true);
			});
			
			$items.filter(function() {
				if ($(this).data('init')) {
					return false;
				}
				return true;
			}).each(function() {
				var id = 'collapse_list_item' + (itemCount++),
					$label = $(this).find('> label.cl'),
					$title = $(this).find('.collapse-list-item-title').first(),
					$titleLabel = $(this).find('> label.collapse-list-item-title-label'),
					$titleInput = $titleLabel.find('> .collapse-list-item-title input'),
					$checkbox = $label.find('input');
				
				$titleLabel.hover(function() {
					$(this).toggleClass('hover');
				});
				
				$titleInput.change(function() {
					$title.toggleClass('selected', $(this).is(':checked'));
				});
				if ($(this).find('ul.collapse-list-list').length) {
					if (!$checkbox.length) {
						$checkbox = $('<input/>', {
							'type': 'checkbox',
							'name': id,
							'id': id
						}).prependTo($(this));
						$checkbox.wrap($('<label class="collapse-list-item-toggle" for="'+id+'"></label>'));
						$checkbox.before('<span>-</span>');
						$label = $checkbox.closest('label');
					}
					$label.hover(function() {
							$(this).toggleClass('hover');
						});
						
					$checkbox.change(function() {
						var $li = $(this).closest('li'),
							checked = $(this).is(':checked');
						$li.find('> label.collapse-list-item-toggle span').html(checked ? '+' : '-');
						updateList($li.find('> ul.collapse-list-list'), checked);
						return $(this);
					});
				}
				
				if (isDraggable) {
					$(this).draggable({
						revert: 'invalid',
						refreshPositions: true,
						start: function(e, ui) {
							$(this).addClass('dragging');
						},
						stop: function(e, ui) {
							$(this).removeClass('dragging');
						}
					})
					.droppable({
						drop: function(e, ui) {
							console.log('Dropped');
							if (collapseTimeout) {
								clearTimeout(collapseTimeout);
							}

							$(ui.draggable).insertBefore($(this)).animate({
								'left': 0,
								'top': 0
							}).find('.cl-t').first().addClass('selected');
							$items.removeClass('drop-over');
						},
						over: function(e, ui) {
							var p1 = $(ui.draggable).offset();
							$(this).addClass('drop-over').parentsUntil('.collapse-list').removeClass('drop-over');
							console.log('Before ' + p1.top);
							
							if ($checkbox.length && $checkbox.is(':checked')) {
								if (collapseTimeout) {
									clearTimeout(collapseTimeout);
								}
								collapseTimeout = setTimeout(function() {
									$checkbox.attr('checked', false).change();
									var p2 = $(ui.draggable).offset();
									console.log('After ' + p2.top + ': Adjusting: ' + (p2.top - p1.top));
									$(ui.draggable).animate({
										'top': '-=' + (p2.top - p1.top)
									});
								}, 1000);
							}
						},
						out: function(e, ul) {
							$(this).removeClass('drop-over');
							if (collapseTimeout) {
								clearTimeout(collapseTimeout);
							}
						}
					});
				}
				$(this).data('init', true);
			});
			
			if (!$collapseList.data('init')) {
				$collapseList.bind('update', function() {
					updateList($rootList, true);
				});
				selectListItem(hash);
				$collapseList.tableCheckboxes();
			}

			return $collapseList.data('init', true);
		});
	};
})(jQuery);

$(document).ready(function() {
	$('.collapse-list').collapseList();
});