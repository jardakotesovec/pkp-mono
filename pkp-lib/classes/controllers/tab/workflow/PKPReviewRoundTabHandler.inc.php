<?php

/**
 * @file controllers/tab/workflow/PKPReviewRoundTabHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewRoundTabHandler
 * @ingroup controllers_tab_workflow
 *
 * @brief Handle AJAX operations for review round tabs on review stages workflow pages.
 */


use APP\handler\Handler;
use APP\notification\Notification;
use APP\template\TemplateManager;

use PKP\core\JSONMessage;
use PKP\notification\PKPNotification;
use PKP\security\authorization\internal\ReviewRoundRequiredPolicy;

class PKPReviewRoundTabHandler extends Handler
{
    //
    // Extended methods from Handler
    //
    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        // We need a review round id in request.
        $this->addPolicy(new ReviewRoundRequiredPolicy($request, $args));

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * JSON fetch the external review round info (tab).
     *
     * @param $args array
     * @param $request PKPRequest
     *
     * @return JSONMessage JSON object
     */
    public function externalReviewRound($args, $request)
    {
        return $this->_reviewRound($args, $request);
    }


    /**
     * @see PKPHandler::setupTemplate
     */
    public function setupTemplate($request)
    {
        AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);
        parent::setupTemplate($request);
    }


    //
    // Protected helper methods.
    //
    /**
     * Internal function to handle both internal and external reviews round info (tab content).
     *
     * @param $request PKPRequest
     * @param $args array
     *
     * @return JSONMessage JSON object
     */
    protected function _reviewRound($args, $request)
    {
        $this->setupTemplate($request);

        // Retrieve the authorized submission, stage id and review round.
        $submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
        $stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
        $reviewRound = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ROUND);

        // Is this round the most recent round?
        $reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO'); /** @var ReviewRoundDAO $reviewRoundDao */
        $lastReviewRound = $reviewRoundDao->getLastReviewRoundBySubmissionId($submission->getId(), $stageId);

        // Add the round information to the template.
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('stageId', $stageId);
        $templateMgr->assign('reviewRoundId', $reviewRound->getId());
        $templateMgr->assign('isLastReviewRound', $reviewRound->getId() == $lastReviewRound->getId());
        $templateMgr->assign('submission', $submission);

        // Assign editor decision actions to the template, only if
        // user is accessing the last review round for this stage.
        $notificationRequestOptions = [
            Notification::NOTIFICATION_LEVEL_NORMAL => [
                PKPNotification::NOTIFICATION_TYPE_REVIEW_ROUND_STATUS => [ASSOC_TYPE_REVIEW_ROUND, $reviewRound->getId()]],
            Notification::NOTIFICATION_LEVEL_TRIVIAL => [],
        ];
        $templateMgr->assign('reviewRoundNotificationRequestOptions', $notificationRequestOptions);

        // If a user is also assigned as an author to this submission, they
        // shouldn't see any editorial actions
        $userAccessibleStages = $this->getAuthorizedContextObject(ASSOC_TYPE_ACCESSIBLE_WORKFLOW_STAGES);
        foreach ($userAccessibleStages as $accessibleStageId => $roles) {
            if (in_array(ROLE_ID_AUTHOR, $roles)) {
                $templateMgr->assign('isAssignedAsAuthor', true);
                break;
            }
        }

        return $templateMgr->fetchJson('workflow/reviewRound.tpl');
    }
}
