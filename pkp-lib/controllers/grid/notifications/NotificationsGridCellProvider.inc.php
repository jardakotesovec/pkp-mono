<?php

/**
 * @file controllers/grid/notifications/NotificationsGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NotificationsGridCellProvider
 * @ingroup controllers_grid_notifications
 *
 * @brief Class for a cell provider that can retrieve labels from notifications
 */


import('lib.pkp.classes.controllers.grid.GridCellProvider');
import('lib.pkp.classes.linkAction.request.AjaxAction');

use APP\template\TemplateManager;

class NotificationsGridCellProvider extends GridCellProvider
{
    /**
     * Get cell actions associated with this row/column combination
     *
     * @param $row GridRow
     * @param $column GridColumn
     *
     * @return array an array of LinkAction instances
     */
    public function getCellActions($request, $row, $column, $position = GRID_ACTION_POSITION_DEFAULT)
    {
        assert($column->getId() == 'task');

        $templateMgr = TemplateManager::getManager($request);

        $notification = $row->getData();
        $contextDao = Application::getContextDAO();
        $context = $contextDao->getById($notification->getContextId());

        $notificationMgr = new NotificationManager();
        $router = $request->getRouter();

        $templateMgr->assign([
            'notificationMgr' => $notificationMgr,
            'notification' => $notification,
            'context' => $context,
            'notificationObjectTitle' => $this->_getTitle($notification),
            'message' => PKPString::stripUnsafeHtml($notificationMgr->getNotificationMessage($request, $notification)),
        ]);

        // See if we're working in a multi-context environment
        $user = $request->getUser();
        $contextDao = Application::getContextDAO();
        $contexts = $contextDao->getAvailable($user ? $user->getId() : null)->toArray();
        $templateMgr->assign('isMultiContext', count($contexts) > 1);

        return [new LinkAction(
            'details',
            new AjaxAction($router->url(
                $request,
                null,
                null,
                'markRead',
                null,
                ['redirect' => 1, 'selectedElements' => [$notification->getId()]]
            )),
            $templateMgr->fetch('controllers/grid/tasks/task.tpl')
        )];
    }


    //
    // Template methods from GridCellProvider
    //
    /**
     * Extracts variables for a given column from a data element
     * so that they may be assigned to template before rendering.
     *
     * @param $row GridRow
     * @param $column GridColumn
     *
     * @return array
     */
    public function getTemplateVarsFromRowColumn($row, $column)
    {
        assert($column->getId() == 'task');

        // The action has the label.
        return ['label' => ''];
    }

    /**
     * Get the title for a notification.
     *
     * @param $notification Notification
     *
     * @return string
     */
    public function _getTitle($notification)
    {
        switch ($notification->getAssocType()) {
            case ASSOC_TYPE_QUEUED_PAYMENT:
                $contextDao = Application::getContextDAO();
                $paymentManager = Application::getPaymentManager($contextDao->getById($notification->getContextId()));
                $queuedPaymentDao = DAORegistry::getDAO('QueuedPaymentDAO'); /** @var QueuedPaymentDAO $queuedPaymentDao */
                $queuedPayment = $queuedPaymentDao->getById($notification->getAssocId());
                if ($queuedPayment) {
                    switch ($queuedPayment->getType()) {
                    case PAYMENT_TYPE_PUBLICATION:
                        $submissionDao = DAORegistry::getDAO('SubmissionDAO'); /** @var SubmissionDAO $submissionDao */
                        return $submissionDao->getById($queuedPayment->getAssocId())->getLocalizedTitle();
                }
                }
                assert(false);
                return '—';
            case ASSOC_TYPE_ANNOUNCEMENT:
                $announcementId = $notification->getAssocId();
                $announcementDao = DAORegistry::getDAO('AnnouncementDAO'); /** @var AnnouncementDAO $announcementDao */
                $announcement = $announcementDao->getById($announcementId);
                if ($announcement) {
                    return $announcement->getLocalizedTitle();
                }
                return null;
            case ASSOC_TYPE_SUBMISSION:
                $submissionId = $notification->getAssocId();
                break;
            case ASSOC_TYPE_SUBMISSION_FILE:
                $fileId = $notification->getAssocId();
                break;
            case ASSOC_TYPE_REVIEW_ASSIGNMENT:
                $reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO'); /** @var ReviewAssignmentDAO $reviewAssignmentDao */
                $reviewAssignment = $reviewAssignmentDao->getById($notification->getAssocId());
                assert($reviewAssignment instanceof \PKP\submission\reviewAssignment\ReviewAssignment);
                $submissionId = $reviewAssignment->getSubmissionId();
                break;
            case ASSOC_TYPE_REVIEW_ROUND:
                $reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO'); /** @var ReviewRoundDAO $reviewRoundDao */
                $reviewRound = $reviewRoundDao->getById($notification->getAssocId());
                assert(is_a($reviewRound, 'ReviewRound'));
                $submissionId = $reviewRound->getSubmissionId();
                break;
            case ASSOC_TYPE_QUERY:
                $queryDao = DAORegistry::getDAO('QueryDAO'); /** @var QueryDAO $queryDao */
                $query = $queryDao->getById($notification->getAssocId());
                assert(is_a($query, 'Query'));
                switch ($query->getAssocType()) {
                    case ASSOC_TYPE_SUBMISSION:
                        $submissionId = $query->getAssocId();
                        break;
                    case ASSOC_TYPE_REPRESENTATION:
                        $representationDao = Application::getRepresentationDAO();
                        $representation = $representationDao->getById($query->getAssocId());
                        $publication = Services::get('publication')->get($representation->getData('publicationId'));
                        $submissionId = $publication->getData('submissionId');
                        break;
                    default: assert(false);
                }
                break;
            default:
                return '—';
        }

        if (!isset($submissionId) && isset($fileId)) {
            assert(is_numeric($fileId));
            $submissionFile = Services::get('submissionFile')->get($fileId);
            assert(is_a($submissionFile, 'SubmissionFile'));
            $submissionId = $submissionFile->getData('submissionId');
        }
        assert(is_numeric($submissionId));
        $submission = Services::get('submission')->get($submissionId);
        assert(is_a($submission, 'Submission'));

        return $submission->getLocalizedTitle();
    }
}
