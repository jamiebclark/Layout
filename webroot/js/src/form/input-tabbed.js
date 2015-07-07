// Allows the user to enter tabs into an inpu
(function($) {
	$(document).ready(function() {
		var tabKeyCode = 9;
		var tabInsert = "\t";
		$('.js-input-tabbed').each(function() {
			$(this).keydown(function(e) {
				var $this, end, start;
				if (e.keyCode == tabKeyCode) {
					start = this.selectionStart;
					end = this.selectionEnd;
					$this = $(this);
					$this.val($this.val().substring(0, start) + tabInsert + $this.val().substring(end));
					this.selectionStart = this.selectionEnd = start + 1;
					return false;
				}
			});
		});
	});
})(jQuery);
