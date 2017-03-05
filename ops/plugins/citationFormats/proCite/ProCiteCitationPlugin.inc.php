<?php

/**
 * @file plugins/citationFormats/proCite/ProCiteCitationPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ProCiteCitationPlugin
 * @ingroup plugins_citationFormats_proCite
 *
 * @brief ProCite citation format plugin
 */

import('classes.plugins.DownloadableCitationPlugin');

class ProCiteCitationPlugin extends DownloadableCitationPlugin {
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'ProCiteCitationPlugin';
	}

	function getDisplayName() {
		return __('plugins.citationFormats.proCite.displayName');
	}

	function getCitationFormatName() {
		return __('plugins.citationFormats.proCite.citationFormatName');
	}

	function getDescription() {
		return __('plugins.citationFormats.proCite.description');
	}

	/**
	 * Set the headers for a downloadable citation.
	 *
	 * @param $article object
	 * @param $issue object
	 * @param $journal object
	 */
	function setHeaders($article, $issue, $journal) {
		header('Content-Disposition: attachment; filename="' . $article->getId() . '-proCite.ris"');
		header('Content-Type: application/x-Research-Info-Systems');
	}
}

?>
