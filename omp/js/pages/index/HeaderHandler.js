/**
 * @defgroup js_pages_index
 */
// Create the pages_index namespace.
$.pkp.pages.index = $.pkp.pages.index || {};

/**
 * @file js/pages/index/HeaderHandler.js
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class HeaderHandler
 * @ingroup js_pages_index
 *
 * @brief Handler for the site header.
 *
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQuery} $headerElement The HTML element encapsulating
	 *  the header.
	 * @param {Object} options Handler options.
	 */
	$.pkp.pages.index.HeaderHandler =
			function($headerElement, options) {

		this.options_ = options;
		this.parent($headerElement, options);

		this.initializeMenu_();

		// Bind to the link action for toggling inline help.
		$headerElement.find('[id^="toggleHelp"]').click(
				this.callbackWrapper(this.toggleInlineHelpHandler_));
		this.publishEvent('toggleInlineHelp');
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.pages.index.HeaderHandler,
			$.pkp.classes.Handler);


	/**
	 * Site handler options.
	 * @private
	 * @type {Object}
	 */
	$.pkp.pages.index.HeaderHandler.prototype.options_ = null;


	//
	// Private helper methods.
	//
	/**
	 * Respond to a user toggling the display of inline help.
	 *
	 * @param {HTMLElement} sourceElement The element that
	 *  issued the event.
	 * @param {Event} event The triggering event.
	 * @return {boolean} Always returns false.
	 * @private
	 */
	$.pkp.pages.index.HeaderHandler.prototype.toggleInlineHelpHandler_ =
			function(sourceElement, event) {
		this.trigger('toggleInlineHelp');
		return false;
	};


	/**
	 * Initialize navigation menu.
	 * @private
	 */
	$.pkp.pages.index.HeaderHandler.prototype.initializeMenu_ =
			function() {
		var $header = this.getHtmlElement();
		var $menu = $('ul.sf-menu', $header);
		$menu.superfish();

		var requestedPage = this.options_.requestedPage;
		var currentUrl = window.location.href;
		var $linkInMenu = $('a[href="' + currentUrl + '"]', $menu).
				parentsUntil('ul.sf-menu').last();

		if ($linkInMenu.length === 0 && requestedPage !== '') {
			// Search for the current url inside the menu links. If not present,
			// remove part of the url and try again until we've removed the
			// page handler part.
			while (true) {
				// Make the url less specific.
				currentUrl = currentUrl.substr(0, currentUrl.lastIndexOf('/'));

				// Make sure we still have the page handler part in url.
				if (currentUrl.indexOf(requestedPage) === -1) {
					break;
				}

				$linkInMenu = $linkInMenu.add($('a[href="' + currentUrl + '"]',
						$menu).parentsUntil('ul.sf-menu').last());
			}
		}

		if ($linkInMenu.length === 1) {
			// Add the current page style.
			$('a', $linkInMenu).first().addClass('pkp_helpers_underline');
		} else {
			// There is no element or more than one that can represent
			// the current page. For now we don't have a use case for this,
			// can be extended if needed.
		}
	};


/** @param {jQuery} $ jQuery closure. */
})(jQuery);
