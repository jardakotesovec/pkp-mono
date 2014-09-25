<?php

/**
 * @file StaticPagesHandler.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.staticPages
 * @class StaticPagesHandler
 * Find static page content and display it when requested.
 */

import('classes.handler.Handler');

class StaticPagesHandler extends Handler {
	/** @var StaticPagesPlugin The static pages plugin */
	static $plugin;

	/**
	 * Set the static pages plugin symbolic name.
	 * @param $plugin StaticPagesPlugin
	 */
	static function setPlugin($plugin) {
		self::$plugin = $plugin;
	}

	/**
	 * Handle index request (redirect to "view")
	 * @param $args array Arguments array.
	 * @param $request PKPRequest Request object.
	 */
	function index($args, $request) {
		$request->redirect(null, null, 'view', $request->getRequestedOp());
	}

	/**
	 * Handle view page request (redirect to "view")
	 * @param $args array Arguments array.
	 * @param $request PKPRequest Request object.
	 */
	function view($args, $request) {
		$path = array_shift($args);

		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_COMMON, LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_PKP_USER);
		$context = $request->getContext();
		$contextId = $context?$context->getId():CONTEXT_ID_NONE;

		$templateMgr = TemplateManager::getManager($request);

		// Get the requested page
		$staticPagesDao = DAORegistry::getDAO('StaticPagesDAO');
		$staticPage = $staticPagesDao->getByPath($contextId, $path);
		if (!$staticPage) {
			$request->redirect(null, 'index');
		}

		// Assign the template vars needed and display
		$templateMgr->assign('title', $staticPage->getLocalizedTitle());
		$templateMgr->assign('content',  $staticPage->getLocalizedContent());

		$templateMgr->display(self::$plugin->getTemplatePath() . 'content.tpl');
	}
}

?>
