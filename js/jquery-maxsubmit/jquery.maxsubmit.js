/**
 * Copyright 2013-2014 Academe Computing Ltd
 * Released under the MIT license
 * Author: Jason Judge <jason@academe.co.uk>
 * Version: 1.1.3
 */
/**
 * jquery.maxsubmit.js
 *
 * Checks how many parameters a form is going to submit, and
 * gives the user a chance to cancel if it exceeds a set number.
 * PHP5.3+ has limits set by default on the number of POST parameters
 * that will be accepted. Parameters beyond that number, usually 1000,
 * will be silently discarded. This can have nasty side-effects in some
 * applications, such as editiong shop products with many variations
 * against a product, which can result in well over 1000 submitted
 * parameters (looking at you WooCommerce). This aims to provide some
 * level of protection.
 *
 */

(function($) {
	$.fn.maxSubmit = function(options) {
		// this.each() is the wrapper for each selected group of checkboxes.
		return this.each(function() {

			var settings = $.extend({
				// The maximum number of parameters the form will be allowed to submit
				// before the user is issued a confirm (OK/Cancel) dialogue.

				max_count: 1000,

				// The message given to the user to confirm they want to submit anyway.
				// Can use {max_count} as a placeholder for the permitted maximum
				// and {form_count} for the counted form items.

				max_exceeded_message:
					'This form has too many fields for the server to accept.\n'
					+ ' Data may be lost if you submit. Are you sure you want to go ahead?',

				// The function that will display the confirm message.
				// Replace this with something fancy such as jquery.ui if you wish.

				confirm_display: function(form_count) {
					if (typeof(form_count) === 'undefined') form_count = '';
					return confirm(
						settings
							.max_exceeded_message
							.replace("{max_count}", settings.max_count)
							.replace("{form_count}", form_count)
					);
				}
			}, options);

			// Form elements will be passed in, so we need to trigger on
			// an attempt to submit that form.

			// First check we do have a form.
			if ($(this).is("form")) {
				$(this).on('submit', function(e) {
					// We have a form, so count up the form items that will be
					// submitted to the server.

					// textarea fields count as one submitted field each.
					var form_count = $('textarea:enabled', this).length;

					// Input fields of all types except checkboxes and radio buttons will
					// all post one parameter.
					// reset inputs are not submitted to the server and files are handled
					// separately.
					form_count += $('input:enabled', this)
						.not("[type='checkbox']")
						.not("[type='radio']")
						.not("[type='file']")
						.not("[type='reset']")
						.length;

					// Checkboxes will post only if checked.
					$('input:checkbox:enabled', this).each(function() {
						if (this.checked) form_count++;
					});

					// Single-select lists will always post one value.
					$('select:enabled:not([multiple])', this).each(function() {
						form_count++;
					});

					// Multi-select lists will post one parameter for each selected item.
					$('select:enabled[multiple]', this).each(function() {
						// The select item value is null if no options are selected.
						var select = $(this).val();
						if (select !== null) form_count += select.length;
					});

					// Each radio button group will post one parameter, regardless of how many
					// radio buttons a group contains.
					// Count the radio groups
					var rgroups = [];
					$('input:enabled:radio').each(function(index, el) {
						var i;
						for(i = 0; i < rgroups.length; i++) {
							if (rgroups[i] == $(el).attr('name')) return;
						}
						rgroups.push($(el).attr('name'));
					});
					form_count += rgroups.length;

					if (form_count > settings.max_count) {
						// If the user cancels, then abort the form submit.
						if (!settings.confirm_display(form_count)) return false;
					}

					// Allow the submit to go ahead.
					return true;
				});
			}

			// Support chaining.
			return this;
		});
	};
}(jQuery));

