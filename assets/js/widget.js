/*
 * Add some extra functionality for easier usage within Yii.
 */
$(document).ready(function() {
	$('body').on('click', 'a.removerow', function() {
		$(this).closest('table').dataTable().fnDeleteRow($(this).closest('tr'));
	});
});