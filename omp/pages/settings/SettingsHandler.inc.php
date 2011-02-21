<?php

/**
 * @file pages/settings/SettingsHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SettingsHandler
 * @ingroup pages_settings
 *
 * @brief Handle requests for settings pages.
 */

// Import the base Handler.
import('classes.handler.Handler');

class SettingsHandler extends Handler {
	/**
	 * Constructor.
	 */
	function SettingsHandler() {
		parent::Handler();
		$this->addRoleAssignment(
			ROLE_ID_PRESS_MANAGER,
			array(
				'index',
				'access'
			)
		);
	}


	//
	// Overridden methods from Handler
	//
	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize(&$request, $args = null) {
		parent::initialize($request, $args);

		// Load grid-specific translations
		Locale::requireComponents(array(LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_OMP_MANAGER));
	}

	/**
	 * @see PKPHandler::authorize()
	 * @param $request PKPRequest
	 * @param $args array
	 * @param $roleAssignments array
	 */
	function authorize(&$request, $args, $roleAssignments) {
		import('classes.security.authorization.OmpPressAccessPolicy');
		$this->addPolicy(new OmpPressAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}


	//
	// Public handler methods
	//
	/**
	 * Display settings index page.
	 * @param $request PKPRequest
	 * @param $args array
	 */
	function index(&$request, &$args) {
		$templateMgr =& TemplateManager::getManager();
		$this->setupTemplate(true);
		$templateMgr->display('settings/index.tpl');
	}

	/**
	 * Display Access and Security page.
	 * @param $request PKPRequest
	 * @param $args array
	 */
	function access(&$request, &$args) {
		$templateMgr =& TemplateManager::getManager();
		$this->setupTemplate(true);
		$templateMgr->display('settings/access.tpl');
	}
}