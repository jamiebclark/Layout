!function(a){var b=function(b,c){this.$element=a(b),this.$content=a('<div class="message-carousel-wrap"></div>').appendTo(this.$element),this.options=c,this.$messages=a(".message-carousel-message").hide(),this.$currentItem,this.key=0,this.transitionInterval,this.transitionTime=5e3,this.fadeDuration=500,this.fadeEasing="easeOutQuad",this.fadeOptions={queue:!1,duration:this.fadeDuration,easing:this.fadeEasing}};b.prototype.createItem=function(b){if(this.$messages.eq(b).length){var c=this.$messages.eq(b).clone().html(),d=a('<div class="message-carousel-item"></div>').appendTo(this.$content),e=d.append(c).outerHeight();return d.css({position:"absolute",top:0,left:0,right:0}).hide(),this.$content.animate({height:e},{duration:this.fadeDuration}),d}return!1},b.prototype.loadMessage=function(){var b=this.createItem(this.key);if(b||(this.key=0,b=this.createItem(this.key)),this.$currentItem){var c=this.$currentItem;this.$currentItem.fadeOut(a.extend(this.fadeOptions,{complete:function(){c.remove()}})),this.$currentItem=b.fadeIn(this.fadeOptions)}else this.$currentItem=b.show();this.key++},b.prototype.start=function(){this.loadMessage();var a=this;this.transitionInterval=setInterval(function(){a.loadMessage()},this.transitionTime)},b.prototype.pause=function(){clearInterval(this.transitionInterval)},a.fn.messageCarousel=function(c){return this.each(function(){var d=a(this),e=d.data("messageCarousel"),f=a.extend({},d.data(),"object"==typeof c&&f),g="string"==typeof c?c:"start";e||d.data("messageCarousel",e=new b(this,f)),g&&e[g]()})},a.fn.messageCarousel.Constructor=b,a.fn.formSubmittedOverlay=function(){return this.each(function(){var b=a(this);return b.data("submitted-overlay-init")?b:(b.submit(function(b){var c=a(this).addClass("submitted-overlay-submitted"),d=0,e='<i class="fa fa-spinner fa-spin"></i>',f=a('<div class="submitted-overlay-mask"></div>').css({width:c.outerWidth()+2*d,height:c.outerHeight()+2*d,top:-1*d,left:-1*d}).appendTo(c),g=a('<div class="submitted-overlay-mask-message"></div>').appendTo(f),h=(a('<div class="submitted-overlay-mask-message-icon">'+e+"</div>").appendTo(g),a('<div class="submitted-overlay-mask-message-title">Loading</div>').appendTo(g),a('<div class="submitted-overlay-mask-message-content"></div>').appendTo(g));return h.messageCarousel(),a(":submit",c).each(function(){a(this).prop("disabled",!0).html(e+" Loading")}),!1}),b.data("submitted-overlay-init",!0),b)})},a(document).bind("ready ajaxComplete",function(){a("form.submitted-overlay").formSubmittedOverlay()})}(jQuery);