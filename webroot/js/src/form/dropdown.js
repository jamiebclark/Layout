//Input Auto Complete
(function($) {
	$.fn.dropdown = function(options) {
		if ($(this).data('dropdown-init')) {
			return $(this);
		}
		
		var defaults = {
			'tag': 'ul',
			'itemTag': 'li',
			'emptyMessage': false,
			'emptyResult': false,
			'defaultTitle': 'Default'
		};
		var options = $.extend(defaults, options);
		
		if (!$(this).closest('.layout-dropdown-holder').length) {
			$(this).wrap($('<div class="layout-dropdown-holder"></div>'));
		}
		var $parent = $(this),
			$dropdown = $('<' + options.tag + '></' + options.tag + '>'),
			$wrap = $parent.closest('.layout-dropdown-holder'),
			offset = $parent.offset(),
			dropOffset = $wrap.offset(),
			defaultVals = [],
			lastTimestamp = 0,
			lastUrl = false;

		function addDropdownOption(value, label) {
			var $option = $('<' + options.itemTag + '></' + options.itemTag + '>');
			if (!label && !value) {
				return false;
			} else if (label) {
				$option.append($('<a></a>', {
						'html' : label,
						'href' : '#'
					}).click(function(e) {
						e.preventDefault();
						$dropdown.trigger('clicked', [value, label]);
					})
				);
			} else {
				$option.append(value);
			}
			$option.appendTo($dropdown);
		}
		
		function addEmptyMessage() {
			if (options.emptyMessage) {
				addDropdownOption('<em>' + options.emptyMessage + '</em>');
			}
		}
		
		$dropdown
			.addClass('layout-dropdown hover-window')
			.appendTo($('body'))
			.hide()
			.bind({
				'show': function() {
					offset = $parent.offset();
					$(this).css({
						'top' : offset.top + $parent.outerHeight(),
						'left' : offset.left,// - $parent.outerWidth(),
						'width' : $parent.outerWidth()
					}).trigger('checkEmpty').show();
				},
				'set': function(e, vals, skipEmpty) {
					if (!skipEmpty) {
						$(this).trigger('empty');
					}
					for (var v = 0; v < vals.length; v++) {
						addDropdownOption(vals[v][0], vals[v][1]);
					}
				},
				'empty': function() {
					$(this).html('');
				},
				'checkEmpty': function() {
					if ($(this).html() === '') {
						$(this).trigger('clear');
					}
				},
				'setDefault': function(e, vals) {
					if (vals) {
						defaultVals = vals;
					}
					$(this).trigger('empty');
					
					if (options.emptyResult && $(this).val() !== '') {
						addDropdownOption($('<em></em>').html(options.emptyResult));
					}					
					if (options.defaultTitle) {
						addDropdownOption($('<strong></strong>').html(options.defaultTitle));
					}
					$(this).trigger('set', [defaultVals, true]);
					addEmptyMessage();
				},
				'clear': function(e) {
					if (defaultVals && defaultVals.length) {
						$(this).trigger('setDefault');
					} else {
						addEmptyMessage();
					}
				},
				'loading': function(e, loadOptions) {
					var loadOptions = $.extend({
						dataType: 'json',
						url: false
					}, loadOptions);
					
					$(this).trigger('show').html('Loading...').addClass('loading');
					
					if (loadOptions.url.indexOf('json') > 0) {
						loadOptions.dataType = 'json';
					} else {
						loadOptions.dataType = 'html';
					}
					if (loadOptions.url && loadOptions.url != lastUrl) {
						lastUrl = loadOptions.url;
						var request = $.ajax(loadOptions)
							.error(function(data) {
								console.log('Dropdown call failed: ' + loadOptions.url);
							})
							.success(function(data, text, httpRequest) {
								var timestamp = Math.round(new Date().getTime() / 1000),
									optionLabel = '';
								if (timestamp < lastTimestamp) {
									$(this).log('Skipping return on result: ' + $(this).val());
									return false;
								}
								lastTimestamp = timestamp;
								if (loadOptions.dataType == 'json') {
									$dropdown.trigger('empty');
									if (data) {
										$.each(data, function(key, val) {
											optionLabel = '<strong>' + val.label + '</strong>';
											if (val.city_state) {
												optionLabel += '<br/><small>' + val.city_state + '</small>';
											}
											addDropdownOption(val.value, optionLabel);
										});
									}
								} else {
									$dropdown.html(data);
									$dropdown.find('a').click(function(e) {
										$dropdown.trigger('clicked', [$(this).attr('href'), $(this).html()]);
									});
								}
								$dropdown.trigger('checkEmpty').trigger('loaded');
							});
					}
				},
				'loaded': function() {
					$(this).removeClass('loading');
				},
				'clicked' : function(e, value, label) {
					e.preventDefault();
					$dropdown.hide();
					if ($.isFunction(options.afterClick)) {
						options.afterClick(value, label);
					}
				}
			});
		$(this).data('dropdown-init', true);
		return $dropdown;
	};
	
	$.fn.inputAutoCompleteMulti = function(options) {
		return this.each(function() {
			var $this = $(this),
				$inputAutocomplete = $('.input-autocomplete', $this),
				$inputMultiValues = $('> .input-autocomplete-multi-values', $this),
				$checkboxContainer = $('input[type="checkbox"]', $inputMultiValues).first().closest('div'),
				$checkboxes = $('input[type="checkbox"]', $checkboxContainer),
				$defaultValues = $('.input-autocomplete-multi-default-values'),
				checkboxName = $this.data('name');
				
			if (!$this.data('autocomplete-init')) {
				if (!$checkboxContainer.length) {
					$checkboxContainer = $('<div class="controls"></div>');
					$inputMultiValues.append($checkboxContainer);
				}					
				$inputAutocomplete.bind('clicked', function(e, value, label) {
					$this.trigger('addValue', [value, label]);
					$inputAutocomplete.trigger('clear');
				});
				$defaultValues.change(function() {
					var $option = $('option:selected', $(this)).first(),
						value = $option.val(),
						label = $option.html();
					$this.trigger('addValue', [value, label]);
					$('option[value='+value+']').each(function() {$(this).remove();});
					$('option', $(this)).first().prop('selected', true);
				});
				$this.bind('addValue', function(e, value, label) {
					var $existing = $checkboxContainer.find('[value="'+value+'"]');
					if (!$existing.length) {
						$('<label class="checkbox">'+label+'</label>').prepend(
							$('<input/>', {
								'type': 'checkbox',
								'name': checkboxName,
								'value': value,
								'checked': true
							})
						).appendTo($checkboxContainer);
					} else {
						$existing.prop('checked', true);
					}
				}).data('autocomplete-init', true);
			}
		});		
	};
	
	$.fn.inputAutoComplete = function(options) {
		var defaults = {
			'click' : false,
			'afterClick' : false,
			'timeoutWait' : 250,
			'store' : 'hidden',
			'dataType' : 'json',
			'action' : 'select',
			'searchTerm': 'text',
			'reset': false
		};
		var options = $.extend(defaults, options);
		
		if ($(this).data('autocomplete-init') && !options.reset) {
			return $(this);
		}
		var $this = $(this),
			$input = $this.find('input[type*=text]').attr('autocomplete', 'off'),
			$hidden = $this.find('input[type*=' + options.store + ']'),
			$display = $this.find('div.display'),
			$defaultVals = $this.find('select.default-vals').first(),
			url = $input.data('url'),
			redirectUrl = $input.data('redirect-url'),
			isJson = false;

		if (url) {
			isJson = (url.indexOf('json') > 0);
		}

		var $dropdown = $input.dropdown({
				'tag': isJson ? 'ul' : 'div',
				'itemTag': isJson ? 'li' : 'div',
				'emptyMessage': 'Begin typing to load results',
				'emptyResult': 'No results found. Please try searching for a different phrase',
				'afterClick': function(value, label) {
					if (options.action == 'select') {
						showDisplay();
					} else if (options.action == 'redirect') {
						window.location.href = redirectUrl ? redirectUrl + value : value;
					} else {
						$.error('Action: ' + options.action + ' not found for jQuery.inputAutoComplete');
					}
					
					if (!$.isFunction(options.click)) {
						clickDisplay(value, label);
					} else {
						options.click(value, label);
					}
					if ($.isFunction(options.afterClick)) {
						options.afterClick(value, label);
					}
					$this.trigger('clicked', [value, label]);
				}
			}),
			timeout = false;
		
		function clickDisplay(value, label) {
			showDisplay();
			$display.html(label);
			$hidden.attr('value', value).prop('disabled', false);
		}
		
		function showDisplay() {
			if ($display.length) {
				$display.show();
				$input.hide().attr('disabled', true);
				$hidden.attr('disabled', false);
			} else {
				showText();
			}
		}
		function showText() {
			$display.hide();
			$input.show().attr('disabled', false);
			$hidden.attr('disabled', true);
		}
		
		if ($defaultVals.length) {
			var defaultVals = [];
			$defaultVals.attr('disabled', true).hide().find('option').each(function() {
				if ($(this).val() !== '') {
					defaultVals.push([$(this).val(), $(this).html()]);
				}
			});
			$dropdown.trigger('setDefault', [defaultVals]);
		}
		
		$display
			.hover(function() {
				$(this).css('cursor', 'pointer');
			})
			.click(function(e) {
				e.preventDefault();
				showText();
				$input.select();
			});
		
		//Init Values
		if (options.action == 'select' && $hidden.val() && $input.val()) {
			clickDisplay($hidden.val(), $input.val());
		} else if ($hidden.val()) {
			if ($display.html() === '') {
				$display.html('Value Set');
			}
			showDisplay();
		} else {
			showText();
		}
		
		$input.keyup(function() {
			if (timeout) {
				clearTimeout(timeout);
			}
			timeout = setTimeout(function() {
				$dropdown.trigger('loading', [{
					dataType: isJson ? 'json' : 'html',
					url: (url + (url.indexOf('?') > 0 ? '&' : '?') + options.searchTerm + '=' + $input.val())
				}]);
			}, options.timeoutWait);
		}).focus(function() {
			$dropdown.trigger('show');
		}).blur(function() {
			//$dropdown.delay(400).slideUp();
		}).bind({
			'reset': function() {
				showText();
			}
		});
		/*
		if ($text.val() == '') {
			showText();
		}
		*/
		$this.bind({
			'clear': function() {
				showText();
				$input.val('');
			}
		}).data('autocomplete-init', true);
		return $this;
	};

	documentReady(function () {
		$('.input-list').inputList();
		
		$('.input-autocomplete').each(function() {
			var loadOptions = {'action': 'select'};
			if ($(this).hasClass('action-redirect')) {
				loadOptions.action = 'redirect';
			}
			$(this).inputAutoComplete(loadOptions);
		});
		
		$('.input-autocomplete-multi').inputAutoCompleteMulti();

	});

	$(document).ready(function() {
		$(this).find('.multi-select').multiSelectInit();
		$(this).find('select[name*="input_select"]').change(function() {
			$(this).closest('div').find('input').first().attr('value', $(this).attr('value')).change();
		});
	});
})(jQuery);

