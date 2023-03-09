<?php

/**
 * @file plugins/generic/webFeed/WebFeedSettingsForm.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WebFeedSettingsForm
 * @brief Form for managers to modify web feeds plugin settings
 */

namespace APP\plugins\generic\webFeed;

use APP\template\TemplateManager;
use PKP\form\Form;

class WebFeedSettingsForm extends Form
{
    /**
     * Constructor
     */
    public function __construct(private WebFeedPlugin $plugin, private int $contextId)
    {
        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));
        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
    }

    /**
     * Initialize form data.
     */
    public function initData(): void
    {
        $contextId = $this->contextId;
        $plugin = $this->plugin;

        $this->setData('displayPage', $plugin->getSetting($contextId, 'displayPage'));
        $this->setData('displayItems', $plugin->getSetting($contextId, 'displayItems'));
        $this->setData('recentItems', $plugin->getSetting($contextId, 'recentItems'));
        $this->setData('includeIdentifiers', $plugin->getSetting($contextId, 'includeIdentifiers'));
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData(): void
    {
        $this->readUserVars(['displayPage', 'displayItems', 'recentItems', 'includeIdentifiers']);

        // check that recent items value is a positive integer
        if ((int) $this->getData('recentItems') <= 0) {
            $this->setData('recentItems', '');
        }

        // if recent items is selected, check that we have a value
        if ($this->getData('displayItems') == 'recent') {
            $this->addCheck(new \PKP\form\validation\FormValidator($this, 'recentItems', 'required', 'plugins.generic.webfeed.settings.recentItemsRequired'));
        }
    }

    /**
     * Fetch the form.
     *
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false): string
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->plugin->getName());
        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $plugin = $this->plugin;
        $contextId = $this->contextId;

        $plugin->updateSetting($contextId, 'displayPage', $this->getData('displayPage'));
        $plugin->updateSetting($contextId, 'displayItems', $this->getData('displayItems'));
        $plugin->updateSetting($contextId, 'recentItems', $this->getData('recentItems'), 'int');
        $plugin->updateSetting($contextId, 'includeIdentifiers', $this->getData('includeIdentifiers'), 'bool');

        parent::execute(...$functionArgs);
    }
}
