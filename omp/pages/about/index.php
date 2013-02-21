<?php

/**
 * @defgroup pages_about
 */

/**
 * @file pages/about/index.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_about
 * @brief Handle requests for about the press functions.
 *
 */

switch ($op) {
	case 'contact':
	case 'description':
	case 'sponsorship':
	case 'editorialTeam':
	case 'editorialPolicies':
	case 'submissions':
		define('HANDLER_CLASS', 'AboutContextHandler');
		import('pages.about.AboutContextHandler');
		break;
	case 'aboutThisPublishingSystem':
		define('HANDLER_CLASS', 'AboutSiteHandler');
		import('pages.about.AboutSiteHandler');
		break;
}

?>
