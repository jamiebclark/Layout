function initLayout() {
	$('.datepicker').datetimepicker({
		format: "mm-dd-yyyy"
	});
	$('.datetimepicker').datetimepicker({
		format: "mm-dd-yyyy HH:iip"
	});
}

$(document)
	.ready(function() {initLayout();})
	.ajaxComplete(function() {initLayout();});