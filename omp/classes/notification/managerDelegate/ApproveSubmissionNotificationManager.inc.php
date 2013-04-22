<?php

/**
 * @file classes/notification/managerDelegate/ApproveSubmissionNotificationManager.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ApproveSubmissionNotificationManager
 * @ingroup managerDelegate
 *
 * @brief Approve submission notification type manager delegate.
 */

import('lib.pkp.classes.notification.NotificationManagerDelegate');

class ApproveSubmissionNotificationManager extends NotificationManagerDelegate {

	/**
	 * Constructor.
	 * @param $request PKPRequest
	 * @param $notificationType int
	 */
	function ApproveSubmissionNotificationManager($notificationType) {
		parent::NotificationManagerDelegate($notificationType);
	}

	/**
	 * @see NotificationManagerDelegate::getStyleClass()
	 */
	public function getStyleClass($notification) {
		return NOTIFICATION_STYLE_CLASS_WARNING;
	}

	/**
	 * @see NotificationManagerDelegate::updateNotification()
	 */
	public function updateNotification($request, $userIds, $assocType, $assocId) {
		$monographId = $assocId;
		$monographDao = DAORegistry::getDAO('MonographDAO');
		$monograph = $monographDao->getById($monographId);

		$context = $request->getContext();
		$contextId = $context->getId();
		$notificationDao = DAORegistry::getDAO('NotificationDAO');

		$notificationTypes = array(
			NOTIFICATION_TYPE_APPROVE_SUBMISSION => false,
			NOTIFICATION_TYPE_FORMAT_NEEDS_APPROVED_SUBMISSION => false,
			NOTIFICATION_TYPE_VISIT_CATALOG => true,
		);

		$isPublished = (boolean) $monograph->getDatePublished();

		foreach ($notificationTypes as $type => $forPublicationState) {
			$notificationFactory = $notificationDao->getByAssoc(
				ASSOC_TYPE_SUBMISSION,
				$monographId,
				null,
				$type,
				$contextId
			);
			$notification = $notificationFactory->next();

			if (!$notification && $isPublished == $forPublicationState) {
				// Create notification.
				$this->createNotification(
					$request,
					null,
					$type,
					$contextId,
					ASSOC_TYPE_SUBMISSION,
					$monographId,
					NOTIFICATION_LEVEL_NORMAL
				);
			} elseif ($notification && $isPublished != $forPublicationState) {
				// Delete existing notification.
				$notificationDao->deleteObject($notification);
			}
		}
	}
}

?>
