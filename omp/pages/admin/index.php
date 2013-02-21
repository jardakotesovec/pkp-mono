<?php

/**
 * @defgroup pages_admin
 */

/**
 * @file pages/admin/index.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_admin
 * @brief Handle requests for site administration functions.
 *
 */

switch ($op) {
	//
	// Site setup
	//
	case 'siteSetup':
	case 'saveSettings':
		define('HANDLER_CLASS', 'AdminSettingsHandler');
		import('pages.admin.AdminSettingsHandler');
		break;
	//
	// Press Management
	//
	case 'contexts':
		import('pages.admin.AdminContextHandler');
		define('HANDLER_CLASS', 'AdminContextHandler');
		break;
	//
	// Merge users
	//
	case 'mergeUsers':
		import('lib.pkp.pages.admin.AdminPeopleHandler');
		define('HANDLER_CLASS', 'AdminPeopleHandler');
		break;
	//
	// Administrative functions
	//
	case 'systemInfo':
	case 'phpinfo':
	case 'expireSessions':
	case 'clearTemplateCache':
	case 'clearDataCache':
		import('lib.pkp.pages.admin.AdminFunctionsHandler');
		define('HANDLER_CLASS', 'AdminFunctionsHandler');
		break;
	//
	// Index and administration settings page
	//
	case 'index':
	case 'settings':
		define('HANDLER_CLASS', 'AdminHandler');
		import('lib.pkp.pages.admin.AdminHandler');
		break;
}

?>
