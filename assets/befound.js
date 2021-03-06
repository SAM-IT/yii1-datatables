/**
 * Respond to events fired by Befound proprietary framework.
 */
Befound.ready(function() {
	$(document).on('select', function(event, model, id) {
		var selector = 'table.dataTable[data-basemodel=' + model + '][data-route][data-listen]';
		$(selector).each(function() {
			$(this).dataTable().api().ajax.url(Befound.App.createUrl($(this).data('route'), {'id' : id})).load();
		})
	});
	$(document).on('update create', function(event, model, data) {
		var selector = 'table.dataTable[data-model=' + model + '][data-route][data-listen]';
		$(selector).each(function() {
			// Get id.
			var field = $(this).data('basemodel').toLowerCase() + '_id';
			if (typeof data.elements[field] != 'undefined')
			{
				$(this).dataTable().api().ajax.url(Befound.App.createUrl($(this).data('route'), {'id' : data.elements[field].value})).load();
			}
			else
			{
				$(this).dataTable().api().ajax.reload();
			}
		})

	});

	$(document).on('delete', function(event, model, data) {
		var selector = 'table.dataTable[data-model=' + model + '][data-listen]';
		$(selector).each(function() {
            if (typeof data.id == 'object') {
                var key = [];
                for (var field in data.id) {
                    key.push(data.id[field]);
                }
                $(this).dataTable().api().row('[data-key="' + key.join(".") + '"]').remove().draw();
            } else {
                $(this).dataTable().api().row('[data-key="' + data.id + '"]').remove().draw();
            }
			
		})

	});
});