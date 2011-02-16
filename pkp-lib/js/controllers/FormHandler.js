/**
 * @file js/controllers/FormHandler.js
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormHandler
 * @ingroup js_controllers
 *
 * @brief PKP form handler.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQuery} $form the wrapped HTML form element.
	 * @param {Object} options options to be passed
	 *  into the validator plug-in.
	 */
	$.pkp.controllers.FormHandler = function($form, options) {
		this.parent($form, options);

		// Check whether we really got a form.
		if (!$form.is('form')) {
			throw Error(['A FormHandler controller can only be bound',
				' to an HTML form element!'].join(''));
		}

		// Activate and configure the validation plug-in.
		var validator = $form.validate({
			submitHandler: this.callbackWrapper(this.handleSubmit),
			showErrors: this.callbackWrapper(this.formChange)
		});

		// Initial form validation.
		if (validator.checkForm()) {
			this.trigger('formValid');
		} else {
			this.trigger('formInvalid');
		}
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.FormHandler, $.pkp.classes.Handler);


	//
	// Public static methods
	//
	/**
	 * Internal callback called whenever the form changes.
	 *
	 * @param {Object} validator The validator plug-in.
	 * @param {Object} errorMap An associative list that attributes
	 *  element names to error messages.
	 * @param {Array} errorList An array with objects that contains
	 *  error messages and the corresponding HTMLElements.
	 */
	$.pkp.controllers.FormHandler.prototype.formChange =
			function(validator, errorMap, errorList) {

		// Show errors generated by the form change.
		validator.defaultShowErrors();

		// Emit validation events.
		if (validator.checkForm()) {
			// Trigger a "form valid" event.
			this.trigger('formValid');
		} else {
			// Trigger a "form invalid" event.
			this.trigger('formInvalid');
		}
	};


	/**
	 * Internal callback called after form validation to handle form
	 * submission.
	 *
	 * You can override this handler if you want to do custom validation
	 * before you submit the form.
	 *
	 * @param {Object} validator The validator plug-in.
	 * @param {HTMLElement} formElement The wrapped HTML form.
	 */
	$.pkp.controllers.FormHandler.prototype.handleSubmit =
			function(validator, formElement) {

		// The default implementation will post the form,
		// and act depending on the returned JSON message.
		var $form = this.getHtmlElement();
		$.post($form.attr('action'), $form.serialize(),
				this.callbackWrapper(this.handleResponse), 'json');
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
	$.pkp.controllers.FormHandler.prototype.handleResponse =
			function(formElement, jsonData) {

		jsonData = this.handleJson(jsonData);
		if (jsonData !== false) {
			if (jsonData.content !== '') {
				// Redisplay the form.
				var $form = this.getHtmlElement();
				$form.replaceWith(jsonData.content);
			} else {
				// Trigger the "form submitted" event.
				this.trigger('formSubmitted');
			}
		}
		return jsonData.status;
	};


/** @param {jQuery} $ jQuery closure. */
})(jQuery);
