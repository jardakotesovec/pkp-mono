<?php

/**
 * @file controllers/grid/submissions/unassignedSubmissions/UnassignedSubmissionsListGridHandler.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UnassignedSubmissionsListGridHandler
 * @ingroup controllers_grid_submissions_unassignedSubmissions
 *
 * @brief Handle unassigned submissions list grid requests.
 */

// Import grid base classes.
import('lib.pkp.controllers.grid.submissions.SubmissionsListGridHandler');
import('lib.pkp.controllers.grid.submissions.SubmissionsListGridRow');

// Filter editor
define('FILTER_EDITOR_ALL', 0);
define('FILTER_EDITOR_ME', 1);

class UnassignedSubmissionsListGridHandler extends SubmissionsListGridHandler {
	/**
	 * Constructor
	 */
	function UnassignedSubmissionsListGridHandler() {
		parent::SubmissionsListGridHandler();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SERIES_EDITOR),
			array('fetchGrid', 'fetchRow', 'deleteSubmission')
		);
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize(&$request) {
		parent::initialize($request);

		// Set title.
		$this->setTitle('common.queue.long.submissionsUnassigned');

		// Add editor specific locale component.
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);
	}


	//
	// Implement template methods from SubmissionListGridHandler
	//
	/**
	 * @see SubmissionListGridHandler::getSubmissions()
	 */
	function getSubmissions($request, $userId) {
		$submissionDao = Application::getSubmissionDAO(); /* @var $submissionDao SubmissionDAO */

		// Determine whether this is a Series Editor or Manager.
		// Managers can access all submissions, Series Editors
		// only assigned submissions.
		$user = $request->getUser();

		// Get all submissions for all contexts that user is
		// enrolled in as manager or series editor.
		$roleDao = DAORegistry::getDAO('RoleDAO');
		$contextDao = Application::getContextDAO();
		$contexts = $contextDao->getAll();

		$accessibleSubmissions = array();
		while ($context = $contexts->next()) {
			$isManager = $roleDao->userHasRole($context->getId(), $userId, ROLE_ID_MANAGER);
			$isSeriesEditor = $roleDao->userHasRole($context->getId(), $userId, ROLE_ID_SERIES_EDITOR);

			if (!$isManager && !$isSeriesEditor) {
				continue;
			}

			$submissionFactory = $submissionDao->getMonographsBySeriesEditorId(
				$context->getId(),
				$isManager?null:$userId
			);

			if (!$submissionFactory->wasEmpty()) {
				$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
				while ($submission = $submissionFactory->next()) {
					if ($submission->getDatePublished() == null && !$stageAssignmentDao->editorAssignedToStage($submission->getId())) {
						$accessibleSubmissions[$submission->getId()] = $submission;
					}
				}
			}
		}

		return $accessibleSubmissions;
	}
}

?>
