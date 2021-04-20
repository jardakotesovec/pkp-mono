<?php

/**
 * @file pages/workflow/WorkflowHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WorkflowHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for the submssion workflow.
 */

import('lib.pkp.pages.workflow.PKPWorkflowHandler');

// Access decision actions constants.
import('classes.workflow.EditorDecisionActionsManager');

class WorkflowHandler extends PKPWorkflowHandler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->addRoleAssignment(
            [ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER, ROLE_ID_ASSISTANT],
            [
                'access', 'index', 'submission',
                'editorDecisionActions', // Submission & review
                'externalReview', // review
                'editorial',
                'production',
                'submissionHeader',
                'submissionProgressBar',
            ]
        );
    }

    /**
     * Setup variables for the template
     *
     * @param $request Request
     */
    public function setupIndex($request)
    {
        parent::setupIndex($request);

        $templateMgr = TemplateManager::getManager($request);
        $submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);

        $submissionContext = $request->getContext();
        if ($submission->getContextId() !== $submissionContext->getId()) {
            $submissionContext = Services::get('context')->get($submission->getContextId());
        }

        $supportedSubmissionLocales = $submissionContext->getSupportedSubmissionLocales();
        $localeNames = AppLocale::getAllLocales();
        $locales = array_map(function ($localeKey) use ($localeNames) {
            return ['key' => $localeKey, 'label' => $localeNames[$localeKey]];
        }, $supportedSubmissionLocales);

        $latestPublication = $submission->getLatestPublication();

        $latestPublicationApiUrl = $request->getDispatcher()->url($request, PKPApplication::ROUTE_API, $submissionContext->getPath(), 'submissions/' . $submission->getId() . '/publications/' . $latestPublication->getId());
        $temporaryFileApiUrl = $request->getDispatcher()->url($request, PKPApplication::ROUTE_API, $submissionContext->getPath(), 'temporaryFiles');
        $relatePublicationApiUrl = $request->getDispatcher()->url($request, PKPApplication::ROUTE_API, $submissionContext->getPath(), 'submissions/' . $submission->getId() . '/publications/' . $latestPublication->getId()) . '/relate';

        import('classes.file.PublicFileManager');
        $publicFileManager = new PublicFileManager();
        $baseUrl = $request->getBaseUrl() . '/' . $publicFileManager->getContextFilesPath($submissionContext->getId());

        $issueEntryForm = new APP\components\forms\publication\IssueEntryForm($latestPublicationApiUrl, $locales, $latestPublication, $submissionContext, $baseUrl, $temporaryFileApiUrl);
        $relationForm = new APP\components\forms\publication\RelationForm($relatePublicationApiUrl, $locales, $latestPublication, $submissionContext, $baseUrl, $temporaryFileApiUrl);

        import('classes.components.forms.publication.IssueEntryForm'); // Constant import
        $templateMgr->setConstants([
            'FORM_ISSUE_ENTRY',
        ]);
        import('classes.components.forms.publication.RelationForm'); // Constant import
        $templateMgr->setConstants([
            'FORM_ID_RELATION',
        ]);

        $sectionWordLimits = [];
        $sectionDao = DAORegistry::getDAO('SectionDAO'); /* @var $sectionDao SectionDAO */
        $sectionIterator = $sectionDao->getByContextId($submissionContext->getId());
        while ($section = $sectionIterator->next()) {
            $sectionWordLimits[$section->getId()] = (int) $section->getAbstractWordCount() ?? 0;
        }


        // Add the word limit to the existing title/abstract form
        $components = $templateMgr->getState('components');
        if (!empty($components[FORM_TITLE_ABSTRACT]) &&
                array_key_exists($submission->getLatestPublication()->getData('sectionId'), $sectionWordLimits)) {
            $limit = (int) $sectionWordLimits[$submission->getLatestPublication()->getData('sectionId')];
            foreach ($components[FORM_TITLE_ABSTRACT]['fields'] as $key => $field) {
                if ($field['name'] === 'abstract') {
                    $components[FORM_TITLE_ABSTRACT]['fields'][$key]['wordLimit'] = $limit;
                    break;
                }
            }
        }
        $components[FORM_ISSUE_ENTRY] = $issueEntryForm->getConfig();
        $components[FORM_ID_RELATION] = $relationForm->getConfig();

        $publicationFormIds = $templateMgr->getState('publicationFormIds');
        $publicationFormIds[] = FORM_ISSUE_ENTRY;

        $templateMgr->setState([
            'components' => $components,
            'publicationFormIds' => $publicationFormIds,
            'sectionWordLimits' => $sectionWordLimits,
        ]);
    }


    //
    // Protected helper methods
    //
    /**
     * Return the editor assignment notification type based on stage id.
     *
     * @param $stageId int
     *
     * @return int
     */
    protected function getEditorAssignmentNotificationTypeByStageId($stageId)
    {
        switch ($stageId) {
            case WORKFLOW_STAGE_ID_SUBMISSION:
                return NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_SUBMISSION;
            case WORKFLOW_STAGE_ID_EXTERNAL_REVIEW:
                return NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EXTERNAL_REVIEW;
            case WORKFLOW_STAGE_ID_EDITING:
                return NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EDITING;
            case WORKFLOW_STAGE_ID_PRODUCTION:
                return NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_PRODUCTION;
        }
        return null;
    }

    /**
     * @copydoc PKPWorkflowHandler::_getRepresentationsGridUrl()
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
}
