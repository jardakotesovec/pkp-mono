<?php

/**
 * @file classes/notification/managerDelegate/PendingRevisionsNotificationManager.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PendingRevisionsNotificationManager
 * @ingroup managerDelegate
 *
 * @brief Pending revision notification types manager delegate.
 */

import('classes.notification.managerDelegate.RevisionsNotificationManager');

class PendingRevisionsNotificationManager extends RevisionsNotificationManager {

	/**
	 * Constructor.
	 * @param $request PKPRequest
	 * @param $notificationType int
	 */
	function PendingRevisionsNotificationManager($notificationType) {
		parent::RevisionsNotificationManager($notificationType);
	}

	/**
	 * @see NotificationManagerDelegate::getNotificationUrl()
	 */
	public function getNotificationUrl(&$request, &$notification) {
		$monographDao = DAORegistry::getDAO('MonographDAO');
		$monograph = $monographDao->getById($notification->getAssocId());

		import('lib.pkp.controllers.grid.submissions.SubmissionsListGridCellProvider');
		list($page, $operation) = SubmissionsListGridCellProvider::getPageAndOperationByUserRoles($request, $monograph);

		if ($page == 'workflow') {
			$stageData = $this->_getStageDataByType();
			$operation = $stageData['path'];
		}

		$router = $request->getRouter();
		$dispatcher = $router->getDispatcher();
		return $dispatcher->url($request, ROUTE_PAGE, null, $page, $operation, $monograph->getId());
	}

	/**
	 * @see NotificationManagerDelegate::getNotificationMessage()
	 */
	public function getNotificationMessage(&$request, &$notification) {
		$stageData = $this->_getStageDataByType();
		$stageKey = $stageData['translationKey'];

		return __('notification.type.pendingRevisions', array('stage' => __($stageKey)));
	}

	/**
	 * @see NotificationManagerDelegate::getNotificationContents()
	 */
	public function getNotificationContents(&$request, &$notification) {
		$stageData = $this->_getStageDataByType();
		$stageId = $stageData['id'];
		$monographId = $notification->getAssocId();

		$monographDao = DAORegistry::getDAO('MonographDAO');
		$monograph = $monographDao->getById($monographId);
		$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
		$lastReviewRound = $reviewRoundDao->getLastReviewRoundByMonographId($monograph->getId(), $stageId);

		import('controllers.api.file.linkAction.AddRevisionLinkAction');
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR); // editor.review.uploadRevision

		$uploadFileAction = new AddRevisionLinkAction(
			$request, $lastReviewRound, array(ROLE_ID_AUTHOR)
		);

		return $this->fetchLinkActionNotificationContent($uploadFileAction);
	}

	/**
	 * @see NotificationManagerDelegate::getNotificationTitle()
	 */
	public function getNotificationTitle(&$notification) {
		$stageData = $this->_getStageDataByType();
		$stageKey = $stageData['translationKey'];
		return __('notification.type.pendingRevisions.title', array('stage' => __($stageKey)));
	}

	/**
	 * @see NotificationManagerDelegate::updateNotification()
	 */
	public function updateNotification(&$request, $userIds, $assocType, $assocId) {
		$userId = current($userIds);
		$monographId = $assocId;
		$stageData = $this->_getStageDataByType();
		$expectedStageId = $stageData['id'];

		$pendingRevisionDecision = $this->findValidPendingRevisionsDecision($monographId, $expectedStageId);
		$removeNotifications = false;

		if ($pendingRevisionDecision) {
			if ($this->responseExists($pendingRevisionDecision, $monographId)) {
				// Some user already uploaded a revision. Flag to delete any existing notification.
				$removeNotifications = true;
			} else {
				// Create or update a pending revision task notification.
				$press = $request->getPress();
				$notificationDao = DAORegistry::getDAO('NotificationDAO'); /* @var $notificationDao NotificationDAO */
				$notificationDao->build(
					$press->getId(),
					NOTIFICATION_LEVEL_TASK,
					$this->getNotificationType(),
					ASSOC_TYPE_MONOGRAPH,
					$monographId,
					$userId
				);
			}
		} else {
			// No pending revision decision or other later decision overriden it.
			// Flag to delete any existing notification.
			$removeNotifications = true;
		}

		if ($removeNotifications) {
			$press = $request->getPress();
			$notificationDao = DAORegistry::getDAO('NotificationDAO');
			$notificationDao->deleteByAssoc(ASSOC_TYPE_MONOGRAPH, $monographId, $userId, $this->getNotificationType(), $press->getId());
		}
	}


	//
	// Private helper methods.
	//
	/**
	 * Get the data for an workflow stage by
	 * pending revisions notification type.
	 * @return string
	 */
	private function _getStageDataByType() {
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */
		$stagesData = $userGroupDao->getWorkflowStageKeysAndPaths();

		switch ($this->getNotificationType()) {
			case NOTIFICATION_TYPE_PENDING_INTERNAL_REVISIONS:
				return $stagesData[WORKFLOW_STAGE_ID_INTERNAL_REVIEW];
			case NOTIFICATION_TYPE_PENDING_EXTERNAL_REVISIONS:
				return $stagesData[WORKFLOW_STAGE_ID_EXTERNAL_REVIEW];
			default:
				assert(false);
		}
	}
}

?>
