<?php

/**
 * @file plugins/generic/webFeed/WebFeedGatewayPlugin.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WebFeedGatewayPlugin
 * @brief Gateway component of web feed plugin
 *
 */

namespace APP\plugins\generic\webFeed;

use APP\core\Application;
use APP\facades\Repo;
use APP\template\TemplateManager;
use PKP\db\DAORegistry;
use PKP\submission\PKPSubmission;

class WebFeedGatewayPlugin extends \PKP\plugins\GatewayPlugin
{
    public const DEFAULT_RECENT_ITEMS = 30;

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
     * Hide this plugin from the management interface (it's subsidiary)
     */
    public function getHideManagement()
    {
        return true;
    }

    /**
     * Get the name of this plugin. The name must be unique within
     * its category.
     *
     * @return string name of plugin
     */
    public function getName()
    {
        return 'WebFeedGatewayPlugin';
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.generic.webfeed.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
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
     * Get whether or not this plugin is enabled. (Should always return true, as the
     * parent plugin will take care of loading this one when needed)
     *
     * @param int $contextId Context ID (optional)
     *
     * @return bool
     */
    public function getEnabled($contextId = null)
    {
        return $this->_parentPlugin->getEnabled($contextId);
    }

    /**
     * Handle fetch requests for this plugin.
     *
     * @param array $args Arguments.
     * @param PKPRequest $request Request object.
     */
    public function fetch($args, $request)
    {
        // Make sure we're within a Server context
        $request = Application::get()->getRequest();
        $server = $request->getServer();
        if (!$server) {
            return false;
        }

        // Make sure there's a current issue for this server
        $issue = Repo::issue()->getCurrent($server->getId(), true);
        if (!$issue) {
            return false;
        }

        if (!$this->_parentPlugin->getEnabled($server->getId())) {
            return false;
        }

        // Make sure the feed type is specified and valid
        $type = array_shift($args);
        $typeMap = [
            'rss' => 'rss.tpl',
            'rss2' => 'rss2.tpl',
            'atom' => 'atom.tpl'
        ];
        $mimeTypeMap = [
            'rss' => 'application/rdf+xml',
            'rss2' => 'application/rss+xml',
            'atom' => 'application/atom+xml'
        ];
        if (!isset($typeMap[$type])) {
            return false;
        }

        // Get limit setting from web feeds plugin
        $recentItems = (int) $this->_parentPlugin->getSetting($server->getId(), 'recentItems');
        if ($recentItems < 1) {
            $recentItems = self::DEFAULT_RECENT_ITEMS;
        }

        $submissionsIterator = Repo::submission()->getCollector()
            ->filterByContextIds([$server->getId()])
            ->filterByStatus([PKPSubmission::STATUS_PUBLISHED])
            ->limit($recentItems)
            ->getMany();
        $submissionsInSections = [];
        foreach ($submissionsIterator as $submission) {
            $submissionsInSections[]['articles'][] = $submission;
        }

        $versionDao = DAORegistry::getDAO('VersionDAO'); /** @var VersionDAO $versionDao */
        $version = $versionDao->getCurrentVersion();

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'opsVersion' => $version->getVersionString(),
            'publishedSubmissions' => $submissionsInSections,
            'server' => $server,
            'issue' => $issue,
            'showToc' => true,
        ]);

        $templateMgr->display($this->_parentPlugin->getTemplateResource($typeMap[$type]), $mimeTypeMap[$type]);
        return true;
    }
}
