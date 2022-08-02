<?php

/**
 * @file controllers/grid/settings/sections/form/SectionForm.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SectionForm
 * @ingroup controllers_grid_settings_section_form
 *
 * @brief Form for adding/editing a section
 */

namespace APP\controllers\grid\settings\sections\form;

use PKP\controllers\grid\settings\sections\form\PKPSectionForm;
use APP\core\Application;
use APP\facades\Repo;
use APP\template\TemplateManager;
use PKP\db\DAORegistry;
use PKP\security\Role;

class SectionForm extends PKPSectionForm
{
    /**
     * Constructor.
     *
     * @param Request $request
     * @param int $sectionId optional
     */
    public function __construct($request, $sectionId = null)
    {
        parent::__construct(
            $request,
            'controllers/grid/settings/sections/form/sectionForm.tpl',
            $sectionId
        );

        // Validation checks for this form
        $this->addCheck(new \PKP\form\validation\FormValidatorLocale($this, 'title', 'required', 'manager.setup.form.section.nameRequired'));
        $this->addCheck(new \PKP\form\validation\FormValidatorLocale($this, 'abbrev', 'required', 'manager.sections.form.abbrevRequired'));
        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'path', 'required', 'manager.setup.form.section.pathRequired'));
        $server = $request->getServer();
    }

    /**
     * Initialize form data from current settings.
     */
    public function initData()
    {
        $request = Application::get()->getRequest();
        $server = $request->getServer();

        $sectionDao = DAORegistry::getDAO('SectionDAO'); /** @var SectionDAO $sectionDao */
        $sectionId = $this->getSectionId();
        if ($sectionId) {
            $section = $sectionDao->getById($sectionId, $server->getId());
        }

        if (isset($section)) {
            $this->setData([
                'title' => $section->getTitle(null), // Localized
                'abbrev' => $section->getAbbrev(null), // Localized
                'reviewFormId' => $section->getReviewFormId(),
                'isInactive' => $section->getIsInactive(),
                'metaIndexed' => !$section->getMetaIndexed(), // #2066: Inverted
                'metaReviewed' => !$section->getMetaReviewed(), // #2066: Inverted
                'abstractsNotRequired' => $section->getAbstractsNotRequired(),
                'identifyType' => $section->getIdentifyType(null), // Localized
                'editorRestriction' => $section->getEditorRestricted(),
                'hideTitle' => $section->getHideTitle(),
                'hideAuthor' => $section->getHideAuthor(),
                'policy' => $section->getPolicy(null), // Localized
                'wordCount' => $section->getAbstractWordCount(),
                'path' => $section->getData('path'),
                'assignedSubeditors' => Repo::user()->getIds(
                    Repo::user()->getCollector()
                        ->filterByContextIds([Application::get()->getRequest()->getContext()->getId()])
                        ->filterByRoleIds([Role::ROLE_ID_SUB_EDITOR])
                        ->assignedToSectionIds([(int) $this->getSectionId()])
                )->toArray(),
            ]);
        } else {
            $this->setData([
                'assignedSubeditors' => [],
            ]);
        }

        parent::initData();
    }

    /**
     * @see Form::validate()
     */
    public function validate($callHooks = true)
    {
        // Validate if it can be inactive
        if ($this->getData('isInactive')) {
            $request = Application::get()->getRequest();
            $context = $request->getContext();
            $sectionId = $this->getSectionId();

            $sectionDao = DAORegistry::getDAO('SectionDAO'); /** @var SectionDAO $sectionDao */
            $sectionsIterator = $sectionDao->getByContextId($context->getId());
            $activeSectionsCount = 0;
            while ($section = $sectionsIterator->next()) {
                if (!$section->getIsInactive() && ($sectionId != $section->getId())) {
                    $activeSectionsCount++;
                }
            }
            if ($activeSectionsCount < 1 && $this->getData('isInactive')) {
                $this->addError('isInactive', __('manager.sections.confirmDeactivateSection.error'));
            }
        }

        return parent::validate($callHooks);
    }

    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('sectionId', $this->getSectionId());

        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc Form::readInputData()
     */
    public function readInputData()
    {
        parent::readInputData();
        $this->readUserVars(['abbrev', 'path', 'description', 'policy', 'identifyType', 'isInactive', 'metaIndexed', 'abstractsNotRequired', 'editorRestriction', 'wordCount']);
    }

    /**
     * Get the names of fields for which localized data is allowed.
     *
     * @return array
     */
    public function getLocaleFieldNames()
    {
        $sectionDao = DAORegistry::getDAO('SectionDAO'); /** @var SectionDAO $sectionDao */
        return $sectionDao->getLocaleFieldNames();
    }

    /**
     * Save section.
     */
    public function execute(...$functionArgs)
    {
        $sectionDao = DAORegistry::getDAO('SectionDAO'); /** @var SectionDAO $sectionDao */
        $request = Application::get()->getRequest();
        $server = $request->getServer();

        // Get or create the section object
        if ($this->getSectionId()) {
            $section = $sectionDao->getById($this->getSectionId(), $server->getId());
        } else {
            $section = $sectionDao->newDataObject();
            $section->setServerId($server->getId());
        }

        // Populate/update the section object from the form
        $section->setTitle($this->getData('title'), null); // Localized
        $section->setAbbrev($this->getData('abbrev'), null); // Localized
        $section->setPath($this->getData('path'));
        $section->setDescription($this->getData('description'), null); // Localized
        $section->setIsInactive($this->getData('isInactive') ? 1 : 0);
        $section->setMetaIndexed($this->getData('metaIndexed') ? 0 : 1); // #2066: Inverted
        $section->setAbstractsNotRequired($this->getData('abstractsNotRequired') ? 1 : 0);
        $section->setIdentifyType($this->getData('identifyType'), null); // Localized
        $section->setEditorRestricted($this->getData('editorRestriction') ? 1 : 0);
        $section->setPolicy($this->getData('policy'), null); // Localized
        $section->setAbstractWordCount($this->getData('wordCount'));

        // Insert or update the section in the DB
        if ($this->getSectionId()) {
            $sectionDao->updateObject($section);
        } else {
            $section->setSequence(REALLY_BIG_NUMBER);
            $this->setSectionId($sectionDao->insertObject($section));
            $sectionDao->resequenceSections($server->getId());
        }

        return parent::execute(...$functionArgs);
    }
}
