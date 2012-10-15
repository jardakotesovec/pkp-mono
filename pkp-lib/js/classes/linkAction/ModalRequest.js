/**
 * @file js/classes/linkAction/ModalRequest.js
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ModalRequest
 * @ingroup js_classes_linkAction
 *
 * @brief Modal link action request.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.linkAction.LinkActionRequest
	 *
	 * @param {jQueryObject} $linkActionElement The element the link
	 *  action was attached to.
	 * @param {{
	 *  modalHandler: Object
	 *  }} options Configuration of the link action
	 *  request.
	 */
	$.pkp.classes.linkAction.ModalRequest =
			function($linkActionElement, options) {

		this.parent($linkActionElement, options);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.classes.linkAction.ModalRequest,
			$.pkp.classes.linkAction.LinkActionRequest);


	//
	// Private properties
	//
	/**
	 * A pointer to the dialog HTML element.
	 * @private
	 * @type {jQueryObject}
	 */
	$.pkp.classes.linkAction.ModalRequest.prototype.$dialog_ = null;


	//
	// Public methods
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.classes.linkAction.ModalRequest.prototype.activate =
			function(element, event) {

		// If there is no title then try to retrieve a title
		// from the calling element's text.
		var modalOptions = this.getOptions(),
				$handledElement = this.getLinkActionElement(),
				title = $handledElement.text(),
				uuid,
				$linkActionElement,
				linkActionHandler,
				handlerOptions,
				dialogHandler;

		if (modalOptions.title === undefined) {
			if (title === '') {
				// Try to retrieve a title from the link action element's
				// title attribute.
				title = $handledElement.attr('title');
			}
			modalOptions.title = title;
		}

		// Generate a unique ID.
		uuid = $.pkp.classes.Helper.uuid();

		// Instantiate the modal.
		if (!modalOptions.modalHandler) {
			throw new Error(['The "modalHandler" setting is required ',
				'in a ModalRequest'].join(''));
		}

		// Make sure that all events triggered on the modal will be
		// forwarded to the link action. This is necessary because the
		// modal has to be created outside the regular DOM.
		$linkActionElement = /** @type {jQueryObject} */ (
				this.getLinkActionElement());
		linkActionHandler = $.pkp.classes.Handler.getHandler($linkActionElement);
		handlerOptions = $.extend(true,
				{$eventBridge: linkActionHandler.getStaticId()}, modalOptions);
		this.$dialog_ = $('<div id=' + uuid + '></div>').pkpHandler(
				modalOptions.modalHandler, handlerOptions);

		// Subscribe to the dialog handler's 'removed' event so that
		// we can clean up.
		dialogHandler = $.pkp.classes.Handler.getHandler(this.$dialog_);
		dialogHandler.bind('pkpRemoveHandler',
				$.pkp.classes.Helper.curry(this.finish, this));

		return /** @type {boolean} */ (this.parent('activate', element, event));
	};


	/**
	 * @inheritDoc
	 */
	$.pkp.classes.linkAction.ModalRequest.prototype.finish =
			function() {

		this.$dialog_.remove();
		return /** @type {boolean} */ (this.parent('finish'));
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
