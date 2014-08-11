/**
 * Respond to events fired by Befound proprietary framework.
 */
Befound.ready(function() {
	var ns = Befound.App.name;
	$(document).on('select.' + ns, function(event, model, id) {
		var selector = 'table.dataTable[data-basemodel=' + model + '][data-route][data-listen]';
		$(selector).each(function() {
			$(this).dataTable().api().ajax.url(Befound.App.createUrl($(this).data('route'), {'id' : id})).load();
		})
	});
	$(document).on('update.' + ns + ' create.' + ns + ' delete.' + ns, function(event, model, data) {
		var selector = 'table.dataTable[data-model=' + model + '][data-route][data-listen]';
		$(selector).each(function() {
			// Get id.
			var field = $(this).data('basemodel').toLowerCase() + '_id';
			if (typeof data.attributes[field] != 'undefined')
			{
				$(this).dataTable().api().ajax.url(Befound.App.createUrl($(this).data('route'), {'id' : data.attributes[field]})).load();
			}
			else
			{
				$(this).dataTable().api().ajax.reload();
			}
		})

	});
});