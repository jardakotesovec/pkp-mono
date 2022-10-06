<?php

/**
 * @file plugins/generic/webFeed/WebFeedBlockPlugin.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2003-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WebFeedBlockPlugin
 * @brief Class for block component of web feed plugin
 */

namespace APP\plugins\generic\webFeed;

class WebFeedBlockPlugin extends \PKP\plugins\BlockPlugin
{
    /** Parent plugin */
    protected WebFeedPlugin $_parentPlugin;

    /**
     * @param $parentPlugin
     */
    public function __construct(WebFeedPlugin $parentPlugin)
    {
        parent::__construct();
        $this->_parentPlugin = $parentPlugin;
    }

    /**
     * Get the name of this plugin. The name must be unique within
     * its category.
     */
    public function getName(): string
    {
        return static::class;
    }

    /**
     * Hide this plugin from the management interface (it's subsidiary)
     */
    public function getHideManagement(): bool
    {
        return true;
    }

    /**
     * Get the display name of this plugin.
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.webfeed.displayName');
    }

    /**
     * Get a description of the plugin.
     */
    public function getDescription(): string
    {
        return __('plugins.generic.webfeed.description');
    }

    /**
     * Override the builtin to get the correct plugin path.
     */
    public function getPluginPath(): string
    {
        return $this->_parentPlugin->getPluginPath();
    }

    /**
     * @copydoc PKPPlugin::getTemplatePath
     */
    public function getTemplatePath($inCore = false): string
    {
        return $this->_parentPlugin->getTemplatePath($inCore) . '/templates';
    }
}
