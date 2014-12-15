function documentReady(actions) {
	$(document).ready(actions).ajaxComplete(actions);
}

(function($) {
	var toggleCount = 1;
	$.fn.layoutToggle = function() {
		return this.each(function() {
			var $toggle = $(this),
				$control = $toggle.find('.layout-toggle-control input[type*=checkbox]').first(),
				$content = $toggle.find('> .layout-toggle-content').first(),
				$offContent = $toggle.find('> .layout-toggle-off');
				tc = toggleCount++;
			
			$toggle.addClass('toggle' + tc);
			
			function toggleOn() {
				$content.showEnableChildren();
				$offContent.hideDisableChildren();
			}
			function toggleOff() {
				$content.hideDisableChildren();
				$offContent.showEnableChildren();
			}
			function toggleCheck() {
				if (!$control.is(':disabled')) {
					if ($control.is(':checked')) {
						toggleOn();
					} else {
						toggleOff();
					}
				}
			}
			
			if (!$toggle.data('layout-toggle-init')) {
				$control.change(function() {
					toggleCheck();
				}).bind('layout-enabled', function() {
					toggleCheck();
				});
				toggleCheck();
				$toggle.data('layout-toggle-init');
			}
			return $toggle;
		});
	};
})(jQuery);

documentReady(function() {
	$('.layout-toggle').layoutToggle();
});


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

documentReady(function() {
	$('.datepicker').datepick();
	$('.timepicker').timepick();
});

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
						.prepend($('<i class="pull-right glyphicon glyphicon-sort-by-attributes"></i>'));
					
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
					.prepend($('<i class="pull-right glyphicon glyphicon-sort-by-attributes-alt"></i>'));
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
})(jQuery);

$(document).ready(function() {
	$('th a').tableSortLink();
	$('.layout-table,.table-checkboxes').tableCheckboxes();
	$('input[name*="[table_checkbox]"]').tableCheckbox();
});

//Animated Ellipsis
(function($) {
	$.fn.animatedEllipsis = function() {
		return this.each(function() {
			if (!$(this).data('animate-ellipsis-init')) {
				var $container = $(this),
					text = $container.html(),
					interval;
				function animate() {
					var pt = 0;
					var text = $container.html();
					function step() {
						var str = text;
						for (var i = 1; i <= pt; i++) {
							str += ".";
						}
						if (++pt > 3) {
							pt = 0;
						}
						$container.html(str);
					}

					interval = setInterval(step, 300);
				}
				
				if (interval) {
					clearInterval(interval);
				}
				animate();
				$container.data('animate-ellipsis-init');
			}
			return $(this);
		});
	};
})(jQuery);

documentReady(function() {
	$('.animated-ellipsis').animatedEllipsis();
});

// AJAX Modal Loading Window 
(function($) {
	$.fn.ajaxModal = function() {
		return this.each(function() {
			var $a = $(this),
				url = $a.attr('href'),
				title = $a.attr('data-modal-title'),
				customTitle = title,
				ajaxWindowId = '',
				ajaxWindowKey = 1;
			do {
				ajaxWindowId = '#ajax-modal' + (ajaxWindowKey++);
			} while ($a.closest(ajaxWindowId).length);

			var $ajaxWindow = $(ajaxWindowId),
				$ajaxWindowHeader = $('.modal-header', $ajaxWindow),
				$ajaxWindowBody = $('.modal-body', $ajaxWindow);
			if (!$ajaxWindow.length) {
				$ajaxWindow = $('<div></div>', {
					'id': 'ajax-modal',
					'class': 'modal fade'
				});
				var $ajaxDialog = $('<div class="modal-dialog modal-lg"></div>').appendTo($ajaxWindow),
					$ajaxContent = $('<div class="modal-content"></div>').appendTo($ajaxDialog),	
					$ajaxWindowHeader = $('<div class="modal-header"></div>')
						.appendTo($ajaxContent),
					$ajaxWindowBody = $('<div class="modal-body"></div>')
						.appendTo($ajaxContent),
					$ajaxWindowFooter = $('<div class="modal-footer"></div>')
						.appendTo($ajaxContent);
				$ajaxWindowHeader.append($('<button></button>', {
					'type': 'button',
					'class': 'close',
					'data-dismiss': 'modal',
					'aria-hidden': 'true',
					'html': '&times;'
				}));
				$('<a></a>', {
					'html': 'Close',
					'href' : '#',
					'class' : 'btn btn-default',
					'click': function(e) {
						e.preventDefault();
						$ajaxWindow.modal('hide');
					}
				}).appendTo($ajaxWindowFooter);
				
				
				$('<a></a>', {
					'html': 'Update',
					'href': '#',
					'class': 'btn btn-primary',
					'click': function(e) {
						e.preventDefault();
						$('form', $ajaxWindowBody).first().submit();
					}
				}).appendTo($ajaxWindowFooter);
			}
			if (!$a.data('ajax-modal-init')) {
				if (!customTitle) {
					title = 'Window';
				}
				$ajaxWindowHeader.append('<h3>' + title + '</h3>');
				$a.click(function(e) {
					e.preventDefault();
					$ajaxWindowBody.append($('<div class="ajax-loading"></div')
						.append($('<span>Loading</span>').animatedEllipsis())
					);
					$ajaxWindowBody.load(url, function() {
						var $footer = $('.modal-footer', $ajaxWindow),
							$form = $('.modal-body form', $ajaxWindow);
						if (!$form.length) {
							$footer.hide();
						} else {
							$footer.show();
						}
						var $bodyTitle = $('h1', $ajaxWindowBody).first(),
							$bodyTitleParent = $bodyTitle.closest('.page-header');
						
						if ($bodyTitle) {
							if (!customTitle) {
								$('h3', $ajaxWindowHeader).html($bodyTitle.html());
							}
							$bodyTitle.remove();
							if ($bodyTitleParent.empty()) {
								$bodyTitleParent.remove();
							}
						}
						
						$('submit,button[type="submit"]', $form).each(function() {
							if (!$(this).attr('name')) {
								$(this).addClass('modal-body-submit').hide();
							}
						});
						$('.form-actions:empty', $form).remove();
						$(document).trigger('ajax-modal-loaded');
					});
					$ajaxWindow.modal('show');
				});
				$a.data('ajax-modal-init', true);
			}
		});
	};
})(jQuery);
/*
$(document).ready(function() {
	$('.ajax-modal').ajaxModal();
});
*/
documentReady(function () {
	$('.ajax-modal').ajaxModal();
});

// Hover
(function($) {
	var hoverCount = 0;
	$.fn.hoverContent = function() {
		return this.each(function() {
			var $this = $(this),
				$content = $this.find('.hover-content'),
				showWait = 600,
				hideWait = 250,
				isHovered = false,
				hoverLeft = $this.hasClass('hover-left');
			
			if (!$('#hover-content-holder').length) {
				$('body').append($('<div id="hover-content-holder"></div>').css('position', 'static'));
			}
			var $hoverContentHolder = $('#hover-content-holder');
			
			if (!$this.data('hover-init')) {
				$this.find('.hover-content,.hover-over').hover(function() {
					isHovered = true;
					$content.delay(showWait).queue(function(n) {
						if (isHovered) {
							$this.addClass('hovering');
							var $pos = $this.offset();
							$content.show();
							//Left-Right
							if (($pos.left + 30 + $content.width()) > $(window).width()) {
								$content.addClass('position-right');
								$pos.left = $pos.left - $content.width() + $this.width();
							} else {
								$content.removeClass('position-right');
							}
							var $css = {
								top : $pos.top + $this.height(),
								left : $pos.left,
								bottom : 'auto'
							};
							//Top-Bottom
							if (($pos.top + $content.height()) > ($(window).scrollTop() + $(window).height())) {
								$content.addClass('position-down');
								$css.top = $pos.top - $content.height();
							} else {
								$content.removeClass('position-down');
							}
							
							if (hoverLeft) {
								$css.top = $pos.top;
								$css.left = $pos.left - $content.width();
								$content.addClass('hover-left');
							} else {
								$content.removeClass('hover-left');
							}
							
							$content.css($css);
						}
						n();
					});
				}, function() {
					isHovered = false;
					$content.delay(hideWait).queue(function(n) {
						if (!isHovered) {
							$this.removeClass('hovering');
							$content.hide();
						}
						n();
					});
				});
				hoverCount++;
				$this.data('hoverId', hoverCount);
				$content.data('hoverId', hoverCount);
				$content.attr('id', 'hover-content' + hoverCount);
				
				$this.bind('remove', function() {
					$('#hover-content' + $this.data('hoverId')).remove();
				});
				$hoverContentHolder.append($content);
			}
			$this.data('hover-init', true);
			return $this;
		});
	};
})(jQuery);

documentReady(function() {
	$('.hover-layout').hoverContent();	
});

//Scroll-fix
(function($) {
	$.fn.affixContent = function() {
		var $this = $(this),
			$content = $('#content'),
			off = $content.offset(),
			w = $(this).outerWidth(),
			t = function() {
				return (this.top = $this.parent().offset().top);
			},
			b = function() {
				return (this.bottom = $('#footer').outerHeight(true));
			},
			styles = {
				'position': '', 
				'top': 'auto',
				'width': w + "px"
			};
		function setStyles() {
			if ($('body').outerWidth() < 500) {
				styles.position = 'static';
				styles.width = '';
			}
			$this.css(styles)
			
		}
		$(this)
			.affix({offset: {top: t, bottom: b}})
			.on('affixed.bs.affix', function() {
				styles.position = 'fixed';
				styles.top = 0;
				setStyles();
			})
			.on('affixed-top.bs.affix', function() {
				styles.position = 'relative';
				styles.top = 'auto';
				setStyles();
			})
			.on('affixed-bottom.affix', function() {
				styles.position = 'relative';
				setStyles();
			});
			
				
	};
	$.fn.scrollfix = function() {
		return this.each(function() {
			function setFix() {
				if (
					// Top clears the top of screen
					($(window).scrollTop() > top) && 
					// Bottom clears the bottom of screen
					((top + height) < ($(window).height() + $(window).scrollTop()))
				) {
					fix();
				} else {
					unfix();
				}
			}

			function fix() {
				var scrollTop = $(window).scrollTop(),
					scrollOffset = (height > $(window).height()) ? height - $(window).height() : 0,
					overBorder = containerBottom && (scrollTop - scrollOffset + height) > containerBottom,
					setPosition,
					setTop;
				
				if (overBorder) {
					setTop = $container.height() - height;
					setPosition = 'absolute';
				} else {
					setTop = (10 - scrollOffset) + "px";
					setPosition = 'fixed';
				}	
				$scroll.css({
					'width': width,
					'position': setPosition,
					'top': setTop
				});
			}
			function unfix() {
				$scroll.css({'position': 'static', 'width': 'auto'});
			}
			
			function setSizes() {
				unfix();
				height = $scroll.outerHeight();
				width = $scroll.outerWidth();
				pos = $scroll.offset();
				top = pos.top;
				containerBottom;
				if ($container.length) {
					containerPos = $container.offset();
					containerBottom = containerPos.top + $container.height();
					$container.css('min-height', height + "px");
				}
				setFix();
			}

			if (!$(this).data('scroll-init')) {
				var $scroll = $(this),
					$container = $scroll.closest('.row').css('position', 'relative'),
					height,
					width,
					pos,
					top,
					containerBottom,
					containerPos;
				setSizes();
				$(window).scroll(function() {
					setFix();
				}).resize(function() {
					setSizes();
				}).load(function() {
					setSizes();
				});
				
				$(this).data('scroll-init', true);
			}

		});
	};
})(jQuery);
documentReady(function() {
	$('.affix-content').affixContent();
	$('.scrollfix').scrollfix();
});


// Media
(function($) {
	$.fn.layoutMedia = function() {
		var fadeDuration = 100;
		return this.each(function() {
			var $this = $(this),
				$wrap = $this.closest('.media-wrap'),
				$actions = $('.media-actionmenu', $wrap),
				$hover = $wrap.length ? $wrap : $this;
				
			if ($wrap.length == 0) {
				$actions = $('.media-actionmenu', $this);
			}
			$hover.hover(function() {
				$this.addClass('media-hover');
				$actions.fadeIn(fadeDuration);
			}, function() {
				$this.removeClass('media-hover');
				$actions.fadeOut(fadeDuration);
			});
		});
	};
})(jQuery);
documentReady(function() {
	$('.media').layoutMedia();
});

//Action Menu Fit
(function($) {
	$.fn.actionMenuFit = function() {
		return this.each(function() {
			var $this = $(this),
				$parent = $this.parent('td'),
				$children = $('> a', $this);
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
})(jQuery);
$(window).load(function() {
	$('.action-menu').actionMenuFit();
});

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
			})
		});
	};
})(jQuery);
documentReady(function() {
	$('.embed-fit').embedFit();
});
