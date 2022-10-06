<?php

/**
 * @file plugins/generic/webFeed/WebFeedBlockPlugin.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WebFeedBlockPlugin
 * @brief Class for block component of web feed plugin
 */

namespace APP\plugins\generic\webFeed;

use APP\facades\Repo;

class WebFeedBlockPlugin extends \PKP\plugins\BlockPlugin
{
    /** @var WebFeedPlugin Parent plugin */
    protected $_parentPlugin;

    /**
     * @param WebFeedPlugin $parentPlugin
     */
    public function __construct($parentPlugin)
    {
        parent::__construct();
        $this->_parentPlugin = $parentPlugin;
    }

    /**
     * Get the name of this plugin. The name must be unique within
     * its category.
     *
     * @return string name of plugin
     */
    public function getName()
    {
        return 'WebFeedBlockPlugin';
    }

    /**
     * Hide this plugin from the management interface (it's subsidiary)
     */
    public function getHideManagement()
    {
        return true;
    }

    /**
     * Get the display name of this plugin.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return __('plugins.generic.webfeed.displayName');
    }

    /**
     * Get a description of the plugin.
     */
    public function getDescription()
    {
        return __('plugins.generic.webfeed.description');
    }

    /**
     * Override the builtin to get the correct plugin path.
     *
     * @return string
     */
    public function getPluginPath()
    {
        return $this->_parentPlugin->getPluginPath();
    }

    /**
     * @copydoc PKPPlugin::getTemplatePath
     */
    public function getTemplatePath($inCore = false)
    {
        return $this->_parentPlugin->getTemplatePath($inCore) . '/templates';
    }

    /**
     * Get the HTML contents for this block.
     *
     * @param object $templateMgr
     * @param PKPRequest $request
     *
     * @return $string
     */
    public function getContents($templateMgr, $request = null)
    {
        $server = $request->getServer();
        if (Repo::issue()->getCurrent($server->getId(), true)) {
            return parent::getContents($templateMgr, $request);
        }
        return '';
    }
}
