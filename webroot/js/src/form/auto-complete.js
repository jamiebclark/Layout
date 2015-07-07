var lastAutoComplete;
var skipFocus = false;
var forceAutoComplete = false;
var autoCompleteVars = [];


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
