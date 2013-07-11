<?php

/**
 * @file classes/article/ArticleGalley.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalley
 * @ingroup article
 * @see ArticleGalleyDAO
 *
 * @brief A galley is a final presentation version of the full-text of an article.
 */

import('lib.pkp.classes.submission.Representation');

class ArticleGalley extends Representation {

	/**
	 * Constructor.
	 */
	function ArticleGalley() {
		parent::Representation();
	}

	/**
	 * Check if galley is an HTML galley.
	 * @return boolean
	 */
	function isHTMLGalley() {
		if ($this->getGalleyType() == 'htmlarticlegalleyplugin')
			return true;
		else
			return false;
	}

	/**
	 * Check if galley is a PDF galley.
	 * @return boolean
	 */
	function isPdfGalley() {
		if ($this->getGalleyType() == 'pdfarticlegalleyplugin')
			return true;
		else
			return false;
	}

	/**
	 * Check if the specified file is a dependent file.
	 * @param $fileId int
	 * @return boolean
	 */
	function isDependentFile($fileId) {
		return false;
	}

	//
	// Get/set methods
	//

	/**
	 * Get views count.
	 * @return int
	 */
	function getViews() {
		$application = PKPApplication::getApplication();
		return $application->getPrimaryMetricByAssoc(ASSOC_TYPE_GALLEY, $this->getId());
	}

	/**
	 * Get the localized value of the galley label.
	 * @return $string
	 */
	function getGalleyLabel() {
		$label = $this->getLabel();
		if ($this->getLocale() != AppLocale::getLocale()) {
			$locales = AppLocale::getAllLocales();
			$label .= ' (' . $locales[$this->getLocale()] . ')';
		}
		return $label;
	}

	/**
	 * Get label/title.
	 * @return string
	 */
	function getLabel() {
		return $this->getData('label');
	}

	/**
	 * Set label/title.
	 * @param $label string
	 */
	function setLabel($label) {
		return $this->setData('label', $label);
	}

	/**
	 * Get locale.
	 * @return string
	 */
	function getLocale() {
		return $this->getData('locale');
	}

	/**
	 * Set locale.
	 * @param $locale string
	 */
	function setLocale($locale) {
		return $this->setData('locale', $locale);
	}

	/**
	 * Get sequence order of supplementary file.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}

	/**
	 * Set sequence order of supplementary file.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}

	/**
	 * Return the "best" article ID -- If a public article ID is set,
	 * use it; otherwise use the internal article Id. (Checks the journal
	 * settings to ensure that the public ID feature is enabled.)
	 * @param $journal Object the journal this galley is in
	 * @return string
	 */
	function getBestGalleyId(&$journal) {
		if ($journal->getSetting('enablePublicGalleyId')) {
			$publicGalleyId = $this->getStoredPubId('publisher-id');
			if (!empty($publicGalleyId)) return $publicGalleyId;
		}
		return $this->getId();
	}

	/**
	 * Set remote URL of the galley.
	 * @param $remoteURL string
	 */
	function setRemoteURL($remoteURL) {
		return $this->setData('remoteURL', $remoteURL);
	}

	/**
	 * Get remote URL of the galley.
	 * @return string
	 */
	function getRemoteURL() {
		return $this->getData('remoteURL');
	}

	/**
	 * Determines if a galley is available or not.
	 * @return boolean
	 */
	function getIsAvailable() {
		return $this->getData('isAvailable') ? true : false;
	}

	/**
	 * Sets whether a galley is available or not.
	 * @param boolean $isAvailable
	 */
	function setIsAvailable($isAvailable) {
		return $this->setData('isAvailable', $isAvailable);
	}

	/**
	 * Set the type of this galley, which maps to an articleGalley plugin.
	 * @param string $galleyType
	 */
	function setGalleyType($galleyType) {
		return $this->setData('galleyType', $galleyType);
	}

	/**
	 * Returns the type of this galley.
	 * @return string
	 */
	function getGalleyType() {
		return $this->getData('galleyType');
	}

	/**
	 * Convenience method for fetching the latest revisions of the files for this galley.
	 * @param $fileType string optional limit to specific file type.
	 * @return array SubmissionFile
	 */
	function getLatestGalleyFiles($fileType = null) {
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$submissionFiles = $submissionFileDao->getLatestRevisionsByAssocId(
				ASSOC_TYPE_GALLEY, $this->getId(),
				$this->getSubmissionId(), SUBMISSION_FILE_PROOF
			);

		if (!$fileType) {
			return $submissionFiles;
		}
		else {
			$filteredFiles = array();
			foreach ($submissionFiles as $id => $file) {
				if ($file->getFileType() == $fileType) {
					$filteredFiles[$id] = $file;
				}
			}

			return $filteredFiles;
		}
	}

	/**
	 * Attempt to retrieve the first file assigned to this galley.
	 * @param $fileType string optional limit to specific file type.
	 * @param $allFiles whether or not to include non-viewable files.
	 * @return SubmissionFile or null
	 */
	function getFirstGalleyFile($fileType = null, $allFiles = false) {
		$submissionFiles = $this->getLatestGalleyFiles(	$fileType);
		if (is_array($submissionFiles) && sizeof($submissionFiles) > 0) {
			if ($allFiles) {
				return array_shift($submissionFiles);
			} else { // return first viewable file.
				foreach ($submissionFiles as $id => $file) {
					if ($file->getViewable()) return $file;
				}
			}
		}

		return null;
	}
}

?>
