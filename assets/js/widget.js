/*
 * Add some extra functionality for easier usage within Yii.
 */
$(document).ready(function() {
	// Clicking on a remove row link will remove it from the table.
	$('body').on('click', 'table.dataTable a.removerow', function() {
		$(this).closest('table').dataTable({"bRetrieve" : true}).fnDeleteRow($(this).closest('tr'));
	});

	// Handle row selection for single select.
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

	// Handle multiple select.
	$('body').on('click', 'table.dataTable.multiSelect tbody tr', function() {
		var $this = $(this);
		$this.toggleClass('selected');
		$this.find('input.select-on-check').prop('checked', $this.hasClass('selected'));
		$this.trigger('change');
	});

	// Don't propagate click events for inputs.'
	$('body').on('click', 'table.dataTable.multiSelect tbody tr input', function(e) {
		e.stopPropagation();
	});

	$('body').on('click', 'table.dataTable.multiSelect tbody tr input.select-on-check', function(e) {
		e.stopPropagation();
		var $grid = $(this).closest('table');
		var $checks = $('input.select-on-check', $grid);
		if (this.checked)
		{
			$(this).closest('tr').addClass('selected');
		}
		else
		{
			$(this).closest('tr').removeClass('selected');
		}
		$("input.select-on-check-all", $grid).prop('checked', $checks.length === $checks.filter(':checked').length);

	});

	$('body').on('click', 'table.dataTable.multiSelect thead tr input.select-on-check-all', function(e) {
		e.stopPropagation();
		if (this.checked)
		{
			$(this).closest('table').find('tbody tr').addClass('selected');
			$(this).closest('table').find('tbody tr input.select-on-check').prop('checked', true);
		}
		else
		{
			$(this).closest('table').find('tbody tr').removeClass('selected');
			$(this).closest('table').find('tbody tr input.select-on-check').prop('checked', false);
		}
	});


	/**
	 * Filter hooks
	 */
	$('body').on('keyup', 'table.dataTable tr.filters input', function(e) {
		$(this).closest('table').dataTable().fnFilter($(this).val(), $(this).parent().index());
	});
	
	$('body').on('change', 'table.dataTable tr.filters select', function(e) {
		$(this).closest('table').dataTable().fnFilter($(this).val(), $(this).parent().index());
	});

	$('body').on('init.dt', 'table.dataTable', function(e, settings, json) {
		settings.oApi._fnLog = function ( settings, level, msg, tn ) {
			msg = 'DataTables warning: '+
				(settings!==null ? 'table id='+settings.sTableId+' - ' : '')+msg;

			if ( tn ) {
				msg += '. For more information about this error, please see '+
				'http://datatables.net/tn/'+tn;
			}

			console.log("DataTable: " + msg);
		};
		if (typeof json == 'undefined')
		{
			json = {
				'data' : settings.oInstance.fnGetData()
			};
			
		}
		$(this).trigger('dataload.dt', [settings, json]);
	});

	$('body').on('processing.dt', 'table.dataTable', function(e, settings, processing) {
		if (processing)
		{
			$(this).parent().parent().parent().trigger('startLoading');
		}
		else
		{
			$(this).parent().parent().parent().trigger('endLoading');
		}
	});

	$('body').on('xhr.dt', 'table.dataTable', function(e, settings, json) {
		$(this).one('draw.dt', function() {
			$(this).trigger('dataload.dt', [settings, json]);
		});
	});
	/*
	 * Update filters
	 */
	$('table.dataTable').on('dataload.dt', function(e, settings, json) {
		for (var i in settings.aoColumns)
		{
			if (typeof settings.aoColumns[i].sFilter != 'undefined' && settings.aoColumns[i].sFilter == 'select')
			{
				var values = {};
				for (var j in json.data)
				{
					values[json.data[j][settings.aoColumns[i].data]] = 1;
				}
				var options = Object.keys(values).sort();
				var select = $('tr.filters th:nth(' + i + ') select')

				select.find('option').remove();
				select.append('<option value="">No filter</option>');

				for (var k in options)
				{
					select.append("<option>" + options[k] + "</option>");
				}
				select.trigger('change');
			}
		}

	})
});

(function($) {
/*
 * Function: fnGetColumnData
 * Purpose:  Return an array of table values from a particular column.
 * Returns:  array string: 1d data array
 * Inputs:   object:oSettings - dataTable settings object. This is always the last argument past to the function
 *           int:iColumn - the id of the column to extract the data from
 *           bool:bUnique - optional - if set to false duplicated values are not filtered out
 *           bool:bFiltered - optional - if set to false all the table data is used (not only the filtered)
 *           bool:bIgnoreEmpty - optional - if set to false empty values are not filtered from the result array
 * Author:   Benedikt Forchhammer <b.forchhammer /AT\ mind2.de>
 */
$.fn.dataTableExt.oApi.fnGetColumnData = function ( oSettings, iColumn, bUnique, bFiltered, bIgnoreEmpty ) {
	console.log('getting data');
    // check that we have a column id
    if ( typeof iColumn == "undefined" ) return new Array();
     
    // by default we only want unique data
    if ( typeof bUnique == "undefined" ) bUnique = true;

    // by default we do want to only look at filtered data
    if ( typeof bFiltered == "undefined" ) bFiltered = true;

    // by default we do not want to include empty values
    if ( typeof bIgnoreEmpty == "undefined" ) bIgnoreEmpty = true;

    // list of rows which we're going to loop through
    var aiRows;

    // use only filtered rows
    if (bFiltered == true) aiRows = oSettings.aiDisplay;
    // use all rows
    else aiRows = oSettings.aiDisplayMaster; // all row numbers

    // set up data array
    var asResultData = new Array();

    for (var i=0,c=aiRows.length; i<c; i++) {
        iRow = aiRows[i];
        var aData = this.fnGetData(iRow);
        var sValue = aData[iColumn];

        // ignore empty values?
        if (bIgnoreEmpty == true && sValue.length == 0) continue;

        // ignore unique values?
        else if (bUnique == true && jQuery.inArray(sValue, asResultData) > -1) continue;

        // else push the value onto the result data array
        else asResultData.push(sValue);
    }

    return asResultData;
}}(jQuery));


// Function for adding metadata to rows. Data is passed in the "extra column".
$.fn.dataTableExt.oApi.fnAddMetaData = function (oSettings, nRow, aData, iDataIndex)
{
	if (typeof aData.metaData != 'undefined')
	{
		$(nRow).attr(aData.metaData);
	}
}

jQuery.extend( jQuery.fn.dataTableExt.oSort, {
    "alt-string-pre": function ( a ) {
        return a.match(/alt="(.*?)"/)[1].toLowerCase();
    },

    "alt-string-asc": function( a, b ) {
        return ((a < b) ? -1 : ((a > b) ? 1 : 0));
    },

    "alt-string-desc": function(a,b) {
        return ((a < b) ? 1 : ((a > b) ? -1 : 0));
    }
} );

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