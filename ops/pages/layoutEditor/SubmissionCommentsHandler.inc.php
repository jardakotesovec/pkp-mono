<?php

/**
 * SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.layoutEditor
 *
 * Handle requests for submission comments. 
 *
 * $Id$
 */

class SubmissionCommentsHandler extends LayoutEditorHandler {
	
	/**
	 * View layout comments.
	 */
	function viewLayoutComments($args) {
		LayoutEditorHandler::validate();
		LayoutEditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		
		SubmissionLayoutHandler::validate($articleId);
		LayoutEditorAction::viewLayoutComments($articleId);
	
	}
	
	/**
	 * Post layout comment.
	 */
	function postLayoutComment() {
		LayoutEditorHandler::validate();
		LayoutEditorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		SubmissionLayoutHandler::validate($articleId);
		LayoutEditorAction::postLayoutComment($articleId, $emailComment);
		
		LayoutEditorAction::viewLayoutComments($articleId);
	
	}

	/**
	 * View proofread comments.
	 */
	function viewProofreadComments($args) {
		LayoutEditorHandler::validate();
		LayoutEditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		
		SubmissionLayoutHandler::validate($articleId);
		LayoutEditorAction::viewProofreadComments($articleId);
	
	}
	
	/**
	 * Post proofread comment.
	 */
	function postProofreadComment() {
		LayoutEditorHandler::validate();
		LayoutEditorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		SubmissionLayoutHandler::validate($articleId);
		LayoutEditorAction::postProofreadComment($articleId, $emailComment);
		
		LayoutEditorAction::viewProofreadComments($articleId);
	
	}

	/**
	 * Edit comment.
	 */
	function editComment($args) {
		LayoutEditorHandler::validate();
		LayoutEditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		$commentId = $args[1];
		
		SubmissionLayoutHandler::validate($articleId);
		SubmissionCommentsHandler::validate($commentId);
		LayoutEditorAction::editComment($commentId);

	}
	
	/**
	 * Save comment.
	 */
	function saveComment() {
		LayoutEditorHandler::validate();
		LayoutEditorHandler::setupTemplate(true);
		
		$articleId = Request::getUserVar('articleId');
		$commentId = Request::getUserVar('commentId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		SubmissionLayoutHandler::validate($articleId);
		SubmissionCommentsHandler::validate($commentId);
		LayoutEditorAction::saveComment($commentId, $emailComment);

		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$comment = &$articleCommentDao->getArticleCommentById($commentId);
		
		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			Request::redirect(sprintf('%s/viewLayoutComments/%d', Request::getRequestedPage(), $articleId));
		} else if ($comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			Request::redirect(sprintf('%s/viewProofreadComments/%d', Request::getRequestedPage(), $articleId));
		}
	}
	
	/**
	 * Delete comment.
	 */
	function deleteComment($args) {
		LayoutEditorHandler::validate();
		LayoutEditorHandler::setupTemplate(true);
		
		$articleId = $args[0];
		$commentId = $args[1];
		
		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$comment = &$articleCommentDao->getArticleCommentById($commentId);
		
		SubmissionLayoutHandler::validate($articleId);
		SubmissionCommentsHandler::validate($commentId);
		LayoutEditorAction::deleteComment($commentId);
		
		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			Request::redirect(sprintf('%s/viewLayoutComments/%d', Request::getRequestedPage(), $articleId));
		} else if ($comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			Request::redirect(sprintf('%s/viewProofreadComments/%d', Request::getRequestedPage(), $articleId));
		}
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
		
		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$user = &Request::getUser();
		
		$comment = &$articleCommentDao->getArticleCommentById($commentId);

		if ($comment == null) {
			$isValid = false;
			
		} else if ($comment->getAuthorId() != $user->getUserId()) {
			$isValid = false;
		}
		
		if (!$isValid) {
			Request::redirect(Request::getRequestedPage());
		}
	}
}
?>
