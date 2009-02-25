<?php

/**
 * @defgroup group
 */
 
/**
 * @file classes/group/Group.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Group
 * @ingroup group
 * @see GroupDAO
 *
 * @brief Describes user groups.
 */

// $Id$


define('GROUP_CONTEXT_EDITORIAL_TEAM',	0x000001);
define('GROUP_CONTEXT_PEOPLE',		0x000002);

class Group extends DataObject {
	/**
	 * Get localized title of journal group.
	 */
	function getGroupTitle() {
		return $this->getLocalizedData('title');
	}

	//
	// Get/set methods
	//

	/**
	 * Get title of group (primary locale)
	 * @param $locale string
	 * @return string
	 */
	function getTitle($locale) {
		return $this->getData('title', $locale);
	}

	/**
	 * Set title of group
	 * @param $title string
	 * @param $locale string
	 */
	function setTitle($title, $locale) {
		return $this->setData('title', $title, $locale);
	}

	/**
	 * Get context of group
	 * @return int
	 */
	function getContext() {
		return $this->getData('context');
	}

	/**
	 * Set context of group
	 * @param $context int
	 */
	function setContext($context) {
		return $this->setData('context',$context);
	}

	/**
	 * Get flag indicating whether or not the group is displayed in "About"
	 * @return boolean
	 */
	function getAboutDisplayed() {
		return $this->getData('aboutDisplayed');
	}

	/**
	 * Set flag indicating whether or not the group is displayed in "About"
	 * @param $aboutDisplayed boolean
	 */
	function setAboutDisplayed($aboutDisplayed) {
		return $this->setData('aboutDisplayed',$aboutDisplayed);
	}

	/**
	 * Get ID of group.
	 * @return int
	 */
	function getGroupId() {
		return $this->getData('groupId');
	}

	/**
	 * Set ID of group.
	 * @param $groupId int
	 */
	function setGroupId($groupId) {
		return $this->setData('groupId', $groupId);
	}

	/**
	 * Get assoc ID for this group.
	 * @return int
	 */
	function getAssocId() {
		return $this->getData('assocId');
	}

	/**
	 * Set assoc ID for this group.
	 * @param $journalId int
	 */
	function setAssocId($assocId) {
		return $this->setData('assocId', $assocId);
	}

	/**
	 * Get assoc type for this group.
	 * @return int
	 */
	function getAssocType() {
		return $this->getData('assocType');
	}

	/**
	 * Set assoc type for this group.
	 * @param $assocType int
	 */
	function setAssocType($assocType) {
		return $this->setData('assocType', $assocType);
	}

	/**
	 * Get sequence of group.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}

	/**
	 * Set sequence of group.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}
}

?>
