<?php

/**
 * @defgroup pages_sectionEditor
 */
 
/**
 * @file pages/sectionEditor/index.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_sectionEditor
 * @brief Handle requests for section editor functions. 
 *
 */

// $Id$


switch ($op) {
	//
	// Submission Tracking
	//
	case 'enrollSearch':
	case 'createReviewer':
	case 'suggestUsername':
	case 'enroll':
	case 'submission':
	case 'submissionRegrets':
	case 'submissionReview':
	case 'submissionEditing':
	case 'submissionProduction':
	case 'submissionHistory':
	case 'changeSection':
	case 'recordDecision':
	case 'selectReviewer':
	case 'notifyReviewer':
	case 'notifyAllReviewers':
	case 'userProfile':
	case 'clearReview':
	case 'cancelReview':
	case 'remindReviewer':
	case 'thankReviewer':
	case 'rateReviewer':
	case 'confirmReviewForReviewer':
	case 'uploadReviewForReviewer':
	case 'enterReviewerRecommendation':
	case 'makeReviewerFileViewable':
	case 'setDueDate':
	case 'viewMetadata':
	case 'saveMetadata':
	case 'removeArticleCoverPage':
	case 'editorReview':
	case 'selectCopyeditor':
	case 'notifyCopyeditor':
	case 'initiateCopyedit':
	case 'thankCopyeditor':
	case 'notifyAuthorCopyedit':
	case 'thankAuthorCopyedit':
	case 'notifyFinalCopyedit':
	case 'thankFinalCopyedit':
	case 'selectCopyeditRevisions':
	case 'uploadReviewVersion':
	case 'uploadCopyeditVersion':
	case 'completeCopyedit':
	case 'completeFinalCopyedit':
	case 'addSuppFile':
	case 'setSuppFileVisibility':
	case 'editSuppFile':
	case 'saveSuppFile':
	case 'deleteSuppFile':
	case 'deleteArticleFile':
	case 'archiveSubmission':
	case 'unsuitableSubmission':
	case 'restoreToQueue':
	case 'updateAcquisitionsArrangement':
	case 'updateCommentsStatus':
	//
	// Layout Editing
	//
	case 'deleteArticleImage':
	case 'uploadLayoutFile':
	case 'uploadLayoutVersion':
	case 'assignProductionEditor':
	case 'notifyLayoutEditor':
	case 'thankLayoutEditor':
	case 'uploadGalley':
	case 'editGalley':
	case 'saveGalley':
	case 'orderGalley':
	case 'deleteGalley':
	case 'proofGalley':
	case 'proofGalleyTop':
	case 'proofGalleyFile':
	case 'uploadSuppFile':
	case 'orderSuppFile':
	//
	// Submission History
	//
	case 'submissionEventLog':
	case 'submissionEventLogType':
	case 'clearSubmissionEventLog':
	case 'submissionEmailLog':
	case 'submissionEmailLogType':
	case 'clearSubmissionEmailLog':
	case 'addSubmissionNote':
	case 'removeSubmissionNote':
	case 'updateSubmissionNote':
	case 'clearAllSubmissionNotes':
	case 'submissionNotes':
	//
	// Misc.
	//
	case 'downloadFile':
	case 'viewFile':
	// Submission Review Form
	case 'clearReviewForm':
	case 'selectReviewForm':
	case 'previewReviewForm':
	case 'viewReviewFormResponse':
	/** Proof Assignment Functions */
	case 'selectProofreader':
	case 'notifyAuthorProofreader':
	case 'thankAuthorProofreader':
	case 'editorInitiateProofreader':
	case 'editorCompleteProofreader':
	case 'notifyProofreader':
	case 'thankProofreader':
	case 'editorInitiateLayoutEditor':
	case 'editorCompleteLayoutEditor':
	case 'notifyLayoutEditorProofreader':
	case 'thankLayoutEditorProofreader':
	/**
	 * Workflow
	 */
	case 'endWorkflowProcess':
	case 'selectInternalReviewer':
	/**
	 * Scheduling functions
	 */
	case 'scheduleForPublication':	
		import('pages.acquisitionsEditor.SubmissionEditHandler');
		define('HANDLER_CLASS', 'SubmissionEditHandler');
		break;
	//
	// Submission Comments
	//
	case 'viewPeerReviewComments':
	case 'postPeerReviewComment':
	case 'viewEditorDecisionComments':
	case 'blindCcReviewsToReviewers':
	case 'postEditorDecisionComment':
	case 'viewCopyeditComments':
	case 'postCopyeditComment':
	case 'emailEditorDecisionComment':
	case 'viewLayoutComments':
	case 'postLayoutComment':
	case 'viewProofreadComments':
	case 'postProofreadComment':
	case 'editComment':
	case 'saveComment':
	case 'deleteComment':
		import('pages.acquisitionsEditor.SubmissionCommentsHandler');
		define('HANDLER_CLASS', 'SubmissionCommentsHandler');
		break;
	default:
		define('HANDLER_CLASS', 'AcquisitionsEditorHandler');
		import('pages.acquisitionsEditor.AcquisitionsEditorHandler');
		break;
}

?>
