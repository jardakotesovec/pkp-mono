<?php

/**
 * @file pages/sectionEditor/SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionCommentsHandler
 * @ingroup pages_sectionEditor
 *
 * @brief Handle requests for submission comments.
 */

import('pages.sectionEditor.SubmissionEditHandler');

class SubmissionCommentsHandler extends SectionEditorHandler {
	/** comment associated with the request **/
	var $comment;

	/**
	 * Constructor
	 **/
	function SubmissionCommentsHandler() {
		parent::SectionEditorHandler();
	}

	/**
	 * View peer review comments.
	 */
	function viewPeerReviewComments($args) {
		$articleId = (int) array_shift($args);
		$reviewId = (int) array_shift($args);

		$this->validate($articleId);
		$this->setupTemplate(true);

		SectionEditorAction::viewPeerReviewComments($this->submission, $reviewId);
	}

	/**
	 * Post peer review comments.
	 */
	function postPeerReviewComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$reviewId = (int) $request->getUserVar('reviewId');

		$this->validate($articleId);
		$this->setupTemplate(true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		if (SectionEditorAction::postPeerReviewComment($this->submission, $reviewId, $emailComment, $request)) {
			SectionEditorAction::viewPeerReviewComments($this->submission, $reviewId);
		}
	}

	/**
	 * View editor decision comments.
	 */
	function viewEditorDecisionComments($args, $request) {
		$articleId = (int) array_shift($args);

		$this->validate($articleId);
		$this->setupTemplate(true);

		SectionEditorAction::viewEditorDecisionComments($this->submission);
	}

	/**
	 * Post peer review comments.
	 */
	function postEditorDecisionComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId);

		$this->setupTemplate(true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		if (SectionEditorAction::postEditorDecisionComment($this->submission, $emailComment, $request)) {
			SectionEditorAction::viewEditorDecisionComments($this->submission);
		}
	}

	/**
	 * Blind CC the reviews to reviewers.
	 */
	function blindCcReviewsToReviewers($args, $request) {
		$articleId = $request->getUserVar('articleId');
		$this->validate($articleId);

		$send = $request->getUserVar('send')?true:false;
		$inhibitExistingEmail = $request->getUserVar('blindCcReviewers')?true:false;

		if (!$send) $this->setupTemplate(true, $articleId, 'editing');
		if (SectionEditorAction::blindCcReviewsToReviewers($this->submission, $send, $inhibitExistingEmail, $request)) {
			$request->redirect(null, null, 'submissionReview', $articleId);
		}
	}

	/**
	 * View copyedit comments.
	 */
	function viewCopyeditComments($args, $request) {
		$articleId = (int) array_shift($args);

		$this->validate($articleId);
		$this->setupTemplate(true);

		SectionEditorAction::viewCopyeditComments($this->submission);
	}

	/**
	 * Post copyedit comment.
	 */
	function postCopyeditComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');

		$this->validate($articleId);
		$this->setupTemplate(true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		if (SectionEditorAction::postCopyeditComment($this->submission, $emailComment, $request)) {
			SectionEditorAction::viewCopyeditComments($this->submission);
		}
	}

	/**
	 * View layout comments.
	 */
	function viewLayoutComments($args, $request) {
		$articleId = (int) array_shift($args);

		$this->validate($articleId);
		$this->setupTemplate(true);

		SectionEditorAction::viewLayoutComments($this->submission, $request);
	}

	/**
	 * Post layout comment.
	 */
	function postLayoutComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');

		$this->validate($articleId);
		$this->setupTemplate(true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		if (SectionEditorAction::postLayoutComment($this->submission, $emailComment, $request)) {
			SectionEditorAction::viewLayoutComments($this->submission);
		}
	}

	/**
	 * View proofread comments.
	 */
	function viewProofreadComments($args, $request) {
		$articleId = (int) array_shift($args);

		$this->validate($articleId);
		$this->setupTemplate(true);

		SectionEditorAction::viewProofreadComments($this->submission);
	}

	/**
	 * Post proofread comment.
	 */
	function postProofreadComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');

		$this->validate($articleId);
		$this->setupTemplate(true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		if (SectionEditorAction::postProofreadComment($this->submission, $emailComment, $request)) {
			SectionEditorAction::viewProofreadComments($this->submission);
		}
	}

	/**
	 * Email an editor decision comment.
	 */
	function emailEditorDecisionComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$this->validate($articleId);

		$this->setupTemplate(true);
		if (SectionEditorAction::emailEditorDecisionComment($this->submission, $request->getUserVar('send'), $request)) {
			if ($request->getUserVar('blindCcReviewers')) {
				SubmissionCommentsHandler::blindCcReviewsToReviewers($args, $request);
			} else {
				$request->redirect(null, null, 'submissionReview', array($articleId));
			}
		}
	}

	/**
	 * Edit comment.
	 */
	function editComment($args, $request) {
		$articleId = (int) array_shift($args);
		$commentId = (int) array_shift($args);

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate($articleId);
		$comment =& $this->comment;

		$this->setupTemplate(true);

		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			$request->redirect(null, $request->getRequestedPage());
		}

		SectionEditorAction::editComment($this->submission, $comment);
	}

	/**
	 * Save comment.
	 */
	function saveComment($args, $request) {
		$articleId = (int) $request->getUserVar('articleId');
		$commentId = (int) $request->getUserVar('commentId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate($articleId);
		$comment =& $this->comment;

		$this->setupTemplate(true);

		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			$request->redirect(null, $request->getRequestedPage());
		}

		// Save the comment.
		SectionEditorAction::saveComment($this->submission, $comment, $emailComment, $request);

		$articleCommentDao =& DAORegistry::getDAO('ArticleCommentDAO');
		$comment =& $articleCommentDao->getArticleCommentById($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			$request->redirect(null, null, 'viewPeerReviewComments', array($articleId, $comment->getAssocId()));
		} else if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			$request->redirect(null, null, 'viewEditorDecisionComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
			$request->redirect(null, null, 'viewCopyeditComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			$request->redirect(null, null, 'viewLayoutComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			$request->redirect(null, null, 'viewProofreadComments', $articleId);
		}
	}

	/**
	 * Delete comment.
	 */
	function deleteComment($args, $request) {
		$articleId = (int) array_shift($args);
		$commentId = (int) array_shift($args);

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate($articleId);
		$comment =& $this->comment;

		$this->setupTemplate(true);

		SectionEditorAction::deleteComment($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			$request->redirect(null, null, 'viewPeerReviewComments', array($articleId, $comment->getAssocId()));
		} else if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			$request->redirect(null, null, 'viewEditorDecisionComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
			$request->redirect(null, null, 'viewCopyeditComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			$request->redirect(null, null, 'viewLayoutComments', $articleId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			$request->redirect(null, null, 'viewProofreadComments', $articleId);
		}
	}
}

?>
