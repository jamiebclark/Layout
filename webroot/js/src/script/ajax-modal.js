// AJAX Modal Loading Window 
(function($) {
	$.fn.ajaxModal = function() {
		return this.each(function() {
			var $a = $(this),
				loadUrl = $a.attr('href'),
				title = $a.attr('data-modal-title'),
				modalClass = $a.attr('data-modal-class'),
				customTitle = title,
				ajaxWindowId = '',
				ajaxWindowKey = 1;
			do {
				ajaxWindowId = '#ajax-modal' + (ajaxWindowKey++);
			} while ($a.closest(ajaxWindowId).length);

			if (!modalClass) {
				modalClass = "modal-lg";
			}

			var $ajaxWindow = $(ajaxWindowId),
				$ajaxWindowHeader = $('.modal-header', $ajaxWindow),
				$ajaxWindowBody = $('.modal-body', $ajaxWindow),
				$ajaxWindowFooter = $('.modal-footer', $ajaxWindow);

			if (!$ajaxWindow.length) {
				$ajaxWindow = $('<div></div>', {
					'id': ajaxWindowId,
					'class': 'modal fade'
				});
				var $ajaxDialog = $('<div class="modal-dialog"></div>')
						.addClass(modalClass).appendTo($ajaxWindow),
					$ajaxContent = $('<div class="modal-content"></div>').appendTo($ajaxDialog);

				$ajaxWindowHeader = $('<div class="modal-header"></div>')
					.appendTo($ajaxContent);
				$ajaxWindowBody = $('<div class="modal-body"></div>')
					.appendTo($ajaxContent);
				$ajaxWindowFooter = $('<div class="modal-footer"></div>')
					.appendTo($ajaxContent);

				$ajaxWindowHeader.append($('<button></button>', {
					'type': 'button',
					'class': 'close',
					'data-dismiss': 'modal',
					'aria-hidden': 'true',
					'html': '&times;'
				}));
				$('<a></a>', {
					'html': 'Close',
					'href' : '#',
					'class' : 'btn btn-default',
					'click': function(e) {
						e.preventDefault();
						$ajaxWindow.modal('hide');
					}
				}).appendTo($ajaxWindowFooter);
				
				$('<a></a>', {
					'html': 'Update',
					'href': '#',
					'class': 'btn btn-primary',
					'click': function(e) {
						e.preventDefault();
						$('form', $ajaxWindowBody).first().submit();
					}
				}).appendTo($ajaxWindowFooter);
			}
			if (!$a.data('ajax-modal-init')) {
				if (!customTitle) {
					title = 'Window';
				}
				$ajaxWindowHeader.append('<h3>' + title + '</h3>');
				$a.click(function(e) {
					e.preventDefault();
					$ajaxWindowBody.append($('<div class="ajax-loading"></div')
						.append($('<span>Loading</span>').animatedEllipsis())
					);
					$.ajax({
						url: loadUrl,
						success: function(data) {
							var $data = $(data),
								$scripts = $('script', $data),
								$content = $('#content-container', $data),
								$footer = $('.modal-footer', $ajaxWindow);
								
							if (!$content.length) {
								$content = $data;
							} else {
								$content = $content.html();
							}

							$ajaxWindowBody.html($content);
							$('.ajax-modal-hide', $ajaxWindowBody).remove();

							if ($scripts.length) {
								$scripts.each(function() {
									if ($(this).attr('src')) {
										$.getScript($(this).attr('src'));
									}
								});
							}

							var $form = $('.modal-body form', $ajaxWindow);

							if (!$form.length) {
								$footer.hide();
							} else {
								$footer.show();
							}
							
							var $bodyTitle = $('h1', $ajaxWindowBody).first(),
								$bodyTitleParent = $bodyTitle.closest('.page-header');
							
							if ($bodyTitle) {
								if (!customTitle) {
									$('h3', $ajaxWindowHeader).html($bodyTitle.html());
								}
								$bodyTitle.remove();
								if ($bodyTitleParent.empty()) {
									$bodyTitleParent.remove();
								}
							}

							$('submit,button[type="submit"]', $form).each(function() {
								if (!$(this).attr('name')) {
									$(this).addClass('modal-body-submit').hide();
								}
							});
							$('.form-actions:empty', $form).remove();
							$(document).trigger('ajax-modal-loaded').ajaxComplete();
						}
					});
					$ajaxWindow.modal('show');
				});
				$a.data('ajax-modal-init', true);
			}
		});
	};

	/*
	$(document).ready(function() {
		$('.ajax-modal').ajaxModal();
	});
	*/
	documentReady(function () {
		$('.ajax-modal').ajaxModal();
	});

})(jQuery);
