/**
 * Global mixins
 *
 * Global mixins affect every single Vue instance created, so they should be
 * be used as little as possible. In most cases, prefer creating a mixin that
 * can be loaded into a component on-demand.
 *
 * @see https://vuejs.org/v2/guide/mixins.html
 */
export default {
	methods: {
		/**
		 * Compile a string translation
		 *
		 * This method can be used in templates:
		 *
		 * {{ __('key') }}
		 *
		 * And parameters can be passed in:
		 *
		 * {{ __('key', { count: this.item_count }) }}
		 *
		 * @param {String} key The translation string to use
		 * @param {Object} params (Optional) Variables to compile with the translation
		 * @return {String}
		 */
		__: function(key, params) {
			if (typeof pkp.localeKeys[key] === 'undefined') {
				if (process.env.NODE_ENV === 'development') {
					// eslint-disable-next-line
					console.error('Missing locale key: ', key);
				}
				return '';
			}

			if (typeof params === 'undefined') {
				return pkp.localeKeys[key];
			}

			return this.replaceLocaleParams(pkp.localeKeys[key], params);
		},

		/**
		 * Display an error message from an ajax request
		 *
		 * This callback expects to be attached to the `error` param of the
		 * jQuery $.ajax method. It can also be fired directly, but should have
		 * a jQuery response object with the following:
		 * {
		 *   responseJSON: {
		 *     error: 'localised.string.key',
		 *     errorMessage: 'The string rendered into localised form for display',
		 *   }
		 * }
		 *
		 * @param {Object} r The response from jQuery's ajax request
		 */
		ajaxErrorCallback(r) {
			// If the user browses away from the page before a response has been
			// received by the ajax request, the error handler will be invoked.
			// Do nothing so the user does not see an obsolete error message.
			if ('status' in r && r.status == 0) {
				return;
			}

			let msg;
			if ('responseJSON' in r && 'errorMessage' in r.responseJSON) {
				msg = r.responseJSON.errorMessage;
			} else {
				msg = this.__('common.unknownError');
			}

			this.openDialog({
				modalName: 'ajaxError',
				confirmLabel: this.__('common.ok'),
				title: this.__('common.error'),
				message: msg,
				callback: () => this.$modal.hide('ajaxError')
			});
		},

		/**
		 * Get the locale-specific string from a locale object.
		 *
		 * It will search for the current locale value. If there's no value for the
		 * current locale, it will revert to the primary locale. If there's still
		 * no match, it will return the first available value or an empty string.
		 *
		 * This method mimics the DataObject::getLocalizedData() method from the
		 * PHP backend.
		 *
		 * This can be used in templates like this:
		 *
		 * {{ localize(fullTitle) }}
		 *
		 * If you want to force a specific locale and not print a fallback:
		 *
		 * {{ localize(fullTitle, 'fr_CA') }}
		 *
		 * @param {Object} multilingualData Key/value hash storing one string per locale
		 * @param {String} requestedLocale Optional. Request a specific locale
		 * @return {String}
		 */
		localize: function(multilingualData, requestedLocale) {
			if (!multilingualData) {
				return '';
			} else if (requestedLocale !== undefined) {
				return multilingualData.hasOwnProperty(requestedLocale)
					? multilingualData[requestedLocale]
					: '';
			} else if (
				multilingualData.hasOwnProperty($.pkp.app.currentLocale) &&
				multilingualData[$.pkp.app.currentLocale]
			) {
				return multilingualData[$.pkp.app.currentLocale];
			} else if (
				multilingualData.hasOwnProperty($.pkp.app.primaryLocale) &&
				multilingualData[$.pkp.app.primaryLocale]
			) {
				return multilingualData[$.pkp.app.primaryLocale];
			}

			for (var key in multilingualData) {
				if (multilingualData[key]) {
					return multilingualData[key];
				}
			}

			return '';
		},

		/**
		 * Get the locale-specific string for current locale
		 * from a string representing a Date.
		 *
		 * example : {{ localizeDate(submission.dateLastActivity) }}
		 *
		 * If you want to force a specific locale:
		 *
		 * {{ localizeDate(date, 'fr_CA') }}
		 *
		 * @param string representing a Date
		 * @param string requestedLocale Optional. Request a specific locale
		 * @return string
		 */
		localizeDate(str, requestedLocale) {
			if (!str) {
				return '';
			}
			str = new Date(str);
			let dateLocale =
				requestedLocale !== undefined
					? requestedLocale.replace('_', '-')
					: $.pkp.app.currentLocale.replace('_', '-');
			return str.toLocaleDateString(dateLocale, {
				weekday: 'long',
				year: 'numeric',
				month: 'long',
				day: 'numeric'
			});
		},

		/**
		 * Replace params in a localized string
		 *
		 * Example:
		 *
		 * var str = this.replaceLocaleParams(
		 * 	'Version {$num}',
		 * 	{num: 1}
		 * );
		 * console.log(str); // result: Version 1
		 *
		 * @param {String} str String to replace params in
		 * @param {Object} params Key/value hash of params to replace
		 * @return {String}
		 */
		replaceLocaleParams(str, params) {
			for (var param in params) {
				let value = params[param];
				// If a locale object is passed, take the value from the current locale
				if (value === Object(value)) {
					value = this.localize(value);
				}
				str = str.replace('{$' + param + '}', value);
			}
			return str;
		},

		/**
		 * Set focus to a focusable element within a dom node
		 *
		 * @param {ParentNode} el
		 * @param {Boolean} toLast Move the focus to the last focusable element
		 */
		setFocusIn(el, toLast) {
			let focusable = el.querySelectorAll(
				'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
			);
			if (focusable.length) {
				if (toLast) {
					focusable[focusable.length - 1].focus();
				} else {
					focusable[0].focus();
				}
			}
		}
	}
};
