<?php

/**
 * @file controllers/grid/users/stageParticipant/form/StageParticipantNotifyForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StageParticipantNotifyForm
 * @ingroup grid_users_stageParticipant_form
 *
 * @brief Form to notify a user regarding a file
 */

use APP\mail\PreprintMailTemplate;

import('lib.pkp.controllers.grid.users.stageParticipant.form.PKPStageParticipantNotifyForm');

class StageParticipantNotifyForm extends PKPStageParticipantNotifyForm
{
    /**
     * Return app-specific stage templates.
     *
     * @return array
     */
    protected function _getStageTemplates()
    {
        return [
            WORKFLOW_STAGE_ID_PRODUCTION => ['EDITOR_ASSIGN']
        ];
    }

    /**
     * return app-specific mail template.
     *
     * @param $submission Submission
     * @param $templateKey string
     * @param $includeSignature boolean optional
     *
     * @return array
     */
    protected function _getMailTemplate($submission, $templateKey, $includeSignature = true)
    {
        if ($includeSignature) {
            return new PreprintMailTemplate($submission, $templateKey);
        } else {
            return new PreprintMailTemplate($submission, $templateKey, null, null, null, false);
        }
    }
}
