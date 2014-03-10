<?php

/**
 * @file pages/workflow/WorkflowHandler.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WorkflowHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for the submssion workflow.
 */

import('lib.pkp.pages.workflow.PKPWorkflowHandler');

// Access decision actions constants.
import('classes.workflow.EditorDecisionActionsManager');

class WorkflowHandler extends PKPWorkflowHandler {
	/**
	 * Constructor
	 */
	function WorkflowHandler() {
		parent::PKPWorkflowHandler();

		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER, ROLE_ID_ASSISTANT),
			array(
				'access', 'submission',
				'editorDecisionActions', // Submission & review
				'externalReview', // review
				'editorial',
				'production', 'galleysTab', // Production
				'submissionProgressBar',
				'expedite'
			)
		);
	}


	//
	// Public handler methods
	//
	/**
	 * Show the production stage
	 * @param $request PKPRequest
	 * @param $args array
	 */
	function production(&$args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$notificationRequestOptions = array(
			NOTIFICATION_LEVEL_NORMAL => array(
				NOTIFICATION_TYPE_VISIT_CATALOG => array(ASSOC_TYPE_SUBMISSION, $submission->getId()),
				NOTIFICATION_TYPE_APPROVE_SUBMISSION => array(ASSOC_TYPE_SUBMISSION, $submission->getId()),
			),
			NOTIFICATION_LEVEL_TRIVIAL => array()
		);

		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$galleys = $galleyDao->getBySubmissionId($submission->getId());
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('galleys', $galleys);

		$templateMgr->assign('productionNotificationRequestOptions', $notificationRequestOptions);
		$templateMgr->display('workflow/production.tpl');
	}

	/**
	 * Fetch the JSON-encoded submission progress bar.
	 * @param $args array
	 * @param $request Request
	 */
	function submissionProgressBar($args, $request) {
		// Assign the actions to the template.
		$templateMgr = TemplateManager::getManager($request);
		$context = $request->getContext();

		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$workflowStages = $userGroupDao->getWorkflowStageKeysAndPaths();
		$stageNotifications = array();
		foreach (array_keys($workflowStages) as $stageId) {
			$stageNotifications[$stageId] = $this->_notificationOptionsByStage($request->getUser(), $stageId, $context->getId());
		}

		$templateMgr->assign('stageNotifications', $stageNotifications);

		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = $publishedArticleDao->getPublishedArticleByArticleId($submission->getId());
		if ($publishedArticle) { // check to see if there os a published article
			$templateMgr->assign('submissionIsReady', true);
		}
		return $templateMgr->fetchJson('workflow/submissionProgressBar.tpl');
	}

	/**
	 * Show the production stage accordion contents
	 * @param $request PKPRequest
	 * @param $args array
	 */
	function galleysTab($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$galleys = $galleyDao->getBySubmissionId($submission->getId());
		$templateMgr->assign('submission', $submission);
		$templateMgr->assign('galleys', $galleys);
		$templateMgr->assign('currentGalleyTabId', (int) $request->getUserVar('currentGalleyTabId'));

		return $templateMgr->fetchJson('workflow/galleysTab.tpl');
	}

	/**
	 * Expedites a submission through the submission process, if the submitter is a manager or editor.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function expedite($args, $request) {

		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		import('controllers.tab.issueEntry.form.IssueEntryPublicationMetadataForm');
		$user = $request->getUser();
		$form = new IssueEntryPublicationMetadataForm($submission->getId(), $user, null, array('expeditedSubmission' => true));
		if ($submission && (int) $request->getUserVar('issueId') > 0) {

			// Process our submitted form in order to create the published article entry.
			$form->readInputData();
			if($form->validate()) {
				$form->execute($request);
				// Create trivial notification in place on the form, and log the event.
				$notificationManager = new NotificationManager();
				$user = $request->getUser();
				import('lib.pkp.classes.log.SubmissionLog');
				SubmissionLog::logEvent($request, $submission, SUBMISSION_LOG_ISSUE_METADATA_UPDATE, 'submission.event.issueMetadataUpdated');
				$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.savedIssueMetadata')));

				// Now, create a galley for this submission.  Assume PDF, and set to 'available'.
				$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
				$articleGalley = $articleGalleyDao->newDataObject();
				$articleGalley->setGalleyType('pdfarticlegalleyplugin');
				$articleGalley->setIsAvailable(true);
				$articleGalley->setSubmissionId($submission->getId());
				$articleGalley->setLocale($submission->getLocale());
				$articleGalley->setLabel('PDF');
				$articleGalley->setSeq($articleGalleyDao->getNextGalleySequence($submission->getId()));
				$articleGalleyId = $articleGalleyDao->insertObject($articleGalley);

				// Next, create a galley PROOF file out of the submission file uploaded.
				$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
				$submissionFiles = $submissionFileDao->getLatestRevisions($submission->getId(), SUBMISSION_FILE_SUBMISSION);
				// Assume a single file was uploaded, but check for something that's PDF anyway.
				foreach ($submissionFiles as $submissionFile) {
					// test both mime type and file extension in case the mime type isn't correct after uploading.
					if ($submissionFile->getFileType() == 'application/pdf' || preg_match('/\.pdf$/', $submissionFile->getOriginalFileName())) {

						// Get the path of the current file because we change the file stage in a bit.
						$currentFilePath = $submissionFile->getFilePath();

						// this will be a new file based on the old one.
						$submissionFile->setFileId(null);
						$submissionFile->setRevision(1);
						$submissionFile->setFileStage(SUBMISSION_FILE_PROOF);
						$submissionFile->setAssocType(ASSOC_TYPE_GALLEY);
						$submissionFile->setAssocId($articleGalleyId);

						$submissionFileDao->insertObject($submissionFile, $currentFilePath);
						break;
					}
				}

				// no errors, close the modal.
				$json = new JSONMessage(true);
				return $json->getString();
			} else {
			$json = new JSONMessage(true, $form->fetch($request));
			return $json->getString();
		}
		} else {
			$json = new JSONMessage(true, $form->fetch($request));
			return $json->getString();
		}
	}

	/**
	 * Determine if a particular stage has a notification pending.  If so, return true.
	 * This is used to set the CSS class of the submission progress bar.
	 * @param PKPUser $user
	 * @param int $stageId
	 */
	function _notificationOptionsByStage(&$user, $stageId, $contextId) {

		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$notificationDao = DAORegistry::getDAO('NotificationDAO');

		$signOffNotificationType = $this->_getSignoffNotificationTypeByStageId($stageId);
		$editorAssignmentNotificationType = $this->_getEditorAssignmentNotificationTypeByStageId($stageId);

		$editorAssignments = $notificationDao->getByAssoc(ASSOC_TYPE_SUBMISSION, $submission->getId(), null, $editorAssignmentNotificationType, $contextId);
		if (isset($signOffNotificationType)) {
			$signoffAssignments = $notificationDao->getByAssoc(ASSOC_TYPE_SUBMISSION, $submission->getId(), $user->getId(), $signOffNotificationType, $contextId);
		}

		// if the User has assigned TASKs in this stage check, return true
		if (!$editorAssignments->wasEmpty() || (isset($signoffAssignments) && !$signoffAssignments->wasEmpty())) {
			return true;
		}

		// check for more specific notifications on those stages that have them.
		if ($stageId == WORKFLOW_STAGE_ID_PRODUCTION) {
			$submissionApprovalNotification = $notificationDao->getByAssoc(ASSOC_TYPE_SUBMISSION, $submission->getId(), null, NOTIFICATION_TYPE_APPROVE_SUBMISSION, $contextId);
			if (!$submissionApprovalNotification->wasEmpty()) {
				return true;
			}
		}

		if ($stageId == WORKFLOW_STAGE_ID_EXTERNAL_REVIEW) {
			$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
			$reviewRounds = $reviewRoundDao->getBySubmissionId($submission->getId(), $stageId);
			$notificationTypes = array(NOTIFICATION_TYPE_REVIEW_ROUND_STATUS, NOTIFICATION_TYPE_ALL_REVIEWS_IN);
			while ($reviewRound = $reviewRounds->next()) {
				foreach ($notificationTypes as $type) {
					$notifications = $notificationDao->getByAssoc(ASSOC_TYPE_REVIEW_ROUND, $reviewRound->getId(), null, $type, $contextId);
					if (!$notifications->wasEmpty()) {
						return true;
					}
				}
			}
		}

		return false;
	}

	//
	// Protected helper methods
	//
	/**
	 * Return the editor assignment notification type based on stage id.
	 * @param $stageId int
	 * @return int
	 */
	protected function _getEditorAssignmentNotificationTypeByStageId($stageId) {
		switch ($stageId) {
			case WORKFLOW_STAGE_ID_SUBMISSION:
				return NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_SUBMISSION;
			case WORKFLOW_STAGE_ID_EXTERNAL_REVIEW:
				return NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EXTERNAL_REVIEW;
			case WORKFLOW_STAGE_ID_EDITING:
				return NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EDITING;
			case WORKFLOW_STAGE_ID_PRODUCTION:
				return NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_PRODUCTION;
		}
		return null;
	}
}

?>
