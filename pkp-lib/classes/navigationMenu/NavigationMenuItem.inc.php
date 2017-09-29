<?php

/**
 * @file classes/navigationMenu/NavigationMenuItem.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NavigationMenuItem
 * @ingroup navigationMenu
 * @see NavigationMenuItemDAO
 *
 * @brief Basic class describing a NavigationMenuItem.
 */

/** ID codes and paths for all default navigationMenuItems */
define('NMI_ID_CURRENT',	0x00000001);
define('NMI_ID_ARCHIVES',	0x00000002);
define('NMI_ID_ABOUT',	0x00000003);
define('NMI_ID_ABOUT_CONTEXT',	0x00000004);
define('NMI_ID_SUBMISSIONS',	0x00000005);
define('NMI_ID_EDITORIAL_TEAM',	0x00000006);
define('NMI_ID_CONTACT',	0x00000007);
define('NMI_ID_LOGOUT',	0x00000008);
define('NMI_ID_ANNOUNCEMENTS',	0x00000009);

class NavigationMenuItem extends DataObject {
	/** @var $navigationMenuItems array The navigationMenuItems underneath this navigationMenuItem */
	var $navigationMenuItems = array();

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Get/set methods
	//

	/**
	 * Set path for this navigation menu item.
	 * @param $path string
	 */
	function setPath($path) {
		$this->setData('path', $path);
	}

	/**
	 * Get path for this navigation menu item.
	 * @return string
	 */
	function getPath() {
		return $this->getData('path');
	}

	/**
	 * Set page handler name for this navigation menu item.
	 * @param $page string
	 */
	function setPage($page) {
		$this->setData('page', $page);
	}

	/**
	 * Get page handler name for this navigation menu item.
	 * @return string
	 */
	function getPage() {
		return $this->getData('page');
	}

	/**
	 * Set page's op handler name for this navigation menu item.
	 * @param $op string
	 */
	function setOp($op) {
		$this->setData('op', $op);
	}

	/**
	 * Get page's op handler name for this navigation menu item.
	 * @return string
	 */
	function getOp() {
		return $this->getData('op');
	}

	/**
	 * Get is_default for this navigation menu item.
	 * @return int
	 */
	function getDefault() {
		return $this->getData('is_default');
	}

	/**
	 * Set is_default for this navigation menu item.
	 * @param $default int
	 */
	function setDefault($default) {
		$this->setData('is_default', $default);
	}

	/**
	 * Get defaultId of this NavigationMenuItem
	 * @return int
	 */
	function getDefaultId() {
		return $this->getData('default_id');
	}

	/**
	 * Set defaultId of this NavigationMenuItem
	 * @param $defaultId int
	 */
	function setDefaultId($defaultId) {
		$this->setData('default_id', $defaultId);
	}

	/**
	 * Get contextId for this navigation menu item.
	 * @return int
	 */
	function getContextId() {
		return $this->getData('context_id');
	}

	/**
	 * Set context_id for this navigation menu item.
	 * @param $contextId int
	 */
	function setContextId($contextId) {
		$this->setData('context_id', $contextId);
	}

	/**
	 * Get the title of the navigation Menu.
	 * @return string
	 */
	function getLocalizedTitle() {
		return $this->getLocalizedData('title');
	}

	/**
	 * Get the title of the navigation menu item.
	 * @param $locale string
	 * @return string
	 */
	function getTitle($locale) {
		return $this->getData('title', $locale);
	}

	/**
	 * Set the title of the navigation menu item.
	 * @param $title string
	 * @param $locale string
	 */
	function setTitle($title, $locale) {
		$this->setData('title', $title, $locale);
	}

	/**
	 * Get the content of the navigation Menu.
	 * @return string
	 */
	function getLocalizedContent() {
		return $this->getLocalizedData('content');
	}

	/**
	 * Get the content of the navigation menu item.
	 * @param $locale string
	 * @return string
	 */
	function getContent($locale) {
		return $this->getData('content', $locale);
	}

	/**
	 * Set the content of the navigation menu item.
	 * @param $content string
	 * @param $locale string
	 */
	function setContent($content, $locale) {
		$this->setData('content', $content, $locale);
	}

	/**
	 * Get seq for this navigation menu item.
	 * @return int
	 */
	function getSequence() {
		return $this->getData('seq');
	}

	/**
	 * Set seq for this navigation menu item.
	 * @param $seq int
	 */
	function setSequence($seq) {
		$this->setData('seq', $seq);
	}
}

?>
