<?php

/**
 * @file classes/monograph/PublishedMonograph.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublishedMonograph
 * @ingroup monograph
 * @see PublishedMonographDAO
 *
 * @brief Published monograph class.
 */


import('classes.monograph.Monograph');

// Access status
define('ARTICLE_ACCESS_ISSUE_DEFAULT', 0);
define('ARTICLE_ACCESS_OPEN', 1);

class PublishedMonograph extends Monograph {

	/**
	 * Constructor.
	 */
	function PublishedMonograph() {
		parent::Monograph();
	}

	/**
	 * Get ID of published monograph.
	 * @return int
	 */
	function getPubId() {
		return $this->getData('pubId');
	}

	/**
	 * Set ID of published monograph.
	 * @param $pubId int
	 */
	function setPubId($pubId) {
		return $this->setData('pubId', $pubId);
	}

	/**
	 * Get date published.
	 * @return date
	 */
	function getDatePublished() {
		return $this->getData('datePublished');
	}

	/**
	 * Set date published.
	 * @param $datePublished date
	 */

	function setDatePublished($datePublished) {
		return $this->SetData('datePublished', $datePublished);
	}

	/**
	 * Get sequence of monograph in table of contents.
	 * @return float
	 */
	function getSeq() {
		return $this->getData('seq');
	}

	/**
	 * Set sequence of monograph in table of contents.
	 * @param $sequence float
	 */
	function setSeq($seq) {
		return $this->setData('seq', $seq);
	}

	/**
	 * Get views of the published monograph.
	 * @return int
	 */
	function getViews() {
		return $this->getData('views');
	}

	/**
	 * Set views of the published monograph.
	 * @param $views int
	 */
	function setViews($views) {
		return $this->setData('views', $views);
	}
}

?>
