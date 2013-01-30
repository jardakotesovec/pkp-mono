<?php

/**
 * @file pages/management/SettingsHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SettingsHandler
 * @ingroup pages_management
 *
 * @brief Handle requests for settings pages.
 */

// Import the base ManagementHandler.
import('pages.management.ManagementHandler');

class SettingsHandler extends ManagementHandler {
	/**
	 * Constructor.
	 */
	function SettingsHandler() {
		parent::Handler();
		$this->addRoleAssignment(
			ROLE_ID_MANAGER,
			array(
				'index',
				'settings',
			)
		);
	}


	//
	// Public handler methods
	//
	/**
	 * Display settings index page.
	 * @param $request PKPRequest
	 * @param $args array
	 */
	function index($args, &$request) {
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate($request);
		$templateMgr->display('management/settings/index.tpl');
	}

	/**
	 * Route to other settings operations.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function settings($args, &$request) {
		$path = array_shift($args);
		switch($path) {
			case 'index':
				$this->index($args, $request);
				break;
			case 'journal':
				$this->journal($args, $request);
				break;
			case 'website':
				$this->website($args, $request);
				break;
			default:
				assert(false);
		}
	}

	/**
	 * Display The Journal page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function journal($args, &$request) {
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate($request);
		$templateMgr->display('management/settings/journal.tpl');
	}

	/**
	 * Display website page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function website($args, &$request) {
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate($request);
		$journal = $request->getJournal();
		$templateMgr->assign('enableAnnouncements', $journal->getSetting('enableAnnouncements'));
		$templateMgr->display('management/settings/website.tpl');
	}
}

?>
