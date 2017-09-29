<?php

/**
 * @file controllers/grid/navigationMenus/NavigationMenusGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NavigationMenusGridHandler
 * @ingroup controllers_grid_navigationMenus
 *
 * @brief Handle navigationMenus grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');
import('lib.pkp.controllers.grid.navigationMenus.form.NavigationMenuItemsForm');
import('lib.pkp.controllers.grid.navigationMenus.form.NavigationMenuItemAssignmentsForm');

class NavigationMenuItemsGridHandler extends GridHandler {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			ROLE_ID_MANAGER,
			array(
				'fetchGrid', 'fetchRow',
				'addNavigationMenuItem', 'editNavigationMenuItem',
				'updateNavigationMenuItem',
				'deleteNavigationMenuItem', 'saveSequence',
				'editNavigationMenuItemAssignment', 'updateNavigationMenuItemAssignment',
			)
		);
	}

	//
	// Overridden template methods
	//
	/**
	 * @copydoc GridHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
		$context = $request->getContext();

		$navigationMenuItemId = $request->getUserVar('navigationMenuItemId');
		if ($navigationMenuItemId) {
			$navigationMenuItemDao = DAORegistry::getDAO('NavigationMenuItemDAO');
			$navigationMenuItem = $navigationMenuItemDao->getById($navigationMenuItemId);
			if (!$navigationMenuItem ||  $navigationMenuItem->getContextId() != $context->getId()) {
				return false;
			}
		}
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		// Basic grid configuration
		$this->setTitle('manager.navigationMenuItems');

		// Set the no items row text
		$this->setEmptyRowText('grid.navigationMenus.navigationMenuItems.noneExist');

		$context = $request->getContext();

		// Columns
		import('lib.pkp.controllers.grid.navigationMenus.NavigationMenuItemsGridCellProvider');
		$navigationMenuItemsCellProvider = new NavigationMenuItemsGridCellProvider();
		$this->addColumn(
			new GridColumn('title',
				'common.title',
				null,
				null,
				$navigationMenuItemsCellProvider
			)
		);

		$this->addColumn(
			new GridColumn('path',
				'grid.navigationMenu.navigationMenuItemPath',
				null,
				null,
				$navigationMenuItemsCellProvider
			)
		);

		$this->addColumn(
			new GridColumn('default',
				'common.default',
				null,
				null,
				$navigationMenuItemsCellProvider
			)
		);

		// Load language components
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER);

		// Add grid action.
		$router = $request->getRouter();

		import('lib.pkp.classes.linkAction.request.AjaxModal');


		$actionArgs = array(

		);

		$this->addAction(
			new LinkAction(
				'addNavigationMenuItem',
				new AjaxModal(
					$router->url($request, null, null, 'addNavigationMenuItem', null, $actionArgs),
					__('grid.action.addNavigationMenuItem'),
					'modal_add_item',
					true
				),
				__('grid.action.addNavigationMenuItem'),
				'add_item'
			)
		);
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		$context = $request->getContext();
		$contextId = $context->getId();

		$navigationMenuItemDao = DAORegistry::getDAO('NavigationMenuItemDAO');
		return $navigationMenuItemDao->getByContextId($contextId);
	}

	/**
	 * @copydoc GridHandler::getRowInstance()
	 */
	protected function getRowInstance() {
		import('lib.pkp.controllers.grid.navigationMenus.NavigationMenuItemsGridRow');
		return new NavigationMenuItemsGridRow();

	}

	//
	// Public grid actions.
	//
	/**
	 * Load and fetch the navigation menu items form in read-only mode.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function updateNavigationMenuItem($args, $request) {
		$navigationMenuItemId = (int)$request->getUserVar('navigationMenuItemId');
		$navigationMenuId = (int)$request->getUserVar('navigationMenuId');
		$navigationMenuIdParent = (int)$request->getUserVar('navigationMenuIdParent');
		$context = $request->getContext();
		$contextId = $context->getId();

		import('lib.pkp.controllers.grid.navigationMenus.form.NavigationMenuItemsForm');
		$navigationMenuItemForm = new NavigationMenuItemsForm($contextId, $navigationMenuItemId, $navigationMenuIdParent);

		$navigationMenuItemForm->readInputData();

		if ($navigationMenuItemForm->validate()) {
			$navigationMenuItemForm->execute($request);

			if ($navigationMenuItemId) {
				// Successful edit of an existing $navigationMenuItem.
				$notificationLocaleKey = 'notification.editedNavigationMenuItem';
			} else {
				// Successful added a new $navigationMenuItemForm.
				$notificationLocaleKey = 'notification.addedNavigationMenuItem';
			}

			// Record the notification to user.
			$notificationManager = new NotificationManager();
			$user = $request->getUser();
			$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __($notificationLocaleKey)));

			// Prepare the grid row data.
			return DAO::getDataChangedEvent($navigationMenuItemId);
		} else {
			return new JSONMessage(false);
		}
	}

	/**
	 * Display form to edit a navigation menu item object.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editNavigationMenuItem($args, $request) {
		$navigationMenuItemId = (int) $request->getUserVar('navigationMenuItemId');
		$navigationMenuIdParent = (int) $request->getUserVar('navigationMenuIdParent');
		$context = $request->getContext();
		$contextId = $context->getId();

		$navigationMenuItemForm = new NavigationMenuItemsForm($contextId, $navigationMenuItemId, $navigationMenuIdParent);
		$navigationMenuItemForm->initData($args, $request);

		return new JSONMessage(true, $navigationMenuItemForm->fetch($request));
	}

	/**
	 * Load and fetch the navigation menu item form in read-only mode.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function addNavigationMenuItem($args, $request) {
		$navigationMenuItemId = (int)$request->getUserVar('navigationMenuItemId');
		$navigationMenuIdParent = (int)$request->getUserVar('navigationMenuIdParent');
		$context = $request->getContext();
		$contextId = $context->getId();

		import('lib.pkp.controllers.grid.navigationMenus.form.NavigationMenuItemsForm');
		$navigationMenuItemForm = new NavigationMenuItemsForm($contextId, $navigationMenuItemId, $navigationMenuIdParent);

		$navigationMenuItemForm->initData($args, $request);

		return new JSONMessage(true, $navigationMenuItemForm->fetch($request));
	}

	/**
	 * Delete a navigation Menu item.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function deleteNavigationMenuItem($args, $request) {
		$navigationMenuItemId = (int) $request->getUserVar('navigationMenuItemId');
		//$navigationMenuIdParent = (int) $request->getUserVar('navigationMenuIdParent');
		$context = $request->getContext();

		$navigationMenuItemDao = DAORegistry::getDAO('NavigationMenuItemDAO');
		$navigationMenuItem = $navigationMenuItemDao->getById($navigationMenuItemId, $context->getId());
		if ($navigationMenuItem && $request->checkCSRF()) {
			$navigationMenuItemDao->deleteObject($navigationMenuItem);

			// Create notification.
			$notificationManager = new NotificationManager();
			$user = $request->getUser();
			$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.removedNavigationMenuItem')));

			return DAO::getDataChangedEvent($navigationMenuItemId);
		}

		return new JSONMessage(false);
	}

	// NavigationMenuItemAssignments
	/**
	 * update NavigationMenuItemAssignment mode.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function updateNavigationMenuItemAssignment($args, $request) {
		$navigationMenuItemAssignmentId = (int)$request->getUserVar('navigationMenuItemId');
		$context = $request->getContext();
		$contextId = $context->getId();

		import('lib.pkp.controllers.grid.navigationMenus.form.NavigationMenuItemAssignmentsForm');
		$navigationMenuItemAssignmentsForm = new NavigationMenuItemAssignmentsForm($contextId, $navigationMenuItemAssignmentId);

		$navigationMenuItemAssignmentsForm->readInputData();

		if ($navigationMenuItemAssignmentsForm->validate()) {
			$navigationMenuItemAssignmentsForm->execute($request);

			if ($navigationMenuItemAssignmentId) {
				// Successful edit of an existing $navigationMenuItemAssignment.
				$notificationLocaleKey = 'notification.editedNavigationMenuItemAssignment';
			}

			// Record the notification to user.
			$notificationManager = new NotificationManager();
			$user = $request->getUser();
			$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __($notificationLocaleKey)));

			// Prepare the grid row data.
			return DAO::getDataChangedEvent($navigationMenuItemAssignmentId);
		} else {
			return new JSONMessage(false);
		}
	}

	/**
	 * Display form to edit a navigation menu item assignment object.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editNavigationMenuItemAssignment($args, $request) {
		$navigationMenuItemAssignmentId = (int) $request->getUserVar('navigationMenuItemAssignmentId');
		$context = $request->getContext();
		$contextId = $context->getId();

		$navigationMenuItemAssignmentForm = new NavigationMenuItemAssignmentsForm($contextId, $navigationMenuItemAssignmentId);
		$navigationMenuItemAssignmentForm->initData($args, $request);

		return new JSONMessage(true, $navigationMenuItemAssignmentForm->fetch($request));
	}
}

?>
