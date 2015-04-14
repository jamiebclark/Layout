function documentReady(actions) {
	jQuery(document).ready(actions).ajaxComplete(actions);
}

var test = 1;

(function($) {
	var toggleCount = 1;
	$.fn.layoutToggle = function() {
		return this.each(function() {
			var $toggle = $(this),
				$control = $toggle.find('.layout-toggle-control input[type*=checkbox]').first(),
				$content = $toggle.find('> .layout-toggle-content').first(),
				$offContent = $toggle.find('> .layout-toggle-off'),
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

	documentReady(function() {
		$('.layout-toggle').layoutToggle();
	});
})(jQuery);



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

	documentReady(function() {
		$('.datepicker').datepick();
		$('.timepicker').timepick();
	});

})(jQuery);

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

// Animated Ellipsis
(function($) {

	$.fn.animateText = function(opts) {
		opts = jQuery.extend({}, opts, {
			refreshInterval: 300,
			step: function(text, key) {}
		});

		return this.each(function() {
			var $container = $(this),
				animationKey = 0,
				animationInterval = false;
			animationInterval = setInterval(function() {
				var txt = opts.step($container.html(), animationKey);
				animationKey++;
				$container.html(txt);
			}, opts.refreshInterval);
		});
	};

	$.fn.animatedEllipsis = function() {
		function animatedEllipsisStep(text, key) {
			var	outputText = '',
				pt = key % 3;
			for (var i = 1; i <= pt; i++) {
				outputText += ".";
			}
			return outputText;
		}

		return this.animateText({
			refreshInteval: 300,
			step: function (text, key) {
				var	outputText = '',
					pt = key % 3;
				for (var i = 1; i <= pt; i++) {
					outputText += ".";
				}
				return outputText;
			}
		});
	};

	documentReady(function() {
		$('.animated-ellipsis').animatedEllipsis();
	});
})(jQuery);


// AJAX Modal Loading Window 
(function($) {
	$.fn.ajaxModal = function() {
		return this.each(function() {
			var $a = $(this),
				url = $a.attr('href'),
				title = $a.attr('data-modal-title'),
				modalClass = $a.attr('data-modal-class'),
				customTitle = title,
				ajaxWindowId = '',
				ajaxWindowKey = 1;
			do {
				ajaxWindowId = '#ajax-modal' + (ajaxWindowKey++);
			} while ($a.closest(ajaxWindowId).length);

			if (!modalClass) {
				modalClass = "modal-lg";
			}

			var $ajaxWindow = $(ajaxWindowId),
				$ajaxWindowHeader = $('.modal-header', $ajaxWindow),
				$ajaxWindowBody = $('.modal-body', $ajaxWindow),
				$ajaxWindowFooter = $('.modal-footer', $ajaxWindow);

			if (!$ajaxWindow.length) {
				$ajaxWindow = $('<div></div>', {
					'id': ajaxWindowId,
					'class': 'modal fade'
				});
				var $ajaxDialog = $('<div class="modal-dialog"></div>')
						.addClass(modalClass).appendTo($ajaxWindow),
					$ajaxContent = $('<div class="modal-content"></div>').appendTo($ajaxDialog);

				$ajaxWindowHeader = $('<div class="modal-header"></div>')
					.appendTo($ajaxContent);
				$ajaxWindowBody = $('<div class="modal-body"></div>')
					.appendTo($ajaxContent);
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

	/*
	$(document).ready(function() {
		$('.ajax-modal').ajaxModal();
	});
	*/
	documentReady(function () {
		$('.ajax-modal').ajaxModal();
	});

})(jQuery);


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
							console.log({
								'Window Width:' : $(window).width(),
								'Position Left' : $pos.left,
								'Content Width' : $content.width(),
								'Outer Width'	: $content.outerWidth()
							});

							if (($pos.left + $content.width()) > $(window).width()) {
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
								$content.addClass('position-down').removeClass('position-up');
								$css.top = $pos.top - $content.height();
							} else {
								$content.removeClass('position-down').addClass('position-up');
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

	documentReady(function() {
		$('.hover-layout,.hover-layout-block').hoverContent();	
	});

})(jQuery);

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
			$this.css(styles);	
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
			function checkIsFixed() {
				if (
					// Top clears the top of screen
					(scrollTop > scrollfixTop) && 
					// Bottom clears the bottom of screen
					((scrollfixTop + height) < (windowHeight + scrollTop))
				) {
					fix();
				} else {
					unfix();
				}
			}

			function fix() {
				var scrollOffset = (height > windowHeight) ? height - windowHeight : 0,
					overBorder = containerBottom && (scrollTop - scrollOffset + height) > containerBottom,
					setPosition,
					setTop;
				
				if (overBorder) {
					setTop = $container.height() - height;
					setPosition = 'absolute';
				} else {
					setTop = (topOffset - scrollOffset) + "px";
					setPosition = 'fixed';
				}	
				$scrollfix.css({
					'width': width,
					'position': setPosition,
					'top': setTop
				});
			}
			function unfix() {
				$scrollfix.css({'position': 'static', 'width': 'auto'});
			}
			
			function setSizes() {
				unfix();
				height = $scrollfix.outerHeight();
				width = $scrollfix.outerWidth();
				scrollfixPos = $scrollfix.offset();
				scrollfixTop = scrollfixPos.top;

				windowHeight = $(window).height();
				topOffset = 10;

				$('.scrollfix-fixed:visible').each(function() {
					if ($(this).css('position') == "fixed") {
						var h = $(this).outerHeight();
						windowHeight -= h;
						topOffset += h;
					}
				});

				//containerBottom;
				if ($container.length) {
					containerPos = $container.offset();
					containerBottom = containerPos.top + $container.height();
					$container.css('min-height', height + "px");
				}
				checkIsFixed();
			}

			if (!$(this).data('scroll-init')) {
				var $scrollfix = $(this),
					$container = $scrollfix.closest('.row').css('position', 'relative'),
					height,
					width,
					scrollfixPos,
					scrollfixTop,
					windowHeight,
					scrollTop,
					topOffset,
					containerBottom,
					containerPos;
				setSizes();
				$(window).scroll(function() {
					scrollTop = $(window).scrollTop() + topOffset;
					setSizes();
					checkIsFixed();
				}).resize(function() {
					setSizes();
				}).load(function() {
					setSizes();
				});
				
				$(this).data('scroll-init', true);
			}

		});
	};

	documentReady(function() {
		$('.affix-content').affixContent();
		$('.scrollfix').scrollfix();
	});

})(jQuery);

// Media
(function($) {
	$.fn.layoutMedia = function() {
		var fadeDuration = 100;
		return this.each(function() {
			var $this = $(this),
				$wrap = $this.closest('.media-wrap'),
				$actions = $('.media-actionmenu', $wrap),
				$hover = $wrap.length ? $wrap : $this;
				
			if ($wrap.length === 0) {
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

	documentReady(function() {
		$('.media').layoutMedia();
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
	
	$(window).load(function() {
		$('.action-menu').actionMenuFit();
	});

})(jQuery);


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
			});
		});
	};

	documentReady(function() {
		$('.embed-fit').embedFit();
	});
})(jQuery);
