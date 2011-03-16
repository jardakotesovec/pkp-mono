/**
 * listbuilder.js
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Implementation of listbuilder interface elements for OMP.
 */

/**
 * addItem
 * Add an item to the list
 * @param {string} handler URL handle the routine
 * @param {string} listbuilderId DOM id to the listbuilder being used
 * @param {Array} localizedButtons
 */
function addItem(handler, listbuilderId, localizedButtons) {
	$(document).ready(function() {
		var form = '#source-' + listbuilderId;

		$('#add-' + listbuilderId).click(function() {
			var newItem = $('#source-' + listbuilderId + ' *').serialize();
			var additionalData = $('#additionalData-' + listbuilderId + ' *').serialize();

			$.post(
				handler,
				additionalData ? newItem + '&' + additionalData : newItem,
				function(returnString) {
					if (returnString.status) {
						$(returnString.content).hide().prependTo('#listGrid-' + listbuilderId).fadeIn('slow').css("display","");
						$('#listGrid-' + listbuilderId + ' tr.empty').hide();

						// Remove the item from the source
						$("#source-" + listbuilderId + " .text").val(""); //If source is text input
						$("#source-" + listbuilderId + " option:selected").remove(); //If source is list

						// If applicable, add the result to source lists elsewhere on the page
						if(returnString.addToSources) {
							var sourceIdString = returnString.sourceIds;
							var sourceIds = new Array();
							sourceIds = sourceIdString.split(',');

							$.each(sourceIds, function(key, value) {
								$("#"+value).append(returnString.sourceHtml);
							});
						}
					} else {
						// Alert that the action failed
						modalAlert(returnString.content, localizedButtons);
					}
				}, 'json'
			);
		});
	});
}

/**
 * deleteItems
 * Delete selected items from the list
 * @param {string} handler URL handle the routine
 * @param {string} listbuilderId DOM id to the listbuilder being used
 */
function deleteItems(handler, listbuilderId) {
	$(document).ready(function() {
		$('#delete-' + listbuilderId).click(function() {
			var selectedItems = [];
			$('#listGrid-' + listbuilderId + ' .selected').each(function(i, selected){
				selectedItems.push('item_' + i + '=' + $(selected).attr('id'));
			});
			var additionalData = $('#additionalData-' + listbuilderId + ' *').serialize();

			$.post(
				handler,
				additionalData ? selectedItems.join('&') + '&' + additionalData : selectedItems.join('&'),
				function(returnString) {
					if (returnString.status) {
						// Remove the select items from the list
						$('#listGrid-' + listbuilderId + ' .selected').each(function(i, selected){
							$(selected).remove();
						});

						// If applicable, remove the result from source lists elsewhere on the page
						if(returnString.removeFromSources) {
							var sourceIdString = returnString.sourceIds;
							var sourceIds = new Array();
							sourceIds = sourceIdString.split(',');

							var itemIdString = returnString.itemIds;
							var itemIds = new Array();
							itemIds = itemIdString.split(',');

							$.each(sourceIds, function(sourceKey, sourceValue) {
								$.each(itemIds, function(itemKey, itemValue) {
									$("#" + sourceValue+" option[value='" + itemValue + "']").remove();
								})
							});
						}
					} else {
						// Alert that the action failed
						alert('DELETING ITEM FAILED');	// FIXME:  Need to translate.  Make this a modal?
					}
				}, 'json'
			);
		});
	});
}

/**
 * selectRow
 * Select a row in a listbuilder grid
 * @param {string} listbuilderGridId The DOM ID of the list builder.
 */
function selectRow(listbuilderGridId) {
	$('#results-'+listbuilderGridId)
		.css('cursor', 'pointer')
		.click(function(e) {
			var $clicked = $(e.target).closest('tr');
			if($clicked.length && !$clicked.hasClass('empty')) {
				$clicked.toggleClass('selected');
			}
			return false;
		});
}

/**
 * getAutocompleteSource
 * Load either an array of data for a local autocomplete interface, or a URL for a server-based autocomplete
 * @param {string} handler URL handle the routine
 * @param {string} id DOM id to the listbuilder being used
 */
function getAutocompleteSource(handler, id) {
	$(document).ready(function(){
		var data = null;
		$.getJSON(
			handler,
			function(returnString) {
				if (returnString.elementId == 'local') {
					// Set the data source to an array (for smaller data sets only)
					data = eval("("+returnString.content+")");
				} else if (returnString.elementId == 'url') {
					// Set the data to the url
					data = returnString.content;
				}

				// Initialize the autocomplete field
				$("#sourceTitle-" + id).autocomplete({
					source: data,
					minLength: 0,
					focus: function(event, ui) {
						$("#sourceTitle-" + id).val(ui.item.label);
						return false;
					},
					select: function (event, ui) {
						$("#sourceId-" + id).val(ui.item.value);
						return false;
					}
				});
			}
		);
	});
}

/**
 * Helper function for getAutocompleteSource
 * @param {Array} row row data
 */
function formatItem(row) {
	return row[0] + " (<strong>id: " + row[1] + "</strong>)";
}

/**
 * Helper function for getAutocompleteSource
 * @param {Array} row row data
 */
function formatResult(row) {
	return row[0].replace(/(<.+?>)/gi, '');
}
