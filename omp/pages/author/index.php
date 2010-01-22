<?php

/**
 * @defgroup pages_author
 */
 
/**
 * @file pages/author/index.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_author
 * @brief Handle requests for press author functions. 
 *
 */

// $Id$

switch ($op) {
	//
	// Monograph Submission
	//
	case 'submit':
	case 'saveSubmit':
	case 'expediteSubmission':
		import('pages.author.SubmitHandler');
		define('HANDLER_CLASS', 'SubmitHandler');
		break;
	//
	// Submission Tracking
	//
	case 'deleteMonographFile':
	case 'deleteSubmission':
	case 'submission':
	case 'submissionReview':
	case 'submissionEditing':
	case 'uploadRevisedVersion':
	case 'viewMetadata':
	case 'saveMetadata':
	case 'removeMonographCoverPage':
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
	case 'index':
	case 'instructions':
		define('HANDLER_CLASS', 'AuthorHandler');
		import('pages.author.AuthorHandler');
		break;
}
?>
