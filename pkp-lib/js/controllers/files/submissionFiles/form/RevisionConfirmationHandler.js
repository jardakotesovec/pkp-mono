/**
 * @file js/controllers/RevisionConfirmationHandler.js
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RevisionConfirmationHandler
 * @ingroup js_controllers_files_submissionFiles_form
 *
 * @brief Revision confirmation tab handler.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.FormHandler
	 *
	 * @param {jQuery} $form The wrapped HTML form element.
	 * @param {Object} options Form validation options.
	 */
	$.pkp.controllers.files.submissionFiles.form.RevisionConfirmationHandler =
			function($form, options) {

		this.parent($form, options);

		// Show the possible revision message.
		$form.find('#possibleRevision').show('slide');

		// Subscribe to wizard events.
		this.bind('wizardAdvanceRequested', this.wizardAdvanceRequested);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.files.submissionFiles.form.RevisionConfirmationHandler,
			$.pkp.controllers.FormHandler);


	//
	// Public methods
	//
	/**
	 * Handle the "advance requested" event triggered by the enclosing wizard.
	 *
	 * @param {HTMLElement} wizardElement The calling wizard.
	 * @param {Event} event The triggered event.
	 */
	$.pkp.controllers.files.submissionFiles.form.RevisionConfirmationHandler.
			prototype.wizardAdvanceRequested = function(wizardElement, event) {

		var $confirmationForm = this.getHtmlElement();
		var revisedFileId = parseInt(
				$confirmationForm.find('#revisedFileId').val(), 10);
		if (revisedFileId > 0) {
			// Submit the form.
			$confirmationForm.submit();
			event.preventDefault();
		}
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.controllers.files.submissionFiles.form.RevisionConfirmationHandler.
			prototype.handleResponse = function(formElement, jsonData) {

		if (jsonData.status === true) {
			// Trigger the file uploaded event.
			this.trigger('fileUploaded', jsonData.uploadedFile);
		}

		this.parent('handleResponse', formElement, jsonData);
	};


/** @param {jQuery} $ jQuery closure. */
})(jQuery);
