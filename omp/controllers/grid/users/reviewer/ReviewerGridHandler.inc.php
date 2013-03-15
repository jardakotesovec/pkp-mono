<?php

/**
 * @file controllers/grid/users/reviewer/ReviewerGridHandler.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerGridHandler
 * @ingroup controllers_grid_users_reviewer
 *
 * @brief Handle reviewer grid requests.
 */

// import grid base classes
import('lib.pkp.classes.controllers.grid.GridHandler');

// import reviewer grid specific classes
import('controllers.grid.users.reviewer.ReviewerGridCellProvider');
import('controllers.grid.users.reviewer.ReviewerGridRow');

// Reviewer selection types
define('REVIEWER_SELECT_SEARCH_BY_NAME',		0x00000001);
define('REVIEWER_SELECT_ADVANCED_SEARCH',		0x00000002);
define('REVIEWER_SELECT_CREATE',				0x00000003);
define('REVIEWER_SELECT_ENROLL_EXISTING',		0x00000004);

class ReviewerGridHandler extends GridHandler {

	/** @var Monograph */
	var $_monograph;

	/** @var integer */
	var $_stageId;


	/**
	 * Constructor
	 */
	function ReviewerGridHandler() {
		parent::GridHandler();

		$allOperations = array_merge($this->_getReviewAssignmentOps(), $this->_getReviewRoundOps());

		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER),
			$allOperations
		);

		// Remove operations related to creation and enrollment of users.
		$nonPressManagerOperations = array_flip($allOperations);
		unset($nonPressManagerOperations['createReviewer']);
		unset($nonPressManagerOperations['enrollReviewer']);
		$nonPressManagerOperations = array_flip($nonPressManagerOperations);

		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT),
			$nonPressManagerOperations
		);
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the authorized monograph.
	 * @return Monograph
	 */
	function &getMonograph() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);
	}

	/**
	 * Get the review stage id.
	 * @return integer
	 */
	function getStageId() {
		return $this->_stageId;
	}

	/**
	 * Get review round object.
	 * @return ReviewRound
	 */
	function &getReviewRound() {
		$reviewRound =& $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ROUND);
		if (is_a($reviewRound, 'ReviewRound')) {
			return $reviewRound;
		} else {
			$reviewAssignment =& $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);
			$reviewRoundId = $reviewAssignment->getReviewRoundId();
			$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
			$reviewRound = $reviewRoundDao->getReviewRoundById($reviewRoundId);
			return $reviewRound;
		}
	}


	//
	// Overridden methods from PKPHandler
	//
	/**
	 * @see PKPHandler::authorize()
	 * @param $request PKPRequest
	 * @param $args array
	 * @param $roleAssignments array
	 */
	function authorize(&$request, &$args, $roleAssignments) {
		$stageId = $request->getUserVar('stageId'); // This is being validated in OmpWorkflowStageAccessPolicy

		// Not all actions need a stageId. Some work off the reviewAssignment which has the type and round.
		$this->_stageId = (int)$stageId;

		// Get the stage access policy
		import('classes.security.authorization.OmpWorkflowStageAccessPolicy');
		$ompWorkflowStageAccessPolicy = new OmpWorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', $stageId);

		// Add policy to ensure there is a review round id.
		import('classes.security.authorization.internal.ReviewRoundRequiredPolicy');
		$ompWorkflowStageAccessPolicy->addPolicy(new ReviewRoundRequiredPolicy($request, $args, 'reviewRoundId', $this->_getReviewRoundOps()));

		// Add policy to ensure there is a review assignment for certain operations.
		import('classes.security.authorization.internal.ReviewAssignmentRequiredPolicy');
		$ompWorkflowStageAccessPolicy->addPolicy(new ReviewAssignmentRequiredPolicy($request, $args, 'reviewAssignmentId', $this->_getReviewAssignmentOps()));
		$this->addPolicy($ompWorkflowStageAccessPolicy);

		return parent::authorize($request, $args, $roleAssignments);
	}

	/*
	 * Configure the grid
	 * @param $request PKPRequest
	 */
	function initialize(&$request) {
		parent::initialize($request);

		// Load submission-specific translations
		AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_SUBMISSION,
			LOCALE_COMPONENT_PKP_MANAGER,
			LOCALE_COMPONENT_PKP_USER,
			LOCALE_COMPONENT_APP_EDITOR
		);

		$this->setTitle('user.role.reviewers');
		$this->setInstructions('editor.monograph.review.reviewersDescription');

		// Grid actions
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$router =& $request->getRouter();
		$actionArgs = array_merge($this->getRequestArgs(), array('selectionType' => REVIEWER_SELECT_SEARCH_BY_NAME));
		$this->addAction(
			new LinkAction(
				'addReviewer',
				new AjaxModal(
					$router->url($request, null, null, 'showReviewerForm', null, $actionArgs),
					__('editor.monograph.addReviewer'),
					'modal_add_user'
					),
				__('editor.monograph.addReviewer'),
				'add_user'
				)
			);

		// Columns
		$cellProvider = new ReviewerGridCellProvider();
		$this->addColumn(
			new GridColumn(
				'name',
				'user.name',
				null,
				'controllers/grid/gridCell.tpl',
				$cellProvider,
				array('width' => 60)
			)
		);

		// Add a column for the status of the review.
		$this->addColumn(
			new GridColumn(
				'considered',
				'common.considered',
				null,
				'controllers/grid/common/cell/statusCell.tpl',
				$cellProvider,
				array('hoverTitle' => true)
			)
		);
	}


	//
	// Overridden methods from GridHandler
	//
	/**
	 * @see GridHandler::getRowInstance()
	 * @return ReviewerGridRow
	 */
	function &getRowInstance() {
		$row = new ReviewerGridRow();
		return $row;
	}

	/**
	 * @see GridHandler::getRequestArgs()
	 */
	function getRequestArgs() {
		$monograph =& $this->getMonograph();
		$reviewRound =& $this->getReviewRound();
		return array(
			'submissionId' => $monograph->getId(),
			'stageId' => $this->getStageId(),
			'reviewRoundId' => $reviewRound->getId()
		);
	}

	/**
	 * @see GridHandler::loadData()
	 */
	function loadData($request, $filter) {
		// Get the existing review assignments for this monograph
		$monograph =& $this->getMonograph(); /* @var $monograph SeriesEditorSubmission */
		$reviewRound =& $this->getReviewRound();
		return $monograph->getReviewAssignments($this->getStageId(), $reviewRound->getRound());
	}


	//
	// Public actions
	//
	/**
	 * Add a reviewer.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function showReviewerForm($args, &$request) {
		$json = new JSONMessage(true, $this->_fetchReviewerForm($args, $request));
		return $json->getString();
	}

	/**
	 * Load the contents of the reviewer form
	 * @param $args array
	 * @param $request Request
	 * @return string JSON
	 */
	function reloadReviewerForm($args, &$request) {
		$json = new JSONMessage(true);
		$json->setEvent('refreshForm', $this->_fetchReviewerForm($args, $request));
		return $json->getString();
	}

	/**
	 * Create a new user as reviewer.
	 * @param $args Array
	 * @param $request Request
	 * @return string Serialized JSON object
	 */
	function createReviewer($args, &$request) {
		return $this->updateReviewer($args, $request);
	}

	/**
	 * Enroll an existing user as reviewer.
	 * @param $args Array
	 * @param $request Request
	 * @return string Serialized JSON object
	 */
	function enrollReviewer($args, &$request) {
		return $this->updateReviewer($args, $request);
	}

	/**
	 * Edit a reviewer
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function updateReviewer($args, &$request) {
		$selectionType = $request->getUserVar('selectionType');
		$formClassName = $this->_getReviewerFormClassName($selectionType);

		// Form handling
		import('controllers.grid.users.reviewer.form.' . $formClassName );
		$reviewerForm = new $formClassName($this->getMonograph(), $this->getReviewRound());
		$reviewerForm->readInputData();
		if ($reviewerForm->validate()) {
			$reviewAssignment =& $reviewerForm->execute($args, $request);

			return DAO::getDataChangedEvent($reviewAssignment->getId());
		} else {
			// There was an error, redisplay the form
			$json = new JSONMessage(true, $reviewerForm->fetch($request));
		}
		return $json->getString();
	}

	/**
	 * Delete a reviewer
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function deleteReviewer($args, &$request) {
		// Delete the review assignment.
		// NB: SeriesEditorAction::clearReview() will check that this review
		// id is actually attached to the monograph so no need for further
		// validation here.
		$monograph =& $this->getMonograph();
		$reviewAssignment =& $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);
		import('classes.submission.seriesEditor.SeriesEditorAction');
		$seriesEditorAction = new SeriesEditorAction();
		$result = $seriesEditorAction->clearReview($request, $monograph->getId(), $reviewAssignment->getId());

		// Render the result.
		if ($result) {
			return DAO::getDataChangedEvent($reviewAssignment->getId());
		} else {
			$json = new JSONMessage(false, __('editor.review.errorDeletingReviewer'));
			return $json->getString();
		}
	}


	/**
	 * Get potential reviewers for editor's reviewer selection autocomplete.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function getReviewersNotAssignedToMonograph($args, &$request) {
		$press =& $request->getPress();
		$monograph =& $this->getMonograph();
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
		$reviewRound =& $this->getReviewRound();
		$round = $reviewRound->getRound();
		$term = $request->getUserVar('term');

		$seriesEditorSubmissionDao = DAORegistry::getDAO('SeriesEditorSubmissionDAO'); /* @var $seriesEditorSubmissionDao SeriesEditorSubmissionDAO */
		$reviewers =& $seriesEditorSubmissionDao->getReviewersNotAssignedToMonograph($press->getId(), $monograph->getId(), $reviewRound, $term);

		$reviewerList = array();
		while($reviewer =& $reviewers->next()) {
			$reviewerList[] = array('label' => $reviewer->getFullName(), 'value' => $reviewer->getId());
			unset($reviewer);
		}

		if (count($reviewerList) == 0) {
			$reviewerList[] = array('label' => __('common.noMatches'), 'value' => '');
		}

		$json = new JSONMessage(true, $reviewerList);
		return $json->getString();
	}

	/**
	 * Get a list of all non-reviewer users in the system to populate the reviewer role assignment autocomplete.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function getUsersNotAssignedAsReviewers($args, &$request) {
		$press =& $request->getPress();
		$term = $request->getUserVar('term');

		$userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */
		$monograph =& $this->getMonograph();

		$users =& $userGroupDao->getUsersNotInRole(ROLE_ID_REVIEWER, $press->getId(), $term);

		$userList = array();
		while ($user =& $users->next()) {
			$userList[] = array('label' => $user->getFullName(), 'value' => $user->getId());
			unset($user);
		}

		if (count($userList) == 0) {
			return $this->noAutocompleteResults();
		}

		$json = new JSONMessage(true, $userList);
		return $json->getString();
	}

	/**
	 * Open a modal to read the reviewer's review and
	 * download any files they may have uploaded
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string serialized JSON object
	 */
	function readReview($args, &$request) {
		$templateMgr =& TemplateManager::getManager($request);

		// Retrieve monograph.
		$templateMgr->assign_by_ref('monograph', $this->getMonograph());

		// Retrieve review assignment.
		$reviewAssignment =& $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);
		$templateMgr->assign_by_ref('reviewAssignment', $reviewAssignment);

		// Retrieve reviewer comment.
		$monographCommentDao = DAORegistry::getDAO('MonographCommentDAO');
		$monographComments =& $monographCommentDao->getReviewerCommentsByReviewerId($reviewAssignment->getReviewerId(), $reviewAssignment->getSubmissionId(), $reviewAssignment->getId());
		$templateMgr->assign_by_ref('reviewerComment', $monographComments->next());

		// Render the response.
		return $templateMgr->fetchJson('controllers/grid/users/reviewer/readReview.tpl');
	}

	/**
	 * An action triggered by a confirmation modal to allow an editor to unconsider a review.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string serialized JSON object
	 */
	function unconsiderReview($args, &$request) {

		// This resets the state of the review to 'unread', but does not delete note history.
		$monograph =& $this->getMonograph();
		$user =& $request->getUser();
		$reviewAssignment =& $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');

		$reviewAssignment->setUnconsidered(REVIEW_ASSIGNMENT_UNCONSIDERED);
		$result = $reviewAssignmentDao->updateObject($reviewAssignment);
		$this->_updateReviewRoundStatus($reviewAssignment);

		// log the unconsider.
		import('classes.log.MonographLog');
		import('classes.log.MonographEventLogEntry');

		$entry = new MonographEventLogEntry();
		$entry->setMonographId($reviewAssignment->getSubmissionId());
		$entry->setUserId($user->getId());
		$entry->setDateLogged(Core::getCurrentDate());
		$entry->setEventType(MONOGRAPH_LOG_REVIEW_UNCONSIDERED);

		MonographLog::logEvent(
				$request,
				$monograph,
				MONOGRAPH_LOG_REVIEW_UNCONSIDERED,
				'log.review.reviewUnconsidered',
				array(
					'editorName' => $user->getFullName(),
					'submissionId' => $monograph->getId(),
					'round' => $reviewAssignment->getRound(),
				)
		);

		// Render the result.
		if ($result) {
			return DAO::getDataChangedEvent($reviewAssignment->getId());
		} else {
			$json = new JSONMessage(false, __('editor.review.errorUnconsideringReview'));
			return $json->getString();
		}
	}

	/**
	 * Mark the review as read and trigger a rewrite of the row.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string serialized JSON object
	 */
	function reviewRead($args, &$request) {
		// Retrieve review assignment.
		$reviewAssignment =& $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT); /* @var $reviewAssignment ReviewAssignment */

		// Mark the latest read date of the review by the editor.
		$user =& $request->getUser();
		$viewsDao = DAORegistry::getDAO('ViewsDAO');
		$viewsDao->recordView(ASSOC_TYPE_REVIEW_RESPONSE, $reviewAssignment->getId(), $user->getId());

		// if the review assignment had been unconsidered, update the flag.
		if ($reviewAssignment->getUnconsidered() == REVIEW_ASSIGNMENT_UNCONSIDERED) {
			$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignment->setUnconsidered(REVIEW_ASSIGNMENT_UNCONSIDERED_READ);
			$reviewAssignmentDao->updateObject($reviewAssignment);
		}

		$this->_updateReviewRoundStatus($reviewAssignment);

		return DAO::getDataChangedEvent($reviewAssignment->getId());
	}

	/**
	 * Displays a modal to allow the editor to enter a message to send to the reviewer as a thank you.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function editThankReviewer($args, &$request) {
		// Identify the review assignment being updated.
		$reviewAssignment =& $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);

		// Initialize form.
		import('controllers.grid.users.reviewer.form.ThankReviewerForm');
		$thankReviewerForm = new ThankReviewerForm($reviewAssignment);
		$thankReviewerForm->initData($args, $request);

		// Render form.
		$json = new JSONMessage(true, $thankReviewerForm->fetch($request));
		return $json->getString();
	}

	/**
	 * Send the acknowledgement email, if desired, and trigger a row refresh action.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string serialized JSON object
	 */
	function thankReviewer($args, &$request) {
		// Identify the review assignment being updated.
		$reviewAssignment =& $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);

		// Form handling
		import('controllers.grid.users.reviewer.form.ThankReviewerForm');
		$thankReviewerForm = new ThankReviewerForm($reviewAssignment);
		$thankReviewerForm->readInputData();
		if ($thankReviewerForm->validate()) {
			$thankReviewerForm->execute($args, $request);
			$json = new JSONMessage(true);
			// Insert a trivial notification to indicate the reviewer was reminded successfully.
			$currentUser =& $request->getUser();
			$notificationMgr = new NotificationManager();
			$messageKey = $thankReviewerForm->getData('skipEmail') ? __('notification.reviewAcknowledged') : __('notification.sentNotification');
			$notificationMgr->createTrivialNotification($currentUser->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => $messageKey));
		} else {
			$json = new JSONMessage(false, __('editor.review.thankReviewerError'));
		}

		$this->_updateReviewRoundStatus($reviewAssignment);
		return DAO::getDataChangedEvent($reviewAssignment->getId());
	}

	/**
	 * Displays a modal to allow the editor to enter a message to send to the reviewer as a reminder
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function editReminder($args, &$request) {
		// Identify the review assignment being updated.
		$reviewAssignment =& $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);

		// Initialize form.
		import('controllers.grid.users.reviewer.form.ReviewReminderForm');
		$reviewReminderForm = new ReviewReminderForm($reviewAssignment);
		$reviewReminderForm->initData($args, $request);

		// Render form.
		$json = new JSONMessage(true, $reviewReminderForm->fetch($request));
		return $json->getString();
	}

	/**
	 * Send the reviewer reminder and close the modal
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function sendReminder($args, &$request) {
		$reviewAssignment =& $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);

		// Form handling
		import('controllers.grid.users.reviewer.form.ReviewReminderForm');
		$reviewReminderForm = new ReviewReminderForm($reviewAssignment);
		$reviewReminderForm->readInputData();
		if ($reviewReminderForm->validate()) {
			$reviewReminderForm->execute($args, $request);
			$json = new JSONMessage(true);
			// Insert a trivial notification to indicate the reviewer was reminded successfully.
			$currentUser =& $request->getUser();
			$notificationMgr = new NotificationManager();
			$notificationMgr->createTrivialNotification($currentUser->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.sentNotification')));
		} else {
			$json = new JSONMessage(false, __('editor.review.reminderError'));
		}
		return $json->getString();
	}

	/**
	 * Displays a modal to send an email message to the user.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function sendEmail($args, &$request) {
		$user =& $request->getUser();

		$reviewAssignment =& $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);

		// Form handling.
		import('lib.pkp.controllers.grid.settings.user.form.UserEmailForm');
		$userEmailForm = new UserEmailForm($reviewAssignment->getReviewerId());
		$userEmailForm->initData($args, $request);

		$json = new JSONMessage(true, $userEmailForm->display($args, $request));
		return $json->getString();
	}


	/**
	 * Displays a modal containing history for the review assignment.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function reviewHistory($args, &$request) {
		$user =& $request->getUser();

		$reviewAssignment =& $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign_by_ref('reviewAssignment', $reviewAssignment);


		return $templateMgr->fetchJson('workflow/reviewHistory.tpl');
	}


	//
	// Private helper methods
	//
	/**
	 * Return a fetched reviewer form data in string.
	 * @param $args Array
	 * @param $request Request
	 * @return String
	 */
	function _fetchReviewerForm($args, &$request) {
		$selectionType = $request->getUserVar('selectionType');
		assert(!empty($selectionType));
		$formClassName = $this->_getReviewerFormClassName($selectionType);
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);

		// Form handling.
		import('controllers.grid.users.reviewer.form.' . $formClassName );
		$reviewerForm = new $formClassName($this->getMonograph(), $this->getReviewRound());
		$reviewerForm->initData($args, $request);
		$reviewerForm->setUserRoles($userRoles);

		return $reviewerForm->fetch($request);
	}

	/**
	 * Get the name of ReviewerForm class for the current selection type.
	 * @param $selectionType String (const)
	 * @return FormClassName String
	 */
	function _getReviewerFormClassName($selectionType) {
		switch ($selectionType) {
			case REVIEWER_SELECT_SEARCH_BY_NAME:
				return 'SearchByNameReviewerForm';
			case REVIEWER_SELECT_ADVANCED_SEARCH:
				return 'AdvancedSearchReviewerForm';
			case REVIEWER_SELECT_CREATE:
				return 'CreateReviewerForm';
			case REVIEWER_SELECT_ENROLL_EXISTING:
				return 'EnrollExistingReviewerForm';
		}
	}

	/**
	 * Get operations that need a review assignment policy.
	 * @return array
	 */
	function _getReviewAssignmentOps() {
		// Define operations that need a review assignment policy.
		return array('readReview', 'reviewHistory', 'reviewRead', 'editThankReviewer', 'thankReviewer', 'editReminder', 'sendReminder', 'deleteReviewer', 'sendEmail', 'unconsiderReview');

	}

	/**
	 * Get operations that need a review round policy.
	 * @return array
	 */
	function _getReviewRoundOps() {
		// Define operations that need a review round policy.
		return array('fetchGrid', 'fetchRow', 'showReviewerForm', 'reloadReviewerForm', 'createReviewer', 'enrollReviewer', 'updateReviewer',
								'getReviewersNotAssignedToMonograph', 'getUsersNotAssignedAsReviewers');
	}

	/**
	 * Update the review round status.
	 */
	function _updateReviewRoundStatus($reviewAssignment) {
		// Update the review round status.
		$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
		$reviewRound = $reviewRoundDao->getReviewRoundById($reviewAssignment->getReviewRoundId());
		$seriesEditorSubmission = $this->getMonograph();
		$seriesEditorSubmission->updateReviewAssignment($reviewAssignment);
		$reviewAssignments = $seriesEditorSubmission->getReviewAssignments($reviewRound->getStageId(), $reviewRound->getRound());
		$reviewRoundDao->updateStatus($reviewRound, $reviewAssignments);
	}
}

?>
