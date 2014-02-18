<?php

/**
 * @defgroup journal Journal
 * Extensions to the pkp-lib "context" concept to specialize it for use in OJS
 * in representing Journal objects and journal-specific concerns.
 */

/**
 * @file classes/journal/Journal.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Journal
 * @ingroup journal
 * @see JournalDAO
 *
 * @brief Describes basic journal properties.
 */


define('PUBLISHING_MODE_OPEN', 0);
define('PUBLISHING_MODE_SUBSCRIPTION', 1);
define('PUBLISHING_MODE_NONE', 2);

import('lib.pkp.classes.context.Context');

class Journal extends Context {
	/**
	 * Constructor.
	 */
	function Journal() {
		parent::Context();
	}

	/**
	 * Get "localized" journal page title (if applicable).
	 * param $home boolean get homepage title
	 * @return string
	 */
	function getLocalizedPageHeaderTitle($home = false) {
		$prefix = $home ? 'home' : 'page';
		$typeArray = $this->getSetting($prefix . 'HeaderTitleType');
		$imageArray = $this->getSetting($prefix . 'HeaderTitleImage');
		$titleArray = $this->getSetting($prefix . 'HeaderTitle');

		$title = null;

		foreach (array(AppLocale::getLocale(), AppLocale::getPrimaryLocale()) as $locale) {
			if (isset($typeArray[$locale]) && $typeArray[$locale]) {
				if (isset($imageArray[$locale])) $title = $imageArray[$locale];
			}
			if (empty($title) && isset($titleArray[$locale])) $title = $titleArray[$locale];
			if (!empty($title)) return $title;
		}
		return null;
	}

	/**
	 * Get "localized" journal page logo (if applicable).
	 * param $home boolean get homepage logo
	 * @return string
	 */
	function getLocalizedPageHeaderLogo($home = false) {
		$prefix = $home ? 'home' : 'page';
		$logoArray = $this->getSetting($prefix . 'HeaderLogoImage');
		foreach (array(AppLocale::getLocale(), AppLocale::getPrimaryLocale()) as $locale) {
			if (isset($logoArray[$locale])) return $logoArray[$locale];
		}
		return null;
	}

	/**
	 * Get localized favicon
	 * @return string
	 */
	function getLocalizedFavicon() {
		$faviconArray = $this->getSetting('journalFavicon');
		foreach (array(AppLocale::getLocale(), AppLocale::getPrimaryLocale()) as $locale) {
			if (isset($faviconArray[$locale])) return $faviconArray[$locale];
		}
		return null;
	}

	//
	// Get/set methods
	//

	/**
	 * Get the association type for this context.
	 * @return int
	 */
	function getAssocType() {
		return ASSOC_TYPE_JOURNAL;
	}

	/**
	 * Get the settings DAO for this context object.
	 * @return DAO
	 */
	static function getSettingsDAO() {
		return DAORegistry::getDAO('JournalSettingsDAO');
	}

	/**
	 * Get the DAO for this context object.
	 * @return DAO
	 */
	static function getDAO() {
		return DAORegistry::getDAO('JournalDAO');
	}


	//
	// Statistics API
	//
	/**
	 * Return all metric types supported by this journal.
	 *
	 * @return array An array of strings of supported metric type identifiers.
	 */
	function getMetricTypes($withDisplayNames = false) {
		// Retrieve report plugins enabled for this journal.
		$reportPlugins =& PluginRegistry::loadCategory('reports', true, $this->getId());
		if (!is_array($reportPlugins)) return array();

		// Run through all report plugins and retrieve all supported metrics.
		$metricTypes = array();
		foreach ($reportPlugins as $reportPlugin) {
			$pluginMetricTypes = $reportPlugin->getMetricTypes();
			if ($withDisplayNames) {
				foreach ($pluginMetricTypes as $metricType) {
					$metricTypes[$metricType] = $reportPlugin->getMetricDisplayType($metricType);
				}
			} else {
				$metricTypes = array_merge($metricTypes, $pluginMetricTypes);
			}
		}

		return $metricTypes;
	}

	/**
	 * Returns the currently configured default metric type for this journal.
	 * If no specific metric type has been set for this journal then the
	 * site-wide default metric type will be returned.
	 *
	 * @return null|string A metric type identifier or null if no default metric
	 *   type could be identified.
	 */
	function getDefaultMetricType() {
		$defaultMetricType = $this->getSetting('defaultMetricType');

		// Check whether the selected metric type is valid.
		$availableMetrics = $this->getMetricTypes();
		if (empty($defaultMetricType)) {
			if (count($availableMetrics) === 1) {
				// If there is only a single available metric then use it.
				$defaultMetricType = $availableMetrics[0];
			} else {
				// Use the site-wide default metric.
				$application = PKPApplication::getApplication();
				$defaultMetricType = $application->getDefaultMetricType();
			}
		} else {
			if (!in_array($defaultMetricType, $availableMetrics)) return null;
		}
		return $defaultMetricType;
	}

	/**
	 * Retrieve a statistics report pre-filtered on this journal.
	 *
	 * @see <http://pkp.sfu.ca/wiki/index.php/OJSdeStatisticsConcept#Input_and_Output_Formats_.28Aggregation.2C_Filters.2C_Metrics_Data.29>
	 * for a full specification of the input and output format of this method.
	 *
	 * @param $metricType null|integer|array metrics selection
	 * @param $columns integer|array column (aggregation level) selection
	 * @param $filters array report-level filter selection
	 * @param $orderBy array order criteria
	 * @param $range null|DBResultRange paging specification
	 *
	 * @return null|array The selected data as a simple tabular
	 *  result set or null if metrics are not supported by this journal.
	 */
	function getMetrics($metricType = null, $columns = array(), $filter = array(), $orderBy = array(), $range = null) {
		// Add a journal filter and run the report.
		$filter[STATISTICS_DIMENSION_CONTEXT_ID] = $this->getId();
		$application = PKPApplication::getApplication();
		return $application->getMetrics($metricType, $columns, $filter, $orderBy, $range);
	}
}

?>
