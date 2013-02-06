<?php

/**
 * @file pages/admin/AdminHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminHandler
 * @ingroup pages_admin
 *
 * @brief Handle requests for site administration functions.
 */

import('classes.handler.Handler');

class AdminHandler extends Handler {
	/**
	 * Constructor
	 */
	function AdminHandler() {
		parent::Handler();

		$this->addRoleAssignment(
			array(ROLE_ID_SITE_ADMIN),
			array('index', 'settings')
		);
	}

	/**
	 * @see PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.PKPSiteAccessPolicy');
		$this->addPolicy(new PKPSiteAccessPolicy($request, null, $roleAssignments));
		$returner = parent::authorize($request, $args, $roleAssignments);

		// Make sure user is in a context. Otherwise, redirect.
		$context = $request->getContext();
		$router = $request->getRouter();
		$requestedOp = $router->getRequestedOp($request);

		// The only operation logged users may access outside a context
		// context is to create contexts.
		if (!$context && $requestedOp != 'contexts') {

			// Try to find a context that user has access to.
			$targetContext = $this->getTargetContext($request);
			if ($targetContext) {
				$url = $router->url($request, $targetContext->getPath(), 'admin', $requestedOp);
			} else {
				$url = $router->url($request, 'index');
			}
			$request->redirectUrl($url);
		}

		if ($requestedOp == 'settings') {
			$contextDao =& $context->getDAO();
			$contextFactory = $contextDao->getAll();
			if ($contextFactory->getCount() == 1) {
				// Don't let users access site settings in a single context installation.
				// In that case, those settings are available under management or are not
				// relevant (like site appearance).
				return false;
			}
		}

		return $returner;
	}

	/**
	 * Display site admin index page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, &$request) {
		$templateMgr = TemplateManager::getManager($request);
		$workingContexts = $this->getWorkingContexts($request);
		$templateMgr->assign('multipleContexts', $workingContexts->getCount() > 0);
		$templateMgr->display('admin/index.tpl');
	}

	/**
	 * Display the administration settings page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function settings($args, &$request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->display('admin/settings.tpl');
	}

	/**
	 * Initialize the handler.
	 */
	function initialize(&$request, $args = null) {
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_ADMIN, LOCALE_COMPONENT_APP_MANAGER, LOCALE_COMPONENT_APP_ADMIN, LOCALE_COMPONENT_APP_COMMON);
		return parent::initialize($request, $args);
	}
}

?>
