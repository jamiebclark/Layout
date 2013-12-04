//Basic functions	
(function($) {
	var disableTrigger = 'layout-disabled';
	$.fn.hideDisableChildren = function() {
		if ($(this).is(':visible')) {
			$(this).slideUp();
		}
		$(this).find(':input,select')
			.filter(function() {
				return !$(this).data('hide-disabled-set');
			})
			.each(function() {
				$(this)
					.data('stored-disabled', $(this).prop('disabled'))
					.data('hide-disabled-set', true)
					.prop('disabled', true)
					.trigger('layout-disabled');
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
			if (!setDisabled) {
				$(this).trigger('layout-enabled');
			}
		});
		
		if (focusFirst) {
			$openInputs.first().select();
		}
		return $(this);
	};
})(jQuery);

(function($) {
	$.fn.inputFormActivate = function() {
		return this.each(function() {
			var $input = $(this),
				$form = $input.closest('form'),
				inactiveClass = 'form-inactive';
			if (!$input.data('form-activate-init')) {
				function updateForm() {
					if ($input.is(':checked')) {
						$form.removeClass(inactiveClass);
					} else {
						$form.addClass(inactiveClass);
					}
				}
				$input.click(function(e) {
					updateForm();
				});
				updateForm();
				$input.data('form-activate-init');
			}
		});
	};
})(jQuery);
documentReady(function() {
	$(':input[class*="form-activate"]').inputFormActivate();
});

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
				if ($dates.length) {
					$dates.datepicker('setDate', val).change();
				}
				if ($times.length) {
					$times.timepicker('setTime', val).change();
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
						setToday();
					} else if ($(this).hasClass('input-date-clear')) {
						setClear();
					}
				});
			});
			
		});
	};
})(jQuery);

$(document).ready(function() {
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
				$checkedControl = $controls.filter(':checked');
			if (!$list.data('input-choice-init')) {
				function select() {
					if (!$checkedControl.length) {
						$checkedControl = $controls.first();
					}
					if (!$checkedControl.is(':disabled')) {
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
				}
				$controls.each(function() {
					$(this)
						.hover(function() {
								$(this).toggleClass('input-choice-hover');
							})
						.click(function(e) {
							$checkedControl = $(this);
							select();
						})
						.bind('layout-enabled', function() {
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
				$listItems = $('> .input-list-inner > .input-list-item', $list),
				$control = $('> .input-list-control', $list),
				$addLink = $('a', $control);

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
						.attr('tabindex', -1)
						.appendTo($listItem)
						.wrap($('<div></div>', {'class' : removeClass + " span1"}))
						.wrap($('<label></label>', {'html': 'Remove'}));
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
			});
			
			$list.bind('cloned', function (e, $cloned) {
				addRemoveBox($cloned);
				$listItems = $('> .input-list-inner > .input-list-item', $list);
			});
			if (!$addLink.length) {
				$addLink = $('<a class="btn btn-small" href="#" tabindex="-1">Add</a>').appendTo($control);
			}
			
			$addLink.click(function(e) {
				e.preventDefault();
				$list.trigger('add');
			});
					
			$listItems.filter(':visible').each(function() {
				addRemoveBox($(this));
				return $(this);
			});
			$list.on('add', function() {
				$listItems.cloneNumbered($list).trigger('inputListAdd');
			}).data('input-list-init', true);
			return $(this);
		});
	};

	$.fn.renumberInput = function(newIdKey, keyIndex) {
		
		if (typeof keyIndex === "undefined") {
			var keyIndex = -1;
		}

		if (!$(this).attr('name')) {
			return $(this);
		}
		var	name = $(this).attr('name'),
			id = $(this).attr('id');
		if (keyIndex != -1) {
			var key = getNameKey(name, keyIndex);
			if (key === false) {
				return $(this);
			}
			var idKeyP = "[" + key.key + "]",
				idKey = key.key,
				newNameStart = name.substr(0,key.index),
				newNameEnd = name.substr(name.indexOf("[", newNameStart.length + idKeyP.length));
			if (newNameEnd != ']') {
				$(this).attr('name', newNameStart + "["+newIdKey+"]" + newNameEnd);
			}
		} else {
			var idKeyMatch = name.match(/\[(\d+)\]/);
			if (idKeyMatch) {
				var idKeyP = idKeyMatch[0],
					idKey = idKeyMatch[1];
				$(this).attr('name', name.replace(idKeyP, "["+newIdKey+"]"));
			}
		}

		if (id) {
			var oldId = id,
				$labels = $('label').filter(function() { return $(this).attr('for') == oldId;}),
				newId = id.replace(idKey, newIdKey);
			$(this).attr('id', newId);
			
			$(this).closest('form').find('label[for="'+oldId+'"]').attr('for',newId);
			/*
			$(this).parent('label[for="'+oldId+'"]').attr('for', newId);
			$(this).closest('.control-group').find('label[for="'+oldId+'"]').attr('for',newId);
			$(this).next('label[for="'+oldId+'"]').attr('for',newId);
			$(this).prev('label[for="'+oldId+'"]').attr('for',newId);
			*/
		}
		return $(this);
	};

	function getNameKey(name, startIndex, forward) {
		if (typeof forward === 'undefined') {
			var forward = true;
		}
		var i = startIndex, 
			k = '', 
			endIndex,
			len = name.length,
			success = true;
		if (forward) {
			while (success && name.charAt(i) != '[') {
				i++;
				if (i > len) {
					success = false;
				}
			}
			while (success && name.charAt(++i) != ']') {
				k += name.charAt(i);
				if (k > len) {
					success = false;
				}
			}
			endIndex = i;
		} else {
			while (success && name.charAt(i) != ']') {
				i--;
				if (i < 0) {
					success = false;
				}
			}
			while (success && name.charAt(--i) != '[') {
				k = name.charAt(i) + k;
				if (k < 0) {
					success = false;
				}
			}
			endIndex = startIndex;
			startIndex = i;
		}
		if (success) {
			return {index: startIndex, key: k, endIndex: endIndex};	
		} else {
			return false;
		}
	}
	
	$.fn.cloneNumbered = function($parent) {
		if ($parent.data('cloning')) {
			return $(this);
		}
		$parent.data('cloning', true);
		var $ids = $(this).find(':input[name*="[id]"]:enabled'),
			$idFirst = $ids.first(),
			idName = $idFirst.attr('name'),
			nameLength = idName.length,
			key = getNameKey(idName, idName.indexOf("[id]"), false);
		$ids = $ids.filter(function() {
			return $(this).attr('name').length < nameLength + 2;	//Filters out sub-ids
		});
		var	$id = $ids.last(),
			name = $id.attr('name');
			
		if ($id.length) {
			var $entry = $(this).last(),
				$cloned = $entry.clone().insertAfter($entry),
				newIdKey = $ids.length;
			$cloned.find('input').removeClass('hasDatepicker');
			$cloned.find('input[name*="[id]"],:text,textareas').val('').trigger('reset');
			$cloned
				.slideDown()
				.data('added', true)
				.find(':input').each(function() {
					return $(this).renumberInput(newIdKey, key.index).removeAttr('disabled');//.removeAttr('checked');
				});
			$cloned.find(':input:visible').first().focus();
			$cloned.data('id-key', newIdKey);
			
			$parent.trigger('cloned', [$cloned]);
			//formLayoutInit();
		}
		$parent.data('cloning', false);
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
		
		if (!$(this).closest('.layout-dropdown-holder').length) {
			$(this).wrap($('<div class="layout-dropdown-holder"></div>'));
		}
		var $parent = $(this),
			$dropdown = $('<' + options.tag + '></' + options.tag + '>'),
			$wrap = $parent.closest('.layout-dropdown-holder'),
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
								console.log('Dropdown call failed: ' + loadOptions.url);
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
			url = $input.data('url'),
			redirectUrl = $input.data('redirect-url'),
			isJson = (url.indexOf('json') > 0),
			$defaultVals = $this.find('select.default-vals').first(),
			$dropdown = $input.dropdown({
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
			$hidden.attr('value', value);
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
				$input.select();
			});
		
		//Init Values
		if (options.action == 'select' && $hidden.val() && $input.val()) {
			clickDisplay($hidden.val(), $input.val());
		} else if ($hidden.val()) {
			if ($display.html() == '') {
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
		$this.bind({
			'clear': function() {
				showText();
				$input.val('');
			}
		}).data('autocomplete-init', true);
		return $this;
	};
})(jQuery);

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
				$ul = $('<ul></ul>').appendTo($div),
				$mask = $('<div class="select-collapse-mask"></div>'),
				$lastLi = false,
				$scrollables = $select.parents().add($(window)),
				$modalParent = $select.closest('.modal'),
				$scrollParent = $select.scrollParent(),
				bulletRepeat = ' - ',
				childIndex = 0,
				lastChildIndex = 0,
				initName = 'collapse-init',
				isDisabled = false;
				
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
				$select.val(val);
			}
			function toggle() {
				return $select.data('expanded') ? hide() : show();
			}
			function show() {
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
				if ($select.is(':visible')) {
					var offset = $select.offset(),
						pos = $select.position(),
						h = $select.outerHeight(),
						w = $select.outerWidth();
				} else {
					var offset = {top: 0, left: 0},
						pos = offset,
						w = 0,
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
			
			if (!$select.data(initName)) {
				$div.appendTo($('body'));
				$mask.appendTo($('body'));
				positionMask();
				var $selectedA = false;
				
				$mask.selectCollapseHoverTrack()
					.hover(function() {$(this).css('cursor','pointer');})
					.click(function() {toggle();});
				
				$(document).click(function() {
					if (!$select.data('hovering') && !$mask.data('hovering') && !$div.data('hovering')) {
						hide();
					}
				});
				$select
					.selectCollapseHoverTrack()
					.click(function(e) {
						e.stopPropagation();
						e.preventDefault();
						toggle();
					});
				
				$div.selectCollapseHoverTrack();
				
				$options.each(function() {
					var $option = $(this),
						$li = $('<li></li>').addClass('no-child'),
						$a = $('<a class="select-collapse-link" href="#"></a>')
							.appendTo($li)
							.wrap('<span class="select-collapse-option"></span>')
							.data('val', $option.val()),
						title = $option.html(),
						titlePre = title.match(/^[^A-Za-z0-9]*/);
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
						for (var i = childIndex; i < lastChildIndex; i++) {
							$ul = $ul.closest('li').closest('ul');
						}
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
})(jQuery);
documentReady(function() {
	$('select.select-collapse').selectCollapse();
});


(function($) {
	$.fn.inputCenterFocus = function() {
		return this.each(function() {
			var $input = $(this),
				$label = $('<div></div>', {
					'class': 'input-centerfocus-label',
					'html': 'Click outside of the textbox to return to normal view'
				}),
				$placeholder = $('<div class="input-centerfocus-placeholder"></div>'),
				$bg = $('<div class="input-centerfocus-bg"></div>'),
				$focusElements = $([$label[0], $placeholder[0], $bg[0]]),
				inputOffset,
				oWidth = $input.css('width'),
				oHeight = $input.outerHeight(),
				oZIndex = $input.css('z-index'),
				oPos = $input.css('position'),
				screenW,
				screenH,
				boxStartLeft,
				boxStartTop,
				boxEndLeft,
				boxEndTop,
				boxEndWidth,
				boxEndHeight,
				boxPctWidth = .8,
				boxPctHeight = .8,
				isWindowFocused = true,
				isFocused = false;
				
			function grow() {
				if (!isWindowFocused) {
					return;
				}
				inputOffset = $input.offset();
				boxStartTop = inputOffset.top;
				boxStartLeft = inputOffset.left;

				screenW = $(window).width();
				screenH = $(window).height();
				
				boxEndHeight = screenH * boxPctHeight;
				boxEndWidth = screenW * boxPctWidth;
				boxEndTop = (screenH - boxEndHeight) / 2;
				boxEndLeft = (screenW - boxEndWidth) / 2;
				
				var boxTop = (boxStartTop - $(window).scrollTop()),
					boxLeft = (boxStartLeft - $(window).scrollLeft());
				$placeholder.css({
					'width': oWidth,
					'height': oHeight
				});
				$(this)
					.addClass('focused')
					.css({
						'width': oWidth,
						'height': oHeight,
						'left': boxLeft,
						'top': boxTop
					})
					.animate({
						'width': boxEndWidth,
						'height': boxEndHeight,
						'left': boxEndLeft,
						'top': boxEndTop
					}, {
						'complete': function() {
							$label.css({
								'top': boxEndTop + boxEndHeight,
								'left': boxEndLeft,
								'width': boxEndWidth
							});
							$focusElements.each(function() {
								$(this).addClass('focused').fadeIn();
							});	
						}
					});
					isFocused = true;
			}
			
			function shrink() {
				if (!isWindowFocused) {
					return;
				}
				$focusElements.each(function() {
					$(this).removeClass('focused').hide();
				});
				$(this)
					.animate({
						'width': oWidth,
						'height': oHeight,
						'left': (boxStartLeft - $(window).scrollLeft()),
						'top': (boxStartTop - $(window).scrollTop())
					}, {
						complete: function() {
							$(this).removeClass('focused');
							isFocused = false;
						}
					});
			}
			
			if (!$input.data('centerfocus-init')) {
				$focusElements.each(function() {
					$(this).hide().insertAfter($input);
				});
				$(window).focus(function() {
					isWindowFocused = true;
					if (isFocused) {
						grow();
					} else {
						shrink();
					}
				}).blur(function() {
					isWindowFocused = false;
				});
				$input.focus(grow).blur(shrink).data('centerfocus-init', true);
			}				
		});
	};
})(jQuery);
documentReady(function() {
	$('.input-centerfocus').inputCenterFocus();
});

(function($) {
	$.fn.textareaAutoresize = function() {
		return this.each(function() {
			var $textarea = $(this),
				h = 0,
				textarea = this;
			function resize() {
				//textarea.style.height = '0px';
				var newHeight = textarea.scrollHeight;
				if (newHeight != h) {
					$textarea.stop().animate({'height': (newHeight) + 'px'});
					h = newHeight;
				}
//				textarea.style.height = (textarea.scrollHeight + 10) + 'px';
			}
			if (!$textarea.data('textareaAutoresize-init')) {
				$textarea
					.css({'overflow': 'hidden', 'height': '0px'})
					.keyup(function() {
						resize();
					})
					.data('textareaAutoresize-init', true);
			}
			resize();
			return $textarea;
		});
	};
})(jQuery);
documentReady(function() {
	$('textarea.textarea-autoresize').textareaAutoresize();
});