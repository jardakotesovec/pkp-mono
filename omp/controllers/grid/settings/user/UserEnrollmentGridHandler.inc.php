<?php

/**
 * @file controllers/grid/settings/user/UserEnrollmentGridHandler.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserEnrollmentGridHandler
 * @ingroup controllers_grid_settings_user
 *
 * @brief Handle user enrollment grid requests.
 */


import('controllers.grid.settings.user.UserGridHandler');

class UserEnrollmentGridHandler extends UserGridHandler {
	/**
	 * Constructor
	 */
	function UserEnrollmentGridHandler() {
		parent::UserGridHandler();
		$this->addRoleAssignment(
				array(ROLE_ID_PRESS_MANAGER),
				array('enrollUser', 'enrollUserFinish'));
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @see PKPHandler::authorize()
	 */
	function authorize(&$request, $args, $roleAssignments) {
		import('classes.security.authorization.OmpPressAccessPolicy');
		$this->addPolicy(new OmpPressAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize(&$request) {
		parent::initialize($request);

		// Basic grid configuration
		$this->setTitle('grid.user.currentEnrollment');

		// Grid actions
		$router =& $request->getRouter();
		$press =& $request->getPress();

		// Enroll user
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$this->addAction(
			new LinkAction(
				'enrollUser',
				new AjaxModal(
					$router->url($request, null, null, 'enrollUser', null, null),
					__('grid.user.enroll'),
					'enrollUser',
					true
					),
				__('grid.user.enroll'),
				'enrollUser')
		);

		//
		// Grid Columns
		//

		// User roles
		import('controllers.grid.settings.user.UserEnrollmentGridCellProvider');
		$cellProvider = new UserEnrollmentGridCellProvider($press->getId());
		$this->addColumn(
			new GridColumn(
				'roles',
				'user.roles',
				null,
				'controllers/grid/settings/user/userGroupsList.tpl',
				$cellProvider
			)
		);
	}


	//
	// Implement template methods from GridHandler
	//
	/**
	 * @see GridHandler::getFilterForm()
	 */
	function getFilterForm() {
		return 'controllers/grid/settings/user/userEnrollmentGridFilter.tpl';
	}


	//
	// Public grid actions
	//
	/**
	 * Enroll a user
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function enrollUser($args, &$request) {
		// Identify the press
		$press =& $request->getPress();

		// Form handling
		import('controllers.grid.settings.user.form.UserEnrollmentForm');
		$userEnrollmentForm = new UserEnrollmentForm();
		$userEnrollmentForm->initData($args, $request);

		$json = new JSONMessage(true, $userEnrollmentForm->display($args, $request));
		return $json->getString();
	}

	/**
	 * Finish enrolling users
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function enrollUserFinish($args, &$request) {
		// Identify the user Id
		$userId = $request->getUserVar('userId');

		// If editing a user, save changes
		if ($userId) {
			$this->updateUser($args, $request);
		}

		$json = new JSONMessage(true);
		return $json->getString();
	}

	/**
	 * Remove all user group assignments for a press for a given user
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function removeUser($args, &$request) {
		// Identify the press
		$press =& $request->getPress();
		$pressId = $press->getId();

		// Identify the user Id
		$userId = $request->getUserVar('rowId');

		if ($userId !== null && !Validation::canAdminister($press->getId(), $userId)) {
			// We don't have administrative rights over this user.
			$json = new JSONMessage(false, Locale::translate('grid.user.cannotAdminister'));
		} else {
			// Remove user from all user group assignments for this press
			$userGroupDao =& DAORegistry::getDAO('UserGroupDAO');

			// Check if this user has any user group assignments for this press
			if (!$userGroupDao->userInAnyGroup($userId, $pressId)) {
				$json = new JSONMessage(false, Locale::translate('grid.user.userNoRoles'));
			} else {
				$userGroupDao->deleteAssignmentsByContextId($pressId, $userId);

				// Successfully removed user's user group assignments
				// Refresh the grid row data to indicate this
				$userDao =& DAORegistry::getDAO('UserDAO');
				$user =& $userDao->getUser($userId);

				$row =& $this->getRowInstance();
				$row->setGridId($this->getId());
				$row->setId($user->getId());
				$row->setData($user);
				$row->initialize($request);

				$json = new JSONMessage(true, $this->_renderRowInternally($request, $row));
			}
		}
		return $json->getString();
	}
}

?>
