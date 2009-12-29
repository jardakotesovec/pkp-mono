<?php

/**
 * @defgroup pages_login
 */
 
/**
 * @file index.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Handle login/logout requests.
 *
 * @ingroup pages_login
 */

// $Id$


switch ($op) {
	case 'signInAsUser':
	case 'signOutAsUser':
	case 'index':
	case 'signIn':
	case 'signOut':
	case 'lostPassword':
	case 'requestResetPassword':
	case 'resetPassword':
	case 'changePassword':
	case 'savePassword':
		define('HANDLER_CLASS', 'LoginHandler');
		import('pages.login.LoginHandler');
		break;
}

?>
