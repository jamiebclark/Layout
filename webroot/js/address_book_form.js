(function($) {
	var nameInputCheckCount = 0;
	
	$.fn.nameInput = function() {
		return this.each(function() {
			var $nameInput = $(this),
				$nameInner = $('.input-name-inner', $nameInput);
				$nameOptions = $('<div class="input-name-options input-multi-row"></div>')
				.insertAfter($nameInner)
				.prepend($('<em>Additional:</em>'));
				
			$nameInput
				.find('input')
				.filter(function() {
					return !($(this).closest('div').hasClass('required'));
				})
				.each(function() {
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
					}).change(function() {
						if ($(this).is(':checked')) {
							$inputHolder.show();

							console.log('Old Value: ' + $input.val());

							if ($input.data('stored-value')) {
								$input.val($input.data('stored-value'));
							}
							console.log('New Value: ' + $input.val());
							$input.focus().select();
						} else {
							$inputHolder.hide();
							console.log('Old Value: ' + $input.val());
							$input.data('stored-value', $input.val()).val('');
							console.log('New Value: ' + $input.val());
						}
						return $(this);					
					}).prependTo($label);
				});			
		});
	};
})(jQuery);

$(document).ready(function() {
	$('.input-name').nameInput();
});