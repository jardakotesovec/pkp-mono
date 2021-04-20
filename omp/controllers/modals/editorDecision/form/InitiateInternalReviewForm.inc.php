<?php

/**
 * @file controllers/modals/editorDecision/form/InitiateInternalReviewForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class InitiateReviewForm
 * @ingroup controllers_modal_editorDecision_form
 *
 * @brief Form for creating the first review round for a submission's internal
 *  review
 */

import('lib.pkp.controllers.modals.editorDecision.form.InitiateReviewForm');

class InitiateInternalReviewForm extends InitiateReviewForm
{
    /**
     * Constructor.
     *
     * @param $submission Submission
     * @param $decision int SUBMISSION_EDITOR_DECISION_...
     * @param $stageId int WORKFLOW_STAGE_ID_...
     */
    public function __construct($submission, $decision, $stageId)
    {
        parent::__construct($submission, $decision, $stageId, 'controllers/modals/editorDecision/form/initiateInternalReviewForm.tpl');
    }

    /**
     * Get the stage ID constant for the submission to be moved to.
     *
     * @return int WORKFLOW_STAGE_ID_...
     */
    public function _getStageId()
    {
        return WORKFLOW_STAGE_ID_INTERNAL_REVIEW;
    }
}
