/**
 * @defgroup js_classes_notification
 */
// Define the namespace
$.pkp.classes.notification = $.pkp.classes.notification || {};


/**
 * @file js/classes/notification/NotificationHelper.js
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NotificationHelper
 * @ingroup js_classes_notification
 *
 * @brief Class that perform notification helper actions.
 */
(function($) {

	$.pkp.classes.notification.NotificationHelper = function() {};

	//
	// Public static helper methods
	//
	/**
	 * Decides which notification will be used: in place or general.
	 * This method finds all notification widgets that are inside of the handled element
	 * of the controller that is handling the current notify user event (page or modal).
	 * We need to make sure that all notifications will be shown inside the same widget
	 * where the notify user was triggered. Beyond that, we also need to make sure that,
	 * inside some widgets (tabs and accordions, for example) we use the right
	 * notification controller.
	 * To do this, the notification element must follow these rules:
	 *
	 * 1 - the notification element must not have a hidden parent, although it can be
	 * hidden itself.
	 *
	 * 2 - the notification element first widget parent also needs to contain the
	 * element that triggered the notify user event.
	 *
	 * 3 - if the notification element is inside an accordion container, it will only
	 * notify user from events that have the trigger element also inside the accordion
	 * container.
	 *
	 * At the final, if this method find and select more than one notification element,
	 * we get the closest comparing to the element that triggered the event.
	 * If it don't find any visible element, it bubbles up the event
	 * so the site handler can show general notifications.
	 *
	 * @param {$.pkp.classes.Handler} handler The widget handler that is handling the
	 * notify user event.
	 * @param {HTMLElement} triggerElement The element that triggered the notify
	 * user event.
	 */
	$.pkp.classes.notification.NotificationHelper.redirectNotifyUserEvent =
			function(handler, triggerElement) {

		// Get the selector for a notification element.
		$notificationSelector = '.pkp_notification';

		// Get the html element of the handler.
		$handledElement = handler.getHtmlElement();

		// Find all notification elements inside the handled element.
		$pageNotificationElements = $($notificationSelector, $handledElement);

		// Create a variable to store all possible notification widgets that can notify this event.
		possibleNotificationWidgets = new Array();

		var i, length;
		for (i = 0, length = $pageNotificationElements.length; i < length; i++) {

			// Get one page notification element.
			$element = $($pageNotificationElements[i]);

			// If it is inside a hidden parent, get next element.
			if ($element.parents(':hidden').length > 0) {
				continue;
			}

			// Find its parent widget.
			// FIXME If we use a class to identify pkp widgets, we can avoid
			// this code duplication from the get handler method in Handler class,
			// unnecessary access to the element data and unnecessary loop.
			$elementParents = $element.parents();
			var j, parentsLength, $elementParentWidget;
			for (j = 0, parentsLength = $elementParents.length; j < parentsLength; j++) {
				handler = $($elementParents[j]).data('pkp.handler');
				if ((handler instanceof $.pkp.classes.Handler)) {
					$elementParentWidget = $($elementParents[j]);
					break;
				}
			}

			// If the element that triggered the event is inside of this widget or is the widget...
			if ($elementParentWidget.has(triggerElement).length || $elementParentWidget[0] == triggerElement) {

				// If it is inside an accordion container, and this accordion container
				// doesn't also contain the element that triggered the event, get other element.
				if ($element.parents('.ui-accordion:first').length > 0) {
					$accordionContainer = $element.parents('.ui-accordion:first');

					if (!$accordionContainer.has(triggerElement)) {
						continue;
					}
				}

				// This notification element is able to notify this event.
				possibleNotificationWidgets.push($element);
			}
		}

		// Check if we found a notification element.
		if (possibleNotificationWidgets.length) {
			// Use the last element of the array, its the closest notification
			// widget related to the element that triggered the action.
			elementKey = possibleNotificationWidgets.length - 1;

			// Show in place notification to user.
			possibleNotificationWidgets[elementKey].triggerHandler('notifyUser');
		} else {
			// Bubble up the notify user event so the site can handle the
			// general notification.
			handler.getHtmlElement().parent().trigger('notifyUser');
		}
	};


	/** @param {jQuery} $ jQuery closure. */
})(jQuery);
