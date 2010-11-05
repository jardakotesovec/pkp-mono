<?php

/**
 * @defgroup submission_common
 */

/**
 * @file classes/submission/common/Action.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Action
 * @ingroup submission_common
 *
 * @brief Application-specific submission actions.
 */


/* These constants correspond to editing decision "decision codes". */
define('SUBMISSION_EDITOR_DECISION_ACCEPT', 1);
define('SUBMISSION_EDITOR_DECISION_EXTERNAL_REVIEW', 2);
define('SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS', 3);
define('SUBMISSION_EDITOR_DECISION_RESUBMIT', 4);
define('SUBMISSION_EDITOR_DECISION_DECLINE', 5);

/* These constants are used as search fields for the various submission lists */
define('SUBMISSION_FIELD_AUTHOR', 1);
define('SUBMISSION_FIELD_EDITOR', 2);
define('SUBMISSION_FIELD_TITLE', 3);
define('SUBMISSION_FIELD_REVIEWER', 4);
define('SUBMISSION_FIELD_COPYEDITOR', 5);
define('SUBMISSION_FIELD_LAYOUTEDITOR', 6);
define('SUBMISSION_FIELD_PROOFREADER', 7);

define('SUBMISSION_FIELD_DATE_SUBMITTED', 4);
define('SUBMISSION_FIELD_DATE_COPYEDIT_COMPLETE', 5);
define('SUBMISSION_FIELD_DATE_LAYOUT_COMPLETE', 6);
define('SUBMISSION_FIELD_DATE_PROOFREADING_COMPLETE', 7);

import('lib.pkp.classes.submission.common.PKPAction');

class Action extends PKPAction {
	/**
	 * Constructor.
	 */
	function Action() {
		parent::PKPAction();
	}

	//
	// Actions.
	//
	/**
	 * Download file.
	 * @param $monographId int
	 * @param $fileId int
	 * @param $revision int
	 */
	function downloadFile($monographId, $fileId, $revision = null) {
		import('classes.file.MonographFileManager');
		$monographFileManager = new MonographFileManager($monographId);
		return $monographFileManager->downloadFile($fileId, $revision);
	}

	/**
	 * View file.
	 * @param $monographId int
	 * @param $fileId int
	 * @param $revision int
	 */
	function viewFile($monographId, $fileId, $revision = null) {
		import('classes.file.MonographFileManager');
		$monographFileManager = new MonographFileManager($monographId);
		return $monographFileManager->viewFile($fileId, $revision);
	}

	/**
	 *
	 * @param $type string the type of instructions (copy, layout, or proof).
	 */
	function instructions($type, $allowed = array('copy', 'layout', 'proof', 'referenceLinking')) {
		$press =& Request::getPress();
		$templateMgr =& TemplateManager::getManager();

		if (!HookRegistry::call('Action::instructions', array(&$type, &$allowed))) {
			if (!in_array($type, $allowed)) {
				return false;
			}

			switch ($type) {
				case 'copy':
					$title = 'submission.copyedit.instructions';
					$instructions = $press->getLocalizedSetting('copyeditInstructions');
					break;
				case 'layout':
					$title = 'submission.layout.instructions';
					$instructions = $press->getLocalizedSetting('layoutInstructions');
					break;
				case 'proof':
					$title = 'submission.proofread.instructions';
					$instructions = $press->getLocalizedSetting('proofInstructions');
					break;
				case 'referenceLinking':
					if (!$press->getSetting('provideRefLinkInstructions')) return false;
					$title = 'submission.layout.referenceLinking';
					$instructions = $press->getLocalizedSetting('refLinkInstructions');
					break;
				default:
					return false;
			}
		}

		$templateMgr->assign('pageTitle', $title);
		$templateMgr->assign('instructions', $instructions);
		$templateMgr->display('submission/instructions.tpl');

		return true;
	}

	/**
	 * Edit comment.
	 * @param $commentId int
	 */
	function editComment($monograph, $comment) {
		if (!HookRegistry::call('Action::editComment', array(&$monograph, &$comment))) {
			import('classes.submission.form.comment.EditCommentForm');

			$commentForm = new EditCommentForm($monograph, $comment);
			$commentForm->initData();
			$commentForm->display();
		}
	}

	/**
	 * Save comment.
	 * @param $commentId int
	 */
	function saveComment($monograph, &$comment, $emailComment) {
		if (!HookRegistry::call('Action::saveComment', array(&$monograph, &$comment, &$emailComment))) {
			import('classes.submission.form.comment.EditCommentForm');

			$commentForm = new EditCommentForm($monograph, $comment);
			$commentForm->readInputData();

			if ($commentForm->validate()) {
				$commentForm->execute();

				// Send a notification to associated users
				import('lib.pkp.classes.notification.NotificationManager');
				$notificationUsers = $monograph->getAssociatedUserIds();
				$notificationManager = new NotificationManager();
				foreach ($notificationUsers as $userRole) {
					$url = Request::url(null, $userRole['role'], 'submissionReview', $monograph->getId(), null, 'editorDecision');
					$notificationManager->createNotification(
						$userRole['id'], 'notification.type.submissionComment',
						$monograph->getLocalizedTitle(), $url, 1, NOTIFICATION_TYPE_SUBMISSION_COMMENT
					);
				}

				if ($emailComment) {
					$commentForm->email($commentForm->emailHelper());
				}

			} else {
				$commentForm->display();
			}
		}
	}

	/**
	 * Delete comment.
	 * @param $commentId int
	 * @param $user object The user who owns the comment, or null to default to Request::getUser
	 */
	function deleteComment($commentId, $user = null) {
		if ($user == null) $user =& Request::getUser();

		$monographCommentDao =& DAORegistry::getDAO('MonographCommentDAO');
		$comment =& $monographCommentDao->getMonographCommentById($commentId);

		if ($comment->getAuthorId() == $user->getId()) {
			if (!HookRegistry::call('Action::deleteComment', array(&$comment))) {
				$monographCommentDao->deleteMonographComment($comment);
			}
		}
	}

	/**
	 * Assign the default participants to a workflow stage.
	 * @param $monographId int
	 * @param $stageId int
	 */
	function assignDefaultStageParticipants($monographId, $stageId) {
		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$userGroupDao =& DAORegistry::getDAO('UserGroupDAO');
		$userGroupStageAssignmentDao =& DAORegistry::getDAO('UserGroupStageAssignmentDAO');
		$monographDao =& DAORegistry::getDAO('MonographDAO');
		$monograph =& $monographDao->getMonograph($monographId);

		// Managerial roles are skipped -- They have access by default and
		//  are assigned for informational purposes only

		// Series editor roles are skipped -- They are assigned by PM roles
		//  or by other series editors

		// Press roles -- For each press role user group assigned to this
		//  stage in setup, iff there is only one user for the group,
		//  automatically assign the user to the stage
		$submissionStageGroups =& $userGroupStageAssignmentDao->getUserGroupsByStage($monograph->getPressId(), $stageId);
		while ($userGroup =& $submissionStageGroups->next()) {
			if($userGroup->getRoleId() == ROLE_ID_PRESS_ASSISTANT) {
				$users =& $userGroupDao->getUsersById($userGroup->getId());
				if($users->getCount() == 1) {
					$user =& $users->next();
					$signoffDao->build('SIGNOFF_STAGE', ASSOC_TYPE_MONOGRAPH, $monographId, $user->getId(), $stageId, $userGroup->getId());
				}
			}
		}

		// Author roles -- Assign only the submitter
		// FIXME #6001: If the submission is a monograph, then the user group
		//   assigned for the submitter should be author; If its a volume,
		// 	 it should be a volume editor user group.
		$authorUserGroup =& $userGroupDao->getDefaultByRoleId($monograph->getPressId(), ROLE_ID_AUTHOR);
		$signoffDao->build('SIGNOFF_STAGE', ASSOC_TYPE_MONOGRAPH, $monographId, $monograph->getUserId(), $stageId, $authorUserGroup->getId());

		// Reviewer roles -- Do nothing
		// FIXME #6002: Need to review this -- Not sure if reviewers should be
		//  added as stage participants
	}
}

?>
