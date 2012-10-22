/**
 * closure-externs.js
 *
 * Copyright (c) 2010-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Import symbols into the closure compiler that are not defined
 * within the compiled files.
 *
 * See https://code.google.com/p/closure-compiler/source/browse/trunk/contrib/externs
 * for pre-extracted extern files, e.g. for jQuery.
 *
 * @externs
 */

/**
 * @param {Object} arg1
 */
jQueryObject.prototype.autocomplete = function(arg1) {};

/**
 * @param {string=} param1
 * @param {string|number=} param2
 * @param {string=} param3
 */
jQueryObject.prototype.button = function(param1, param2, param3) {};


/**
 * @param {Object=} options
 */
jQueryObject.prototype.validate = function(options) {};

/**
 * @param {Object=} options
 */
jQueryObject.prototype.jLabel = function(options) {};

/**
 * @param {Object=} options
 */
jQueryObject.prototype.selectBox = function(options) {};

jQueryObject.prototype.superfish = function() {};

/**
 * @param {Object|string=} param1
 */
jQueryObject.prototype.plupload = function(param1) {};

jQueryObject.prototype.equalizeElementHeights = function() {};

/**
 * @param {Object=} options
 */
jQueryObject.prototype.slider = function(options) {};

/**
 * @param {string|Object=} param1
 * @param {string|number|Object=} param2
 * @param {string|number|Object=} param3
 */
jQueryObject.prototype.tabs = function(param1, param2, param3) {};

/**
 * @param {string|Object} param1
 * @param {string|Object=} param2
 */
jQueryObject.prototype.datepicker = function(param1, param2) {};

/**
 * @param {string|Object} param1
 * @param {string|Object|boolean|number=} param2
 * @param {string|boolean=} param3
 */
jQueryObject.prototype.accordion = function(param1, param2, param3) {};

/**
 * Handler plug-in.
 * @param {string} handlerName The handler to be instantiated
 *  and attached to the target HTML element(s).
 * @param {Object=} options Parameters to be passed on
 *  to the handler.
 * @return {jQueryObject} Selected HTML elements for chaining.
 */
jQueryObject.prototype.pkpHandler = function(handlerName, options) {};

/**
 * Re-implementation of jQuery's html() method
 * with a remote source.
 * @param {string} url the AJAX endpoint from which to
 *  retrieve the HTML to be inserted.
 * @param {Object=} callback function to be called on ajax success.
 * @return {jQueryObject} Selected HTML elements for chaining.
 */
jQueryObject.prototype.pkpAjaxHtml = function(url, callback) {};

/**
 * @param {string|Object=} param1
 * @param {string=} param2
 * @param {string|Object=} param3
 */
jQueryObject.prototype.dialog = function(param1, param2, param3) {};

/**
 * @param {string|Object=} param1
 * @param {string|Object|number=} param2
 */
jQueryObject.prototype.roundabout = function(param1, param2) {};

/**
 * @constructor
 * @param {Object=} options
 * @param {jQueryObject=} form
 */
jQuery.validator = function(options, form) {};

jQuery.validator.prototype.checkForm = function() {};

jQuery.validator.prototype.defaultShowErrors = function() {};

jQuery.validator.prototype.settings = {};

/**
 * @constructor
 * @param {Object=} options
 */
jQuery.pnotify = function(options) {};

/**
 * @param {Object=} userDefinedSettings
 * @return {jQueryObject}
 */
jQueryObject.prototype.imgPreview = function(userDefinedSettings) {};

/**
 * @constructor
 * @private
 */
function tinyMCEObject() {};

tinyMCEObject.prototype.triggerSave = function() {};

/**
 * @param {string} c
 * @param {boolean} u
 * @param {string} v
 */
tinyMCEObject.prototype.execCommand = function(c, u, v) {};

/**
 * @type {string} c
 */
tinyMCEObject.prototype.editorId = '';

tinyMCEObject.prototype.getWin = function() {};

tinyMCEObject.prototype.getBody = function() {};

tinyMCEObject.prototype.getContainer = function() {};

tinyMCEObject.prototype.onSetContent = function() {};

/**
 * @param {Object} param1
 */
tinyMCEObject.prototype.onSetContent.add = function(param1) {};

/**
 * @param {Object} param1
 */
tinyMCEObject.prototype.onSetContent.remove = function(param1) {};

/**
 * @type {tinyMCEObject}
 */
var tinyMCE;


$.pkp.locale = {
	search_noKeywordError: '',
	form_dataHasChanged: ''
};

$.pkp.cons = {
	WORKFLOW_STAGE_ID_SUBMISSION: 0,
	WORKFLOW_STAGE_ID_INTERNAL_REVIEW: 0,
	WORKFLOW_STAGE_ID_EXTERNAL_REVIEW: 0,
	WORKFLOW_STAGE_ID_EDITING: 0,
	WORKFLOW_STAGE_ID_PRODUCTION: 0,
	REALLY_BIG_NUMBER: 0,
	ORDER_CATEGORY_GRID_CATEGORIES_ONLY: 0,
	ORDER_CATEGORY_GRID_CATEGORIES_AND_ROWS: 0,
	LISTBUILDER_SOURCE_TYPE_SELECT: 0,
	LISTBUILDER_OPTGROUP_LABEL: 0
}
