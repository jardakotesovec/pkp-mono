<?php

/**
 * @file classes/notification/managerDelegate/AllReviewsInNotificationManager.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AllReviewsInNotificationManager
 * @ingroup managerDelegate
 *
 * @brief All reviews in notification types manager delegate.
 */

import('lib.pkp.classes.notification.NotificationManagerDelegate');

class AllReviewsInNotificationManager extends NotificationManagerDelegate {

	/**
	 * Constructor.
	 * @param $request PKPRequest
	 * @param $notificationType int
	 */
	function AllReviewsInNotificationManager($notificationType) {
		parent::NotificationManagerDelegate($notificationType);
	}

	/**
	 * @see NotificationManagerDelegate::getStyleClass()
	 */
	public function getStyleClass(&$notification) {
		return NOTIFICATION_STYLE_CLASS_WARNING;
	}

	/**
	 * @see NotificationManagerDelegate::updateNotification()
	 */
	public function updateNotification(&$request, $userIds, $assocType, $assocId) {
		$reviewRoundDao =& DAORegistry::getDAO('ReviewRoundDAO');
		$reviewRound =& $reviewRoundDao->getReviewRoundById($assocId);

		$stageAssignmentDao =& DAORegistry::getDAO('StageAssignmentDAO');
		$stageAssignments = $stageAssignmentDao->getEditorsAssignedToStage($reviewRound->getSubmissionId(), $reviewRound->getStageId());

		$notificationDao =& DAORegistry::getDAO('NotificationDAO'); /* @var $notificationDao NotificationDAO */
		$press =& $request->getPress();
		$pressId = $press->getId();

		foreach ($stageAssignments as $stageAssignment) {
			$userId = $stageAssignment->getUserId();

			// Get any existing notification.
			$notificationFactory =& $notificationDao->getByAssoc(
				ASSOC_TYPE_REVIEW_ROUND,
				$reviewRound->getId(), $userId,
				NOTIFICATION_TYPE_ALL_REVIEWS_IN,
				$pressId
			);

			$currentStatus = $reviewRound->getStatus();
			if (in_array($currentStatus, $reviewRoundDao->getEditorDecisionRoundStatus()) ||
			in_array($currentStatus, array(REVIEW_ROUND_STATUS_PENDING_REVIEWERS, REVIEW_ROUND_STATUS_PENDING_REVIEWS))) {
				// Editor has taken a decision in round or there are pending
				// reviews or no reviews. Delete any existing notification.
				if (!$notificationFactory->wasEmpty()) {
					$notification =& $notificationFactory->next();
					$notificationDao->deleteObject($notification);
					unset($notification);
				}
			} else {
				// There is no currently decision in round. Also there is reviews,
				// but no pending reviews. Insert notification, if not already present.
				if ($notificationFactory->wasEmpty()) {
					$this->createNotification($request, $userId, NOTIFICATION_TYPE_ALL_REVIEWS_IN, $pressId,
						ASSOC_TYPE_REVIEW_ROUND, $reviewRound->getId(), NOTIFICATION_LEVEL_TASK);
				}
			}

			unset($notificationFactory);
		}
	}
}

?>