<?php

/**
 * @defgroup pages_workflow Workflow Pages
 */

/**
 * @file pages/workflow/index.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_workflow
 * @brief Handle requests for workflow functions.
 *
 */

switch ($op) {
	case 'access':
	case 'index':
	case 'submission':
	case 'externalReview':
	case 'editorial':
	case 'production':
	case 'galleysTab':
	case 'editorDecisionActions':
	case 'submissionHeader':
	case 'submissionProgressBar':
	case 'expedite':
		define('HANDLER_CLASS', 'WorkflowHandler');
		import('pages.workflow.WorkflowHandler');
		break;
	case 'fetchGalley':
		define('HANDLER_CLASS', 'GalleyHandler');
		import('pages.workflow.GalleyHandler');
		break;
}

?>
