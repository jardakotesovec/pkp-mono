<?php
/**
 * @file controllers/grid/settings/reviewForms/form/ReviewFormElements.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewFormElements
 * @ingroup controllers_grid_settings_reviewForms_form
 *
 * @brief Form for manager to edit review form elements.
 */

import('lib.pkp.classes.form.Form');

use PKP\core\JSONMessage;

class ReviewFormElements extends Form
{
    /** The ID of the review form being edited */
    public $reviewFormId;

    /**
     * Constructor.
     *
     * @param $reviewFormId
     */
    public function __construct($reviewFormId)
    {
        parent::__construct('manager/reviewForms/reviewFormElements.tpl');

        $this->reviewFormId = (int) $reviewFormId;

        // Validation checks for this form
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /**
     * @copydoc Form::fetch
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $json = new JSONMessage();

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('reviewFormId', $this->reviewFormId);

        return parent::fetch($request, $template, $display);
    }

    /**
     * Initialize form data from current settings.
     */
    public function initData()
    {
        if (isset($this->reviewFormId)) {
            // Get review form
            $reviewFormDao = DAORegistry::getDAO('ReviewFormDAO'); /** @var ReviewFormDAO $reviewFormDao */
            $reviewForm = $reviewFormDao->getById($this->reviewFormId, Application::getContextAssocType(), $this->contextId);

            // Get review form elements
            $reviewFormElementDao = DAORegistry::getDAO('ReviewFormElementDAO'); /** @var ReviewFormElementDAO $reviewFormElementDao */
            $reviewFormElements = $reviewFormElementDao->getByReviewFormId($reviewFormId, null);

            // Set data
            $this->setData('reviewFormId', $reviewFormId);
            $this->setData('reviewFormElements', $reviewFormElements);
        }
    }
}
