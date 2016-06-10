function autoCompleteVar(a,b){for(var c=0;c<autoCompleteVars.length;c++)if(autoCompleteVars[c][0]==a)return autoCompleteVars[c][1]=b,!0;return autoCompleteVars.push(new Array(a,b)),!0}function getUuid(){return"xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g,function(a){var b=16*Math.random()|0,c="x"==a?b:3&b|8;return c.toString(16)})}var lastAutoComplete,skipFocus=!1,forceAutoComplete=!1,autoCompleteVars=[],dropdownOver=!1,dropdownInputFocus=!1;jQuery.fn.log=function(a){return console.log("%s: %o",a,this),this},jQuery.fn.selectDropdownInit=function(){return $(this).find("li").mouseover(function(){$(this).addClass("hover")}).mouseout(function(){$(this).removeClass("hover")}).click(function(){$(this).selectDropdownClick()}).find("a").click(function(){$(this).closest("li").selectDropdownClick()}),$(this)},jQuery.fn.selectDropdownClick=function(){return skipFocus=!0,forceAutoComplete=!0,$(this).trigger("autoCompleteClick",$(this).html()).closest(".selectDropdown").hide(),$(this)},jQuery.fn.autoCompleteEntry=function(){if(forceAutoComplete||lastAutoComplete!=$(this).attr("value")){var a=(new Date).getTime();forceAutoComplete=!1,lastAutoComplete=$(this).attr("value");var b=$(this).parent().find(".selectDropdown"),c=b.attr("url")+"?";if(c+="search="+$(this).attr("value"),autoCompleteVars)for(var d=0;d<autoCompleteVars.length;d++)c+="&",c+=autoCompleteVars[d][0],c+="=",c+=autoCompleteVars[d][1];$("#javascriptDebug").append($("<div></div>").html(a)),b.ajaxLoad(c,{success:function(a){b.selectDropdownInit(),b.closest(".inputAutoComplete").trigger("autoCompleteUpdate")}})}return $(this)},jQuery.fn.hideDropdown=function(){return dropdownOver||dropdownInputFocus||$(this).parent().find(".selectDropdown").slideUp(),$(this)},jQuery.fn.multiSelectInit=function(){var a=$(this),b=a.contents(),c=$("<div></div>").attr("class","select-item").append(b);$(this).html($("<div></div>").attr("class","select-list").append(c));$('<div><a href="#">Add</a></div>').find("a").click(function(b){var c=a.find(".select-item").first();a.find(".select-list").append(c),b.preventDefault()})},function(a){a.fn.inputDateAllDay=function(){return this.each(function(){function b(){var b=0;g.each(function(){a(this).data("stored-val",a(this).val()).hide(),0===b++?a(this).val("12:00am"):a(this).val("11:59pm")})}function c(){g.each(function(){a(this).val(a(this).data("stored-val")).show()})}function d(){e.is(":checked")?b():c()}var e=a(this),f=e.closest(".datepair"),g=a('input[name*="time"]',f);e.data("all-day-init")||(e.click(function(a){d()}),d()),e.data("all-day-init",!0)})},a.fn.inputDate=function(){return this.each(function(){function b(a){g.length&&g.datepicker("setDate",a).change(),h.length&&h.timepicker("setTime",a).change()}function c(){return b(new Date)}function d(){return b(null)}var e=a(this),f=a(".date,.time",e).filter(function(){return!a(this).data("input-date-init")}),g=f.filter(function(){return a(this).hasClass("date")}),h=f.filter(function(){return a(this).hasClass("time")}),i=a(".input-date-control a",e);e.on("today",function(){c()}).on("clear",function(){d()}),f.each(function(){a(this).data("input-date-init",!0)}),i.each(function(){a(this).click(function(b){b.preventDefault(),a(this).hasClass("input-date-today")?c():a(this).hasClass("input-date-clear")&&d()})})})},documentReady(function(){a(".input-date-all-day").inputDateAllDay(),a(".input-date,.input-time").inputDate()})}(jQuery);var dateRangeDiffs=[];!function(a){function b(a,b){return c(g(a),e(b))}function c(a,b){return 1e4*a+b}function d(a){for(var b=Math.round(a/1e4),c=a-1e4*b;c>=2400;)c-=2400,b+=1;return{date:b,time:c}}function e(a){if(!a)return 0;if(a&&a.match(/^\d+$/))f=parseInt(a,10),24>f?f*=100:f>2400&&(f=0);else{var b=/(\d+)\s*:*\s*(\d*)\s*([am|pm|AM|PM]*)/;if(timeMatch=a.match(b),!timeMatch)return!1;var c=timeMatch[1],d=timeMatch[2],e=timeMatch[3];(isNaN(c)||""===c)&&(c=0),c=parseInt(c,10),(isNaN(d)||""===d)&&(d=0),d=parseInt(d,10),"PM"==e||"pm"==e?12!=c&&(c+=12):12==c&&(c=0);var f=100*c+d}return f}function f(a){a>1e4&&(a-=1e4*Math.floor(a/1e4));var b=Math.floor(a/100),c=a-100*b,d="am";b>=12?(d="pm",b>12&&(b-=12)):0===b&&(b=12);var e="";return e+=b+":"+j(c)+d}function g(a){var b=/(\d+)\s*\/\s*(\d+)\s*\/*\s*(\d*)/,c=/(\d+)\-(\d+)\-(\d+)/,d=a?a.match(b):!1,e=new Date,f=0,g=0,h=0;if(d)f=d[3],g=d[1],h=d[2],(isNaN(f)||""===f)&&(f=e.getFullYear());else if(a){if(d=a.match(c),!d)return!1;f=d[1],g=d[2],h=d[3]}return 1e4*parseInt(f,10)+100*parseInt(g,10)+parseInt(h,10)}function h(a){var b=i(a),c=b[1]+"/"+b[2]+"/"+b[0];return c}function i(a){a>1e8&&(a=Math.floor(a/1e8));var b=Math.floor(a/1e4),c=Math.floor((a-1e4*b)/100),d=a-1e4*b-100*c,e=new Array(b,c,d);return e}function j(a){return isNaN(a)||1!=a.toString().length?a:"0"+a}a.fn.calendarPickFocus=function(){},a.fn.calendarPickBlur=function(){},a.fn.dateRangeDiffs=function(){return a(this).find(".dateRange").dateRangeDiff(),a(this)},a.fn.dateRangeDiff=function(b){var c=a(this).closest(".dateRange");if(c.attr("id")||(b=!1,c.attr("id","dateRange"+dateRangeDiffs.length)),!b){var d=c.dateDiffObjs();dateRangeDiffs[c.attr("id")]=d.stampDiff}return dateRangeDiffs[c.attr("id")]},a.fn.dateRangeObjs=function(){var b=a(this).closest(".dateRange"),c={dateStart:b.find(".datetime").first(),timeStart:b.find(".time").first(),dateStop:b.find(".dateRange2 .datetime").first(),timeStop:b.find(".dateRange2 .time").first()};return c},a.fn.dateDiffObjs=function(){var c=a(this).dateRangeObjs();return c.startStamp=b(c.dateStart.attr("value"),c.timeStart.attr("value")),c.stopStamp=b(c.dateStop.attr("value"),c.timeStop.attr("value")),c.stampDiff=c.stopStamp-c.startStamp,c},a.fn.dateRangeSet=function(b,c){var d=a(this).dateRangeObjs();return d.dateStart.dateStampSet(b),d.timeStart.timeStampSet(b),d.dateStop.dateStampSet(c),d.timeStop.timeStampSet(c),a(this)},a.fn.dateStampSet=function(b){var c=d(b);return a(this).attr("value",h(c.date)),a(this)},a.fn.timeStampSet=function(b){var c=d(b);return a(this).attr("value",f(c.time)),a(this)},a.fn.dateRangeIn=function(){return a(this).dateRangeDiff(!0),a(this)},a.fn.dateRangeOut=function(b){var c;c=1==a(this).closest(".dateBuild").find(".dateBuild").length?"date"==b?"dateStart":"timeStart":"date"==b?"dateStop":"timeStop";var d=a(this).dateDiffObjs(),e=a(this).closest(".dateRange"),f=e.attr("id"),g=dateRangeDiffs[f];if(d.stampDiff<0){var h="dateStart"==c||"timeStart"==c;d.stampDiff>-1200?d.stopStamp+=1200:d.stampDiff>-2400?d.stopStamp+=2400:h?d.stopStamp=d.startStamp+g:d.startStamp=d.stopStamp-g}return e.dateRangeSet(d.startStamp,d.stopStamp).dateRangeDiff(),!0},a.fn.setDateBuild=function(b,c){var d=a(this).closest(".dateBuild").find("input[class*=date]"),e=a(this).closest(".dateBuild").find("input.time");return d.length>0&&d.attr("value",b).change(),e.length>0&&e.attr("value",c).change(),a(this)},a.fn.calendarHover=function(){return this.each(function(){return a(this).hover(function(){},function(){a(this).parent().children("input").hasClass("focus")||a(this).hide()})})}}(jQuery),$(document).ready(function(){$("div.calendarPick,div.timePick").calendarHover(),$("[class*=dateBuild]").find("input").focus(function(){$(this).dateRangeIn()}).blur(function(){return $(this).dateRangeOut($(this).hasClass("datetime")?"date":"time"),$(this)})}),function(a){a.fn.dropdown=function(b){function c(c,d){var e=a("<"+b.itemTag+"></"+b.itemTag+">");return d||c?(d?e.append(a("<a></a>",{html:d,href:"#"}).click(function(a){a.preventDefault(),g.trigger("clicked",[c,d])})):e.append(c),void e.appendTo(g)):!1}function d(){b.emptyMessage&&c("<em>"+b.emptyMessage+"</em>")}if(a(this).data("dropdown-init"))return a(this);var e={tag:"ul",itemTag:"li",emptyMessage:!1,emptyResult:!1,defaultTitle:"Default"},b=a.extend(e,b);a(this).closest(".layout-dropdown-holder").length||a(this).wrap(a('<div class="layout-dropdown-holder"></div>'));var f=a(this),g=a("<"+b.tag+"></"+b.tag+">"),h=f.closest(".layout-dropdown-holder"),i=f.offset(),j=(h.offset(),[]),k=0,l=!1;return g.addClass("layout-dropdown hover-window").appendTo(a("body")).hide().bind({show:function(){i=f.offset(),a(this).css({top:i.top+f.outerHeight(),left:i.left,width:f.outerWidth()}).trigger("checkEmpty").show()},set:function(b,d,e){e||a(this).trigger("empty");for(var f=0;f<d.length;f++)c(d[f][0],d[f][1])},empty:function(){a(this).html("")},checkEmpty:function(){""===a(this).html()&&a(this).trigger("clear")},setDefault:function(e,f){f&&(j=f),a(this).trigger("empty"),b.emptyResult&&""!==a(this).val()&&c(a("<em></em>").html(b.emptyResult)),b.defaultTitle&&c(a("<strong></strong>").html(b.defaultTitle)),a(this).trigger("set",[j,!0]),d()},clear:function(b){j&&j.length?a(this).trigger("setDefault"):d()},loading:function(b,d){var d=a.extend({dataType:"json",url:!1},d);if(a(this).trigger("show").html("Loading...").addClass("loading"),d.url.indexOf("json")>0?d.dataType="json":d.dataType="html",d.url&&d.url!=l){l=d.url;a.ajax(d).error(function(a){console.log("Dropdown call failed: "+d.url)}).success(function(b,e,f){var h=Math.round((new Date).getTime()/1e3),i="";return k>h?(a(this).log("Skipping return on result: "+a(this).val()),!1):(k=h,"json"==d.dataType?(g.trigger("empty"),b&&a.each(b,function(a,b){i="<strong>"+b.label+"</strong>",b.city_state&&(i+="<br/><small>"+b.city_state+"</small>"),c(b.value,i)})):(g.html(b),g.find("a").click(function(b){g.trigger("clicked",[a(this).attr("href"),a(this).html()])})),void g.trigger("checkEmpty").trigger("loaded"))})}},loaded:function(){a(this).removeClass("loading")},clicked:function(c,d,e){c.preventDefault(),g.hide(),a.isFunction(b.afterClick)&&b.afterClick(d,e)}}),a(this).data("dropdown-init",!0),g},a.fn.inputAutoCompleteMulti=function(b){return this.each(function(){var b=a(this),c=a(".input-autocomplete",b),d=a("> .input-autocomplete-multi-values",b),e=a('input[type="checkbox"]',d).first().closest("div"),f=(a('input[type="checkbox"]',e),a(".input-autocomplete-multi-default-values")),g=b.data("name");b.data("autocomplete-init")||(e.length||(e=a('<div class="controls"></div>'),d.append(e)),c.bind("clicked",function(a,d,e){b.trigger("addValue",[d,e]),c.trigger("clear")}),f.change(function(){var c=a("option:selected",a(this)).first(),d=c.val(),e=c.html();b.trigger("addValue",[d,e]),a("option[value="+d+"]").each(function(){a(this).remove()}),a("option",a(this)).first().prop("selected",!0)}),b.bind("addValue",function(b,c,d){var f=e.find('[value="'+c+'"]');f.length?f.prop("checked",!0):a('<label class="checkbox">'+d+"</label>").prepend(a("<input/>",{type:"checkbox",name:g,value:c,checked:!0})).appendTo(e)}).data("autocomplete-init",!0))})},a.fn.inputAutoComplete=function(b){function c(a,b){d(),j.html(b),i.attr("value",a).prop("disabled",!1)}function d(){j.length?(j.show(),h.hide().attr("disabled",!0),i.attr("disabled",!1)):e()}function e(){j.hide(),h.show().attr("disabled",!1),i.attr("disabled",!0)}var f={click:!1,afterClick:!1,timeoutWait:250,store:"hidden",dataType:"json",action:"select",searchTerm:"text",reset:!1},b=a.extend(f,b);if(a(this).data("autocomplete-init")&&!b.reset)return a(this);var g=a(this),h=g.find("input[type*=text]").attr("autocomplete","off"),i=g.find("input[type*="+b.store+"]"),j=g.find("div.display"),k=g.find("select.default-vals").first(),l=h.data("url"),m=h.data("redirect-url"),n=!1;l&&(n=l.indexOf("json")>0);var o=h.dropdown({tag:n?"ul":"div",itemTag:n?"li":"div",emptyMessage:"Begin typing to load results",emptyResult:"No results found. Please try searching for a different phrase",afterClick:function(e,f){"select"==b.action?d():"redirect"==b.action?window.location.href=m?m+e:e:a.error("Action: "+b.action+" not found for jQuery.inputAutoComplete"),a.isFunction(b.click)?b.click(e,f):c(e,f),a.isFunction(b.afterClick)&&b.afterClick(e,f),g.trigger("clicked",[e,f])}}),p=!1;if(k.length){var q=[];k.attr("disabled",!0).hide().find("option").each(function(){""!==a(this).val()&&q.push([a(this).val(),a(this).html()])}),o.trigger("setDefault",[q])}return j.hover(function(){a(this).css("cursor","pointer")}).click(function(a){a.preventDefault(),e(),h.select()}),"select"==b.action&&i.val()&&h.val()?c(i.val(),h.val()):i.val()?(""===j.html()&&j.html("Value Set"),d()):e(),h.keyup(function(){p&&clearTimeout(p),p=setTimeout(function(){o.trigger("loading",[{dataType:n?"json":"html",url:l+(l.indexOf("?")>0?"&":"?")+b.searchTerm+"="+h.val()}])},b.timeoutWait)}).focus(function(){o.trigger("show")}).blur(function(){}).bind({reset:function(){e()}}),g.bind({clear:function(){e(),h.val("")}}).data("autocomplete-init",!0),g},documentReady(function(){a(".input-list").inputList(),a(".input-autocomplete").each(function(){var b={action:"select"};a(this).hasClass("action-redirect")&&(b.action="redirect"),a(this).inputAutoComplete(b)}),a(".input-autocomplete-multi").inputAutoCompleteMulti()}),a(document).ready(function(){a(this).find(".multi-select").multiSelectInit(),a(this).find('select[name*="input_select"]').change(function(){a(this).closest("div").find("input").first().attr("value",a(this).attr("value")).change()})})}(jQuery),function(a){a.fn.hideDisableChildren=function(){return a(this).is(":visible")&&a(this).slideUp(),a(this).find(":input,select").filter(function(){return a(this).prop("disabled")===!1||!a(this).data("hide-disabled-set")}).each(function(){a(this).data("stored-disabled",a(this).prop("disabled")).data("hide-disabled-set",!0).prop("disabled",!0).trigger("layout-disabled")}),a(this)},a.fn.showEnableChildren=function(b){a(this).is(":hidden")&&a(this).slideDown();var c=a(this).find(":input").each(function(){var b=!1;a(this).data("stored-disabled")&&(b=a(this).data("stored-disabled")),a(this).data("hide-disabled-set",!1).prop("disabled",!1),b||a(this).trigger("layout-enabled")});return b&&c.first().select(),a(this)}}(jQuery),function(a){a.fn.inputCenterFocus=function(){return this.each(function(){function b(){if(v){d=m.offset(),h=d.top,g=d.left,e=a(window).width(),f=a(window).height(),l=f*u,k=e*t,j=(f-l)/2,i=(e-k)/2;var b=h-a(window).scrollTop(),c=g-a(window).scrollLeft();o.css({width:r,height:s}),a("body").addClass("input-centerfocus-open"),a(this).addClass("focused").css({width:r,height:s,left:c,top:b}).animate({width:k,height:l,left:i,top:j},{complete:function(){n.css({top:j+l,left:i,width:k}),q.each(function(){a(this).addClass("focused").fadeIn()})}}),w=!0}}function c(){v&&(q.each(function(){a(this).removeClass("focused").hide()}),a("body").removeClass("input-centerfocus-open"),a(this).animate({width:r,height:s,left:g-a(window).scrollLeft(),top:h-a(window).scrollTop()},{complete:function(){a(this).removeClass("focused"),w=!1}}))}var d,e,f,g,h,i,j,k,l,m=a(this),n=a("<div></div>",{"class":"input-centerfocus-label",html:"Click outside of the textbox to return to normal view"}),o=a('<div class="input-centerfocus-placeholder"></div>'),p=a('<div class="input-centerfocus-bg"></div>'),q=a([n[0],o[0],p[0]]),r=m.css("width"),s=m.outerHeight(),t=(m.css("z-index"),m.css("position"),.8),u=.8,v=!0,w=!1;m.data("centerfocus-init")||(q.each(function(){a(this).hide().insertAfter(m)}),a(window).focus(function(){v=!0,w?b():c()}).blur(function(){v=!1}),m.focus(b).blur(c).data("centerfocus-init",!0))})},documentReady(function(){a(".input-centerfocus").inputCenterFocus()})}(jQuery),function(a){a.fn.inputChoices=function(){return this.each(function(){function b(){d=a(".input-choice",h),e=a(".input-choice-control input",d),f=a(".input-choice-content",d),g=e.filter(":checked")}function c(){if(b(),g.length||(g=e.first()),!g.is(":disabled")){g.prop("checked",!0);var c=g.closest(".input-choice");d.not(c).removeClass("input-choice-active"),c.addClass("input-choice-active"),a(":input",f).each(function(){var b=a(this).closest(".input-choice"),c=b.hasClass("input-choice-active");c&&a(this).data("is-required")?a(this).prop("required",!0):!c&&a(this).prop("required")&&a(this).data("is-required",!0).removeAttr("required")}),f.filter(function(){return!a(this).closest(".input-choice").hasClass("input-choice-active")}).each(function(){a(this).hideDisableChildren()}),a(".input-choice-content",c).showEnableChildren()}}var d,e,f,g,h=a(this);return b(),h.data("input-choice-init")||(e.each(function(){a(this).hover(function(){a(this).toggleClass("input-choice-hover")}).click(function(b){g=a(this),c()}).bind("layout-enabled",function(){c()})}),h.data("input-choice-init",!0),a(document).bind("read ajaxComplete",function(){c()}),a(window).bind("load unload",function(){c()})),c(),h})}}(jQuery),documentReady(function(){$(".input-choices").inputChoices()}),function(a){a.fn.inputFormActivate=function(){function b(a,b){b.is(":checked")?a.removeClass("form-inactive"):a.addClass("form-inactive")}return this.each(function(){var c=a(this),d=c.closest("form");c.data("form-activate-init")||(c.click(function(a){b(d,c)}),b(d,c),c.data("form-activate-init"))})},documentReady(function(){a(':input[class*="form-activate"]').inputFormActivate()})}(jQuery),function(a){a.fn.inputGroupCash=function(){function b(a){return a.replace(/[^0-9\.\-]/g,"")}return this.each(function(){var c=a(this);return c.change(function(){c.val(b(c.val()))}),c})},a(document).bind("ready ajaxComplete",function(){a(":input.input-group-cash").inputGroupCash()})}(jQuery),function(a){function b(a,b,c){if("undefined"==typeof c)var c=!0;var d,e=b,f="",g=a.length,h=!0;if(c){for(;h&&"["!=a.charAt(e);)e++,e>g&&(h=!1);for(;h&&"]"!=a.charAt(++e);)f+=a.charAt(e),f>g&&(h=!1);d=e}else{for(;h&&"]"!=a.charAt(e);)e--,0>e&&(h=!1);for(;h&&"["!=a.charAt(--e);)f=a.charAt(e)+f,0>f&&(h=!1);d=b,b=e}return h?{index:b,key:f,endIndex:d}:!1}a.fn.inputList=function(){return this.each(function(){function b(b){var d=b.getIdInputs(),e=d.first();if(!d.length)return!1;var f="input-list-item-remove",g="remove",h=b.find("."+f+" input[type=checkbox]"),i=b.children(":not(."+f+")");if(c.data("input-list-remove-command")&&(g=c.data("input-list-remove-command")),!h.length){b.wrapInner('<div class="input-list-item-inner"></div>');var j=e.attr("name").substr(0,e.data("id-input-after-key-index"))+"["+g+"]",k=j.replace(/(\[([^\]]+)\])/g,"_$2"),h=a("<input/>",{type:"checkbox",name:j,value:1,id:k}).attr("name",j).val(1).attr("id",k);h.attr("tabindex",-1).appendTo(b).wrap(a("<div></div>",{"class":f})).wrap(a("<label></label>",{html:"&times;"}))}h.change(function(){a(this).is(":checked")?(a(this).parent().addClass("active"),b.addClass("remove").find(":input").filter(function(){var b=a(this).attr("name");return b!=h.attr("name")&&(!b.match(/\[id\]/)||a(this).is("select"))}).prop("disabled",!0)):(a(this).parent().removeClass("active"),b.removeClass("remove").find(":input").prop("disabled",!1),i.slideDown())}).prop("checked",!1).change()}var c=a(this),d=a("> .input-list-inner > .input-list-item",c),e=a("> .input-list-control",c),f=a("a",e);return a(this).data("input-list-init")?a(this):(d.each(function(){return a(this).addClass("row"),a(this)}),c.bind("cloned",function(e,f){b(f),d=a("> .input-list-inner > .input-list-item",c)}),f&&f.length||(f=a('<a class="btn btn-default btn-sm" href="#" tabindex="-1">Add</a>').appendTo(e)),f.click(function(a){a.preventDefault(),c.trigger("add")}),d.filter(":visible").each(function(){return b(a(this)),a(this)}),c.on("add",function(a){d.cloneNumbered(c).trigger("inputListAdd").last(),a.stopPropagation()}).data("input-list-init",!0),a(this))})},a.fn.renumberInput=function(c,d){if("undefined"==typeof d)var d=-1;if(!a(this).attr("name"))return a(this);var e=a(this).attr("name"),f=a(this).attr("id");if(-1!=d){var g=b(e,d);if(g===!1)return a(this);var h=g.key,i="["+h+"]",j=e.substr(0,g.index),k=e.indexOf("[",j.length+i.length),l=k>-1?e.substr(k):"",m=j+"["+c+"]"+l;"]"!=l&&a(this).attr("name",m)}else{var n=e.match(/\[(\d+)\]/);if(n){var i=n[0],h=n[1];a(this).attr("name",e.replace(i,"["+c+"]"))}}if(f){var o=f,p=(a("label").filter(function(){return a(this).attr("for")==o}),f.replace(h,c));a(this).attr("id",p),a(this).parents().last().find('label[for="'+o+'"]').attr("for",p)}return a(this)},a.fn.getIdInputs=function(){var b,c=a(this),d=/(\[([\d]+)\])[\[id\]]*$/;return $ids=a(":input:enabled",c).filter(function(){var c=a(this).attr("name"),e=d.exec(c);if(!e)return!1;if(!b){c.indexOf(e[0]);b=c.length-e[0].length}return a(this).data("id-input-key-index",b),a(this).data("id-input-after-key-index",b+e[1].length),a(this)}),$ids.data("id-inputs-key-index",b),$ids},a.fn.cloneNumbered=function(b){if(b.data("cloning"))return a(this);b.data("cloning",!0);var c=a(this).getIdInputs(),d=c.data("id-inputs-key-index");d||console.log("Key not found");var e=c.last();e.attr("name");if(e.length){var f=a(this).last(),g=f.clone(),h=c.length;a("input",g).removeClass("hasDatepicker"),a('input[name*="[id]"]',g).val("").trigger("reset"),a(".clone-numbered-index",g).html(h+1),g.find(":input").each(function(){return a(this).renumberInput(h,d).removeAttr("disabled")}),g.insertAfter(f),a(":checkbox,:radio",g).each(function(){"undefined"!=typeof a(this).data("clone-numbered-default")&&a(this).data("clone-numbered-default")==a(this).val()&&a(this).prop("checked",!0)}),a(".no-clone",g).remove(),a(":input",g).not(":hidden,:checkbox,:radio,:submit,:reset").each(function(){var b="";a(this).data("clone-numbered-default")?b=a(this).data("clone-numbered-default"):a(this).attr("default")&&(b=a(this).attr("default")),a(this).val(b),a(this).trigger("reset")}),g.slideDown().data("added",!0).effect("highlight"),g.find(":input:visible").first().focus(),g.data("id-key",h),b.trigger("cloned",[g]),f.trigger("entry-cloned")}return b.data("cloning",!1),a(document).trigger("ajaxComplete"),a(this)}}(jQuery),function(a){a(document).ready(function(){var b=9,c="	";a(".js-input-tabbed").each(function(){a(this).keydown(function(d){var e,f,g;return d.keyCode==b?(g=this.selectionStart,f=this.selectionEnd,e=a(this),e.val(e.val().substring(0,g)+c+e.val().substring(f)),this.selectionStart=this.selectionEnd=g+1,!1):void 0})})})}(jQuery),function(a){function b(b,c,d,e){if("undefined"==typeof e)var e=a("body");var f=a(c,e);return f.length||(f=a("<"+b+"></"+b+">",d).appendTo(e)),f}a.fn.selectCollapseHoverTrack=function(){return a(this).hover(function(){a(this).data("hovering",!0)},function(){a(this).data("hovering",!1)}),a(this)},a.fn.selectCollapse=function(){return this.each(function(){function c(a){t.find(".active").removeClass("active");var b=a.closest("span.select-collapse-option").addClass("active").closest("li");k(),i(b),d(a.data("val")),g(),o.data(C)&&o.focus()}function d(a){o.val(a).trigger("change")}function e(){return o.data("expanded")?g():f()}function f(){t.is(":hidden")&&(u.focus().val(""),m()),l(),o.data("expanded",!0).attr("disabled","disabled");var b=o.offset(),c=o.outerHeight(),d=o.outerWidth();o.zIndex();return t.show().css({top:b.top+c,left:b.left,width:d}),a(".expanded > ul",t).show(),u.focus(),!0}function g(){return l(),o.data("expanded",!1),D||o.removeAttr("disabled"),t.hide(),!0}function h(a,b){var b="undefined"!=typeof b?b:!0;a.addClass("expanded").find(".select-collapse-bullet").first().html("-");var c=a.find("ul").first();c.is(":hidden")&&c.slideDown(),b&&i(a,!1)}function i(b){b.parentsUntil(".select-collapse-window","li").each(function(){h(a(this),!1)})}function j(a,b){var b="undefined"!=typeof b?b:!0;a.removeClass("expanded").find(".select-collapse-bullet").first().html("+");var c=a.find("ul").first();c.is(":hidden")||c.slideUp(),b&&k(a)}function k(b){var b="undefined"!=typeof b?b:t;b.find("li.expanded").each(function(){j(a(this),!1)})}function l(){var a,b,c,d;o.is(":visible")?(a=o.offset(),b=o.position(),d=o.outerHeight(),c=o.outerWidth()):(a={top:0,left:0},b=a,c=0,d=0),A.length&&b.top>A.height()&&(d-=b.top-A.height(),0>d&&(d=0)),x.css({position:"absolute",top:a.top,left:a.left,right:a.left+c,bottom:a.top+d,width:c,height:d})}function m(){var b=u.val(),d=b.toLowerCase(),e=o.data("optionVals");if(""===b)return v.show(),w.hide(),a(this);v.hide(),w.show().empty();for(var f=0;f<e.length;f++){var g=e[f].label,h=g.toLowerCase().indexOf(d);-1!=h&&(g=g.replace(b,"<strong>"+b+"</strong>"),a('<a href="#"></a>').appendTo(w).data("target",e[f].target).wrap('<li><span class="select-collapse-option"></span></li>').html(g).click(function(b){b.preventDefault(),b.stopPropagation(),c(a(a(this).data("target")))}))}}function n(){var b=[],d=a("option",o),e=!1,f=0,g=0,i=[];o.data("optionVals",[]),v.empty(),console.log({Select:o,Options:a("option",o).length}),console.log("FOUND "+d.length+" Options"),d.each(function(d){var k=a(this),l=a("<li></li>").addClass("no-child"),m=a('<a class="select-collapse-link" href="#"></a>').appendTo(l).wrap('<span class="select-collapse-option"></span>').data("val",k.val()).attr("id","select-collapse-"+o.attr("id")+"-"+d),n=k.html(),p=n.match(/^[^A-Za-z0-9]*/),q=0;if(p){p=p[0],n=n.substring(p.length),"_"==p.substr(0,1)&&(B="_"),f=p.split(B).length-1;var r=f*B.length;r<p.length&&(n=p.substring(r)+n)}if(f>g)e&&(v=a("<ul></ul>").appendTo(e),e.removeClass("no-child").find("a").first().before(a('<a class="select-collapse-bullet" href="#">+</a>').click(function(b){b.preventDefault();var c=a(this).closest("li");c.hasClass("expanded")?j(c):h(c)})),j(e));else if(g>f){for(q=f;g>q;q++)v=v.closest("li").closest("ul"),b.pop();b.pop()}else b.pop();if(g=f,l.appendTo(v),m.html(n),k.is(":selected")&&(E=m),k.attr("disabled")?m.addClass("disabled").click(function(a){a.preventDefault()}):m.click(function(a){a.preventDefault(),c(m)}),e=l,a(this).val()){var s="";for(b.push(n),q=0;q<b.length;q++)s+="/"+b[q];i.push({label:s,value:a(this).val(),target:"#"+m.attr("id")})}}),o.data("optionVals",i)}var o=a(this),p=o.data("uuid")?o.data("uuid"):getUuid(),q=o.attr("id")?o.attr("id"):"select-collapse-"+p,r="select-collapse-window-"+p,s="select-collapse-mask-"+p;o.data("uuid",p).attr("id",q),o.data("optionVals")||o.data("optionVals",[]);var t=b("div","#"+r,{id:r,"class":"select-collapse-window"}),u=b("input",".select-collapse-search",{"class":"select-collapse-search",type:"text"},t),v=b("ul",".select-collapse-options",{"class":"select-collapse-options"},t),w=b("ul",".select-collapse-search-results",{"class":"select-collapse-search-results"},t),x=b("div","#"+s,{id:s,"class":"select-collapse-mask"}),y=o.parents().add(a(window)),z=o.closest(".modal"),A=o.scrollParent(),B=" - ",C="collapse-init",D=o.is(":disabled");if(!o.data(C)){l();var E=!1;x.selectCollapseHoverTrack().hover(function(){a(this).css("cursor","pointer")}).click(function(){e()}),a(document).click(function(){!o.data("expanded")||o.data("hovering")||x.data("hovering")||t.data("hovering")||g()}),o.selectCollapseHoverTrack().click(function(a){o.data("expanded",!0).attr("disabled","disabled"),a.stopPropagation(),a.preventDefault(),e()}).hover(function(a){l()}),t.selectCollapseHoverTrack(),u.keyup(function(){m()}),E.length&&c(E),y.each(function(){a(this).scroll(function(){o.length&&l(),o.data("expanded")&&f()})}),z.on("hide",function(){g()}).on("shown",function(){l()}),o.is(":disabled")&&(D=!0),o.on("layout-disabled",function(){D=!0}).on("layout-enabled",function(){D=!1}).data(C,!0)}return n(),a(this)})},a(document).bind("ajaxComplete",function(){console.log("AJAX IS COMPLETE YOU FOOL")}),a(document).bind("ajaxComplete ready",function(){a("select.select-collapse").selectCollapse()})}(jQuery),function(a){a.fn.textareaAutoresize=function(){return this.each(function(){function b(){var a=e.scrollHeight;a!=d&&(c.stop().animate({height:a+"px"}),d=a)}var c=a(this),d=0,e=this;return c.data("textareaAutoresize-init")||c.css({overflow:"hidden",height:"0px"}).keyup(function(){b()}).data("textareaAutoresize-init",!0),b(),c})},documentReady(function(){a("textarea.textarea-autoresize").textareaAutoresize()})}(jQuery);