<?php

/**
 * @defgroup pages_reviewer
 */

/**
 * @file pages/reviewer/index.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_reviewer
 * @brief Handle requests for reviewer functions.
 *
 */

switch ($op) {
	//
	// Submission Tracking
	//
	case 'submission':
	case 'saveStep':
	case 'showDeclineReview':
	case 'saveDeclineReview':
		define('HANDLER_CLASS', 'ReviewHandler');
		import('pages.reviewer.ReviewHandler');
		break;
}

?>
