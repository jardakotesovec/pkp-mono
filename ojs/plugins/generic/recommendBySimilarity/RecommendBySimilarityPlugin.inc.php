<?php

/**
 * @file plugins/generic/recommendBySimilarity/RecommendBySimilarityPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RecommendBySimilarityPlugin
 * @ingroup plugins_generic_recommendBySimilarity
 *
 * @brief Plugin to recommend similar articles.
 */


import('lib.pkp.classes.plugins.GenericPlugin');

define('RECOMMEND_BY_SIMILARITY_PLUGIN_COUNT', 10);

class RecommendBySimilarityPlugin extends GenericPlugin {

	/**
	 * Constructor
	 */
	function RecommendBySimilarityPlugin() {
		parent::GenericPlugin();
	}


	//
	// Implement template methods from PKPPlugin.
	//
	/**
	 * @see PKPPlugin::register()
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return $success;

		if ($success && $this->getEnabled()) {
			HookRegistry::register('Templates::Article::Footer::PageFooter', array($this, 'callbackTemplateArticlePageFooter'));
		}
		return $success;
	}

	/**
	 * @see PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.recommendBySimilarity.displayName');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.recommendBySimilarity.description');
	}

	/**
	 * @see PKPPlugin::getTemplatePath()
	 */
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	}


	//
	// View level hook implementations.
	//
	/**
	 * @see templates/article/footer.tpl
	 */
	function callbackTemplateArticlePageFooter($hookName, $params) {
		$smarty =& $params[1];
		$output =& $params[2];

		// Identify similarity terms for the given article.
		$displayedArticle = $smarty->get_template_vars('article');
		$articleId = $displayedArticle->getId();
		import('classes.search.ArticleSearch');
		$articleSearch = new ArticleSearch();
		$searchTerms = $articleSearch->getSimilarityTerms($articleId);
		if (empty($searchTerms)) return false;

		// If we got similarity terms then execute a search with...
		// ... request, journal and error messages, ...
		$request = PKPApplication::getRequest();
		$router = $request->getRouter();
		$journal = $router->getContext($request);
		$error = null;
		// ... search keywords ...
		$query = implode(' ', $searchTerms);
		$keywords = array(null => $query);
		// ... and pagination.
		$rangeInfo = Handler::getRangeInfo($request, 'articlesBySimilarity');
		$rangeInfo->setCount(RECOMMEND_BY_SIMILARITY_PLUGIN_COUNT);

		$results = $articleSearch->retrieveResults($request, $journal, $keywords, $error, null, null, $rangeInfo, array($articleId));
		$smarty->assign('articlesBySimilarity', $results);
		$smarty->assign('articlesBySimilarityQuery', $query);

		$output .= $smarty->fetch($this->getTemplatePath() . 'articleFooter.tpl');
		return false;
	}
}
?>
