/**
 * @defgroup js_controllers_grid
 */
/**
 * @file js/controllers/grid/issues/BackIssueGridHandler.js
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BackIssueGridHandler
 * @ingroup js_controllers_grid
 *
 * @brief A subclass of the GridHandler for the grid displaying back issues.
 *   It handles communication between the future and back issue grids, so that
 *   updates to published issues are synchronized.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.grid.issues = $.pkp.controllers.grid.issues || {};

	/** @type {Object} */
	$.pkp.controllers.grid.issues.BackIssueGridHandler =
			$.pkp.controllers.grid.issues.BackIssueGridHandler || {};


	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.grid.GridHandler
	 *
	 * @param {jQueryObject} $grid The grid this handler is
	 *  attached to.
	 * @param {{features}} options Grid handler configuration.
	 */
	$.pkp.controllers.grid.issues.BackIssueGridHandler =
			function($grid, options) {
		this.parent($grid, options);

		this.bindGlobal('issuePublished', this.refreshGridHandler );
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.grid.issues.BackIssueGridHandler,
			$.pkp.controllers.grid.GridHandler);


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
