<?php

/**
 * @file controllers/listbuilder/settings/SetupListbuilderHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SetupListbuilderHandler
 * @ingroup listbuilder
 *
 * @brief Base class for setup listbuilders
 */

import('lib.pkp.classes.controllers.listbuilder.ListbuilderHandler');

class SetupListbuilderHandler extends ListbuilderHandler {
	/**
	 * Constructor
	 */
	function SetupListbuilderHandler() {
		parent::ListbuilderHandler();
		$this->addRoleAssignment(
				ROLE_ID_PRESS_MANAGER,
				array('fetch', 'fetchRow', 'save'));
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

	/**
	 * @see GridHandler::getIsSubcomponent
	 */
	function getIsSubcomponent() {
		return true;
	}
}

?>
