<?php

/**
 * @file classes/monograph/PublishedMonograph.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
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

	/**
	 * Get the audience of the published monograph.
	 * @return int
	 */
	function getAudience() {
		return $this->getData('audience');
	}

	/**
	 * Set the audience for the published monograph.
	 * @param $audience int (onix code)
	 */
	function setAudience($audience) {
		return $this->setData('audience', $audience);
	}

	/**
	 * Get the audienceRangeQualifier of the published monograph.
	 * @return int
	 */
	function getAudienceRangeQualifier() {
		return $this->getData('audienceRangeQualifier');
	}

	/**
	 * Set the audienceRangeQualifier for the published monograph.
	 * @param $audienceRangeQualifier int (onix code)
	 */
	function setAudienceRangeQualifier($audienceRangeQualifier) {
		return $this->setData('audienceRangeQualifier', $audienceRangeQualifier);
	}

	/**
	 * Get the audienceRangeFrom field for the published monograph.
	 * @return int
	 */
	function getAudienceRangeFrom() {
		return $this->getData('audienceRangeFrom');
	}

	/**
	 * Set the audienceRangeFrom field for the published monograph.
	 * @param $audienceRangeFrom int (onix code)
	 */
	function setAudienceRangeFrom($audienceRangeFrom) {
		return $this->setData('audienceRangeFrom', $audienceRangeFrom);
	}

	/**
	 * Get the audienceRangeTo field for the published monograph.
	 * @return int
	 */
	function getAudienceRangeTo() {
		return $this->getData('audienceRangeTo');
	}

	/**
	 * Set the audienceRangeTo field for the published monograph.
	 * @param $audienceRangeTo int (onix code)
	 */
	function setAudienceRangeTo($audienceRangeTo) {
		return $this->setData('audienceRangeTo', $audienceRangeTo);
	}

	/**
	 * Get the audienceRangeExact field of the published monograph.
	 * @return int
	 */
	function getAudienceRangeExact() {
		return $this->getData('audienceRangeExact');
	}

	/**
	 * Set the audienceRangeExact field for the published monograph.
	 * @param $audienceRangeExact int (onix code)
	 */
	function setAudienceRangeExact($audienceRangeExact) {
		return $this->setData('audienceRangeExact', $audienceRangeExact);
	}

	/**
	 * Retrieves the assigned publication formats for this mongraph
	 * @param $onlyApproved boolean whether to fetch only those that are in the catalog.
	 * @return array PublicationFormat
	 */
	function getPublicationFormats($onlyApproved = false) {
		$publicationFormatDao =& DAORegistry::getDAO('PublicationFormatDAO');
		if ($onlyApproved) {
			$formats =& $publicationFormatDao->getApprovedByMonographId($this->getId());
		} else {
			$formats =& $publicationFormatDao->getByMonographId($this->getId());
		}
		return $formats->toArray();
	}

	/**
	 * Return string of approved publication formats, separated by comma.
	 * @return string
	 */
	function getPublicationFormatString() {
		$separator = ', ';
		$formats = $this->getPublicationFormats(true);
		$str = '';

		foreach ($formats as $format) { /* @var $format PublicationFormat */
			if (!empty($str)) {
				$str .= $separator;
			}
			$str .= $format->getLocalizedName();
		}

		return $str;
	}

	/**
	 * Returns whether or not this published monograph has formats assigned to it
	 * @return boolean
	 */
	function hasPublicationFormats() {
		$formats =& $this->getPublicationFormats();
		if (sizeof($formats) > 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get the categories for this published monograph.
	 * @see PublishedMonographDAO::getCategories
	 * @return Iterator
	 */
	function getCategories() {
		$publishedMonographDao =& DAORegistry::getDAO('PublishedMonographDAO');
		return $publishedMonographDao->getCategories(
			$this->getId(),
			$this->getPressId()
		);
	}

	/**
	 * Get the cover image.
	 * @return array
	 */
	function getCoverImage() {
		return $this->getData('coverImage');
	}

	/**
	 * Set the cover image.
	 * @param $coverImage array
	 */
	function setCoverImage($coverImage) {
		return $this->setData('coverImage', $coverImage);
	}

	/**
	 * Get whether or not this monograph is available in the catalog.
	 * A monograph is available if it has at least one publication format that
	 * has been flagged as 'available' in the catalog and if it has metadata
	 * approved.
	 * @return boolean
	 */
	function isAvailable() {
		$publicationFormats =& $this->getPublicationFormats(true);
		if (sizeof($publicationFormats) > 0 && $this->isMetadataApproved()) {
			return true;
		}
		return false;
	}

	/**
	 * Get the Representative objects assigned as suppliers for this published monograph.
	 * @return Array Representative
	 */
	function getSuppliers() {
		$representativeDao =& DAORegistry::getDAO('RepresentativeDAO');
		$suppliers =& $representativeDao->getSuppliersByMonographId($this->getId());
		return $suppliers;
	}

	/**
	 * Get the Representative objects assigned as agents for this published monograph.
	 * @return Array Representative
	 */
	function getAgents() {
		$representativeDao =& DAORegistry::getDAO('RepresentativeDAO');
		$agents =& $representativeDao->getAgentsByMonographId($this->getId());
		return $agents;
	}
}

?>
