<?php

/**
 * @file plugins/articleGalleys/pdfArticleGalley/PdfArticleGalleyPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PdfArticleGalleyPlugin
 * @ingroup plugins_articleGalleys_pdfArticleGalley
 *
 * @brief Class for PdfArticleGalley plugin
 */

import('classes.plugins.ArticleGalleyPlugin');

class PdfArticleGalleyPlugin extends ArticleGalleyPlugin {
	/**
	 * Install default settings on journal creation.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.articleGalleys.pdfArticleGalley.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.articleGalleys.pdfArticleGalley.description');
	}

	/**
	 * @see ArticleGalleyPlugin::getArticleGalley
	 */
	function getArticleGalley(&$templateMgr, $request = null, $params) {
		$journal = $request->getJournal();
		if (!$journal) return '';

		$fileId = (isset($params['fileId']) && is_numeric($params['fileId'])) ? (int) $fileId : null;
		if (!$fileId) {
			// unfortunate, but occasionally browsers upload PDF files as application/octet-stream.
			// Even setting the file type in the display template will not cause a correct render in this case.
			// So, update the file type if this is the case.
			$galley = $templateMgr->get_template_vars('galley'); // set in ArticleHandler
			$file = $galley->getFirstGalleyFile('pdf');
			if (!preg_match('/\.pdf$/', $file->getFileType())) {
				$file->setFileType('application/pdf');
				$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
				$submissionFileDao->updateObject($file);
			}
		}
		$templateMgr->assign('pluginJSPath', $this->getJSPath($request));

		return parent::getArticleGalley($templateMgr, $request, $params);
	}

	/**
	 * returns the base path for JS included in this plugin.
	 * @param $request PKPRequest
	 * @return string
	 */
	function getJSPath($request) {
		return $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js';
	}
}

?>
