/*
 * Add some extra functionality for easier usage within Yii.
 */
$(document).ready(function() {
	$('body').on('click', 'table.dataTable a.removerow', function() {
		$(this).closest('table').dataTable({"bRetrieve" : true}).fnDeleteRow($(this).closest('tr'));
	});
	$('body').on('click', 'table.dataTable.singleSelect tbody tr', function() {
		if (!$(this).hasClass('selected'))
		{
			$(this).parent().children().removeClass('selected');
			$(this).addClass('selected');
			$(this).find('input.select-on-check').attr('checked', true);
		}
		else
		{
			$(this).removeClass('selected');
		}
	});
	$('body').on('click', 'table.dataTable.multiSelect tbody tr', function() {
		if (!$(this).hasClass('selected'))
		{
			$(this).find('input.select-on-check').attr('checked', true);
			$(this).addClass('selected');
		}
		else
		{
			$(this).removeClass('selected');
			$(this).find('input.select-on-check').attr('checked', false);
		}
	});
	// Don't propagate click events for inputs.'
	$('body').on('click', 'table.dataTable.multiSelect tbody tr input', function(e) {
		e.stopPropagation();
	});
	$('body').on('click', 'table.dataTable.multiSelect tbody tr input.select-on-check', function(e) {
		e.stopPropagation();
		if (this.checked)
		{
			$(this).closest('tr').addClass('selected');
		}
		else
		{
			$(this).closest('tr').removeClass('selected');
		}
	});

	$('body').on('click', 'table.dataTable.multiSelect thead tr input.select-on-check-all', function(e) {
		e.stopPropagation();
		if (this.checked)
		{
			$(this).closest('table').find('tbody tr').addClass('selected');
			$(this).closest('table').find('tbody tr input.select-on-check').attr('checked', true);
		}
		else
		{
			$(this).closest('table').find('tbody tr').removeClass('selected');
			$(this).closest('table').find('tbody tr input.select-on-check').attr('checked', false);
		}
	});

});