<?php

/**
 * @file pages/management/SettingsHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SettingsHandler
 * @ingroup pages_management
 *
 * @brief Handle requests for settings pages.
 */

// Import the base ManagementHandler.
import('lib.pkp.pages.management.ManagementHandler');

class SettingsHandler extends ManagementHandler {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_SITE_ADMIN),
			array(
				'access',
			)
		);
		$this->addRoleAssignment(
			ROLE_ID_MANAGER,
			array(
				'settings',
			)
		);
	}

	/**
	 * Add the workflow settings page
	 *
	 * @param $args array
	 * @param $request Request
	 */
	function workflow($args, $request) {
		parent::workflow($args, $request);
		TemplateManager::getManager($request)->display('management/workflow.tpl');
	}

	/**
	 * Add the distribution settings page
	 *
	 * @param $args array
	 * @param $request Request
	 */
	function distribution($args, $request) {
		parent::distribution($args, $request);
		TemplateManager::getManager($request)->display('management/distribution.tpl');
	}
}
