<?php

/**
 * @file controllers/grid/users/stageParticipant/StageParticipantGridHandler.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StageParticipantGridHandler
 * @ingroup controllers_grid_users_stageParticipant
 *
 * @brief Handle stageParticipant grid requests.
 */

// import grid base classes
import('lib.pkp.classes.controllers.grid.CategoryGridHandler');

// import stageParticipant grid specific classes
import('lib.pkp.controllers.grid.users.stageParticipant.StageParticipantGridRow');
import('lib.pkp.controllers.grid.users.stageParticipant.StageParticipantGridCategoryRow');
import('classes.log.SubmissionEventLogEntry'); // App-specific.

class PKPStageParticipantGridHandler extends CategoryGridHandler {
	/**
	 * Constructor
	 */
	function PKPStageParticipantGridHandler() {
		parent::CategoryGridHandler();
		//Assistants get read-only access
		$this->addRoleAssignment(
			array(ROLE_ID_ASSISTANT),
			$peOps = array('fetchGrid', 'fetchCategory', 'fetchRow')
		);
		// Managers and Editors additionally get administrative access
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR),
			array_merge($peOps, array('addParticipant', 'deleteParticipant', 'saveParticipant', 'fetchUserList'))
		);
	}


	//
	// Getters/Setters
	//
	/**
	 * Get the authorized submission.
	 * @return Submission
	 */
	function &getSubmission() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
	}

	/**
	 * Get the authorized workflow stage.
	 * @return integer
	 */
	function getStageId() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
	}

	//
	// Overridden methods from PKPHandler
	//
	/**
	 * @see PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		$stageId = (int) $request->getUserVar('stageId');
		import('classes.security.authorization.WorkflowStageAccessPolicy');
		$this->addPolicy(new WorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', $stageId));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Determine whether the current user has admin priveleges for this
	 * grid.
	 * @return boolean
	 */
	function _canAdminister() {
		// If the current role set includes Manager or Editor, grant.
		return (boolean) array_intersect(
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR),
			$this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES)
		);
	}


	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);

		// Load submission-specific translations
		AppLocale::requireComponents(
			LOCALE_COMPONENT_APP_EDITOR,
			LOCALE_COMPONENT_PKP_USER,
			LOCALE_COMPONENT_APP_DEFAULT,
			LOCALE_COMPONENT_PKP_DEFAULT,
			LOCALE_COMPONENT_PKP_SUBMISSION
		);

		// Columns
		import('lib.pkp.controllers.grid.users.stageParticipant.StageParticipantGridCellProvider');
		$cellProvider = new StageParticipantGridCellProvider();
		$this->addColumn(new GridColumn(
			'participants',
			null,
			null,
			'controllers/grid/gridCell.tpl',
			$cellProvider
		));

		// The "Add stage participant" grid action is available to
		// Editors and Managers only
		if ($this->_canAdminister()) {
			$router = $request->getRouter();
			$this->addAction(
				new LinkAction(
					'requestAccount',
					new AjaxModal(
						$router->url($request, null, null, 'addParticipant', null, $this->getRequestArgs()),
						__('editor.submission.addStageParticipant'),
						'modal_add_user'
					),
					__('common.add'),
					'add_user'
				)
			);
		}

		$this->setEmptyCategoryRowText('editor.submission.noneAssigned');
	}


	//
	// Overridden methods from [Category]GridHandler
	//
	/**
	 * @see CategoryGridHandler::getCategoryData()
	 */
	function getCategoryData(&$userGroup) {
		// Retrieve useful objects.
		$submission = $this->getSubmission();
		$stageId = $this->getStageId();

		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO'); /* @var $stageAssignmentDao StageAssignmentDAO */
		$stageAssignments = $stageAssignmentDao->getBySubmissionAndStageId(
			$submission->getId(),
			$stageId,
			$userGroup->getId()
		);

		return $stageAssignments->toAssociativeArray();
	}

	/**
	 * @see GridHandler::isSubComponent()
	 */
	function getIsSubcomponent() {
		return true;
	}

	/**
	 * @see GridHandler::getRowInstance()
	 */
	function getRowInstance() {
		$submission = $this->getSubmission();
		return new StageParticipantGridRow($submission, $this->getStageId(), $this->_canAdminister());
	}

	/**
	 * @see CategoryGridHandler::getCategoryRowInstance()
	 */
	function getCategoryRowInstance() {
		$submission = $this->getSubmission();
		return new StageParticipantGridCategoryRow($submission, $this->getStageId());
	}

	/**
	 * @see CategoryGridHandler::getCategoryRowIdParameterName()
	 */
	function getCategoryRowIdParameterName() {
		return 'userGroupId';
	}

	/**
	 * @see GridHandler::getRequestArgs()
	 */
	function getRequestArgs() {
		$submission = $this->getSubmission();
		return array_merge(
			parent::getRequestArgs(),
			array('submissionId' => $submission->getId(),
			'stageId' => $this->getStageId())
		);
	}

	/**
	 * @see GridHandler::loadData()
	 */
	function loadData($request, $filter) {
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */
		$context = $request->getContext();
		$userGroups = $userGroupDao->getUserGroupsByStage($context->getId(), $this->getStageId(), false, true);

		return $userGroups;
	}


	//
	// Public actions
	//
	/**
	 * Add a participant to the stages
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function addParticipant($args, $request) {
		$submission = $this->getSubmission();
		$stageId = $this->getStageId();
		$userGroups = $this->getGridDataElements($request);

		import('lib.pkp.controllers.grid.users.stageParticipant.form.AddParticipantForm');
		$form = new AddParticipantForm($submission, $stageId, $userGroups);
		$form->initData();

		$json = new JSONMessage(true, $form->fetch($request));
		return $json->getString();
	}


	/**
	 * Update the row for the current userGroup's stage participant list.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function saveParticipant($args, $request) {
		$submission = $this->getSubmission();
		$stageId = $this->getStageId();
		$userGroups =& $this->getGridDataElements($request);

		import('lib.pkp.controllers.grid.users.stageParticipant.form.AddParticipantForm');
		$form = new AddParticipantForm($submission, $stageId, $userGroups);
		$form->readInputData();
		if ($form->validate()) {
			list($userGroupId, $userId, $stageAssignmentId) = $form->execute();

			$notificationMgr = new NotificationManager();

			// Check user group role id.
			$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
			$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');

			$userGroup = $userGroupDao->getById($userGroupId);
			if ($userGroup->getRoleId() == ROLE_ID_MANAGER) {
				$notificationMgr->updateNotification(
					$request,
					array(NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_SUBMISSION,
						NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_INTERNAL_REVIEW,
						NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EXTERNAL_REVIEW,
						NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EDITING,
						NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_PRODUCTION),
					null,
					ASSOC_TYPE_SUBMISSION,
					$submission->getId()
				);
				$stages = Application::getApplicationStages();
				foreach ($stages as $workingStageId) {
					// remove the 'editor required' task if we now have an editor assigned
					if ($stageAssignmentDao->editorAssignedToStage($submission->getId(), $stageId)) {
						$notificationDao = DAORegistry::getDAO('NotificationDAO');
						$notificationDao->deleteByAssoc(ASSOC_TYPE_SUBMISSION, $submission->getId(), null, NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_REQUIRED);
					}
				}
			}

			// Create trivial notification.
			$user = $request->getUser();
			$notificationMgr->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.addedStageParticipant')));

			// Log addition.
			$userDao = DAORegistry::getDAO('UserDAO');
			$assignedUser =& $userDao->getById($userId);
			import('lib.pkp.classes.log.PkpLog');
			PkpLog::logEvent($request, $submission, SUBMISSION_LOG_ADD_PARTICIPANT, 'submission.event.participantAdded', array('name' => $assignedUser->getFullName(), 'username' => $assignedUser->getUsername(), 'userGroupName' => $userGroup->getLocalizedName()));

			return DAO::getDataChangedEvent($userGroupId);
		} else {
			$json = new JSONMessage(true, $form->fetch($request));
			return $json->getString();
		}
	}

	/**
	 * Delete the participant from the user groups
	 * @param $args
	 * @param $request
	 * @return void
	 */
	function deleteParticipant($args, $request) {
		$submission = $this->getSubmission();
		$stageId = $this->getStageId();
		$assignmentId = (int) $request->getUserVar('assignmentId');

		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO'); /* @var $stageAssignmentDao StageAssignmentDAO */
		$stageAssignment = $stageAssignmentDao->getById($assignmentId);
		if (!$stageAssignment || $stageAssignment->getSubmissionId() != $submission->getId()) {
			fatalError('Invalid Assignment');
		}

		// Delete all user submission file signoffs not completed, if any.
		$userId = $stageAssignment->getUserId();
		$signoffDao = DAORegistry::getDAO('SignoffDAO');
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');

		$signoffsFactory =& $signoffDao->getByUserId($userId);
		while($signoff =& $signoffsFactory->next()) {
			if (($signoff->getSymbolic() != 'SIGNOFF_COPYEDITING' &&
				$signoff->getSymbolic() != 'SIGNOFF_PROOFING') ||
				$signoff->getAssocType() != ASSOC_TYPE_SUBMISSION_FILE ||
				$signoff->getDateCompleted()) continue;
			$submissionFileId = $signoff->getAssocId();
			$submissionFile = $submissionFileDao->getLatestRevision($submissionFileId, null, $stageAssignment->getSubmissionId());
			if (is_a($submissionFile, 'SubmissionFile')) {
				$signoffDao->deleteObject($signoff);
			}
		}

		// Delete the assignment
		$stageAssignmentDao->deleteObject($stageAssignment);

		// FIXME: perhaps we can just insert the notification on page load
		// instead of having it there all the time?
		$stages = Application::getApplicationStages();
		foreach ($stages as $workingStageId) {
			// remove user's assignment from this user group from all the stages
			// (no need to check if user group is assigned, since nothing will be deleted if there isn't)
			$stageAssignmentDao->deleteByAll($submission->getId(), $workingStageId, $stageAssignment->getUserGroupId(), $stageAssignment->getUserId());
		}

		$notificationMgr = new NotificationManager();
		$notificationMgr->updateNotification(
			$request,
			array(NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_SUBMISSION,
				NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_INTERNAL_REVIEW,
				NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EXTERNAL_REVIEW,
				NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EDITING,
				NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_PRODUCTION),
			null,
			ASSOC_TYPE_SUBMISSION,
			$submission->getId()
		);

		// Log removal.
		$userDao = DAORegistry::getDAO('UserDAO');
		$assignedUser = $userDao->getById($userId);
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$userGroup = $userGroupDao->getById($stageAssignment->getUserGroupId());
		import('lib.pkp.classes.log.PkpLog');
		PkpLog::logEvent($request, $submission, SUBMISSION_LOG_REMOVE_PARTICIPANT, 'submission.event.participantRemoved', array('name' => $assignedUser->getFullName(), 'username' => $assignedUser->getUsername(), 'userGroupName' => $userGroup->getLocalizedName()));

		// Redraw the category
		return DAO::getDataChangedEvent($stageAssignment->getUserGroupId());
	}

	/**
	 * Get the list of users for the specified user group
	 * @param $args array
	 * @param $request Request
	 * @return JSON string
	 */
	function fetchUserList($args, $request) {
		$submission = $this->getSubmission();
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);

		$userGroupId = (int) $request->getUserVar('userGroupId');

		$userStageAssignmentDao = DAORegistry::getDAO('UserStageAssignmentDAO'); /* @var $userStageAssignmentDao UserStageAssignmentDAO */
		$users = $userStageAssignmentDao->getUsersNotAssignedToStageInUserGroup($submission->getId(), $stageId, $userGroupId);

		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$userGroup =& $userGroupDao->getById($userGroupId);
		$roleId = $userGroup->getRoleId();

		$subEditorFilterId = $this->_getIdForSubEditorFilter($submission);
		$contextId = $submission->getContextId();

		$filterSubEditors = false;
		if ($roleId == ROLE_ID_SUB_EDITOR && $subEditorFilterId) {
			$seriesEditorsDao = Application::getSubEditorDAO();
			// Flag to filter sub editors only.
			$filterSubEditors = true;
		}

		$userList = array();
		while($user =& $users->next()) {
			if ($filterSubEditors && !$seriesEditorsDao->editorExists($contextId, $subEditorFilterId, $user->getId())) {
				unset($user);
				continue;
			}
			$userList[$user->getId()] = $user->getFullName();
			unset($user);
		}

		if (count($userList) == 0) {
			$userList[0] = __('common.noMatches');
		}

		$json = new JSONMessage(true, $userList);
		return $json->getString();
	}

	function _getIdForSubEditorFilter($submission) {
		assert(false); // implemented by sub classes.
	}
}

?>
