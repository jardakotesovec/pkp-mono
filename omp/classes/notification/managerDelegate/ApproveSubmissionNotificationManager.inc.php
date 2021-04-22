<?php

/**
 * @file classes/notification/managerDelegate/ApproveSubmissionNotificationManager.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ApproveSubmissionNotificationManager
 * @ingroup classes_notification_managerDelegate
 *
 * @brief Notification manager delegate that handles notifications related with
 * submission approval process.
 */

import('lib.pkp.classes.notification.managerDelegate.PKPApproveSubmissionNotificationManager');

class ApproveSubmissionNotificationManager extends PKPApproveSubmissionNotificationManager
{
    /**
     * Constructor.
     *
     * @param $notificationType int NOTIFICATION_TYPE_...
     */
    public function __construct($notificationType)
    {
        parent::__construct($notificationType);
    }

    /**
     * @copydoc PKPNotificationOperationManager::getNotificationUrl()
     */
    public function getNotificationUrl($request, $notification)
    {
        $router = $request->getRouter();
        $dispatcher = $router->getDispatcher();
        $contextDao = Application::getContextDAO();
        $context = $contextDao->getById($notification->getContextId());

        switch ($notification->getType()) {
            case NOTIFICATION_TYPE_VISIT_CATALOG:
                return $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $context->getPath(), 'manageCatalog');
        }

        return parent::getNotificationUrl($request, $notification);
    }

    /**
     * @copydoc PKPNotificationOperationManager::getNotificationTitle()
     */
    public function getNotificationTitle($notification)
    {
        switch ($notification->getType()) {
            case NOTIFICATION_TYPE_APPROVE_SUBMISSION:
            case NOTIFICATION_TYPE_FORMAT_NEEDS_APPROVED_SUBMISSION:
                return __('notification.type.approveSubmissionTitle');
            case NOTIFICATION_TYPE_VISIT_CATALOG:
                return __('notification.type.visitCatalogTitle');
        }
    }

    /**
     * @copydoc PKPNotificationOperationManager::getNotificationMessage()
     */
    public function getNotificationMessage($request, $notification)
    {
        switch ($notification->getType()) {
            case NOTIFICATION_TYPE_FORMAT_NEEDS_APPROVED_SUBMISSION:
                return __('notification.type.formatNeedsApprovedSubmission');
            case NOTIFICATION_TYPE_VISIT_CATALOG:
                return __('notification.type.visitCatalog');
            case NOTIFICATION_TYPE_APPROVE_SUBMISSION:
                return __('notification.type.approveSubmission');
        }

        return parent::getNotificationMessage($request, $notification);
    }
}
