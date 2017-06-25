//Input Auto Complete
(function($) {
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
				$inputAutocomplete.on('clicked', function(e, value, label) {
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
				$this.on('addValue', function(e, value, label) {
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
		}).on({
			'reset': function() {
				showText();
			}
		});
		/*
		if ($text.val() == '') {
			showText();
		}
		*/
		$this.on({
			'clear': function() {
				showText();
				$input.val('');
			}
		}).data('autocomplete-init', true);
		return $this;
	};

	documentReady(function () {
		$('.input-autocomplete').each(function() {
			var loadOptions = {'action': 'select'};
			if ($(this).hasClass('action-redirect')) {
				loadOptions.action = 'redirect';
			}
			$(this).inputAutoComplete(loadOptions);
		});
		
		$('.input-autocomplete-multi').inputAutoCompleteMulti();
	});
	
})(jQuery);