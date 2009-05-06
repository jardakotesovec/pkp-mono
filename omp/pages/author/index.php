<?php

/**
 * @defgroup pages_author
 */
 
/**
 * @file pages/author/index.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_author
 * @brief Handle requests for journal author functions. 
 *
 */

// $Id$

switch ($op) {
	//
	// Article Submission
	//
	case 'submit':
	case 'saveSubmit':
	case 'submitSuppFile':
	case 'saveSubmitSuppFile':
	case 'deleteSubmitSuppFile':
	case 'expediteSubmission':
		import('pages.author.SubmitHandler');
		define('HANDLER_CLASS', 'SubmitHandler');
		break;
	//
	// Submission Tracking
	//
	case 'deleteArticleFile':
	case 'deleteSubmission':
	case 'submission':
	case 'editSuppFile':
	case 'setSuppFileVisibility':
	case 'saveSuppFile':
	case 'addSuppFile':
	case 'submissionReview':
	case 'submissionEditing':
	case 'uploadRevisedVersion':
	case 'viewMetadata':
	case 'saveMetadata':
	case 'removeArticleCoverPage':
	case 'uploadCopyeditVersion':
	case 'completeAuthorCopyedit':
	//
	// Misc.
	//
	case 'downloadFile':
	case 'viewFile':
	case 'download':
	//
	// Proofreading Actions
	//
	case 'authorProofreadingComplete':
	case 'proofGalley':
	case 'proofGalleyTop':
	case 'proofGalleyFile':
	// 
	// Payment Actions
	//
	case 'paySubmissionFee':
	case 'payPublicationFee':
		import('pages.author.TrackSubmissionHandler');
		define('HANDLER_CLASS', 'TrackSubmissionHandler');
		break;
	//
	// Submission Comments
	//
	case 'viewEditorDecisionComments':
	case 'viewCopyeditComments':
	case 'postCopyeditComment':
	case 'emailEditorDecisionComment':
	case 'viewProofreadComments':
	case 'viewLayoutComments':
	case 'postLayoutComment':
	case 'postProofreadComment':
	case 'editComment':
	case 'saveComment':
	case 'deleteComment':
		import('pages.author.SubmissionCommentsHandler');
		define('HANDLER_CLASS', 'SubmissionCommentsHandler');
		break;
	default:
		define('HANDLER_CLASS', 'AuthorHandler');
		import('pages.author.AuthorHandler');
		break;
}
?>
