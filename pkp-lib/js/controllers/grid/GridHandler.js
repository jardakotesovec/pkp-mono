/**
 * @defgroup js_controllers_grid
 */
// Define the namespace.
$.pkp.controllers.grid = $.pkp.controllers.grid || {};


/**
 * @file js/controllers/grid/GridHandler.js
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GridHandler
 * @ingroup js_controllers_grid
 *
 * @brief Grid row handler.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQuery} $grid The grid this handler is
	 *  attached to.
	 * @param {Object} options Grid handler configuration.
	 */
	$.pkp.controllers.grid.GridHandler = function($grid, options) {
		this.parent($grid, options);

		// We give a chance for this handler to initialize
		// before we initialize its features.
		this.initialize(options);

		this.initFeatures_(options.features);
	};
	$.pkp.classes.Helper.inherits($.pkp.controllers.grid.GridHandler,
			$.pkp.classes.Handler);


	//
	// Private properties
	//
	/**
	 * The id of the grid.
	 * @private
	 * @type {?string}
	 */
	$.pkp.controllers.grid.GridHandler.prototype.gridId_ = null;


	/**
	 * The URL to fetch a grid row.
	 * @private
	 * @type {?string}
	 */
	$.pkp.controllers.grid.GridHandler.prototype.fetchRowUrl_ = null;


	/**
	 * The URL to fetch the entire grid.
	 * @private
	 * @type {?string}
	 */
	$.pkp.controllers.grid.GridHandler.prototype.fetchGridUrl_ = null;


	/**
	 * The selector for the grid body.
	 * @private
	 * @type {?string}
	 */
	$.pkp.controllers.grid.GridHandler.prototype.bodySelector_ = null;


	/**
	 * This grid features.
	 * @private
	 * @type {object}
	 */
	$.pkp.controllers.grid.GridHandler.prototype.features_ = null;


	//
	// Public methods
	//
	/**
	 * Get the fetch row URL.
	 * @return {?string} URL to the "fetch row" operation handler.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.getFetchRowUrl =
			function() {

		return this.fetchRowUrl_;
	};


	/**
	 * Get all grid rows.
	 *
	 * @return {jQuery} The rows as a JQuery object.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.getRows =
			function() {
		return $('.gridRow', this.getHtmlElement());
	};


	/**
	 * Get the id prefix of this grid.
	 * @return {string} The ID prefix of this grid.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.getGridIdPrefix =
			function() {
		return 'component-' + this.gridId_;
	};


	/**
	 * Get the id prefix of this grid row.
	 * @return {string} The id prefix of this grid row.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.getRowIdPrefix =
			function() {
		return this.getGridIdPrefix() + '-row-';
	};


	/**
	 * Get the data element id of the passed grid row.
	 * @param {jQuery} $gridRow The grid row JQuery object.
	 * @return {string} The data element id of the passed grid row.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.getRowDataId =
			function($gridRow) {
		var gridRowHtmlClasses = $gridRow.attr('class').split(' ');
		var rowDataIdPrefix = 'element';
		var index, rowDataId;
		for (index in gridRowHtmlClasses) {
			var startExtractPosition = gridRowHtmlClasses[index]
					.indexOf(rowDataIdPrefix);
			if (startExtractPosition != -1) {
				rowDataId = gridRowHtmlClasses[index].slice(rowDataIdPrefix.length);
				break;
			}
		}

		return rowDataId;
	};


	/**
	 * Get the parent grid row of the passed element, if any.
	 * @param {jQuery} $element The element that is inside the row.
	 * @return {jQuery} The element parent grid row.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.getParentRow =
			function($element) {
		return $element.parents('.gridRow:first');
	};


	/**
	 * Get the same type elements of the passed element.
	 * @param {jQuery} $element The element to get the type from.
	 * @return {jQuery} The grid elements with the same type
	 * of the passed element.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.getElementsByType =
			function($element) {
		if ($element.hasClass('gridRow')) {
			var $container = $element.parents('tbody:first');
			return $('.gridRow', $container);
		} else {
			return null;
		}
	};


	/**
	 * Get the empty element based on the type of the passed element.
	 * @param {jQuery} $element The element to get the type from.
	 * @return {jQuery} The empty element.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.getEmptyElement =
			function($element) {
		if ($element.hasClass('gridRow')) {
			// Return the rows empty element placeholder.
			var $container = $element.parents('tbody:first');
			return $container.next('.empty');
		} else {
			return null;
		}
	};


	/**
	 * Show/hide row actions.
	 *
	 * @param {HTMLElement} sourceElement The element that
	 *  issued the event.
	 * @param {Event} event The triggering event.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.toggleRowActions =
			function(sourceElement, event) {

		// Toggle the row actions.
		var $controlRow = $(sourceElement).parents('tr').next('.row_controls');
		this.applyToggleRowActionEffect_($controlRow);
	};


	/**
	 * Hide all visible row controls.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.hideAllVisibleRowActions =
			function() {
		var $visibleControlRows = $('.row_controls:visible', this.getHtmlElement());
		var index, limit;
		for (index = 0, limit = $visibleControlRows.length; index < limit; index++) {
			this.applyToggleRowActionEffect_($($visibleControlRows[index]));
		}
	};


	/**
	 * Hide row actions div container.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.hideRowActionsDiv =
			function() {
		var $rowActionDivs = $('.gridRow div.row_actions', this.getHtmlElement());
		$rowActionDivs.hide();

		// FIXME: Hack to correctly align the first column cell content after
		// hiding the row actions div.
		var index, limit;
		for (index = 0, limit = $rowActionDivs.length; index < limit; index++) {
			var $div = $($rowActionDivs[index]);
			$div.parents('.row_container:first').
					attr('style', 'padding-left:0px !important');
		}
	};


	/**
	 * Show row actions div container.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.showRowActionsDiv =
			function() {

		var $rowActionDivs = $('.gridRow div.row_actions', this.getHtmlElement());
		$rowActionDivs.show();

		// FIXME: This is a hack. It removes the inline style that grid handler
		// inserts in the row container when it hides the row actions div.
		// See $.pkp.controllers.grid.GridHandler.prototype.hideRowActionsDiv
		var index, limit;
		for (index = 0, limit = $rowActionDivs.length; index < limit; index++) {
			var $div = $($rowActionDivs[index]);
			$div.parents('.row_container:first').removeAttr('style');
		}
	};


	/**
	 * Enable/disable all link actions inside this grid.
	 * @param {boolean} enable Enable/disable flag.
	 * @param {JQuery} $linkElements Link elements JQuery object.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.changeLinkActionsState =
			function(enable, $linkElements) {
		if ($linkElements === undefined) {
			$linkElements = $('.pkp_controllers_linkAction', this.getHtmlElement());
		}
		$linkElements.each(function() {
			var linkHandler = $.pkp.classes.Handler.getHandler($(this));
			if (enable) {
				linkHandler.enableLink();
			} else {
				linkHandler.disableLink();
			}
		});
	};


	/**
	 * Re-sequence all grid rows based on the passed sequence map.
	 * @param {array} sequenceMap A sequence array with the row id as value.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.resequenceRows =
			function(sequenceMap) {
		var rowId, index;
		for (index in sequenceMap) {
			rowId = sequenceMap[index];
			var $row = $('#' + rowId);
			this.appendElement($row);
		}
		this.updateControlRowsPosition();
	};


	/**
	 * Move all grid control rows to their correct position,
	 * below of each correspondent data grid row.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.updateControlRowsPosition =
			function() {
		var $rows = this.getRows();
		var index, limit;
		for (index = 0, limit = $rows.length; index < limit; index++) {
			var $row = $($rows[index]);
			var $controlRow = this.getControlRowByGridRow($row);
			if ($controlRow.length > 0) {
				$controlRow.insertAfter($row);
			}
		}
	};


	//
	// Protected methods
	//
	/**
	 * Set data and execute operations to initialize.
	 *
	 * @protected
	 *
	 * @param {array} options Grid options.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.initialize =
			function(options) {
		// Bind the handler for the "elements changed" event.
		this.bind('dataChanged', this.refreshGridHandler);

		// Bind the handler for the "add new row" event.
		this.bind('addRow', this.addRowHandler_);

		// Handle grid filter events.
		this.bind('formSubmitted', this.refreshGridWithFilterHandler_);

		// Save the ID of this grid.
		this.gridId_ = options.gridId;

		// Save the URL to fetch a row.
		this.fetchRowUrl_ = options.fetchRowUrl;

		// Save the URL to fetch the entire grid
		this.fetchGridUrl_ = options.fetchGridUrl;

		// Save the selector for the grid body.
		this.bodySelector_ = options.bodySelector;

		// Show/hide row action feature.
		this.activateRowActions_();

		this.trigger('gridInitialized');
	};


	/**
	 * Call features hooks.
	 *
	 * @protected
	 *
	 * @param {String} hookName The name of the hook.
	 * @param {Array} args The arguments array.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.callFeaturesHook =
			function(hookName, args) {
		var featureName;
		if (!$.isArray(args)) {
			args = [args];
		}
		for (featureName in this.features_) {
			this.features_[featureName][hookName].
					apply(this.features_[featureName], args);
		}
	};


	/**
	 * Refresh either a single row of the grid or the whole grid.
	 *
	 * @protected
	 *
	 * @param {HTMLElement} sourceElement The element that
	 *  issued the event.
	 * @param {Event} event The triggering event.
	 * @param {number=} opt_elementId The id of a data element that was
	 *  updated, added or deleted. If not given then the whole grid
	 *  will be refreshed.
	 *  @param {Boolean=} opt_fetchedAlready Flag that subclasses can send
	 *  telling that a fetch operation was already handled there.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.refreshGridHandler =
			function(sourceElement, event, opt_elementId, opt_fetchedAlready) {

		// Check if subclasses already handled the fetch of new elements.
		if (!opt_fetchedAlready) {
			if (opt_elementId) {
				// Retrieve a single row from the server.
				$.get(this.fetchRowUrl_, {rowId: opt_elementId},
						this.callbackWrapper(this.replaceElementResponseHandler_), 'json');
			} else {
				// Retrieve the whole grid from the server.
				$.get(this.fetchGridUrl_, null,
						this.callbackWrapper(this.replaceGridResponseHandler_), 'json');
			}
		}

		// Let the calling context (page?) know that the grids are being redrawn.
		this.trigger('gridRefreshRequested');
		this.publishChangeEvents();
	};


	/**
	 * Delete a grid element.
	 *
	 * @protected
	 *
	 * @param {jQuery} $element The element to be deleted.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.deleteElement =
			function($element) {

		// Check whether we really only match one element.
		if ($element.length !== 1) {
			throw Error('There were ' + $element.length +
					' rather than 1 element to delete!');
		}

		// Check whether this is the last row.
		var lastElement = false;
		if (this.getElementsByType($element).length == 1) {
			lastElement = true;
		}

		// Remove the controls row, if any.
		if ($element.hasClass('gridRow')) {
			this.deleteControlsRow_($element);
		}

		var $emptyElement = this.getEmptyElement($element);

		$element.fadeOut(500, function() {
			$(this).remove();
			if (lastElement) {
				$emptyElement.fadeIn(500);
			}
		});
	};


	/**
	 * Append a new row to the end of the list.
	 *
	 * @protected
	 *
	 * @param {jQuery} $newRow The new row to append.
	 * @param {jQuery} $opt_gridBody The tbody container element.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.appendElement =
			function($newRow, $opt_gridBody) {

		if ($opt_gridBody == undefined) {
			$opt_gridBody = this.getHtmlElement().find(this.bodySelector_);
		}

		// Add the new element.
		$opt_gridBody.append($newRow);

		// Hide the empty placeholder.
		var $emptyElement = this.getEmptyElement($newRow);
		$emptyElement.hide();

		this.callFeaturesHook('appendElement', $newRow);
	};


	/**
	 * Update an existing element using the passed new element content.
	 *
	 * @protected
	 *
	 * @param {jQuery} $existingElement The element that is already in grid.
	 * @param {jQuery} $newElement The element with new content.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.replaceElement =
			function($existingElement, $newElement) {

		if ($newElement.hasClass('gridRow')) {
			this.deleteControlsRow_($existingElement);
		}

		$existingElement.replaceWith($newElement);
		this.callFeaturesHook('replaceElement', $newElement);
	};


	/**
	 * Does the passed row have a different number of columns than the
 	 * existing grid?
 	 *
 	 * @protected
 	 *
	 * @param {jQuery} $row The row to be checked against grid columns.
	 * @param {Boolean} checkColSpan Will get the number of row columns
	 * by column span.
	 * @return {Boolean} Whether it has the same number of grid columns
	 * or not.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.hasSameNumOfColumns =
			function($row, checkColSpan) {
		var $grid = this.getHtmlElement();
		var numColumns = $grid.find('th').length;
		var $tdElements = $row.first('tr').find('td');

		if (checkColSpan) {
			var numCellsInNewRow = $tdElements.attr('colspan');
		} else {
			var numCellsInNewRow = $tdElements.length;
		}

		return (numColumns == numCellsInNewRow);
	};


	//
	// Private methods
	//
	/**
	 * Refresh the grid after its filter has changed.
	 *
	 * @private
	 *
	 * @param {$.pkp.controllers.form.ClientFormHandler} filterForm
	 *  The filter form.
	 * @param {Event} event A "formSubmitted" event.
	 * @param {string} filterData Serialized filter data.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.refreshGridWithFilterHandler_ =
			function(filterForm, event, filterData) {

		// Retrieve the grid from the server and add the
		// filter data as form data.
		$.post(this.fetchGridUrl_, filterData,
				this.callbackWrapper(this.replaceGridResponseHandler_), 'json');
	};


	/**
	 * Add a new row to the grid.
	 *
	 * @private
	 *
	 * @param {HTMLElement} sourceElement The element that
	 *  issued the event.
	 * @param {Event} event The triggering event.
	 * @param {Object} params The request parameters to use to generate
	 *  the new row.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.addRowHandler_ =
			function(sourceElement, event, params) {

		// Retrieve a single new row from the server.
		$.get(this.fetchRowUrl_, params,
				this.callbackWrapper(this.replaceElementResponseHandler_), 'json');
	};


	/**
	 * Callback to insert, remove or replace a row after an
	 * element has been inserted, update or deleted.
	 *
	 * @private
	 *
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} jsonData A parsed JSON response object.
	 * @return {Boolean} Return false when no replace action is taken.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.replaceElementResponseHandler_ =
			function(ajaxContext, jsonData) {

		jsonData = this.handleJson(jsonData);
		if (jsonData !== false) {
			if (jsonData.elementNotFound) {
				// The server reported that this element no
				// longer exists in the database so let's
				// delete it.
				var elementId = jsonData.elementNotFound;
				var $element = this.getHtmlElement().
						find('.element' + elementId);

				// Sometimes we get a delete event before the
				// element has actually been inserted (e.g. when deleting
				// elements due to a cancel action or similar).
				if ($element.length === 0) {
					return false;
				}

				this.deleteElement($element);
			} else {
				// The server returned mark-up to replace
				// or insert the row.
				this.insertOrReplaceElement_(jsonData.content);

				// Refresh row action event binding.
				this.activateRowActions_();
			}
		}
	};


	/**
	 * Callback to replace a grid's content.
	 *
	 * @private
	 *
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} jsonData A parsed JSON response object.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.replaceGridResponseHandler_ =
			function(ajaxContext, jsonData) {

		jsonData = this.handleJson(jsonData);
		if (jsonData !== false) {
			// Get the grid that we're updating
			var $grid = this.getHtmlElement();

			// Replace the grid content
			$grid.replaceWith(jsonData.content);

			// Refresh row action event binding.
			this.activateRowActions_();
		}
	};


	/**
	 * Helper that inserts or replaces an element.
	 *
	 * @private
	 *
	 * @param {string} elementContent The new mark-up of the element.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.insertOrReplaceElement_ =
			function(elementContent) {

		// Parse the HTML returned from the server.
		var $newElement = $(elementContent), newElementId = $newElement.attr('id');

		// Does the element exist already?
		var $grid = this.getHtmlElement(),
				$existingElement = newElementId ? $grid.find('#' + newElementId) : {};

		if ($existingElement.length > 1) {
			throw Error('There were ' + $existingElement.length +
					' rather than 0 or 1 elements to be replaced!');
		}

		if (!this.hasSameNumOfColumns($newElement)) {
			// Redraw the whole grid so new columns
			// get added/removed to match element.
			$.get(this.fetchGridUrl_, null,
					this.callbackWrapper(this.replaceGridResponseHandler_), 'json');
		} else {
			if ($existingElement.length === 1) {
				// Update element.
				this.replaceElement($existingElement, $newElement);
			} else {
				// Insert row.
				this.appendElement($newElement);
			}
		}
	};


	/**
	 * Helper that deletes the row of controls (if present).
	 *
	 * @private
	 *
	 * @param {jQuery} $row The row whose matching control row should be deleted.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.deleteControlsRow_ =
			function($row) {
		var $controlRow = $('#' + $row.attr('id') + '-control-row',
				this.getHtmlElement());

		if ($controlRow.is('tr') && $controlRow.hasClass('row_controls')) {
			$controlRow.remove();
		}
	};


	/**
	 * Get the control row for the passed the grid row.
	 *
	 * @param {jQuery} $gridRow The grid row JQuery object.
	 * @return {jQuery} The control row JQuery object.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.getControlRowByGridRow =
			function($gridRow) {
		var rowId = $gridRow.attr('id');
		var controlRowId = rowId + '-control-row';
		return $('#' + controlRowId);
	};


	/**
	 * Helper that attaches click events to row actions.
	 *
	 * @private
	 */
	$.pkp.controllers.grid.GridHandler.prototype.activateRowActions_ =
			function() {

		var $grid = this.getHtmlElement();
		$grid.find('a.settings').unbind('click').bind('click',
				this.callbackWrapper(this.toggleRowActions));
	};


	/**
	 * Apply the effect for hide/show row actions.
	 *
	 * @private
	 *
	 * @param {jQuery} $controlRow The control row JQuery object.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.applyToggleRowActionEffect_ =
			function($controlRow) {
		// FIXME #7582: IE8 and Safari don't work well with delay to show
		// or hide the control grid rows.
		var delay = 0;

		var $row = $controlRow.prev().find('td:not(.indent_row)');
		if ($controlRow.is(':visible')) {
			setTimeout(function() {
				$row.removeClass('no_border');
			}, delay);
			$controlRow.hide(delay);
		} else {
			$row.addClass('no_border');
			$controlRow.show(delay);
		}
		clearTimeout();
	};


	/**
	 * Add a grid feature.
	 *
	 * @private
	 *
	 * @param {string} id Feature id.
	 * @param {$.pkp.classes.features.Feature} $feature The grid
	 * feature to be added.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.addFeature_ =
			function(id, $feature) {
		if (!this.features_) {
			this.features_ = [];
		}
		this.features_[id] = $feature;
	};


	/**
	 * Add grid features.
	 *
	 * @private
	 *
	 * @param {Array} features The features options array.
	 */
	$.pkp.controllers.grid.GridHandler.prototype.initFeatures_ =
			function(features) {
		var id;
		for (id in features) {
			var $feature =
					($.pkp.classes.Helper.objectFactory(
							features[id].JSClass,
							[this, features[id].options]));

			this.addFeature_(id, $feature);
			this.features_[id].init();
		}
	};


/** @param {jQuery} $ jQuery closure. */
})(jQuery);
