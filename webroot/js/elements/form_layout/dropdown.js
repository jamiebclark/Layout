//Input Auto Complete
(function($) {
	$.fn.dropdown = function(options) {}
	/*
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
			.on({
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
	*/
	

	$(document).ready(function() {
		$(this).find('select[name*="input_select"]').change(function() {
			$(this).closest('div').find('input').first().attr('value', $(this).attr('value')).change();
		});
	});
})(jQuery);

