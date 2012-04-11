/**
 * @defgroup js_pages_workflow
 */
// Create the pages_workflow namespace.
$.pkp.pages.workflow = $.pkp.pages.workflow || {};

/**
 * @file js/pages/workflow/SubmissionHeaderHandler.js
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionHeaderHandler
 * @ingroup js_pages_workflow
 *
 * @brief Handler for the workflow header.
 *
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQuery} $submissionHeader The HTML element encapsulating
	 *  the header div.
	 * @param {Object} options Handler options.
	 */
	$.pkp.pages.workflow.SubmissionHeaderHandler =
			function($submissionHeader, options) {

		this.parent($submissionHeader, options);

		// show and hide on click of link
		$('#participantToggle').click(function() {
			$('.participant_popover').toggle();
		});

		this.bind('gridRefreshRequested', this.refreshWorkflowContent_);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.pages.workflow.SubmissionHeaderHandler,
			$.pkp.classes.Handler);


	//
	// Private functions
	//
	/**
	 * Potentially refresh workflow content on contained grid changes.
	 *
	 * @param {JQuery} callingElement The calling element.
	 *  that triggered the event.
	 * @private
	 */
	$.pkp.pages.workflow.SubmissionHeaderHandler.prototype.refreshWorkflowContent_ =
			function(callingElement, event) {

		var $updateSourceElement = $(event.target);
		if ($updateSourceElement.attr('id').match(/^stageParticipantGridContainer/)) {
			// If the participants grid was the event source, we
			// may need to re-draw workflow contents.
			
		}
	};
/** @param {jQuery} $ jQuery closure. */
})(jQuery);
