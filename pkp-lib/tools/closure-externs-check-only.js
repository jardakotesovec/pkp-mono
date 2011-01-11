/**
 * closure-externs-check-only.js
 *
 * Copyright (c) 2010-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Import symbols into the closure compiler that are not defined
 * within the files compiled during the strict check phase of the build
 * script. (We only include classes for strict checking, not legacy
 * function.)
 *
 * @externs
 */

// FIXME: Replace the reference to the ajaxAction() function
// with an object/event oriented approach, see #6339.
/**
 * @param {string} actType can be either 'get' or 'post', 'post' expects a form as
 *  a child element of 'actOnId' if no form has been explicitly given.
 * @param {string} actOnId the ID of an element to be changed.
 * @param {string} callingElement selector of the element that triggers the ajax call
 * @param {string} url the url to be called, defaults to the form action in case of
 *  action type 'post'.
 * @param {Object=} data (post action type only) the data to be posted, defaults to
 *  the form data.
 * @param {string=} eventName the name of the event that triggers the action, default 'click'.
 * @param {string=} form the selector of a form element.
 */
function ajaxAction(actType, actOnId, callingElement, url, data, eventName, form) {};
