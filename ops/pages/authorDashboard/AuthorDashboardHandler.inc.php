<?php

/**
 * @file pages/authorDashboard/AuthorDashboardHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AuthorDashboardHandler
 * @ingroup pages_authorDashboard
 *
 * @brief Handle requests for the author dashboard.
 */

// Import base class
import('lib.pkp.pages.authorDashboard.PKPAuthorDashboardHandler');

use APP\submission\Submission;
use APP\facades\Repo;
use APP\template\TemplateManager;

class AuthorDashboardHandler extends PKPAuthorDashboardHandler
{
    /**
     * Setup variables for the template
     *
     * @param $request Request
     */
    public function setupTemplate($request)
    {
        parent::setupTemplate($request);
        $templateMgr = TemplateManager::getManager($request);

        $submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);

        $submissionContext = $request->getContext();
        if ($submission->getContextId() !== $submissionContext->getId()) {
            $submissionContext = Services::get('context')->get($submission->getContextId());
        }

        $supportedFormLocales = $submissionContext->getSupportedFormLocales();
        $localeNames = AppLocale::getAllLocales();
        $locales = array_map(function ($localeKey) use ($localeNames) {
            return ['key' => $localeKey, 'label' => $localeNames[$localeKey]];
        }, $supportedFormLocales);

        $latestPublication = $submission->getLatestPublication();
        $relatePublicationApiUrl = $request->getDispatcher()->url($request, PKPApplication::ROUTE_API, $submissionContext->getPath(), 'submissions/' . $submission->getId() . '/publications/' . $latestPublication->getId()) . '/relate';

        $publishUrl = $request->getDispatcher()->url(
            $request,
            PKPApplication::ROUTE_COMPONENT,
            null,
            'modals.publish.OPSPublishHandler',
            'publish',
            null,
            [
                'submissionId' => $submission->getId(),
                'publicationId' => '__publicationId__',
            ]
        );

        $relationForm = new APP\components\forms\publication\RelationForm($relatePublicationApiUrl, $locales, $latestPublication);

        // Import constants
        import('classes.components.forms.publication.RelationForm');

        $templateMgr->setConstants([
            'FORM_ID_RELATION' => FORM_ID_RELATION,
            'FORM_PUBLISH' => FORM_PUBLISH,
        ]);

        $components = $templateMgr->getState('components');
        $components[FORM_ID_RELATION] = $relationForm->getConfig();

        $publicationFormIds = $templateMgr->getState('publicationFormIds');
        $publicationFormIds[] = FORM_PUBLISH;

        $templateMgr->setState([
            'components' => $components,
            'publicationFormIds' => $publicationFormIds,
            'publishLabel' => __('publication.publish'),
            'publishUrl' => $publishUrl,
            'unpublishConfirmLabel' => __('publication.unpublish.confirm'),
            'unpublishLabel' => __('publication.unpublish'),
        ]);

        // If authors can publish show publish buttons
        $canPublish = Repo::publication()->canCurrentUserPublish($submission->getId()) ? true : false;
        $templateMgr->assign('canPublish', $canPublish);
    }

    /**
     * @copydoc PKPAuthorDashboardHandler::_getRepresentationsGridUrl()
     */
    protected function _getRepresentationsGridUrl($request, $submission)
    {
        return $request->getDispatcher()->url(
            $request,
            PKPApplication::ROUTE_COMPONENT,
            null,
            'grid.preprintGalleys.PreprintGalleyGridHandler',
            'fetchGrid',
            null,
            [
                'submissionId' => $submission->getId(),
                'publicationId' => '__publicationId__',
            ]
        );
    }

    /**
     * Translate the requested operation to a stage id.
     *
     * @param $request Request
     * @param $args array
     *
     * @return integer One of the WORKFLOW_STAGE_* constants.
     */
    protected function identifyStageId($request, $args)
    {
        if ($stageId = $request->getUserVar('stageId')) {
            return (int) $stageId;
        }
        // Maintain the old check for previous path urls
        $router = $request->getRouter();
        $workflowPath = $router->getRequestedOp($request);
        $stageId = WorkflowStageDAO::getIdFromPath($workflowPath);
        if ($stageId) {
            return $stageId;
        }
        // Finally, retrieve the requested operation, if the stage id is
        // passed in via an argument in the URL, like index/submissionId/stageId
        $stageId = $args[1];
        // Translate the operation to a workflow stage identifier.
        assert(WorkflowStageDAO::getPathFromId($stageId) !== null);
        return $stageId;
    }
}
