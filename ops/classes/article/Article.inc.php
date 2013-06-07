<?php

/**
 * @defgroup article
 */

/**
 * @file classes/article/Article.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Article
 * @ingroup article
 * @see ArticleDAO
 *
 * @brief Article class.
 */

// Author display in ToC
define ('AUTHOR_TOC_DEFAULT', 0);
define ('AUTHOR_TOC_HIDE', 1);
define ('AUTHOR_TOC_SHOW', 2);

// Article RT comments
define ('COMMENTS_SECTION_DEFAULT', 0);
define ('COMMENTS_DISABLE', 1);
define ('COMMENTS_ENABLE', 2);

import('lib.pkp.classes.submission.Submission');

class Article extends Submission {
	/**
	 * Constructor.
	 */
	function Article() {
		parent::Submission();
	}


	//
	// Get/set methods
	//

	/**
	 * Return the "best" article ID -- If a public article ID is set,
	 * use it; otherwise use the internal article Id. (Checks the journal
	 * settings to ensure that the public ID feature is enabled.)
	 * @param $journal Object the journal this article is in
	 * @return string
	 */
	function getBestArticleId($journal = null) {
		// Retrieve the journal, if necessary.
		if (!isset($journal)) {
			$journalDao = DAORegistry::getDAO('JournalDAO');
			$journal = $journalDao->getById($this->getJournalId());
		}

		if ($journal->getSetting('enablePublicArticleId')) {
			$publicArticleId = $this->getPubId('publisher-id');
			if (!empty($publicArticleId)) return $publicArticleId;
		}
		return $this->getId();
	}

	/**
	 * Get ID of journal.
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journalId');
	}

	/**
	 * Set ID of journal.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
	}

	/**
	 * Get the context ID.
	 * @return int
	 */
	function getContextId() {
		return $this->getJournalId();
	}

	/**
	 * Set the context ID.
	 * @param $contextId int
	 */
	function setContextId($contextId) {
		return $this->setJournalId($contextId);
	}

	/**
	 * Get ID of article's section.
	 * @return int
	 */
	function getSectionId() {
		return $this->getData('sectionId');
	}

	/**
	 * Set ID of article's section.
	 * @param $sectionId int
	 */
	function setSectionId($sectionId) {
		return $this->setData('sectionId', $sectionId);
	}

	/**
	 * Get title of article's section.
	 * @return string
	 */
	function getSectionTitle() {
		return $this->getData('sectionTitle');
	}

	/**
	 * Set title of article's section.
	 * @param $sectionTitle string
	 */
	function setSectionTitle($sectionTitle) {
		return $this->setData('sectionTitle', $sectionTitle);
	}

	/**
	 * Get section abbreviation.
	 * @return string
	 */
	function getSectionAbbrev() {
		return $this->getData('sectionAbbrev');
	}

	/**
	 * Set section abbreviation.
	 * @param $sectionAbbrev string
	 */
	function setSectionAbbrev($sectionAbbrev) {
		return $this->setData('sectionAbbrev', $sectionAbbrev);
	}

	/**
	 * Get current review round.
	 * @return int
	 */
	function getCurrentRound() {
		return $this->getData('currentRound');
	}

	/**
	 * Set current review round.
	 * @param $currentRound int
	 */
	function setCurrentRound($currentRound) {
		return $this->setData('currentRound', $currentRound);
	}

	/**
	 * get expedited
	 * @return boolean
	 */
	function getFastTracked() {
		return $this->getData('fastTracked');
	}

	/**
	 * set fastTracked
	 * @param $fastTracked boolean
	 */
	function setFastTracked($fastTracked) {
		return $this->setData('fastTracked',$fastTracked);
	}

	/**
	 * Return locale string corresponding to RT comments status.
	 * @return string
	 */
	function getCommentsStatusString() {
		switch ($this->getCommentsStatus()) {
			case COMMENTS_DISABLE:
				return 'article.comments.disable';
			case COMMENTS_ENABLE:
				return 'article.comments.enable';
			default:
				return 'article.comments.sectionDefault';
		}
	}

	/**
	 * Return boolean indicating if article RT comments should be enabled.
	 * Checks both the section and article comments status. Article status
	 * overrides section status.
	 * @return int
	 */
	function getEnableComments() {
		switch ($this->getCommentsStatus()) {
			case COMMENTS_DISABLE:
				return false;
			case COMMENTS_ENABLE:
				return true;
			case COMMENTS_SECTION_DEFAULT:
				$sectionDao = DAORegistry::getDAO('SectionDAO');
				$section = $sectionDao->getById($this->getSectionId(), $this->getJournalId(), true);
				if ($section->getDisableComments()) {
					return false;
				} else {
					return true;
				}
		}
	}

	/**
	 * Get an associative array matching RT comments status codes with locale strings.
	 * @return array comments status => localeString
	 */
	function &getCommentsStatusOptions() {
		static $commentsStatusOptions = array(
			COMMENTS_SECTION_DEFAULT => 'article.comments.sectionDefault',
			COMMENTS_DISABLE => 'article.comments.disable',
			COMMENTS_ENABLE => 'article.comments.enable'
		);
		return $commentsStatusOptions;
	}

	/**
	 * Get the signoff for this article
	 * @param $signoffType string
	 * @return Signoff
	 */
	function getSignoff($signoffType) {
		$signoffDao = DAORegistry::getDAO('SignoffDAO');
		return $signoffDao->build($signoffType, ASSOC_TYPE_ARTICLE, $this->getId());
	}

	/**
	 * Get the file for this article at a given signoff stage
	 * @param $signoffType string
	 * @param $idOnly boolean Return only file ID
	 * @return ArticleFile
	 */
	function getFileBySignoffType($signoffType, $idOnly = false) {
		$articleFileDao = DAORegistry::getDAO('ArticleFileDAO');
		$signoffDao = DAORegistry::getDAO('SignoffDAO');

		$signoff = $signoffDao->build($signoffType, ASSOC_TYPE_ARTICLE, $this->getId());
		if (!$signoff) {
			$returner = false;
			return $returner;
		}

		if ($idOnly) {
			$returner = $signoff->getFileId();
			return $returner;
		}

		return $articleFileDao->getArticleFile($signoff->getFileId(), $signoff->getFileRevision());
	}

	/**
	 * Get the user associated with a given signoff and this article
	 * @param $signoffType string
	 * @return User
	 */
	function getUserBySignoffType($signoffType) {
		$signoffDao = DAORegistry::getDAO('SignoffDAO');
		$userDao = DAORegistry::getDAO('UserDAO');

		$signoff = $signoffDao->build($signoffType, ASSOC_TYPE_ARTICLE, $this->getId());
		if (!$signoff) {
			$returner = false;
			return $returner;
		}

		return $userDao->getById($signoff->getUserId());
	}

	/**
	 * Get the user id associated with a given signoff and this article
	 * @param $signoffType string
	 * @return int
	 */
	function getUserIdBySignoffType($signoffType) {
		$signoffDao = DAORegistry::getDAO('SignoffDAO');

		$signoff = $signoffDao->build($signoffType, ASSOC_TYPE_ARTICLE, $this->getId());
		if (!$signoff) return false;

		return $signoff->getUserId();
	}
}

?>
