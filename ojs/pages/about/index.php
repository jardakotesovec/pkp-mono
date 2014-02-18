<?php

/**
 * @defgroup pages_about About Pages
 */
 
/**
 * @file pages/about/index.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_about
 * @brief Handle requests for about the journal functions. 
 *
 */

switch($op) {
	case 'description':
	case 'contact':
	case 'subscriptions':
	case 'editorialTeam':
	case 'editorialPolicies':
	case 'submissions':
	case 'memberships':
	case 'sponsorship':
	case 'history':
		define('HANDLER_CLASS', 'AboutContextHandler');
		import('pages.about.AboutContextHandler');
		break;
	case 'aboutThisPublishingSystem':
		define('HANDLER_CLASS', 'AboutSiteHandler');
		import('lib.pkp.pages.about.AboutSiteHandler');
		break;
}

?>
