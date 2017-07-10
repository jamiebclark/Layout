(function($) {
	var nameInputCheckCount = 0;
	
	$.fn.nameInput = function() {
		return this.each(function() {
			var $nameInput = $(this),
				$nameInner = $('.input-name-inner', $nameInput);
				$nameOptions = $('<div class="input-name-options input-multi-row"></div>'),
				$inputs = $nameInput
					.find('input')
					.filter(function() {
						return !($(this).closest('div.input').hasClass('required'));
					});
			if ($inputs.length) {
				$nameOptions.insertAfter($nameInner).prepend($('<em>Additional:</em>'));
				$inputs.each(function() {
					nameInputCheckCount++;
					var $input = $(this),
						$inputHolder = $(this).closest('div.input'),
						label = $inputHolder.find('label').html(),
						isBlank = $(this).val() == '',
						id = 'contactNameCheck' + nameInputCheckCount,
						$label = $('<label></label>', {
							'html' : label,
							'for' : id
						}).appendTo($nameOptions);
						
					if (isBlank) {
						$inputHolder.hide();
					}
					var $check = $('<input/>', {
						type : 'checkbox',
						checked : isBlank ? false : 'checked',
						id : id,
						'tabindex': -1
					}).change(function() {
						if ($(this).is(':checked')) {
							$inputHolder.show();
							if ($input.data('stored-value')) {
								$input.val($input.data('stored-value'));
							}
							$input.focus().select();
						} else {
							$inputHolder.hide();
							$input.data('stored-value', $input.val()).val('');
						}
						return $(this);					
					}).prependTo($label);
				});
			}
		});
	};

	$(document).ready(function() {
		$('.input-name').nameInput();
	});
})(jQuery);

