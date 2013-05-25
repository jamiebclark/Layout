//Basic functions	
(function($) {
	$.fn.hideDisableChildren = function() {
		if ($(this).is(':visible')) {
			$(this).slideUp();
		}
		$(this).find(':input')
			.filter(function() {
				return !$(this).data('hide-disabled-set');
			})
			.each(function() {
				$(this)
					.data('stored-disabled', $(this).prop('disabled'))
					.data('hide-disabled-set', true)
					.prop('disabled', true);
			});
		return $(this);
	};
	$.fn.showEnableChildren = function(focusFirst) {
		if ($(this).is(':hidden')) {
			$(this).slideDown();
		}
		var $openInputs = $(this).find(':input').each(function() {
			var setDisabled = false;
			if ($(this).data('stored-disabled')) {
				setDisabled = $(this).data('stored-disabled');
			}
			$(this).data('hide-disabled-set', false).prop('disabled', setDisabled);
		});
		
		if (focusFirst) {
			$openInputs.first().select();
		}
		return $(this);
	};
})(jQuery);

// Input Date
(function($) {
	$.fn.inputDateAllDay = function() {
		return this.each(function() {
			var $input = $(this),
				$parent = $input.closest('.control-date-all-day').parent().closest('div'),
				$timeInputs = $('input[name*="time"]', $parent);

			if (!$input.data('all-day-init')) {
				function click() {
					var timeCount = 0;
					$timeInputs.each(function() {
						$(this).data('stored-val', $(this).val()).hide();
						if (timeCount++ == 0) {
							$(this).val("12:00am");
						} else {
							$(this).val("11:59pm");
						}
					});
				}
				function unclick() {
					$timeInputs.each(function() {
						$(this).val($(this).data('stored-val')).show();
					});
				}
				function update() {
					if ($input.is(':checked')) {
						click();
					} else {
						unclick();
					}
				}
				
				$input.click(function(e) {
					update();
				});
				update();
			}
			$input.data('all-day-init', true);
		});
	};
	$.fn.inputDate = function() {
		return this.each(function () {
			var $holder = $(this),
				$inputs = $('.date,.time', $holder).filter(function() {
					return !$(this).data('input-date-init');
				}),
				$dates = $inputs.filter(function() { return $(this).hasClass('date');}),
				$times = $inputs.filter(function() { return $(this).hasClass('time');}),
				$controls = $('.input-date-control a', $holder);
			function set(val) {
				if ($dates) {
					$dates.datepicker('setDate', val);
				}
				if ($times) {
					$times.timepicker('setTime', val);
				}
			}
			function setToday() {
				return set(new Date());
			}
			function setClear() {
				return set(null);
			}
			$holder
				.on('today', function() {
					setToday();
				})
				.on('clear', function() {
					setClear();
				});
			$inputs.each(function() {
				$(this).data('input-date-init', true);
			});
			$controls.each(function() {
				$(this).click(function(e) {
					e.preventDefault();
					if ($(this).hasClass('input-date-today')) {
						console.log('Today');
						console.log([$dates.length, $times.length]);
						setToday();
					} else if ($(this).hasClass('input-date-clear')) {
						console.log('Clear');
						setClear();
					}
				});
			});
			
		});
	};
})(jQuery);

documentReady(function() {
	$('.input-date-all-day').inputDateAllDay();
	$('.input-date,.input-time').inputDate();
});


var dateRangeDiffs = new Array();
(function($) {
	//Constants
	var calHover = false;
	var calClick = false;

	var timeHover = false;
	var timeClick = false;

	var dateBuildMonthLoad = false;

	//Time formatting functions
	function timeStampFromStr(dateStr, timeStr) {
		return timeStampFromInt(dateToInt(dateStr), timeToInt(timeStr));
	}
	function timeStampFromInt(dateInt, timeInt) {
		return dateInt * 10000 + timeInt;
	}
	function timeStampSplit(timeStamp) {
		var date = Math.round(timeStamp / 10000);
		var time = timeStamp - date * 10000;
		
		while (time >= 2400) {
			time -= 2400;
			date += 1;	
		}
		return {date : date, time : time};
	}

	function timeToInt(timeStr) {
		if (!timeStr) {
			return 0;
		}
		//Checks if it's a regular number
		if (timeStr && timeStr.match(/^\d+$/)) {
			timeInt = parseInt(timeStr,10);
			if (timeInt < 24) {
				//User just entered the hours (in military time)
				timeInt *= 100;
			} else if (timeInt > 2400) {
				//Anything else viewed as potential military time
				timeInt = 0;
			}
		} else {
			var reg = /(\d+)\s*:*\s*(\d*)\s*([am|pm|AM|PM]*)/;
			timeMatch = timeStr.match(reg);
			if (!timeMatch) {
				return false;
			}
			var h = timeMatch[1];
			var m = timeMatch[2];
			var a = timeMatch[3];
			
			if (isNaN(h) || h == '') {
				h = 0;
			}
			h = parseInt(h,10);
			
			if (isNaN(m) || m == '') {
				m = 0;
			}
			m = parseInt(m,10);
			
			if (a == 'PM' || a == 'pm') {
				if (h != 12) { //Skips 12:00PM
					h += 12;
				}
			} else if (h == 12) {
				//12:00AM
				h = 0;
			}
			var timeInt = (h * 100) + m;
		}
		return timeInt;
	}

	function timeIntToStr(timeInt) {
		//Makes sure to strip any added date stuff
		if (timeInt > 10000) {
			timeInt = timeInt - (Math.floor(timeInt / 10000) * 10000);
		}
		var h = Math.floor(timeInt / 100);
		var m = timeInt - (h * 100);
		var a = 'am';
		if (h >= 12) {
			a = 'pm';
			if (h > 12) {
				h -= 12;
			}
		} else if (h == 0) {
			h = 12;
		}
		var timeStr = '';
		timeStr += h + ':' + zeroPad(m) + a;
		return timeStr;
	}

	function dateToInt(dateStr) {
		var slashDateReg = /(\d+)\s*\/\s*(\d+)\s*\/*\s*(\d*)/;
		var dashDateReg = /(\d+)\-(\d+)\-(\d+)/;
		var dateInt;
		var dateArray = dateStr ? dateStr.match(slashDateReg) : false;
		
		var date = new Date();
		
		var y = 0, m = 0, d = 0;
		if (dateArray) {
			//m/d/Y
			y = dateArray[3];
			m = dateArray[1];
			d = dateArray[2];
			if (isNaN(y) || y == '') {
				y = date.getFullYear();
			}
		} else if (dateStr) {
			dateArray = dateStr.match(dashDateReg);
			if (dateArray) {
			//Y-m-d
				y = dateArray[1];
				m = dateArray[2];
				d = dateArray[3];
			} else {
				return false;
			}
		}
		return parseInt(y,10) * 10000 + parseInt(m,10) * 100 + parseInt(d,10);
	}

	function timeIntGet(getStr, timeInt) {
		var newInt = 0;
		if (getStr == 't') {
			newInt = timeInt - (Math.floor(timeInt / 10000) * 10000);
		} else if (getStr == 'h') {
			newInt = timeIntGet('t', timeInt);
			newInt = Math.floor(newInt / 100);
		} else if (getStr == 'g') {
			newInt = timeIntGet('h', timeInt);
			if (newInt > 12) {
				newInt -= 12;
			} else if (newInt == 0) {
				newInt = 12;
			}
		}
		return parseInt(newInt,10);
	}

	function dateIntToSlash(dateInt) {
		var dateArray = dateIntToArray(dateInt);
		var dateStr = dateArray[1] + "/" + dateArray[2] + "/" + dateArray[0];
		return dateStr;
	}

	function dateIntToDash(dateInt) {
		var dateArray = dateIntToArray(dateInt);
		return 
			dateArray[1].toString() + "-" +
			dateArray[2].toString() + "-" +
			dateArray[0].toString();
	}

	function dateIntToArray(dateInt) {
		//Strips any time info from the end
		if (dateInt > 100000000) {
			dateInt = Math.floor(dateInt / 100000000);
		}
		var y = Math.floor(dateInt / 10000);
		var m = Math.floor((dateInt - y * 10000) / 100);
		var d = dateInt - y * 10000 - m * 100;
		var dateArray = new Array(y,m,d);
		return dateArray;
	}

	function zeroPad(val){
		return (!isNaN(val) && val.toString().length==1)?"0"+val:val;
	}
	$.fn.calendarPickFocus = function() {
	};
	
	$.fn.calendarPickBlur = function() {
	};
	
	$.fn.dateRangeDiffs = function() {
		$(this).find('.dateRange').dateRangeDiff();
		return $(this);
	};
	
	$.fn.dateRangeDiff = function(skipIfFound) {
		var p = $(this).closest('.dateRange');
		if (!p.attr('id')) {
			skipIfFound = false;
			p.attr('id', 'dateRange' + dateRangeDiffs.length);
		}

		if (!skipIfFound) {
			var d = p.dateDiffObjs();
			dateRangeDiffs[p.attr('id')] = d.stampDiff;
		}
		return dateRangeDiffs[p.attr('id')];
	};
		
	
	$.fn.dateRangeObjs = function() {
		var p = $(this).closest('.dateRange');
		var objs = {
			dateStart : p.find('.datetime').first(),
			timeStart : p.find('.time').first(),
			dateStop : p.find('.dateRange2 .datetime').first(),
			timeStop : p.find('.dateRange2 .time').first()
		};
		return objs;		
	};
	
	$.fn.dateDiffObjs = function() {
		var obj = $(this).dateRangeObjs();
		obj.startStamp = timeStampFromStr(obj.dateStart.attr('value'), obj.timeStart.attr('value'));
		obj.stopStamp  = timeStampFromStr(obj.dateStop.attr('value'), obj.timeStop.attr('value'));
		obj.stampDiff = obj.stopStamp - obj.startStamp;
		return obj;
	};
	
	$.fn.dateRangeSet = function(startStamp, stopStamp) {
		var d = $(this).dateRangeObjs();
		d.dateStart.dateStampSet(startStamp);
		d.timeStart.timeStampSet(startStamp);
		d.dateStop.dateStampSet(stopStamp);
		d.timeStop.timeStampSet(stopStamp);
		return $(this);
	};
	$.fn.dateStampSet = function(timeStamp) {
		var dateObj = timeStampSplit(timeStamp);
		$(this).attr('value', dateIntToSlash(dateObj.date));
		return $(this);
	};
	$.fn.timeStampSet = function(timeStamp) {
		var dateObj = timeStampSplit(timeStamp);
		$(this).attr('value', timeIntToStr(dateObj.time));
		return $(this);
	};

	$.fn.dateRangeIn = function() {
		$(this).dateRangeDiff(true);
		return $(this);
	};
	
	$.fn.dateRangeOut = function(dateType) {
		var timeStart = timeStop = dateStart = dateStop = '';
		var dateLabel;

		if ($(this).closest('.dateBuild').find('.dateBuild').length == 1) {
			dateLabel = dateType == 'date' ? 'dateStart' : 'timeStart';
		} else {
			dateLabel = dateType == 'date' ? 'dateStop' : 'timeStop';
		}
		var d = $(this).dateDiffObjs();
		var p = $(this).closest('.dateRange');
		var pId = p.attr('id');
		var oDiff = dateRangeDiffs[pId];
		
		//Time mis-match
		if (d.stampDiff < 0) {
			var isStart = (dateLabel == 'dateStart' || dateLabel == 'timeStart');
			//Checks to see if it can make a minor adjustment in AM / PM to fix the problem
			if (d.stampDiff > -1200) {
				d.stopStamp += 1200;
			} else if (d.stampDiff > -2400) {
				d.stopStamp += 2400;
			} else {
				//Adjusts the opposite stamp to the recorded timestamp difference from before
				if (isStart) {
					d.stopStamp = d.startStamp + oDiff;
				} else {
					d.startStamp = d.stopStamp - oDiff;
				}
			}
		}
		p.dateRangeSet(d.startStamp, d.stopStamp).dateRangeDiff();
		return true;
	};
	
	//Date Build Controls
	$.fn.setDateBuild = function(dateVal, timeVal) {
		var $d = $(this).closest('.dateBuild').find('input[class*=date]');
		var $t = $(this).closest('.dateBuild').find('input.time');
		
		if ($d.length > 0) {
			$d.attr('value',dateVal).change();
		}
		if ($t.length > 0) {
			$t.attr('value',timeVal).change();
		}
		return $(this);
	};
	
	$.fn.calendarHover = function() {
		return this.each(function() {
			return $(this).hover(function() {}, function() {
				if (!$(this).parent().children('input').hasClass('focus')) {
					$(this).hide();
				}
			});
		});
	};
})(jQuery);

$(document).ready(function() {
	$('div.calendarPick,div.timePick').calendarHover();
	$('[class*=dateBuild]').find('input').focus(function() {
		$(this).dateRangeIn();
	}).blur(function() {
		$(this).dateRangeOut($(this).hasClass('datetime') ? 'date' : 'time');
		return $(this);
	});
});




var lastAutoComplete;
var skipFocus = false;
var forceAutoComplete = false;
var autoCompleteVars = new Array();


var dropdownOver = false;
var dropdownInputFocus = false;

function autoCompleteVar(key, val) {
	for (var i = 0; i < autoCompleteVars.length; i++) {
		if (autoCompleteVars[i][0] == key) {
			autoCompleteVars[i][1] = val;
			return true;
		}
	}
	autoCompleteVars.push(new Array(key, val));
	return true;
}

jQuery.fn.log = function (msg) {
	console.log("%s: %o", msg, this);
	return this;
}

jQuery.fn.selectDropdownInit = function() {
	$(this).find('li').mouseover(function() {
		$(this).addClass('hover');
	}).mouseout(function() {
		$(this).removeClass('hover');
	}).click(function() {
		$(this).selectDropdownClick();
	}).find('a').click(function() {
		$(this).closest('li').selectDropdownClick();
		//return false;
	});
	return $(this);
};

jQuery.fn.selectDropdownClick = function() {
	skipFocus = true;
	forceAutoComplete = true;
	$(this).trigger('autoCompleteClick', $(this).html()).closest('.selectDropdown').hide();
	//$(this).closest('.inputAutoComplete').find('input').attr('value', '').focus();
	
	return $(this);
}
jQuery.fn.autoCompleteEntry = function() {
	if (forceAutoComplete || lastAutoComplete != $(this).attr('value')) {
		var t = (new Date()).getTime();
		forceAutoComplete = false;
		lastAutoComplete = $(this).attr('value');
		var dropdown = $(this).parent().find('.selectDropdown');
		var url = dropdown.attr('url') + '?';
		url += 'search=' + $(this).attr('value');
		if (autoCompleteVars) {
			for (var i = 0; i < autoCompleteVars.length; i++) {
				url += '&';
				url += autoCompleteVars[i][0];
				url += '=';
				url += autoCompleteVars[i][1];
				/*if (i < autoCompleteVars.length - 1) {
					
				}*/
			}
		}
		
		$('#javascriptDebug').append($('<div></div>').html(t));
		
		dropdown.ajaxLoad(url, {
			success : function (msg) {
				dropdown.selectDropdownInit();
				dropdown.closest('.inputAutoComplete').trigger('autoCompleteUpdate');
			}
		});
	}
	return $(this);
};

jQuery.fn.hideDropdown = function() {
	if (!dropdownOver && !dropdownInputFocus) {
		$(this).parent().find('.selectDropdown').slideUp();
	}
	return $(this);
};

jQuery.fn.multiSelectInit = function() {
	var $parent = $(this);
	var $content = $parent.contents();
	var $container = $('<div></div>').attr('class', 'select-item').append($content);
	$(this).html($('<div></div>').attr('class', 'select-list').append($container));
	
	var $addLink = $('<div><a href="#">Add</a></div>').find('a').click(function(e) {
		var $selectItem = $parent.find('.select-item').first();
		$parent.find('.select-list').append($selectItem);
		e.preventDefault();
	});
};

var dropdownDelay = 500;
var dropdownTimeout;
var dropdownInput;

(function($) {
	$.fn.inputChoices = function() {
		return this.each(function() {
			var $list = $(this),
				$choices = $('.input-choice', $list),
				$controls = $('.input-choice-control input', $choices),
				$contents = $('.input-choice-content', $choices),
				$checkedControl = $(':checked', $controls);
			if (!$list.data('input-choice-init')) {
				function select() {
					if (!$checkedControl.length) {
						$checkedControl = $controls.first();
					}
					var $choice = $checkedControl.closest('.input-choice');
					$choices.each(function() {
						$(this).removeClass('input-choice-active');
					});
					$choice.addClass('input-choice-active');
					$contents
						.filter(function() {
							return !$(this).closest('.input-choice').hasClass('input-choice-active');
						})
						.each(function() {
							$(this).hideDisableChildren();
						});
					$('.input-choice-content', $choice).showEnableChildren();

				}
				$controls.each(function() {
					$(this)
						.hover(function() {
								$(this).toggleClass('input-choice-hover');
							})
						.click(function(e) {
							$checkedControl = $(this);
							select();
						});
				});
				select();
				$list.data('input-choice-init', true);
			}
		});
	};
})(jQuery);
documentReady(function() {
	$('.input-choices').inputChoices();
});

(function($) {
	$.fn.inputList = function() {
		return this.each(function() {
			var $list = $(this),
				$listItems = $('.input-list-item', $list),
				$addLink = $('<a class="btn btn-small" href="#">Add</a>'),
				$control = $('.input-list-control', $list);
			
			if ($(this).data('input-list-init')) {
				return $(this);
			}

			function addRemoveBox($listItem) {
				var $id = $(':input[name*="id]"]', $listItem).first();
				if (!$id.length) {
					return false;
				}
				var removeClass = 'input-list-item-remove',
					$checkbox = $listItem.find('.' + removeClass + ' input[type=checkbox]'),
					$content = $listItem.children(':not(.'+removeClass+')');
					
				if (!$checkbox.length) {
					$listItem.wrapInner('<div class="span11 input-list-item-inner"></div>');
					var removeName = $id.attr('name').replace(/\[id\]/,'[remove]'),
						removeBoxId = removeName.replace(/(\[([^\]]+)\])/g, '_$2'),
						$checkbox = $('<input/>', {
							'type' : 'checkbox',
							'name' : removeName,
							'value' : 1,
							'id' : removeBoxId
						}).attr('name', removeName).val(1).attr('id',removeBoxId);
					$checkbox
						.appendTo($listItem)
						.wrap($('<div></div>', {'class' : removeClass + " span1"}))
						.after($('<label></label>', {'html': 'Remove','for': removeBoxId}));
				}
				$checkbox.change(function() {
					if ($(this).is(':checked')) {
						$(this).parent().addClass('active');
						$listItem.addClass('remove').find(':input').filter(function() {
							var name = $(this).attr('name');
							return name != $checkbox.attr('name') && (
								!(name.match(/\[id\]/)) || $(this).is('select')
							);
						}).prop('disabled',true);
						//$content.slideUp();
					} else {
						$(this).parent().removeClass('active');
						$listItem.removeClass('remove').find(':input').prop('disabled',false);
						$content.slideDown();
					}
				}).prop('checked', false).change();
			}
			$listItems.each(function() {
				$(this).addClass('row-fluid');
				return $(this);
			}).bind('cloned', function (e, $cloned) {
				addRemoveBox($cloned);
				$listItems = $('.input-list-item', $list);
			});
			$addLink.click(function(e) {
					e.preventDefault();
					$listItems.cloneNumbered().trigger('inputListAdd');
				}).appendTo($control);
					
			$listItems.filter(':visible').each(function() {
				addRemoveBox($(this));
				return $(this);
			});
			$list.data('input-list-init', true);
			return $(this);
		});
	};

	$.fn.renumberInput = function(newIdKey) {
		if (!$(this).attr('name')) {
			return $(this);
		}
		var reg = /\[(\d+)\]/,
			name = $(this).attr('name'),
			id = $(this).attr('id'),
			idKeyMatch = name.match(reg),
			idKeyP = idKeyMatch[0],
			idKey = idKeyMatch[1];
		$(this).attr('name', name.replace(idKeyP, "["+newIdKey+"]"));
		if (id) {
			var oldId = id,
				$labels = $('label').filter(function() { return $(this).attr('for') == oldId;}),
				newId = id.replace(idKey, newIdKey);
			$(this).attr('id', newId);
			$(this).parent('label[for="'+oldId+'"]').attr('for', newId);
			$(this).closest('.control-group').find('label[for="'+oldId+'"]').attr('for',newId);
			$(this).next('label[for="'+oldId+'"]').attr('for',newId);
			$(this).prev('label[for="'+oldId+'"]').attr('for',newId);
		}
		return $(this);
	};

	$.fn.cloneNumbered = function() {
		if ($(this).data('cloning')) {
			return $(this);
		}
		$(this).data('cloning', true);
		var $ids = $(this).find(':input[name*="[id]"]:enabled'),
			$id = $ids.last(),
			name = $id.attr('name');
		if ($id.length) {
			var $entry = $(this).last(),
				$cloned = $entry.clone().insertAfter($entry),
				newIdKey = $ids.length;
			$cloned.find('input').removeClass('hasDatepicker');
			$cloned.find(':text,textarea').val('').trigger('reset');
			$cloned
				.slideDown()
				.data('added', true)
				.find(':input').each(function() {
					return $(this).renumberInput(newIdKey).removeAttr('disabled');//.removeAttr('checked');
				});
			$cloned.find(':input:visible').first().focus();
			$(this).trigger('cloned', [$cloned]);
			formLayoutInit();
		}
		$(this).data('cloning', false);
		$(document).trigger('ajaxComplete');
		return $(this);
	};
})(jQuery);

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
		
		if (!$(this).closest('.dropdown-holder').length) {
			$(this).wrap($('<div class="dropdown-holder"></div>'));
		}
		var $parent = $(this),
			$dropdown = $('<' + options.tag + '></' + options.tag + '>'),
			$wrap = $parent.closest('.dropdown-holder'),
			offset = $parent.offset(),
			dropOffset = $wrap.offset(),
			defaultVals = new Array(),
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
						$dropdown.trigger('dropdownClicked', [value, label]);
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
			.addClass('dropdown')
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
					if ($(this).html() == '') {
						$(this).trigger('clear');
					}
				},
				'setDefault': function(e, vals) {
					if (vals) {
						defaultVals = vals;
					}
					$(this).trigger('empty');
					
					if (options.emptyResult && $(this).val() != '') {
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
								console.log('Dropdown call failed');
							})
							.success(function(data, text, httpRequest) {
								var timestamp = Math.round(new Date().getTime() / 1000);
								if (timestamp < lastTimestamp) {
									$(this).log('Skipping return on result: ' + $(this).val());
									return false;
								}
								lastTimestamp = timestamp;
								if (loadOptions.dataType == 'json') {
									$dropdown.trigger('empty');
									$.each(data, function(key, val) {
										addDropdownOption(val.value, val.label);
									});
								} else {
									$dropdown.html(data);
									$dropdown.find('a').click(function(e) {
										$dropdown.trigger('dropdownClicked', [$(this).attr('href'), $(this).html()]);
									});
								}
								$dropdown.trigger('checkEmpty').trigger('loaded');
							});
					}
				},
				'loaded': function() {
					$(this).removeClass('loading');
				},
				'dropdownClicked' : function(e, value, label) {
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
	
	$.fn.formAutoComplete = function(options) {
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
			$text = $this.find('input[type*=text]').attr('autocomplete', 'off'),
			$hidden = $this.find('input[type*=' + options.store + ']'),
			$display = $this.find('div.display'),
			url = $text.attr('url'),
			redirectUrl = $text.attr('redirect_url'),
			isJson = (url.indexOf('json') > 0),
			$defaultVals = $this.find('select.default-vals').first(),
			$dropdown = $text.dropdown({
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
						$.error('Action: ' + options.action + ' not found for jQuery.formAutoComplete');
					}
					
					if (!$.isFunction(options.click)) {
						clickDisplay(value, label);
					} else {
						options.click(value, label);
					}
					if ($.isFunction(options.afterClick)) {
						options.afterClick(value, label);
					}
				}
			}),
			timeout = false;
		
		function clickDisplay(value, label) {
			showDisplay();
			$display.html(label);
			$hidden.attr('value', value);
		}
		
		function showDisplay() {
			if ($display.length) {
				$display.show();
				$text.hide().attr('disabled', true);
				$hidden.attr('disabled', false);
			} else {
				showText();
			}
		}
		function showText() {
			$display.hide();
			$text.show().attr('disabled', false);
			$hidden.attr('disabled', true);
		}
		
		if ($defaultVals.length) {
			var defaultVals = new Array();
			$defaultVals.attr('disabled', true).hide().find('option').each(function() {
				if ($(this).val() != '') {
					defaultVals.push(new Array($(this).val(), $(this).html()));
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
				$text.select();
			});
		
		//Init Values
		if (options.action == 'select' && $hidden.val() && $text.val()) {
			clickDisplay($hidden.val(), $text.val());
		} else if ($hidden.val()) {
			if ($display.html() == '') {
				$display.html('Value Set');
			}
			showDisplay();
		} else {
			showText();
		}
		
		$text.keyup(function() {
			if (timeout) {
				clearTimeout(timeout);
			}
			timeout = setTimeout(function() {
				$dropdown.trigger('loading', [{
					dataType: isJson ? 'json' : 'html',
					url: (url + (url.indexOf('?') > 0 ? '&' : '?') + options.searchTerm + '=' + $text.attr('value'))
				}]);
			}, options.timeoutWait);
		}).focus(function() {
			$dropdown.trigger('show');
		}).blur(function() {
			$dropdown.delay(400).slideUp();
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
		
		$(this).data('autocomplete-init', true);
		return $(this);
	};
})(jQuery);

function formLayoutInit() {
	$('.input-list').inputList();
	
	$('.input-autocomplete').each(function() {
		var loadOptions = {'action': 'select'};
		if ($(this).hasClass('action-redirect')) {
			loadOptions.action = 'redirect';
		}
		$(this).formAutoComplete(loadOptions);
	});
	$('.input-autocomplete-multi').each(function() {
		if (!$(this).data('autocomplete-init')) {
			var $vals = $(this).find('> .vals'),
				$input = $(this).find('input').first();
			
			if (!$vals.length) {
				$vals = $('<div class="vals"></div>').appendTo($(this));
			}
			var loadOptions = {
				'afterClick': function(value, label) {
					var $existing = $vals.find('[value="'+value+'"]');
					if (!$existing.length) {
						var length = $vals.find(':input').length,
							name = $input.attr('name');
						$('<label>'+label+'</label>').prepend(
							$('<input/>', {
								'type': 'checkbox',
								'name': name,
								'value': value,
								'checked': true
							}).renumberInput(length)
						).appendTo($vals);
						$input.renumberInput(length + 1).val('');
					} else {
						$existing.attr('checked', true);
					}
				}
			};
			if ($(this).hasClass('action-redirect')) {
				loadOptions.action = 'redirect';
			}
			$(this).formAutoComplete(loadOptions);
			if ($(this).find('.dropdown-holder').length) {
				$vals.appendTo($(this).find('> .dropdown-holder'));
			}
		}
	});
	
}

$(document).ajaxComplete(function() {
	formLayoutInit();
});

$(document).ready(function() {
	formLayoutInit();
	$(this).find('.multi-select').multiSelectInit();
	$(this).find('select[name*="input_select"]').change(function() {
		$(this).closest('div').find('input').first().attr('value', $(this).attr('value')).change();
	});
});