<?php

/**
 * @defgroup publicationFormat
 */

/**
 * @file classes/publicationFormat/PublicationFormat.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublicationFormat
 * @ingroup publicationFormat
 * @see PublicationFormatDAO
 *
 * @brief Basic class describing a publication format.
 */

class PublicationFormat extends DataObject {
	/**
	 * Constructor
	 */
	function PublicationFormat() {
		parent::DataObject();
	}

	/**
	 * get press id
	 * @return int
	 */
	function getPressId() {
		return $this->getData('pressId');
	}

	/**
	 * set press id
	 * @param $pressId int
	 */
	function setPressId($pressId) {
		return $this->setData('pressId', $pressId);
	}

	/**
	 * get enabled flag
	 * @return int
	 */
	function getEnabled() {
		return $this->getData('enabled');
	}

	/**
	 * set enabled flag
	 * @param $enabled int
	 */
	function setEnabled($enabled) {
		return $this->setData('enabled', $enabled);
	}

	/**
	 * Set the name of the publication format
	 * @param $name string
	 * @param $locale string
	 */
	function setName($name, $locale) {
		$this->setData('name', $name, $locale);
	}

	/**
	 * Get the name of the publication format
	 * @param $locale string
	 * @return string
	 */
	function getName($locale) {
		return $this->getData('name', $locale);
	}

	/**
	 * Get the localized name of the publication format
	 * @return string
	 */
	function getLocalizedName() {
		return $this->getLocalizedData('name');
	}

	/**
	 * Set the designation of the publication format
	 * @param $abbrev string
	 * @param $locale string
	 */
	function setDesignation($abbrev, $locale) {
		$this->setData('designation', $abbrev, $locale);
	}

	/**
	 * Get the designation of the publication format
	 * @param $locale string
	 * @return string
	 */
	function getDesignation($locale) {
		return $this->getData('designation', $locale);
	}

	/**
	 * Get the localized designation of the publication format
	 * @return string
	 */
	function getLocalizedDesignation() {
		return $this->getLocalizedData('designation');
	}

	/**
	 * Sets the entry key for the publication format
	 * @param string $entryKey
	 */
	function setEntryKey($entryKey) {
		$this->setData('entryKey', $entryKey);
	}

	/**
	 * Get the entry key for this publication format
	 * @return string
	 */
	function getEntryKey() {
		return $this->getData('entryKey');
	}
}

?>
