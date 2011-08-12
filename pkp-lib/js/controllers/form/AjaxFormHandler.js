/**
 * @file js/controllers/form/AjaxFormHandler.js
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AjaxFormHandler
 * @ingroup js_controllers_form
 *
 * @brief Form handler that submits the form to the server via AJAX and
 *  either replaces the form if it is re-rendered by the server or
 *  triggers the "formSubmitted" event after the server confirmed
 *  form submission.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.FormHandler
	 *
	 * @param {jQuery} $form the wrapped HTML form element.
	 * @param {Object} options options to be passed
	 *  into the validator plug-in.
	 */
	$.pkp.controllers.form.AjaxFormHandler = function($form, options) {
		options.submitHandler = this.submitForm;
		this.parent($form, options);

		this.bind('refreshForm', this.refreshFormHandler_);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.form.AjaxFormHandler,
			$.pkp.controllers.form.FormHandler);


	//
	// Public methods
	//
	/**
	 * Internal callback called after form validation to handle form
	 * submission.
	 *
	 * @param {Object} validator The validator plug-in.
	 * @param {HTMLElement} formElement The wrapped HTML form.
	 */
	$.pkp.controllers.form.AjaxFormHandler.prototype.submitForm =
			function(validator, formElement) {

		// This form implementation will post the form,
		// and act depending on the returned JSON message.
		var $form = this.getHtmlElement();
		$.post($form.attr('action'), $form.serialize(),
				this.callbackWrapper(this.handleResponse), 'json');
	};


	/**
	 * Callback to replace a modal's content.
	 *
	 * @private
	 *
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} jsonData A parsed JSON response object.
	 */
	$.pkp.controllers.form.AjaxFormHandler.prototype.refreshFormHandler_ =
			function(sourceElement, event, content) {

		if (content) {
			// Get the grid that we're updating
			var $element = this.getHtmlElement();

			// Replace the grid content
			$element.replaceWith(content);
		}
	};


	/**
	 * Internal callback called after form validation to handle the
	 * response to a form submission.
	 *
	 * You can override this handler if you want to do custom handling
	 * of a form response.
	 *
	 * @param {HTMLElement} formElement The wrapped HTML form.
	 * @param {Object} jsonData The data returned from the server.
	 * @return {boolean} The response status.
	 */
	$.pkp.controllers.form.AjaxFormHandler.prototype.handleResponse =
			function(formElement, jsonData) {

		jsonData = this.handleJson(jsonData);
		if (jsonData !== false) {
			if (jsonData.content === '') {
				// Trigger the "form submitted" event.
				this.trigger('notifyUser');
				this.trigger('formSubmitted');
			} else {
				// Redisplay the form.
				var $form = this.getHtmlElement();
				$form.replaceWith(jsonData.content);
			}
		}
		return jsonData.status;
	};

/** @param {jQuery} $ jQuery closure. */
})(jQuery);
