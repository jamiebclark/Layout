function documentReady(a){jQuery(document).ready(a).ajaxComplete(a)}var test=1;!function(a){a.fn.ajaxModal=function(){return this.each(function(){var b=a(this),c=b.attr("href"),d=b.attr("data-modal-title"),e=b.attr("data-modal-class"),f=d,g="",h=1;do g="#ajax-modal"+h++;while(b.closest(g).length);e||(e="modal-lg");var i=a(g),j=a(".modal-header",i),k=a(".modal-body",i),l=a(".modal-footer",i);if(!i.length){i=a("<div></div>",{id:g,"class":"modal fade"});var m=a('<div class="modal-dialog"></div>').addClass(e).appendTo(i),n=a('<div class="modal-content"></div>').appendTo(m);j=a('<div class="modal-header"></div>').appendTo(n),k=a('<div class="modal-body"></div>').appendTo(n),l=a('<div class="modal-footer"></div>').appendTo(n),j.append(a("<button></button>",{type:"button","class":"close","data-dismiss":"modal","aria-hidden":"true",html:"&times;"})),a("<a></a>",{html:"Close",href:"#","class":"btn btn-default",click:function(a){a.preventDefault(),i.modal("hide")}}).appendTo(l),a("<a></a>",{html:"Update",href:"#","class":"btn btn-primary",click:function(b){b.preventDefault(),a("form",k).first().submit()}}).appendTo(l)}b.data("ajax-modal-init")||(f||(d="Window"),j.append("<h3>"+d+"</h3>"),b.click(function(b){b.preventDefault(),k.append(a('<div class="ajax-loading"></div').append(a("<span>Loading</span>").animatedEllipsis())),a.ajax({url:c,success:function(b){var c=a(b),d=a("script",c),e=a("#content-container",c),g=a(".modal-footer",i);e=e.length?e.html():c,k.html(e),a(".ajax-modal-hide",k).remove(),d.length&&d.each(function(){a(this).attr("src")&&a.getScript(a(this).attr("src"))});var h=a(".modal-body form",i);h.length?g.show():g.hide();var l=a("h1",k).first(),m=l.closest(".page-header");l&&(f||a("h3",j).html(l.html()),l.remove(),m.empty()&&m.remove()),a('submit,button[type="submit"]',h).each(function(){a(this).attr("name")||a(this).addClass("modal-body-submit").hide()}),a(".form-actions:empty",h).remove(),a(document).trigger("ajax-modal-loaded").ajaxComplete()}}),i.modal("show")}),b.data("ajax-modal-init",!0))})},documentReady(function(){a(".ajax-modal").ajaxModal()})}(jQuery),function(a){a.fn.animateText=function(b){return b=jQuery.extend({},b,{refreshInterval:300,step:function(a,b){}}),this.each(function(){var c=a(this),d=0,e=!1;e=setInterval(function(){var a=b.step(c.html(),d);d++,c.html(a)},b.refreshInterval)})},a.fn.animatedEllipsis=function(){return this.animateText({refreshInteval:300,step:function(a,b){for(var c="",d=b%3,e=1;d>=e;e++)c+=".";return c}})},documentReady(function(){a(".animated-ellipsis").animatedEllipsis()})}(jQuery),function(a){a.fn.datepick=function(){return this.each(function(){var b=a(this).datepicker(),c=b.closest("div"),d=a(".timepicker",b.closest(".date-time-input"));return b.data("date-init")||(a(".today",c).click(function(a){a.preventDefault(),b.datepicker("setDate","now")}),a(".clear",c).click(function(a){a.preventDefault(),b.datepicker("setDate")}),b.val()&&b.datepicker("setDate",b.val()),b.change(function(){return d.length&&d.focus(),a(this)})),b.data("date-init",!0),b})},a.fn.timepick=function(){return this.each(function(){var b=a(this).timepicker(),c=b.closest("div");return b.data("time-init")||(a(".today",c).click(function(a){a.preventDefault(),b.timepicker("setTime",new Date)}),a(".clear",c).click(function(a){a.preventDefault(),b.val("")})),b.data("time-init",!0),b})},a(document).bind("ready ajaxComplete",function(){a(".datepicker").datepick(),a(".timepicker").timepick()})}(jQuery),function(a){a.fn.embedFit=function(){return this.each(function(){function b(){var a=c.width();e.width(a).height(a/h)}var c=a(this),d=a("object,iframe",c).first(),e=a("embed,object,iframe",c),f=d.attr("width")?d.attr("width"):d.width(),g=d.attr("height")?d.attr("height"):d.height(),h=f/g;c.data("embed-fit-init")||(b(),a(window).resize(function(){b()}),c.data("embed-fit-init",!0)),c.on("resize",function(){b()})})},documentReady(function(){a(".embed-fit").embedFit()})}(jQuery),function(a){var b=0;a.fn.hoverContent=function(){return this.each(function(){var c=a(this),d=c.find(".hover-content"),e=600,f=250,g=!1,h=c.hasClass("hover-left");a("#hover-content-holder").length||a("body").append(a('<div id="hover-content-holder"></div>').css("position","static"));var i=a("#hover-content-holder");return c.data("hover-init")||(c.find(".hover-content,.hover-over").hover(function(){g=!0,d.delay(e).queue(function(b){if(g){c.addClass("hovering");var e=c.offset();d.show(),console.log({"Window Width:":a(window).width(),"Position Left":e.left,"Content Width":d.width(),"Outer Width":d.outerWidth()}),e.left+d.width()>a(window).width()?(d.addClass("position-right"),e.left=e.left-d.width()+c.width()):d.removeClass("position-right");var f={top:e.top+c.height(),left:e.left,bottom:"auto"};e.top+d.height()>a(window).scrollTop()+a(window).height()?(d.addClass("position-down").removeClass("position-up"),f.top=e.top-d.height()):d.removeClass("position-down").addClass("position-up"),h?(f.top=e.top,f.left=e.left-d.width(),d.addClass("hover-left")):d.removeClass("hover-left"),d.css(f)}b()})},function(){g=!1,d.delay(f).queue(function(a){g||(c.removeClass("hovering"),d.hide()),a()})}),b++,c.data("hoverId",b),d.data("hoverId",b),d.attr("id","hover-content"+b),c.bind("remove",function(){a("#hover-content"+c.data("hoverId")).remove()}),i.append(d)),c.data("hover-init",!0),c})},documentReady(function(){a(".hover-layout,.hover-layout-block").hoverContent()})}(jQuery),function(a){a.fn.layoutMedia=function(){var b=250,c="easeOutSine";return this.each(function(){var d=a(this),e=d.closest(".media-wrap"),f=a(".media-actionmenu",e),g=e.length?e:d;0===e.length&&(f=a(".media-actionmenu",d)),d.data("layout-media-init")||(g.hover(function(){d.addClass("media-hover"),f.stop().fadeIn(b,c)},function(){d.removeClass("media-hover"),f.stop().fadeOut(b,c)}),d.data("layout-media-init",!0))})},documentReady(function(){a(".media").layoutMedia()})}(jQuery),function(a){a.fn.affixContent=function(){function b(){a("body").outerWidth()<500&&(h.position="static",h.width=""),c.css(h)}var c=a(this),d=a("#content"),e=(d.offset(),a(this).outerWidth()),f=function(){return this.top=c.parent().offset().top},g=function(){return this.bottom=a("#footer").outerHeight(!0)},h={position:"",top:"auto",width:e+"px"};a(this).affix({offset:{top:f,bottom:g}}).on("affixed.bs.affix",function(){h.position="fixed",h.top=0,b()}).on("affixed-top.bs.affix",function(){h.position="relative",h.top="auto",b()}).on("affixed-bottom.affix",function(){h.position="relative",b()})},a.fn.scrollfix=function(){return this.each(function(){function b(){o>m&&n+o>m+j?c():d()}function c(){var a,b,c=j>n?j-n:0,d=q&&o-c+j>q;d?(b=f.height()-j,a="absolute"):(b=p-c+"px",a="fixed"),g.css({width:k,position:a,top:b})}function d(){g.css({position:"static",width:"auto"})}function e(){d(),j=g.outerHeight(!0),k=g.outerWidth(),l=g.offset(),m=l.top,n=a(window).height(),p=10,a(".scrollfix-fixed:visible").each(function(){if("fixed"==a(this).css("position")){var b=a(this).outerHeight();n-=b,p+=b}}),f.length&&(r=f.offset(),q=r.top+f.height(),f.css("min-height",j+"px")),b()}if(!a(this).data("scroll-init")){var f,g=a(this),h=new Array(".row",".container");for(var i in h)if(f=g.closest(h[i]),f.length)break;f.css("position","relative");var j,k,l,m,n,o,p,q,r;e(),a(window).on("scroll",function(){o=a(window).scrollTop()+p,e(),b()}).resize(function(){e()}).load(function(){e(),b()}),a(this).data("scroll-init",!0)}})},documentReady(function(){a(".affix-content").affixContent(),a(".scrollfix").scrollfix()})}(jQuery),function(a){var b=0,c=0;a.fn.tableCheckbox=function(){return this.each(function(){function d(){var a=g.attr("name"),b=/\[table_checkbox\]\[([\d]+)\]/m,c=a.match(b);return c?c[1]:0}function e(b,c,d){var e;if(b>c){var f=b;b=c,c=f}for(var g=b;c>=g;g++)e=a('input[name="data[table_checkbox]['+g+']"]'),e.length&&(d?e.prop("checked",!0):e.removeProp("checked"),e.trigger("afterClick"))}function f(){g.is(":checked")?h.addClass("active"):h.removeClass("active")}var g=a(this),h=g.closest("tr"),i=!1;return a(document).keydown(function(b){return i=16==b.keyCode,a(this)}).keyup(function(){return i=!1,a(this)}),g.data("index",d()).click(function(d){var h=g.data("index"),j=h==b,k=j?c:b,l=h,m=a(this).is(":checked");return i&&e(k,l,m),j||(c=b,b=h),f(),d.stopPropagation(),a(this)}).on("afterClick",function(){f()}),h.hover(function(){a(this).toggleClass("row-hover")}).click(function(a){g.click()}),a(this)})},a.fn.tableSortLink=function(){return this.each(function(){var b=a(this),c="table-sort-links",d="table-sort-links-toggle",e="table-sort-links-dropdown";if(!b.attr("href").match(/.*sort.*direction.*/))return a(this);b.addClass(d).wrap(a("<div></div>").addClass(c)),b.after(function(){var b=a(this),c=b.attr("href"),d=b.hasClass("asc"),f=b.hasClass("desc"),g="",h=b.html();if(d?g="asc":f&&(g="desc"),(d||f)&&b.addClass("active"),!c)return"";var i=a("<div></div>").addClass(e).append(function(){var b="asc";return d&&(b+=" selected"),a("<a>Ascending</a>").attr({href:c.replace("direction:desc","direction:asc"),"class":b,title:'Sort the table by "'+h+'" in Ascending order'}).prepend(a('<i class="pull-right glyphicon glyphicon-sort-by-attributes"></i>'))});return i.append(function(){var b="desc";return f&&(b+=" selected"),a("<a>Descending</a>").attr({href:c.replace("direction:asc","direction:desc"),"class":b,title:"Sort the table by this column in Descending order"}).prepend(a('<i class="pull-right glyphicon glyphicon-sort-by-attributes-alt"></i>'))}),i.before("<br/>").hide()});var f=b.closest("."+c),g=a("."+e,f);return f.hover(function(){f.not(":animated")&&(b.addClass("is-hovered"),g.stop(!0).delay(500).slideDown(100))},function(){f.not(":animated")&&(b.first().removeClass("is-hovered"),g.stop(!0).slideUp(100))}),a(this)})},a.fn.tableCheckboxes=function(){return this.each(function(){function b(){i=h.filter(function(){return a(this).is(":checked")})}function c(b,c){if(c!==!1)var c=!0;b.each(function(){a(this).prop("checked",!c).click()})}function d(){var b=a(".table-with-checked-info",k);i.length?k.addClass("fixed"):k.removeClass("fixed"),b.length||(b=a('<div class="table-with-checked-info"></div>').prependTo(k)),b.html(i.length+" Checked ");var d=i.length==h.length;b.append(a("<a></a>",{href:"#",html:d?"Uncheck All":"Check All",click:function(a){a.preventDefault(),c(h,!d)}}))}var e=a(this),f=e.closest("form"),g=a('input[name*="[table_checkbox]"]',e),h=a('input[name*="[table_checkbox]"]',f),i=a(":checked",g),j=a("th input.check-all",f),k=a(".table-with-checked",f);return j.click(function(b){c(g,a(this).is(":checked"))}),g.click(function(a){b(),1==k.length&&d()}),e})},a(document).ready(function(){a("th a").tableSortLink(),a(".layout-table,.table-checkboxes").tableCheckboxes(),a('input[name*="[table_checkbox]"]').tableCheckbox()})}(jQuery),function(a){a.fn.actionMenuFit=function(){return this.each(function(){var b=a(this),c=b.parent("td"),d=a("> a",b),e=c.css("padding-left"),f=c.css("padding-right"),g=0;return d.each(function(){g+=a(this).outerWidth()}),e&&(g+=parseFloat(e)),f&&(g+=parseFloat(f)),c.css("width",g),b})},a(window).load(function(){a(".action-menu").actionMenuFit()})}(jQuery),function(a){var b=1;a.fn.layoutToggle=function(){return this.each(function(){function c(){h.showEnableChildren(),i.hideDisableChildren()}function d(){h.hideDisableChildren(),i.showEnableChildren()}function e(){g.is(":disabled")||(g.is(":checked")?c():d())}var f=a(this),g=f.find(".layout-toggle-control input[type*=checkbox]").first(),h=f.find("> .layout-toggle-content").first(),i=f.find("> .layout-toggle-off"),j=b++;return f.addClass("toggle"+j),f.data("layout-toggle-init")||(g.change(function(){e()}).bind("layout-enabled",function(){e()}),e(),f.data("layout-toggle-init")),f})},documentReady(function(){a(".layout-toggle").layoutToggle()})}(jQuery);