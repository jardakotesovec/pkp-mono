<?php

/**
 * @file classes/submission/seriesEditor/SeriesEditorAction.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SeriesEditorAction
 * @ingroup submission
 *
 * @brief SeriesEditorAction class.
 */


// Access decision actions constants.
import('classes.workflow.EditorDecisionActionsManager');
import('classes.submission.common.Action');

class SeriesEditorAction extends Action {

	/**
	 * Constructor.
	 */
	function SeriesEditorAction() {
		parent::Action();
	}

	//
	// Actions.
	//

	/**
	 * Assign the default participants to a workflow stage.
	 * @param $monograph Monograph
	 * @param $stageId int
	 * @param $request Request
	 */
	function assignDefaultStageParticipants($submission, $stageId, $request) {
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');

		// Managerial roles are skipped -- They have access by default and
		//  are assigned for informational purposes only

		// Series editor roles are skipped -- They are assigned by PM roles
		//  or by other series editors

		// Press roles -- For each press role user group assigned to this
		//  stage in setup, iff there is only one user for the group,
		//  automatically assign the user to the stage
		// But skip authors and reviewers, since these are very submission specific
		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
		$submissionStageGroups = $userGroupDao->getUserGroupsByStage($submission->getContextId(), $stageId, true, true);
		while ($userGroup = $submissionStageGroups->next()) {
			$users = $userGroupDao->getUsersById($userGroup->getId());
			if($users->getCount() == 1) {
				$user = $users->next();
				$stageAssignmentDao->build($submission->getId(), $userGroup->getId(), $user->getId());
			}
		}

		$notificationMgr = new NotificationManager();
		$notificationMgr->updateNotification(
			$request,
			array(
				NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_SUBMISSION,
				NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_INTERNAL_REVIEW,
				NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EXTERNAL_REVIEW,
				NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EDITING,
				NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_PRODUCTION),
			null,
			ASSOC_TYPE_SUBMISSION,
			$submission->getId()
		);

		// Reviewer roles -- Do nothing. Reviewers are not included in the stage participant list, they
		// are administered via review assignments.

		// Author roles
		// Assign only the submitter in whatever ROLE_ID_AUTHOR capacity they were assigned previously
		$submitterAssignments = $stageAssignmentDao->getBySubmissionAndStageId($submission->getId(), null, null, $submission->getUserId());
		while ($assignment = $submitterAssignments->next()) {
			$userGroup = $userGroupDao->getById($assignment->getUserGroupId());
			if ($userGroup->getRoleId() == ROLE_ID_AUTHOR) {
				$stageAssignmentDao->build($submission->getId(), $userGroup->getId(), $assignment->getUserId());
				// Only assign them once, since otherwise we'll one assignment for each previous stage.
				// And as long as they are assigned once, they will get access to their submission.
				break;
			}
		}
	}

	/**
	 * Increment a monograph's workflow stage.
	 * @param $monograph Monograph
	 * @param $newStage integer One of the WORKFLOW_STAGE_* constants.
	 * @param $request Request
	 */
	function incrementWorkflowStage(&$monograph, $newStage, $request) {
		// Change the monograph's workflow stage.
		$monograph->setStageId($newStage);
		$monographDao = DAORegistry::getDAO('MonographDAO'); /* @var $monographDao MonographDAO */
		$monographDao->updateObject($monograph);

		// Assign the default users to the next workflow stage.
		$this->assignDefaultStageParticipants($monograph, $newStage, $request);
	}

	/**
	 * Assigns a reviewer to a submission.
	 * @param $request PKPRequest
	 * @param $seriesEditorSubmission object
	 * @param $reviewerId int
	 * @param $reviewRound ReviewRound
	 * @param $reviewDueDate datetime optional
	 * @param $responseDueDate datetime optional
	 */
	function addReviewer($request, $seriesEditorSubmission, $reviewerId, &$reviewRound, $reviewDueDate = null, $responseDueDate = null, $reviewMethod = null) {
		$seriesEditorSubmissionDao = DAORegistry::getDAO('SeriesEditorSubmissionDAO');
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = DAORegistry::getDAO('UserDAO');

		$reviewer =& $userDao->getById($reviewerId);

		// Check to see if the requested reviewer is not already
		// assigned to review this monograph.

		$assigned = $reviewAssignmentDao->reviewerExists($reviewRound->getId(), $reviewerId);

		// Only add the reviewer if he has not already
		// been assigned to review this monograph.
		$stageId = $reviewRound->getStageId();
		$round = $reviewRound->getRound();
		if (!$assigned && isset($reviewer) && !HookRegistry::call('SeriesEditorAction::addReviewer', array(&$seriesEditorSubmission, $reviewerId))) {
			$reviewAssignment = new ReviewAssignment();
			$reviewAssignment->setSubmissionId($seriesEditorSubmission->getId());
			$reviewAssignment->setReviewerId($reviewerId);
			$reviewAssignment->setDateAssigned(Core::getCurrentDate());
			$reviewAssignment->setStageId($stageId);
			$reviewAssignment->setRound($round);
			$reviewAssignment->setReviewRoundId($reviewRound->getId());
			if (isset($reviewMethod)) {
				$reviewAssignment->setReviewMethod($reviewMethod);
			}
			$reviewAssignmentDao->insertObject($reviewAssignment);

			$seriesEditorSubmission->addReviewAssignment($reviewAssignment);
			$seriesEditorSubmissionDao->updateSeriesEditorSubmission($seriesEditorSubmission);

			$this->setDueDates($request, $seriesEditorSubmission, $reviewAssignment, $reviewDueDate, $responseDueDate);

			// Add notification
			$notificationMgr = new NotificationManager();
			$notificationMgr->createNotification(
				$request,
				$reviewerId,
				NOTIFICATION_TYPE_REVIEW_ASSIGNMENT,
				$seriesEditorSubmission->getPressId(),
				ASSOC_TYPE_REVIEW_ASSIGNMENT,
				$reviewAssignment->getId(),
				NOTIFICATION_LEVEL_TASK
			);

			// Insert a trivial notification to indicate the reviewer was added successfully.
			$currentUser = $request->getUser();
			$notificationMgr = new NotificationManager();
			$notificationMgr->createTrivialNotification($currentUser->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.addedReviewer')));

			// Update the review round status.
			$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO'); /* @var $reviewRoundDao ReviewRoundDAO */
			$reviewAssignments = $seriesEditorSubmission->getReviewAssignments($stageId, $round);
			$reviewRoundDao->updateStatus($reviewRound, $reviewAssignments);

			$notificationMgr->updateNotification(
				$request,
				array(NOTIFICATION_TYPE_ALL_REVIEWS_IN),
				null,
				ASSOC_TYPE_REVIEW_ROUND,
				$reviewRound->getId()
			);

			// Add log
			import('lib.pkp.classes.log.SubmissionLog');
			import('classes.log.SubmissionEventLogEntry');
			SubmissionLog::logEvent($request, $seriesEditorSubmission, SUBMISSION_LOG_REVIEW_ASSIGN, 'log.review.reviewerAssigned', array('reviewerName' => $reviewer->getFullName(), 'submissionId' => $seriesEditorSubmission->getId(), 'stageId' => $stageId, 'round' => $round));
		}
	}

	/**
	 * Sets the due date for a review assignment.
	 * @param $request PKPRequest
	 * @param $monograph Object
	 * @param $reviewId int
	 * @param $dueDate string
	 * @param $numWeeks int
	 * @param $logEntry boolean
	 */
	function setDueDates($request, $monograph, $reviewAssignment, $reviewDueDate = null, $responseDueDate = null, $logEntry = false) {
		$userDao = DAORegistry::getDAO('UserDAO');
		$press = $request->getContext();

		$reviewer = $userDao->getById($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return false;

		if ($reviewAssignment->getSubmissionId() == $monograph->getId() && !HookRegistry::call('SeriesEditorAction::setDueDates', array(&$reviewAssignment, &$reviewer, &$reviewDueDate, &$responseDueDate))) {

			// Set the review due date
			$defaultNumWeeks = $press->getSetting('numWeeksPerReview');
			$reviewAssignment->setDateDue(DAO::formatDateToDB($reviewDueDate, $defaultNumWeeks, false));

			// Set the response due date
			$defaultNumWeeks = $press->getSetting('numWeeksPerReponse');
			$reviewAssignment->setDateResponseDue(DAO::formatDateToDB($responseDueDate, $defaultNumWeeks, false));

			// update the assignment (with both the new dates)
			$reviewAssignment->stampModified();
			$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO'); /* @var $reviewAssignmentDao ReviewAssignmentDAO */
			$reviewAssignmentDao->updateObject($reviewAssignment);

			// N.B. Only logging Date Due
			if ($logEntry) {
				// Add log
				import('lib.pkp.classes.log.SubmissionLog');
				import('classes.log.SubmissionEventLogEntry');
				SubmissionLog::logEvent(
					$request,
					$monograph,
					SUBMISSION_LOG_REVIEW_SET_DUE_DATE,
					'log.review.reviewDueDateSet',
					array(
						'reviewerName' => $reviewer->getFullName(),
						'dueDate' => strftime(
							Config::getVar('general', 'date_format_short'),
							strtotime($reviewAssignment->getDateDue())
						),
						'submissionId' => $monograph->getId(),
						'stageId' => $reviewAssignment->getStageId(),
						'round' => $reviewAssignment->getRound()
					)
				);
			}
		}
	}

	/**
	 * Get the text of all peer reviews for a submission
	 * @param $seriesEditorSubmission SeriesEditorSubmission
	 * @param $reviewRoundId int
	 * @return string
	 */
	function getPeerReviews($seriesEditorSubmission, $reviewRoundId) {
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$submissionCommentDao = DAORegistry::getDAO('SubmissionCommentDAO');
		$reviewFormResponseDao = DAORegistry::getDAO('ReviewFormResponseDAO');
		$reviewFormElementDao = DAORegistry::getDAO('ReviewFormElementDAO');

		$reviewAssignments =& $reviewAssignmentDao->getBySubmissionId($seriesEditorSubmission->getId(), $reviewRoundId);
		$reviewIndexes =& $reviewAssignmentDao->getReviewIndexesForRound($seriesEditorSubmission->getId(), $reviewRoundId);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);

		$body = '';
		$textSeparator = "------------------------------------------------------";
		foreach ($reviewAssignments as $reviewAssignment) {
			// If the reviewer has completed the assignment, then import the review.
			if ($reviewAssignment->getDateCompleted() != null && !$reviewAssignment->getCancelled()) {
				// Get the comments associated with this review assignment
				$submissionComments = $submissionCommentDao->getSubmissionComments($seriesEditorSubmission->getId(), COMMENT_TYPE_PEER_REVIEW, $reviewAssignment->getId());

				$body .= "\n\n$textSeparator\n";
				// If it is not a double blind review, show reviewer's name.
				if ($reviewAssignment->getReviewMethod() != SUBMISSION_REVIEW_METHOD_DOUBLEBLIND) {
					$body .= $reviewAssignment->getReviewerFullName() . "\n";
				} else {
					$body .= __('submission.comments.importPeerReviews.reviewerLetter', array('reviewerLetter' => String::enumerateAlphabetically($reviewIndexes[$reviewAssignment->getId()]))) . "\n";
				}

				while ($comment = $submissionComments->next()) {
					// If the comment is viewable by the author, then add the comment.
					if ($comment->getViewable()) {
						$body .= String::html2text($comment->getComments()) . "\n\n";
					}
				}
				$body .= "$textSeparator\n\n";

				if ($reviewFormId = $reviewAssignment->getReviewFormId()) {
					$reviewId = $reviewAssignment->getId();


					$reviewFormElements =& $reviewFormElementDao->getReviewFormElements($reviewFormId);
					if(!$submissionComments) {
						$body .= "$textSeparator\n";

						$body .= __('submission.comments.importPeerReviews.reviewerLetter', array('reviewerLetter' => String::enumerateAlphabetically($reviewIndexes[$reviewAssignment->getId()]))) . "\n\n";
					}
					foreach ($reviewFormElements as $reviewFormElement) {
						$body .= String::html2text($reviewFormElement->getLocalizedQuestion()) . ": \n";
						$reviewFormResponse = $reviewFormResponseDao->getReviewFormResponse($reviewId, $reviewFormElement->getId());

						if ($reviewFormResponse) {
							$possibleResponses = $reviewFormElement->getLocalizedPossibleResponses();
							if (in_array($reviewFormElement->getElementType(), $reviewFormElement->getMultipleResponsesElementTypes())) {
								if ($reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_CHECKBOXES) {
									foreach ($reviewFormResponse->getValue() as $value) {
										$body .= "\t" . String::htmltext($possibleResponses[$value-1]['content']) . "\n";
									}
								} else {
									$body .= "\t" . String::html2text($possibleResponses[$reviewFormResponse->getValue()-1]['content']) . "\n";
								}
								$body .= "\n";
							} else {
								$body .= "\t" . String::html2text($reviewFormResponse->getValue()) . "\n\n";
							}
						}

					}
					$body .= "$textSeparator\n\n";

				}


			}
		}

		return $body;
	}
}

?>
