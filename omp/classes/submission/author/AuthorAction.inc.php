<?php

/**
 * @file classes/submission/author/AuthorAction.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorAction
 * @ingroup submission
 *
 * @brief AuthorAction class.
 */

// $Id$


import('submission.common.Action');

class AuthorAction extends Action {

	/**
	 * Constructor.
	 */
	function AuthorAction() {
		parent::Action();
	}

	/**
	 * Actions.
	 */

	/**
	 * Designates the original file the review version.
	 * @param $authorSubmission object
	 * @param $designate boolean
	 */
	function designateReviewVersion($authorSubmission, $designate = false) {
		import('file.MonographFileManager');
		$monographFileManager =& new MonographFileManager($authorSubmission->getMonographId());
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');

		if ($designate && !HookRegistry::call('AuthorAction::designateReviewVersion', array(&$authorSubmission))) {
			$submissionFile =& $authorSubmission->getSubmissionFile();
			if ($submissionFile) {
				$reviewFileId = $monographFileManager->copyToReviewFile($submissionFile->getFileId());

				$authorSubmission->setReviewFileId($reviewFileId);

				$authorSubmissionDao->updateAuthorSubmission($authorSubmission);

				$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
				$sectionEditorSubmissionDao->createReviewRound($authorSubmission->getMonographId(), 1, 1);
			}
		}
	}

	/**
	 * Delete an author file from a submission.
	 * @param $monograph object
	 * @param $fileId int
	 * @param $revisionId int
	 */
	function deleteMonographFile($monograph, $fileId, $revisionId) {
		import('file.MonographFileManager');

		$monographFileManager =& new MonographFileManager($monograph->getMonographId());
		$monographFileDao =& DAORegistry::getDAO('MonographFileDAO');
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');

		$monographFile =& $monographFileDao->getMonographFile($fileId, $revisionId, $monograph->getMonographId());
		$authorSubmission = $authorSubmissionDao->getAuthorSubmission($monograph->getMonographId());
		$authorRevisions = $authorSubmission->getAuthorFileRevisions();

		// Ensure that this is actually an author file.
		if (isset($monographFile)) {
			HookRegistry::call('AuthorAction::deleteMonographFile', array(&$monographFile, &$authorRevisions));
			foreach ($authorRevisions as $round) {
				foreach ($round as $revision) {
					if ($revision->getFileId() == $monographFile->getFileId() &&
					    $revision->getRevision() == $monographFile->getRevision()) {
						$monographFileManager->deleteFile($monographFile->getFileId(), $monographFile->getRevision());
					}
				}
			}
		}
	}

	/**
	 * Upload the revised version of an monograph.
	 * @param $authorSubmission object
	 */
	function uploadRevisedVersion($authorSubmission) {
		import("file.MonographFileManager");
		$monographFileManager =& new MonographFileManager($authorSubmission->getMonographId());
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');

		$fileName = 'upload';
		if ($monographFileManager->uploadedFileExists($fileName)) {
			HookRegistry::call('AuthorAction::uploadRevisedVersion', array(&$authorSubmission));
			if ($authorSubmission->getRevisedFileId() != null) {
				$fileId = $monographFileManager->uploadEditorDecisionFile($fileName, $authorSubmission->getRevisedFileId());
			} else {
				$fileId = $monographFileManager->uploadEditorDecisionFile($fileName);
			}
		}

		if (isset($fileId) && $fileId != 0) {
			$authorSubmission->setRevisedFileId($fileId);

			$authorSubmissionDao->updateAuthorSubmission($authorSubmission);

			// Add log entry
			$user =& Request::getUser();
			import('monograph.log.MonographLog');
			import('monograph.log.MonographEventLogEntry');
			MonographLog::logEvent($authorSubmission->getMonographId(), ARTICLE_LOG_AUTHOR_REVISION, ARTICLE_LOG_TYPE_AUTHOR, $user->getUserId(), 'log.author.documentRevised', array('authorName' => $user->getFullName(), 'fileId' => $fileId, 'monographId' => $authorSubmission->getMonographId()));
		}
	}

	/**
	 * Author completes editor / author review.
	 * @param $authorSubmission object
	 */
	function completeAuthorCopyedit($authorSubmission, $send = false) {
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$press =& Request::getPress();

		if ($authorSubmission->getCopyeditorDateAuthorCompleted() != null) {
			return true;
		}

		$user =& Request::getUser();
		import('mail.MonographMailTemplate');
		$email =& new MonographMailTemplate($authorSubmission, 'COPYEDIT_AUTHOR_COMPLETE');

		$editAssignments = $authorSubmission->getEditAssignments();

		$copyeditor =& $authorSubmission->getCopyeditor();

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('AuthorAction::completeAuthorCopyedit', array(&$authorSubmission, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_AUTHOR_COMPLETE, ARTICLE_EMAIL_TYPE_COPYEDIT, $authorSubmission->getMonographId());
				$email->send();
			}

			$authorSubmission->setCopyeditorDateAuthorCompleted(Core::getCurrentDate());
			$authorSubmission->setCopyeditorDateFinalNotified(Core::getCurrentDate());
			$authorSubmissionDao->updateAuthorSubmission($authorSubmission);

			// Add log entry
			import('monograph.log.MonographLog');
			import('monograph.log.MonographEventLogEntry');
			MonographLog::logEvent($authorSubmission->getMonographId(), ARTICLE_LOG_COPYEDIT_REVISION, ARTICLE_LOG_TYPE_AUTHOR, $user->getUserId(), 'log.copyedit.authorFile');

			return true;

		} else {
			if (!Request::getUserVar('continued')) {
				if (isset($copyeditor)) {
					$email->addRecipient($copyeditor->getEmail(), $copyeditor->getFullName());
					$assignedSectionEditors = $email->ccAssignedEditingSectionEditors($authorSubmission->getMonographId());
					$assignedEditors = $email->ccAssignedEditors($authorSubmission->getMonographId());
					if (empty($assignedSectionEditors) && empty($assignedEditors)) {
						$email->addCc($press->getSetting('contactEmail'), $press->getSetting('contactName'));
						$editorName = $press->getSetting('contactName');
					} else {
						$editor = array_shift($assignedSectionEditors);
						if (!$editor) $editor = array_shift($assignedEditors);
						$editorName = $editor->getEditorFullName();
					}
				} else {
					$assignedSectionEditors = $email->toAssignedEditingSectionEditors($authorSubmission->getMonographId());
					$assignedEditors = $email->ccAssignedEditors($authorSubmission->getMonographId());
					if (empty($assignedSectionEditors) && empty($assignedEditors)) {
						$email->addRecipient($press->getSetting('contactEmail'), $press->getSetting('contactName'));
						$editorName = $press->getSetting('contactName');
					} else {
						$editor = array_shift($assignedSectionEditors);
						if (!$editor) $editor = array_shift($assignedEditors);
						$editorName = $editor->getEditorFullName();
					}
				}

				$paramArray = array(
					'editorialContactName' => isset($copyeditor)?$copyeditor->getFullName():$editorName,
					'authorName' => $user->getFullName()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::url(null, 'author', 'completeAuthorCopyedit', 'send'), array('monographId' => $authorSubmission->getMonographId()));

			return false;
		}
	}

	/**
	 * Set that the copyedit is underway.
	 */
	function copyeditUnderway($authorSubmission) {
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');		

		if ($authorSubmission->getCopyeditorDateAuthorNotified() != null && $authorSubmission->getCopyeditorDateAuthorUnderway() == null) {
			HookRegistry::call('AuthorAction::copyeditUnderway', array(&$authorSubmission));
			$authorSubmission->setCopyeditorDateAuthorUnderway(Core::getCurrentDate());
			$authorSubmissionDao->updateAuthorSubmission($authorSubmission);
		}
	}	

	/**
	 * Upload the revised version of a copyedit file.
	 * @param $authorSubmission object
	 * @param $copyeditStage string
	 */
	function uploadCopyeditVersion($authorSubmission, $copyeditStage) {
		import("file.MonographFileManager");
		$monographFileManager =& new MonographFileManager($authorSubmission->getMonographId());
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
		$monographFileDao =& DAORegistry::getDAO('MonographFileDAO');

		// Authors cannot upload if the assignment is not active, i.e.
		// they haven't been notified or the assignment is already complete.
		if (!$authorSubmission->getCopyeditorDateAuthorNotified() || $authorSubmission->getCopyeditorDateAuthorCompleted()) return;

		$fileName = 'upload';
		if ($monographFileManager->uploadedFileExists($fileName)) {
			HookRegistry::call('AuthorAction::uploadCopyeditVersion', array(&$authorSubmission, &$copyeditStage));
			if ($authorSubmission->getCopyeditFileId() != null) {
				$fileId = $monographFileManager->uploadCopyeditFile($fileName, $authorSubmission->getCopyeditFileId());
			} else {
				$fileId = $monographFileManager->uploadCopyeditFile($fileName);
			}
		}

		$authorSubmission->setCopyeditFileId($fileId);

		if ($copyeditStage == 'author') {
			$authorSubmission->setCopyeditorEditorAuthorRevision($monographFileDao->getRevisionNumber($fileId));
		}

		$authorSubmissionDao->updateAuthorSubmission($authorSubmission);
	}

	//
	// Comments
	//

	/**
	 * View layout comments.
	 * @param $monograph object
	 */
	function viewLayoutComments($monograph) {
		if (!HookRegistry::call('AuthorAction::viewLayoutComments', array(&$monograph))) {
			import("submission.form.comment.LayoutCommentForm");
			$commentForm =& new LayoutCommentForm($monograph, ROLE_ID_EDITOR);
			$commentForm->initData();
			$commentForm->display();
		}
	}

	/**
	 * Post layout comment.
	 * @param $monograph object
	 * @param $emailComment boolean
	 */
	function postLayoutComment($monograph, $emailComment) {
		if (!HookRegistry::call('AuthorAction::postLayoutComment', array(&$monograph, &$emailComment))) {
			import("submission.form.comment.LayoutCommentForm");

			$commentForm =& new LayoutCommentForm($monograph, ROLE_ID_AUTHOR);
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
	 * View editor decision comments.
	 * @param $monograph object
	 */
	function viewEditorDecisionComments($monograph) {
		if (!HookRegistry::call('AuthorAction::viewEditorDecisionComments', array(&$monograph))) {
			import("submission.form.comment.EditorDecisionCommentForm");

			$commentForm =& new EditorDecisionCommentForm($monograph, ROLE_ID_AUTHOR);
			$commentForm->initData();
			$commentForm->display();
		}
	}

	/**
	 * Email editor decision comment.
	 * @param $authorSubmission object
	 * @param $send boolean
	 */
	function emailEditorDecisionComment($authorSubmission, $send) {
		$userDao =& DAORegistry::getDAO('UserDAO');
		$press =& Request::getPress();

		$user =& Request::getUser();
		import('mail.MonographMailTemplate');
		$email =& new MonographMailTemplate($authorSubmission);

		$editAssignments = $authorSubmission->getEditAssignments();
		$editors = array();
		foreach ($editAssignments as $editAssignment) {
			array_push($editors, $userDao->getUser($editAssignment->getEditorId()));
		}

		if ($send && !$email->hasErrors()) {
			HookRegistry::call('AuthorAction::emailEditorDecisionComment', array(&$authorSubmission, &$email));
			$email->send();

			$monographCommentDao =& DAORegistry::getDAO('MonographCommentDAO');
			$monographComment =& new MonographComment();
			$monographComment->setCommentType(COMMENT_TYPE_EDITOR_DECISION);
			$monographComment->setRoleId(ROLE_ID_AUTHOR);
			$monographComment->setMonographId($authorSubmission->getMonographId());
			$monographComment->setAuthorId($authorSubmission->getUserId());
			$monographComment->setCommentTitle($email->getSubject());
			$monographComment->setComments($email->getBody());
			$monographComment->setDatePosted(Core::getCurrentDate());
			$monographComment->setViewable(true);
			$monographComment->setAssocId($authorSubmission->getMonographId());
			$monographCommentDao->insertMonographComment($monographComment);

			return true;
		} else {
			if (!Request::getUserVar('continued')) {
				$email->setSubject($authorSubmission->getLocalizedTitle());
				if (!empty($editors)) {
					foreach ($editors as $editor) {
						$email->addRecipient($editor->getEmail(), $editor->getFullName());
					}
				} else {
					$email->addRecipient($press->getSetting('contactEmail'), $press->getSetting('contactName'));
				}
			}

			$email->displayEditForm(Request::url(null, null, 'emailEditorDecisionComment', 'send'), array('monographId' => $authorSubmission->getMonographId()), 'submission/comment/editorDecisionEmail.tpl');

			return false;
		}
	}

	/**
	 * View copyedit comments.
	 * @param $monograph object
	 */
	function viewCopyeditComments($monograph) {
		if (!HookRegistry::call('AuthorAction::viewCopyeditComments', array(&$monograph))) {
			import("submission.form.comment.CopyeditCommentForm");

			$commentForm =& new CopyeditCommentForm($monograph, ROLE_ID_AUTHOR);
			$commentForm->initData();
			$commentForm->display();
		}
	}

	/**
	 * Post copyedit comment.
	 * @param $monograph object
	 */
	function postCopyeditComment($monograph, $emailComment) {
		if (!HookRegistry::call('AuthorAction::postCopyeditComment', array(&$monograph, &$emailComment))) {
			import("submission.form.comment.CopyeditCommentForm");

			$commentForm =& new CopyeditCommentForm($monograph, ROLE_ID_AUTHOR);
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
	 * View proofread comments.
	 * @param $monograph object
	 */
	function viewProofreadComments($monograph) {
		if (!HookRegistry::call('AuthorAction::viewProofreadComments', array(&$monograph))) {
			import("submission.form.comment.ProofreadCommentForm");

			$commentForm =& new ProofreadCommentForm($monograph, ROLE_ID_AUTHOR);
			$commentForm->initData();
			$commentForm->display();
		}
	}

	/**
	 * Post proofread comment.
	 * @param $monograph object
	 * @param $emailComment boolean
	 */
	function postProofreadComment($monograph, $emailComment) {
		if (!HookRegistry::call('AuthorAction::postProofreadComment', array(&$monograph, &$emailComment))) {
			import("submission.form.comment.ProofreadCommentForm");

			$commentForm =& new ProofreadCommentForm($monograph, ROLE_ID_AUTHOR);
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

	//
	// Misc
	//

	/**
	 * Download a file an author has access to.
	 * @param $monograph object
	 * @param $fileId int
	 * @param $revision int
	 * @return boolean
	 * TODO: Complete list of files author has access to
	 */
	function downloadAuthorFile($monograph, $fileId, $revision = null) {
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');		

		$submission =& $authorSubmissionDao->getAuthorSubmission($monograph->getMonographId());
		$layoutAssignment =& $submission->getLayoutAssignment();

		$canDownload = false;

		// Authors have access to:
		// 1) The original submission file.
		// 2) Any files uploaded by the reviewers that are "viewable",
		//    although only after a decision has been made by the editor.
		// 3) The initial and final copyedit files, after initial copyedit is complete.
		// 4) Any of the author-revised files.
		// 5) The layout version of the file.
		// 6) Any supplementary file
		// 7) Any galley file
		// 8) All review versions of the file
		// 9) Current editor versions of the file
		// THIS LIST SHOULD NOW BE COMPLETE.
		if ($submission->getSubmissionFileId() == $fileId) {
			$canDownload = true;
		} else if ($submission->getCopyeditFileId() == $fileId) {
			if ($revision != null) {
				$copyAssignmentDao =& DAORegistry::getDAO('CopyAssignmentDAO');
				$copyAssignment =& $copyAssignmentDao->getCopyAssignmentByMonographId($monograph->getMonographId());
				if ($copyAssignment && $copyAssignment->getInitialRevision()==$revision && $copyAssignment->getDateCompleted()!=null) $canDownload = true;
				else if ($copyAssignment && $copyAssignment->getFinalRevision()==$revision && $copyAssignment->getDateFinalCompleted()!=null) $canDownload = true;
				else if ($copyAssignment && $copyAssignment->getEditorAuthorRevision()==$revision) $canDownload = true; 
			} else {
				$canDownload = false;
			}
		} else if ($submission->getRevisedFileId() == $fileId) {
			$canDownload = true;
		} else if ($layoutAssignment->getLayoutFileId() == $fileId) {
			$canDownload = true;
		} else {
			// Check reviewer files
			foreach ($submission->getReviewAssignments() as $roundReviewAssignments) {
				foreach ($roundReviewAssignments as $reviewAssignment) {
					if ($reviewAssignment->getReviewerFileId() == $fileId) {
						$monographFileDao =& DAORegistry::getDAO('MonographFileDAO');

						$monographFile =& $monographFileDao->getMonographFile($fileId, $revision);

						if ($monographFile != null && $monographFile->getViewable()) {
							$canDownload = true;
						}
					}
				}
			}

			// Check supplementary files
			foreach ($submission->getSuppFiles() as $suppFile) {
				if ($suppFile->getFileId() == $fileId) {
					$canDownload = true;
				}
			}

			// Check galley files
			foreach ($submission->getGalleys() as $galleyFile) {
				if ($galleyFile->getFileId() == $fileId) {
					$canDownload = true;
				}
			}

			// Check current review version
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewFilesByRound =& $reviewAssignmentDao->getReviewFilesByRound($monograph->getMonographId());
			$reviewFile = @$reviewFilesByRound[$monograph->getCurrentRound()];
			if ($reviewFile && $fileId == $reviewFile->getFileId()) {
				$canDownload = true;
			}

			// Check editor version
			$editorFiles = $submission->getEditorFileRevisions($monograph->getCurrentRound());
			if (is_array($editorFiles)) foreach ($editorFiles as $editorFile) {
				if ($editorFile->getFileId() == $fileId) {
					$canDownload = true;
				}
			}
		}

		$result = false;
		if (!HookRegistry::call('AuthorAction::downloadAuthorFile', array(&$monograph, &$fileId, &$revision, &$canDownload, &$result))) {
			if ($canDownload) {
				return Action::downloadFile($monograph->getMonographId(), $fileId, $revision);
			} else {
				return false;
			}
		}
		return $result;
	}
}

?>
