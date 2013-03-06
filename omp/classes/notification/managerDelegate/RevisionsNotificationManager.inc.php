<?php

/**
 * @file classes/notification/managerDelegate/RevisionsNotificationManager.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RevisionsNotificationManager
 * @ingroup managerDelegate
 *
 * @brief Base class for revision notification types manager delegate.
 */

import('lib.pkp.classes.notification.NotificationManagerDelegate');
import('classes.monograph.MonographFile'); // Bring file stage constants.
import('classes.workflow.EditorDecisionActionsManager'); // Access decision actions constants.

abstract class RevisionsNotificationManager extends NotificationManagerDelegate {

	/**
	 * Constructor.
	 * @param $request PKPRequest
	 * @param $notificationType int
	 */
	function RevisionsNotificationManager($notificationType) {
		parent::NotificationManagerDelegate($notificationType);
	}

	/**
	 * @see NotificationManagerDelegate::getStyleClass()
	 */
	public function getStyleClass(&$notification) {
		return NOTIFICATION_STYLE_CLASS_WARNING;
	}


	//
	// Protected helper methods.
	//
	/**
	 * Find any still valid pending revisions decision for the passed
	 * monograph id. A valid decision is one that is not overriden by any
	 * other decision.
	 * @param $monographId int
	 * @param $expectedStageId int
	 * @return mixed array or null
	 */
	protected function findValidPendingRevisionsDecision($monographId, $expectedStageId) {
		$seriesEditorSubmissionDao =& DAORegistry::getDAO('SeriesEditorSubmissionDAO');
		$editorDecisions = $seriesEditorSubmissionDao->getEditorDecisions($monographId);
		$workingDecisions = array_reverse($editorDecisions);
		$postReviewDecisions = array(SUBMISSION_EDITOR_DECISION_SEND_TO_PRODUCTION);
		$pendingRevisionDecision = null;

		foreach ($workingDecisions as $decision) {
			if (in_array($decision['decision'], $postReviewDecisions)) {
				// Decisions at later stages do not override the pending revisions one.
				continue;
			} elseif ($decision['decision'] == SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS) {
				if ($decision['stageId'] == $expectedStageId) {
					$pendingRevisionDecision = $decision;
					// Only the last pending revisions decision is relevant.
					break;
				} else {
					// Both internal and external pending revisions decisions are
					// valid at the same time. Continue to search.
					continue;
				}

			} else {
				break;
			}
		}

		return $pendingRevisionDecision;
	}

	/**
	 * Find any file upload that's a revision and can be considered as
	 * a pending revisions decision response.
	 * @param $decision array
	 * @param $monographId int
	 * @return boolean
	 */
	protected function responseExists($decision, $monographId) {
		$stageId = $decision['stageId'];
		$round = $decision['round'];
		$sentRevisions = false;

		$reviewRoundDao =& DAORegistry::getDAO('ReviewRoundDAO');
		$reviewRound =& $reviewRoundDao->getReviewRound($monographId, $stageId, $round);

		$submissionFileDao =& DAORegistry::getDAO('SubmissionFileDAO');
		$monographFiles =&  $submissionFileDao->getRevisionsByReviewRound($reviewRound, SUBMISSION_FILE_REVIEW_REVISION);

		if (is_array($monographFiles)) {
			foreach ($monographFiles as $file) {
				if ($file->getDateUploaded() > $decision['dateDecided']) {
					$sentRevisions = true;
					break;
				}
			}
		}

		return $sentRevisions;
	}
}

?>