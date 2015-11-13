<?php

/**
 * @file plugins/generic/webFeed/WebFeedGatewayPlugin.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WebFeedGatewayPlugin
 * @ingroup plugins_generic_webFeed
 *
 * @brief Gateway component of web feed plugin
 *
 */

import('lib.pkp.classes.plugins.GatewayPlugin');

class WebFeedGatewayPlugin extends GatewayPlugin {
	/** @var string Name of parent plugin */
	var $parentPluginName;

	function WebFeedGatewayPlugin($parentPluginName) {
		parent::GatewayPlugin();
		$this->parentPluginName = $parentPluginName;
	}

	/**
	 * Hide this plugin from the management interface (it's subsidiary)
	 */
	function getHideManagement() {
		return true;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'WebFeedGatewayPlugin';
	}

	function getDisplayName() {
		return __('plugins.generic.webfeed.displayName');
	}

	function getDescription() {
		return __('plugins.generic.webfeed.description');
	}

	/**
	 * Get the web feed plugin
	 * @return object
	 */
	function &getWebFeedPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		return $plugin;
	}

	/**
	 * Override the builtin to get the correct plugin path.
	 */
	function getPluginPath() {
		$plugin =& $this->getWebFeedPlugin();
		return $plugin->getPluginPath();
	}

	/**
	 * Override the builtin to get the correct template path.
	 * @return string
	 */
	function getTemplatePath() {
		$plugin =& $this->getWebFeedPlugin();
		return $plugin->getTemplatePath();
	}

	/**
	 * Get whether or not this plugin is enabled. (Should always return true, as the
	 * parent plugin will take care of loading this one when needed)
	 * @return boolean
	 */
	function getEnabled() {
		$plugin =& $this->getWebFeedPlugin();
		return $plugin->getEnabled(); // Should always be true anyway if this is loaded
	}

	/**
	 * Get the management verbs for this plugin (override to none so that the parent
	 * plugin can handle this)
	 * @return array
	 */
	function getManagementVerbs() {
		return array();
	}

	/**
	 * Handle fetch requests for this plugin.
	 * @param $args array Arguments.
	 * @param $request PKPRequest Request object.
	 */
	function fetch($args, $request) {
		// Make sure we're within a Journal context
		$request = $this->getRequest();
		$journal = $request->getJournal();
		if (!$journal) return false;

		// Make sure there's a current issue for this journal
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getCurrent($journal->getId(), true);
		if (!$issue) return false;

		$webFeedPlugin =& $this->getWebFeedPlugin();
		if (!$webFeedPlugin->getEnabled()) return false;

		// Make sure the feed type is specified and valid
		$type = array_shift($args);
		$typeMap = array(
			'rss' => 'rss.tpl',
			'rss2' => 'rss2.tpl',
			'atom' => 'atom.tpl'
		);
		$mimeTypeMap = array(
			'rss' => 'application/rdf+xml',
			'rss2' => 'application/rss+xml',
			'atom' => 'application/atom+xml'
		);
		if (!isset($typeMap[$type])) return false;

		// Get limit setting from web feeds plugin
		$displayItems = $webFeedPlugin->getSetting($journal->getId(), 'displayItems');
		$recentItems = (int) $webFeedPlugin->getSetting($journal->getId(), 'recentItems');

		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		if ($displayItems == 'recent' && $recentItems > 0) {
			import('lib.pkp.classes.db.DBResultRange');
			$rangeInfo = new DBResultRange($recentItems, 1);
			$publishedArticleObjects =& $publishedArticleDao->getPublishedArticlesByJournalId($journal->getId(), $rangeInfo, true);
			$publishedArticles = array();
			while ($publishedArticle = $publishedArticleObjects->next()) {
				$publishedArticles[]['articles'][] = $publishedArticle;
			}
		} else {
			$publishedArticles =& $publishedArticleDao->getPublishedArticlesInSections($issue->getId(), true);
		}

		$versionDao = DAORegistry::getDAO('VersionDAO');
		$version = $versionDao->getCurrentVersion();

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('ojsVersion', $version->getVersionString());
		$templateMgr->assign('publishedArticles', $publishedArticles);
		$templateMgr->assign('journal', $journal);
		$templateMgr->assign('issue', $issue);
		$templateMgr->assign('showToc', true);

		$templateMgr->display($this->getTemplatePath() . $typeMap[$type], $mimeTypeMap[$type]);

		return true;
	}
}

?>
