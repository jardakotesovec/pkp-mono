<?php

/**
 * @file classes/services/NavigationMenuService.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NavigationMenuService
 * @ingroup services
 *
 * @brief Helper class that encapsulates NavigationMenu business logic
 */

namespace APP\Services;

/** types for all ops default navigationMenuItems */
define('NMI_TYPE_ARCHIVES',	'NMI_TYPE_ARCHIVES');

class NavigationMenuService extends \PKP\Services\PKPNavigationMenuService {

	/**
	 * Initialize hooks for extending PKPSubmissionService
	 */
	public function __construct() {

		\HookRegistry::register('NavigationMenus::itemTypes', array($this, 'getMenuItemTypesCallback'));
		\HookRegistry::register('NavigationMenus::displaySettings', array($this, 'getDisplayStatusCallback'));
	}

	/**
	 * Return all default navigationMenuItemTypes.
	 * @param $hookName string
	 * @param $args array of arguments passed
	 */
	public function getMenuItemTypesCallback($hookName, $args) {
		$types =& $args[0];

		\AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_PKP_USER, LOCALE_COMPONENT_APP_EDITOR);

		$opsTypes = array(
			NMI_TYPE_ARCHIVES => array(
				'title' => __('navigation.archives'),
				'description' => __('manager.navigationMenus.archives.description'),
			),
		);

		$types = array_merge($types, $opsTypes);
	}

	/**
	 * Callback for display menu item functionallity
	 * @param $hookName string
	 * @param $args array of arguments passed
	 */
	function getDisplayStatusCallback($hookName, $args) {
		$navigationMenuItem =& $args[0];

		$request = \Application::get()->getRequest();
		$dispatcher = $request->getDispatcher();
		$templateMgr = \TemplateManager::getManager(\Application::get()->getRequest());

		$isUserLoggedIn = \Validation::isLoggedIn();
		$isUserLoggedInAs = \Validation::isLoggedInAs();
		$context = $request->getContext();

		$this->transformNavMenuItemTitle($templateMgr, $navigationMenuItem);

		$menuItemType = $navigationMenuItem->getType();

		// Conditionally hide some items
		switch ($menuItemType) {
			case NMI_TYPE_ARCHIVES:
				$navigationMenuItem->setIsDisplayed($context && $context->getData('publishingMode') != PUBLISHING_MODE_NONE);
				break;
		}

		if ($navigationMenuItem->getIsDisplayed()) {

			// Set the URL
			switch ($menuItemType) {
				case NMI_TYPE_ARCHIVES:
					$navigationMenuItem->setUrl($dispatcher->url(
						$request,
						\PKPApplication::ROUTE_PAGE,
						null,
						'preprints',
						null,
						null
					));
					break;
			}
		}
	}
}
