
// Input Date
(function($) {
	$.fn.inputDateAllDay = function() {
		return this.each(function() {
			var $input = $(this),
				$parent = $input.closest('.datepair'),
				$timeInputs = $('input[name*="time"]', $parent);

			function click() {
				var timeCount = 0;
				$timeInputs.each(function() {
					$(this).data('stored-val', $(this).val()).hide();
					if (timeCount++ === 0) {
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

			if (!$input.data('all-day-init')) {
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

	documentReady(function() {
		$('.input-date-all-day').inputDateAllDay();
		$('.input-date,.input-time').inputDate();
	});
})(jQuery);


var dateRangeDiffs = [];
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
			
			if (isNaN(h) || h === '') {
				h = 0;
			}
			h = parseInt(h,10);
			
			if (isNaN(m) || m === '') {
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
		} else if (h === 0) {
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
			if (isNaN(y) || y === '') {
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
			} else if (newInt === 0) {
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
		var dateArray = dateIntToArray(dateInt),
			y = dateArray[1].toString(),
			m = dateArray[2].toString(),
			d = dateArray[0].toString();
		return y + "-" + m + "-" + d;
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
		var timeStart = '',
			timeStop = '',
			dateStart = '',
			dateStop = '',
			dateLabel;

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
