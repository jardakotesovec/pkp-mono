<?php

/**
 * @file classes/submission/reviewer/ReviewerAction.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerAction
 * @ingroup submission
 *
 * @brief ReviewerAction class.
 */

// $Id$


import('submission.common.Action');

class ReviewerAction extends Action {

	/**
	 * Constructor.
	 */
	function ReviewerAction() {

	}

	/**
	 * Actions.
	 */

	/**
	 * Records whether or not the reviewer accepts the review assignment.
	 * @param $user object
	 * @param $reviewerSubmission object
	 * @param $decline boolean
	 * @param $send boolean
	 */
	function confirmReview($reviewerSubmission, $decline, $send) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$reviewId = $reviewerSubmission->getReviewId();

		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);
		$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return true;

		// Only confirm the review for the reviewer if 
		// he has not previously done so.
		if ($reviewAssignment->getDateConfirmed() == null) {
			import('mail.MonographMailTemplate');
			$email = new MonographMailTemplate($reviewerSubmission, $decline?'REVIEW_DECLINE':'REVIEW_CONFIRM');
			// Must explicitly set sender because we may be here on an access
			// key, in which case the user is not technically logged in
			$email->setFrom($reviewer->getEmail(), $reviewer->getFullName());
			if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
				HookRegistry::call('ReviewerAction::confirmReview', array(&$reviewerSubmission, &$email, $decline));
				if ($email->isEnabled()) {
					$email->setAssoc($decline?ARTICLE_EMAIL_REVIEW_DECLINE:ARTICLE_EMAIL_REVIEW_CONFIRM, ARTICLE_EMAIL_TYPE_REVIEW, $reviewId);
					$email->send();
				}

				$reviewAssignment->setDeclined($decline);
				$reviewAssignment->setDateConfirmed(Core::getCurrentDate());
				$reviewAssignment->stampModified();
				$reviewAssignmentDao->updateObject($reviewAssignment);

				// Add log
				import('article.log.MonographLog');
				import('article.log.MonographEventLogEntry');

				$entry = new MonographEventLogEntry();
				$entry->setMonographId($reviewAssignment->getMonographId());
				$entry->setUserId($reviewer->getUserId());
				$entry->setDateLogged(Core::getCurrentDate());
				$entry->setEventType($decline?ARTICLE_LOG_REVIEW_DECLINE:ARTICLE_LOG_REVIEW_ACCEPT);
				$entry->setLogMessage($decline?'log.review.reviewDeclined':'log.review.reviewAccepted', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $reviewAssignment->getMonographId(), 'round' => $reviewAssignment->getRound()));
				$entry->setAssocType(ARTICLE_LOG_TYPE_REVIEW);
				$entry->setAssocId($reviewAssignment->getReviewId());

				MonographLog::logEventEntry($reviewAssignment->getMonographId(), $entry);

				return true;
			} else {
				if (!Request::getUserVar('continued')) {
					$assignedEditors = $email->ccAssignedEditors($reviewerSubmission->getMonographId());
					$reviewingSectionEditors = $email->toAssignedReviewingSectionEditors($reviewerSubmission->getMonographId());
					if (empty($assignedEditors) && empty($reviewingSectionEditors)) {
						$journal =& Request::getPress();
						$email->addRecipient($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
						$editorialContactName = $journal->getSetting('contactName');
					} else {
						if (!empty($reviewingSectionEditors)) $editorialContact = array_shift($reviewingSectionEditors);
						else $editorialContact = array_shift($assignedEditors);
						$editorialContactName = $editorialContact->getEditorFullName();
					}

					$email->assignParams(array(
						'editorialContactName' => $editorialContactName,
						'reviewerName' => $reviewer->getFullName(),
						'reviewDueDate' => strftime(Config::getVar('general', 'date_format_short'), strtotime($reviewAssignment->getDateDue()))
					));
				}
				$paramArray = array('reviewId' => $reviewId);
				if ($decline) $paramArray['declineReview'] = 1;
				$email->displayEditForm(Request::url(null, 'reviewer', 'confirmReview'), $paramArray);
				return false;
			}
		}
		return true;
	}

	/**
	 * Records the reviewer's submission recommendation.
	 * @param $reviewId int
	 * @param $recommendation int
	 * @param $send boolean
	 */
	function recordRecommendation(&$reviewerSubmission, $recommendation, $send) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		// Check validity of selected recommendation
		$reviewerRecommendationOptions =& ReviewAssignment::getReviewerRecommendationOptions();
		if (!isset($reviewerRecommendationOptions[$recommendation])) return true;

		$reviewAssignment =& $reviewAssignmentDao->getById($reviewerSubmission->getReviewId());
		$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return true;

		// Only record the reviewers recommendation if
		// no recommendation has previously been submitted.
		if ($reviewAssignment->getRecommendation() === null || $reviewAssignment->getRecommendation === '') {
			import('mail.MonographMailTemplate');
			$email = new MonographMailTemplate($reviewerSubmission, 'REVIEW_COMPLETE');
			// Must explicitly set sender because we may be here on an access
			// key, in which case the user is not technically logged in
			$email->setFrom($reviewer->getEmail(), $reviewer->getFullName());

			if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
				HookRegistry::call('ReviewerAction::recordRecommendation', array(&$reviewerSubmission, &$email, $recommendation));
				if ($email->isEnabled()) {
					$email->setAssoc(ARTICLE_EMAIL_REVIEW_COMPLETE, ARTICLE_EMAIL_TYPE_REVIEW, $reviewerSubmission->getReviewId());
					$email->send();
				}

				$reviewAssignment->setRecommendation($recommendation);
				$reviewAssignment->setDateCompleted(Core::getCurrentDate());
				$reviewAssignment->stampModified();
				$reviewAssignmentDao->updateObject($reviewAssignment);

				// Add log
				import('article.log.MonographLog');
				import('article.log.MonographEventLogEntry');

				$entry = new MonographEventLogEntry();
				$entry->setMonographId($reviewAssignment->getMonographId());
				$entry->setUserId($reviewer->getUserId());
				$entry->setDateLogged(Core::getCurrentDate());
				$entry->setEventType(ARTICLE_LOG_REVIEW_RECOMMENDATION);
				$entry->setLogMessage('log.review.reviewRecommendationSet', array('reviewerName' => $reviewer->getFullName(), 'articleId' => $reviewAssignment->getMonographId(), 'round' => $reviewAssignment->getRound()));
				$entry->setAssocType(ARTICLE_LOG_TYPE_REVIEW);
				$entry->setAssocId($reviewAssignment->getReviewId());

				MonographLog::logEventEntry($reviewAssignment->getMonographId(), $entry);
			} else {
				if (!Request::getUserVar('continued')) {
					$assignedEditors = $email->ccAssignedEditors($reviewerSubmission->getMonographId());
					$reviewingSectionEditors = $email->toAssignedReviewingSectionEditors($reviewerSubmission->getMonographId());
					if (empty($assignedEditors) && empty($reviewingSectionEditors)) {
						$journal =& Request::getPress();
						$email->addRecipient($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
						$editorialContactName = $journal->getSetting('contactName');
					} else {
						if (!empty($reviewingSectionEditors)) $editorialContact = array_shift($reviewingSectionEditors);
						else $editorialContact = array_shift($assignedEditors);
						$editorialContactName = $editorialContact->getEditorFullName();
					}

					$reviewerRecommendationOptions =& ReviewAssignment::getReviewerRecommendationOptions();

					$email->assignParams(array(
						'editorialContactName' => $editorialContactName,
						'reviewerName' => $reviewer->getFullName(),
						'articleTitle' => strip_tags($reviewerSubmission->getMonographTitle()),
						'recommendation' => Locale::translate($reviewerRecommendationOptions[$recommendation])
					));
				}

				$email->displayEditForm(Request::url(null, 'reviewer', 'recordRecommendation'),
					array('reviewId' => $reviewerSubmission->getReviewId(), 'recommendation' => $recommendation)
				);
				return false;
			}
		}
		return true;
	}

	/**
	 * Upload the annotated version of an article.
	 * @param $reviewId int
	 */
	function uploadReviewerVersion($reviewId) {
		import("file.MonographFileManager");
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');		
		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);

		$articleFileManager = new MonographFileManager($reviewAssignment->getMonographId());

		// Only upload the file if the reviewer has yet to submit a recommendation
		// and if review forms are not used
		if (($reviewAssignment->getRecommendation() === null || $reviewAssignment->getRecommendation() === '') && !$reviewAssignment->getCancelled()) {
			$fileName = 'upload';
			if ($articleFileManager->uploadedFileExists($fileName)) {
				HookRegistry::call('ReviewerAction::uploadReviewFile', array(&$reviewAssignment));
				if ($reviewAssignment->getReviewerFileId() != null) {
					$fileId = $articleFileManager->uploadReviewFile($fileName, $reviewAssignment->getReviewerFileId());
				} else {
					$fileId = $articleFileManager->uploadReviewFile($fileName);
				}
			}
		}

		if (isset($fileId) && $fileId != 0) {
			$reviewAssignment->setReviewerFileId($fileId);
			$reviewAssignment->stampModified();
			$reviewAssignmentDao->updateObject($reviewAssignment);

			// Add log
			import('article.log.MonographLog');
			import('article.log.MonographEventLogEntry');

			$userDao =& DAORegistry::getDAO('UserDAO');
			$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId());

			$entry = new MonographEventLogEntry();
			$entry->setMonographId($reviewAssignment->getMonographId());
			$entry->setUserId($reviewer->getUserId());
			$entry->setDateLogged(Core::getCurrentDate());
			$entry->setEventType(ARTICLE_LOG_REVIEW_FILE);
			$entry->setLogMessage('log.review.reviewerFile');
			$entry->setAssocType(ARTICLE_LOG_TYPE_REVIEW);
			$entry->setAssocId($reviewAssignment->getReviewId());

			MonographLog::logEventEntry($reviewAssignment->getMonographId(), $entry);
		}
	}

	/**
	 * Delete an annotated version of an article.
	 * @param $reviewId int
	 * @param $fileId int
	 * @param $revision int If null, then all revisions are deleted.
	 */
        function deleteReviewerVersion($reviewId, $fileId, $revision = null) {
		import("file.MonographFileManager");

		$articleId = Request::getUserVar('articleId');
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);

		if (!HookRegistry::call('ReviewerAction::deleteReviewerVersion', array(&$reviewAssignment, &$fileId, &$revision))) {
			$articleFileManager = new MonographFileManager($reviewAssignment->getMonographId());
			$articleFileManager->deleteFile($fileId, $revision);
		}
        }

	/**
	 * View reviewer comments.
	 * @param $user object Current user
	 * @param $article object
	 * @param $reviewId int
	 */
	function viewPeerReviewComments(&$user, &$article, $reviewId) {
		if (!HookRegistry::call('ReviewerAction::viewPeerReviewComments', array(&$user, &$article, &$reviewId))) {
			import("submission.form.comment.PeerReviewCommentForm");

			// FIXME: Need construction by reference or validation always fails on PHP 4.x
			$commentForm =& new PeerReviewCommentForm($article, $reviewId, ROLE_ID_REVIEWER);
			$commentForm->setUser($user);
			$commentForm->initData();
			$commentForm->setData('reviewId', $reviewId);
			$commentForm->display();
		}
	}

	/**
	 * Post reviewer comments.
	 * @param $user object Current user
	 * @param $article object
	 * @param $reviewId int
	 * @param $emailComment boolean
	 */
	function postPeerReviewComment(&$user, &$article, $reviewId, $emailComment) {
		if (!HookRegistry::call('ReviewerAction::postPeerReviewComment', array(&$user, &$article, &$reviewId, &$emailComment))) {
			import("submission.form.comment.PeerReviewCommentForm");

			// FIXME: Need construction by reference or validation always fails on PHP 4.x
			$commentForm =& new PeerReviewCommentForm($article, $reviewId, ROLE_ID_REVIEWER);
			$commentForm->setUser($user);
			$commentForm->readInputData();

			if ($commentForm->validate()) {
				$commentForm->execute();

				if ($emailComment) {
					$commentForm->email();
				}

			} else {
				$commentForm->display();
				return false;
			}
			return true;
		}
	}

	/**
	 * Edit review form response.
	 * @param $reviewId int
	 * @param $reviewFormId int
	 */
	function editReviewFormResponse($reviewId, $reviewFormId) {
		if (!HookRegistry::call('ReviewerAction::editReviewFormResponse', array($reviewId, $reviewFormId))) {
			import('submission.form.ReviewFormResponseForm');

			// FIXME: Need construction by reference or validation always fails on PHP 4.x
			$reviewForm =& new ReviewFormResponseForm($reviewId, $reviewFormId);
			$reviewForm->initData();
			$reviewForm->display();
		}
	}

	/**
	 * Save review form response.
	 * @param $reviewId int
	 * @param $reviewFormId int
	 */
	function saveReviewFormResponse($reviewId, $reviewFormId) {
		if (!HookRegistry::call('ReviewerAction::saveReviewFormResponse', array($reviewId, $reviewFormId))) {
			import('submission.form.ReviewFormResponseForm');

			// FIXME: Need construction by reference or validation always fails on PHP 4.x
			$reviewForm =& new ReviewFormResponseForm($reviewId, $reviewFormId);
			$reviewForm->readInputData();
			if ($reviewForm->validate()) {
				$reviewForm->execute();
			} else {
				$reviewForm->display();
				return false;
			}
			return true;
		}
	}

	//
	// Misc
	//

	/**
	 * Download a file a reviewer has access to.
	 * @param $reviewId int
	 * @param $article object
	 * @param $fileId int
	 * @param $revision int
	 */
	function downloadReviewerFile($reviewId, $article, $fileId, $revision = null) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');		
		$reviewAssignment =& $reviewAssignmentDao->getById($reviewId);
		$journal =& Request::getPress();

		$canDownload = false;

		// Reviewers have access to:
		// 1) The current revision of the file to be reviewed.
		// 2) Any file that he uploads.
		// 3) Any supplementary file that is visible to reviewers.
		if ((!$reviewAssignment->getDateConfirmed() || $reviewAssignment->getDeclined()) && $journal->getSetting('restrictReviewerFileAccess')) {
			// Restrict files until review is accepted
		} else if ($reviewAssignment->getReviewFileId() == $fileId) {
			if ($revision != null) {
				$canDownload = ($reviewAssignment->getReviewRevision() == $revision);
			}
		} else if ($reviewAssignment->getReviewerFileId() == $fileId) {
			$canDownload = true;
		} else {
			foreach ($reviewAssignment->getSuppFiles() as $suppFile) {
				if ($suppFile->getFileId() == $fileId && $suppFile->getShowReviewers()) {
					$canDownload = true;
				}
			}
		}

		$result = false;
		if (!HookRegistry::call('ReviewerAction::downloadReviewerFile', array(&$article, &$fileId, &$revision, &$canDownload, &$result))) {
			if ($canDownload) {
				return Action::downloadFile($article->getMonographId(), $fileId, $revision);
			} else {
				return false;
			}
		}
		return $result;
	}

	/**
	 * Edit comment.
	 * @param $commentId int
	 */
	function editComment ($article, $comment, $reviewId) {
		if (!HookRegistry::call('ReviewerAction::editComment', array(&$article, &$comment, &$reviewId))) {
			import ("submission.form.comment.EditCommentForm");

			// FIXME: Need construction by reference or validation always fails on PHP 4.x
			$commentForm =& new EditCommentForm ($article, $comment);
			$commentForm->initData();
			$commentForm->setData('reviewId', $reviewId);
			$commentForm->display(array('reviewId' => $reviewId));
		}
	}
}

?>
