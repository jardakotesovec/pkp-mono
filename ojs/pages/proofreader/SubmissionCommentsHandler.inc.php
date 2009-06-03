<?php

/**
 * @file SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionCommentsHandler
 * @ingroup pages_proofreader
 *
 * @brief Handle requests for submission comments.
 */

// $Id$


import('pages.proofreader.SubmissionProofreadHandler');

class SubmissionCommentsHandler extends ProofreaderHandler {
	/** comment associated with the request **/
	var $comment;

	/**
	 * Constructor
	 **/
	function SubmissionCommentsHandler() {
		parent::ProofreaderHandler();
	}

	/**
	 * View proofread comments.
	 */
	function viewProofreadComments($args) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = $args[0];

		$submissionProofreadHandler =& new SubmissionProofreadHandler();
		$submissionProofreadHandler->validate($articleId);
		$submission =& $submissionProofreadHandler->submission;
		ProofreaderAction::viewProofreadComments($submission);
	}

	/**
	 * Post proofread comment.
	 */
	function postProofreadComment() {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = Request::getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		$submissionProofreadHandler =& new SubmissionProofreadHandler();
		$submissionProofreadHandler->validate($articleId);
		$submission =& $submissionProofreadHandler->submission;

		if (ProofreaderAction::postProofreadComment($submission, $emailComment)) {
			ProofreaderAction::viewProofreadComments($submission);
		}
	}

	/**
	 * View layout comments.
	 */
	function viewLayoutComments($args) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = $args[0];

		$submissionProofreadHandler =& new SubmissionProofreadHandler();
		$submissionProofreadHandler->validate($articleId);
		$submission =& $submissionProofreadHandler->submission;
		ProofreaderAction::viewLayoutComments($submission);

	}

	/**
	 * Post layout comment.
	 */
	function postLayoutComment() {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = Request::getUserVar('articleId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		$submissionProofreadHandler =& new SubmissionProofreadHandler();
		$submissionProofreadHandler->validate($articleId);
		$submission =& $submissionProofreadHandler->submission;
		if (ProofreaderAction::postLayoutComment($submission, $emailComment)) {
			ProofreaderAction::viewLayoutComments($submission);
		}

	}

	/**
	 * Edit comment.
	 */
	function editComment($args) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = $args[0];
		$commentId = $args[1];

		$submissionProofreadHandler =& new SubmissionProofreadHandler();
		$submissionProofreadHandler->validate($articleId);
		$submission =& $submissionProofreadHandler->submission;
		list($comment) = SubmissionCommentsHandler::validate($commentId);
		ProofreaderAction::editComment($submission, $comment);

	}

	/**
	 * Save comment.
	 */
	function saveComment() {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = Request::getUserVar('articleId');
		$commentId = Request::getUserVar('commentId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		$submissionProofreadHandler =& new SubmissionProofreadHandler();
		$submissionProofreadHandler->validate($articleId);
		$submission =& $submissionProofreadHandler->submission;
		$this->validate($commentId);
		$comment =& $this->comment;
		ProofreaderAction::saveComment($submission, $comment, $emailComment);

		// Determine which page to redirect back to.
		$commentPageMap = array(
			COMMENT_TYPE_PROOFREAD => 'viewProofreadComments',
			COMMENT_TYPE_LAYOUT => 'viewLayoutComments'
		);

		// Redirect back to initial comments page
		Request::redirect(null, null, $commentPageMap[$comment->getCommentType()], $articleId);
	}

	/**
	 * Delete comment.
	 */
	function deleteComment($args) {
		$this->validate();
		$this->setupTemplate(true);

		$articleId = $args[0];
		$commentId = $args[1];

		$articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
		$comment =& $articleCommentDao->getArticleCommentById($commentId);

		$submissionProofreadHandler =& new SubmissionProofreadHandler();
		$submissionProofreadHandler->validate($articleId);
		$submission =& $submissionProofreadHandler->submission;
		$this->validate($commentId);
		$comment =& $this->comment;
		ProofreaderAction::deleteComment($commentId);

		// Determine which page to redirect back to.
		$commentPageMap = array(
			COMMENT_TYPE_PROOFREAD => 'viewProofreadComments',
			COMMENT_TYPE_LAYOUT => 'viewLayoutComments'
		);

		// Redirect back to initial comments page
		Request::redirect(null, null, $commentPageMap[$comment->getCommentType()], $articleId);
	}


	//
	// Validation
	//

	/**
	 * Validate that the user is the author of the comment.
	 */
	function validate($commentId) {
		parent::validate();

		$isValid = true;

		$articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
		$user =& Request::getUser();

		$comment =& $articleCommentDao->getArticleCommentById($commentId);

		if ($comment == null) {
			$isValid = false;

		} else if ($comment->getAuthorId() != $user->getId()) {
			$isValid = false;
		}

		if (!$isValid) {
			Request::redirect(null, Request::getRequestedPage());
		}

		$this->comment =& $comment;
		return true;
	}
}

?>
