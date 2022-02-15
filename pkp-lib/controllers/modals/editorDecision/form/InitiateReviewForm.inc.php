<?php

/**
 * @file controllers/modals/editorDecision/form/InitiateReviewForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class InitiateReviewForm
 * @ingroup controllers_modal_editorDecision_form
 *
 * @brief Form for creating the first review round for a submission
 */

use APP\facades\Repo;
use APP\workflow\EditorDecisionActionsManager;
use PKP\controllers\modals\editorDecision\form\EditorDecisionForm;
use PKP\submission\action\EditorAction;

use PKP\submission\reviewRound\ReviewRound;

class InitiateReviewForm extends EditorDecisionForm
{
    /**
     * Get the stage ID constant for the submission to be moved to.
     *
     * @return int WORKFLOW_STAGE_ID_...
     */
    public function _getStageId()
    {
        assert(false); // Subclasses should override.
    }

    //
    // Implement protected template methods from Form
    //
    /**
     * Execute the form.
     */
    public function execute(...$functionParams)
    {
        parent::execute(...$functionParams);

        $request = Application::get()->getRequest();

        // Retrieve the submission.
        $submission = $this->getSubmission();

        // Record the decision.
        $actionLabels = (new EditorDecisionActionsManager())->getActionLabels($request->getContext(), $submission, $this->getStageId(), [$this->_decision]);
        $editorAction = new EditorAction();
        $editorAction->recordDecision($request, $submission, $this->_decision, $actionLabels);

        // Move to the internal review stage.
        $editorAction->incrementWorkflowStage($submission, $this->_getStageId());
        $validDoiWorkflowStages = [WORKFLOW_STAGE_ID_EDITING, WORKFLOW_STAGE_ID_PRODUCTION];
        if ($request->getContext()->getData(\PKP\context\Context::SETTING_DOI_CREATION_TIME) === Repo::doi()::CREATION_TIME_COPYEDIT
            && in_array($this->_getStageId(), $validDoiWorkflowStages)
        ) {
            Repo::submission()->createDois($submission);
        }

        // Create an initial internal review round.
        $this->_initiateReviewRound($submission, $this->_getStageId(), $request, ReviewRound::REVIEW_ROUND_STATUS_PENDING_REVIEWERS);
    }
}
