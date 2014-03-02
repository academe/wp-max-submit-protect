/**
 * Copyright 2013-2014 Academe Computing Ltd
 * Released under the MIT license
 * Author: Jason Judge <jason@academe.co.uk>
 * Version: 1.1.1
 */
/**
 * jquery.maxsubmittest.js
 *
 * Used to look at what jquery.maxsubmit is counting as fields that will be submitted.
 * To use:
 * 1. Include this script, jquery.maxSubmitTest.js, after jquery.maxSubmit.js
 * 2. Add the following HTML to the page, within the form you want to test:
 *     <div class="test-maxsubmit"><a>Test MaxSubmit</a></div>
 * 3. Initialise the test function:
 *     $('.test-maxsubmit').maxSubmitTest();
 * When you click on the "Test MaxSubmit" text, a table will be inserted immediately below it,
 * within div.test-maxsubmit. The table will list the field types, names and values, which can be
 * compared to what Firebug or similar shows is being posted.
 */

(function($) {
	/**
	 * TBC
	 */
	$.fn.maxSubmitTest = function() {
		return this.each(function() {
			$(this).click(function() {
				// Get the list of elements.
				var fields = $(this).closest('form').maxSubmitCount(true);

				// Create a table for populating with the results.
				// Remove the table if it already exists.
				$(this).closest('div').find('table').remove();

				// New table element and give it a header.
				var table = document.createElement('table');
				$(table).append('<tr><th>Index</th><th>Type</th><th>Name</th><th>Value</th></tr>');

				// Add each element as a row.
				for (var i = 0; i < fields.length; i++) {
					var tr = document.createElement('tr');

					var td_index = document.createElement('td');
					var td_type = document.createElement('td');
					var td_name = document.createElement('td');
					var td_value = document.createElement('td');

					$(td_index).append('' + (i+1));

					if ($(fields[i]).prop('tagName') == 'OPTION') {
						$(td_type).append($(fields[i]).closest('select').get(0).type);
					} else {
						$(td_type).append(fields[i].type);
					}

					// Get the name of the element.
					// If a multiselect list, then we need to go to the parent to get
					// the name.
					if ($(fields[i]).prop('tagName') == 'OPTION') {
						$(td_name).append($(fields[i]).closest('select').attr('name'));
					} else {
						$(td_name).append(fields[i].name);
					}

					// Get the value, encoding entities for display.
					$(td_value).append($('<div/>').text($(fields[i]).val()).html());

					// Build up the row.
					tr.appendChild(td_index);
					tr.appendChild(td_type);
					tr.appendChild(td_name);
					tr.appendChild(td_value);

					// Add the row to the table.
					table.appendChild(tr);
				}

				// Put the new table into the page.
				$(this).closest('div').append(table);
			});
		});
	};
}(jQuery));

