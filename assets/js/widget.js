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


	/**
	 * Filter hooks
	 */
	$('table.dataTable').on('keyup', 'tr.filters input', function(e) {
		$(this).closest('table').dataTable().fnFilter($(this).val(), $(this).parent().index());
	});
	
	$('table.dataTable').on('change', 'tr.filters select', function(e) {
			$(this).closest('table').dataTable().fnFilter($(this).val(), $(this).parent().index());
		});

	$('table.dataTable').on('init.dt', function(e, settings, json) {
		console.log('init');
		if (typeof json == 'undefined')
		{
			json = {
				'aaData' : settings.oInstance.fnGetData()
			};
			
		}
		$(this).trigger('dataload.dt', [settings, json]);
		

	});
	$('table.dataTable').on('xhr.dt', function(e, settings, json) {
		if ($(this).attr('id') == 'DataTables_Table_2')
		{
			$(this).one('draw.dt', function() {
				$(this).trigger('dataload.dt', [settings, json]);
			});
		}
	});
	/*
	 * Update filters
	 */
	$('table.dataTable').on('dataload.dt', function(e, settings, json) {
		console.log('dataload');
		console.log(json);
		for (var i in settings.aoColumns)
		{
			if (typeof settings.aoColumns[i].sFilter != 'undefined' && settings.aoColumns[i].sFilter == 'select')
			{
				var values = {};
				for (var j in json.aaData)
				{
					values[json.aaData[j][i]] = 1;
				}
				var options = Object.keys(values).sort();
				var select = $('tr.filters th:nth(' + i + ') select')

				select.find('option').remove();
				select.append('<option value="">No filter</option>');

				for (var k in options)
				{
					select.append("<option>" + options[k] + "</option>");
				}
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
	if (aData.length > $(nRow).children().length )
	{
		$(nRow).attr(aData[aData.length -1]);
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