<?php

/**
 * @file controllers/grid/settings/sections/form/PKPSectionForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPSectionForm
 * @ingroup controllers_grid_settings_section_form
 *
 * @brief Form for adding/editing a section
 */

import('lib.pkp.classes.form.Form');

use APP\template\TemplateManager;

class PKPSectionForm extends Form
{
    /** the id for the section being edited **/
    public $_sectionId;

    /** @var int The current user ID */
    public $_userId;

    /** @var string Cover image extension */
    public $_imageExtension;

    /** @var array Cover image information from getimagesize */
    public $_sizeArray;

    /**
     * Constructor.
     *
     * @param $request PKPRequest
     * @param $template string Template path
     * @param $sectionId int optional
     */
    public function __construct($request, $template, $sectionId = null)
    {
        $this->setSectionId($sectionId);

        $user = $request->getUser();
        $this->_userId = $user->getId();

        parent::__construct($template);

        // Validation checks for this form
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));

        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_PKP_MANAGER);
    }

    /**
     * @copydoc Form::readInputData()
     */
    public function readInputData()
    {
        $this->readUserVars(['title', 'subEditors']);
    }

    /**
     * Get the section ID for this section.
     *
     * @return int
     */
    public function getSectionId()
    {
        return $this->_sectionId;
    }

    /**
     * Set the section ID for this section.
     *
     * @param $sectionId int
     */
    public function setSectionId($sectionId)
    {
        $this->_sectionId = $sectionId;
    }

    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $params = [
            'contextId' => $request->getContext()->getId(),
            'roleIds' => ROLE_ID_SUB_EDITOR,
        ];

        $usersIterator = Services::get('user')->getMany($params);
        $subeditors = [];
        foreach ($usersIterator as $user) {
            $subeditors[(int) $user->getId()] = $user->getFullName();
        }

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'subeditors' => $subeditors,
        ]);

        return parent::fetch($request, $template, $display);
    }

    /**
     * Save changes to subeditors
     *
     */
    public function execute(...$functionArgs)
    {
        $contextId = Application::get()->getRequest()->getContext()->getId();
        $subEditorsDao = DAORegistry::getDAO('SubEditorsDAO'); /** @var SubEditorsDAO $subEditorsDao */
        $subEditorsDao->deleteBySubmissionGroupId($this->getSectionId(), ASSOC_TYPE_SECTION, $contextId);
        $subEditors = $this->getData('subEditors');
        if (!empty($subEditors)) {
            $roleDao = DAORegistry::getDAO('RoleDAO'); /** @var RoleDAO $roleDao */
            foreach ($subEditors as $subEditor) {
                if ($roleDao->userHasRole($contextId, $subEditor, ROLE_ID_SUB_EDITOR)) {
                    $subEditorsDao->insertEditor($contextId, $this->getSectionId(), $subEditor, ASSOC_TYPE_SECTION);
                }
            }
        }

        parent::execute($functionArgs);
    }
}
