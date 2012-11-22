<?php

/**
 * @file classes/controllers/grid/languages/LanguageGridHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LanguageGridHandler
 * @ingroup classes_controllers_grid_languages
 *
 * @brief Handle language grid requests.
 */

import('lib.pkp.classes.controllers.grid.languages.PKPLanguageGridHandler');

class LanguageGridHandler extends PKPLanguageGridHandler {
	/**
	 * Constructor
	 */
	function LanguageGridHandler() {
		parent::PKPLanguageGridHandler();
		$this->addRoleAssignment(array(
			ROLE_ID_PRESS_MANAGER),
			array('saveLanguageSetting', 'setContextPrimaryLocale'));
	}


	//
	// Implement template methods from PKPHandler.
	//
	/**
	 * @see GridHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('classes.security.authorization.OmpPressAccessPolicy');
		$this->addPolicy(new OmpPressAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize(&$request) {
		parent::initialize($request);

		// Load user-related translations.
		AppLocale::requireComponents(
			LOCALE_COMPONENT_OMP_MANAGER
		);
	}


	//
	// Public handler methods.
	//
	/**
	 * Save changes to the context object.
	 * @param $context Context
	 */
	function updateContext($context) {
		$pressDao =& DAORegistry::getDAO('PressDAO');
		$pressDao->updateObject($context);
	}
}

?>
