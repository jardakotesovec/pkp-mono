/**
 * @file js/controllers/form/ThemeOptionsHandler.js
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief This handles theme options. When a new theme is selected, it removes
 *  the theme options because different themes may have different options. In
 *  the future it will automatically reload the new themes' options.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQueryObject} $container the wrapped HTML form element.
	 * @param {Object} options form options.
	 */
	$.pkp.controllers.form.ThemeOptionsHandler = function($container, options) {
		this.parent($container, options);
		var $activeThemeOptions;

		$activeThemeOptions = $container.find('#activeThemeOptions');
		if ($activeThemeOptions.length) {
			$container.find('#themePluginPath').change(function(e) {
				$activeThemeOptions.empty();
			});
			$activeThemeOptions.find('input[type="color"]').each(function() {
				var $colourInput = $(this);
				console.log($colourInput);
				$colourInput.spectrum({
					preferredFormat: 'hex',
					showInitial: true,
					showButtons: false,
					change: function(colour)  {
						$colourInput.val(
								/** @type {{toHexString: function()}} */
								colour.toHexString()
						);
					}
				});
			});
		}
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.form.ThemeOptionsHandler,
			$.pkp.classes.Handler);


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
