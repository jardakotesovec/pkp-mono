<?php

/**
 * @file classes/submission/reviewer/ReviewerAction.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerAction
 * @ingroup submission
 *
 * @brief ReviewerAction class.
 */


// Access decision actions constants.
import('classes.workflow.EditorDecisionActionsManager');
import('classes.submission.common.Action');

class ReviewerAction extends Action {

	/**
	 * Constructor
	 */
	function ReviewerAction() {
		parent::Action();
	}

	//
	// Actions.
	//
	/**
	 * Records whether or not the reviewer accepts the review assignment.
	 * @param $reviewerSubmission object
	 * @param $decline boolean
	 * @param $send boolean
	 */
	function confirmReview($request, $reviewerSubmission, $decline, $send) {
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = DAORegistry::getDAO('UserDAO');

		$reviewId = $reviewerSubmission->getReviewId();

		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);
		$reviewer =& $userDao->getById($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return true;

		// Only confirm the review for the reviewer if
		// he has not previously done so.
		if ($reviewAssignment->getDateConfirmed() == null) {
			import('classes.mail.MonographMailTemplate');
			$email = new MonographMailTemplate($reviewerSubmission, $decline?'REVIEW_DECLINE':'REVIEW_CONFIRM');
			// Must explicitly set sender because we may be here on an access
			// key, in which case the user is not technically logged in
			$email->setReplyTo($reviewer->getEmail(), $reviewer->getFullName());
			if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
				HookRegistry::call('ReviewerAction::confirmReview', array($request, &$reviewerSubmission, &$email, $decline));
				if ($email->isEnabled()) {
					$email->setEventType($decline?SUBMISSION_EMAIL_REVIEW_DECLINE:SUBMISSION_EMAIL_REVIEW_CONFIRM);
					$email->send($request);
				}

				$reviewAssignment->setDeclined($decline);
				$reviewAssignment->setDateConfirmed(Core::getCurrentDate());
				$reviewAssignment->stampModified();
				$reviewAssignmentDao->updateObject($reviewAssignment);

				// Add log
				import('lib.pkp.classes.log.SubmissionLog');
				import('classes.log.SubmissionEventLogEntry');

				$entry = new SubmissionEventLogEntry();
				$entry->setMonographId($reviewAssignment->getSubmissionId());
				$entry->setUserId($reviewer->getId());
				$entry->setDateLogged(Core::getCurrentDate());
				$entry->setEventType($decline?SUBMISSION_LOG_REVIEW_DECLINE:SUBMISSION_LOG_REVIEW_ACCEPT);

				SubmissionLog::logEvent(
					$request,
					$reviewerSubmission,
					$decline?SUBMISSION_LOG_REVIEW_DECLINE:SUBMISSION_LOG_REVIEW_ACCEPT,
					$decline?'log.review.reviewDeclined':'log.review.reviewAccepted',
					array(
						'reviewerName' => $reviewer->getFullName(),
						'monographId' => $reviewAssignment->getSubmissionId(),
						'round' => $reviewAssignment->getRound()
					)
				);

				return true;
			} else {
				if (!$request->getUserVar('continued')) {
					$assignedEditors = $email->ccAssignedEditors($reviewerSubmission->getId());
					$reviewingSeriesEditors = $email->toAssignedReviewingSeriesEditors($reviewerSubmission->getId());
					if (empty($assignedEditors) && empty($reviewingSeriesEditors)) {
						$press = $request->getPress();
						$email->addRecipient($press->getSetting('contactEmail'), $press->getSetting('contactName'));
						$editorialContactName = $press->getSetting('contactName');
					} else {
						if (!empty($reviewingSeriesEditors)) $editorialContact = array_shift($reviewingSeriesEditors);
						else $editorialContact = array_shift($assignedEditors);
						$editorialContactName = $editorialContact->getEditorFullName();
					}

					// Format the review due date
					$reviewDueDate = strtotime($reviewAssignment->getDateDue());
					$dateFormatShort = Config::getVar('general', 'date_format_short');
					if ($reviewDueDate == -1) $reviewDueDate = $dateFormatShort; // Default to something human-readable if no date specified
					else $reviewDueDate = strftime($dateFormatShort, $reviewDueDate);

					$email->assignParams(array(
						'editorialContactName' => $editorialContactName,
						'reviewerName' => $reviewer->getFullName(),
						'reviewDueDate' => $reviewDueDate
					));
				}
				$paramArray = array('reviewId' => $reviewId);
				if ($decline) $paramArray['declineReview'] = 1;
				$email->displayEditForm($request->url(null, 'reviewer', 'confirmReview'), $paramArray);
				return false;
			}
		}
		return true;
	}
}

?>
