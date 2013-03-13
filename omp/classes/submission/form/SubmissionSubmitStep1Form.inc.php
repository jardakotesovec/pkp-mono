<?php

/**
 * @file classes/submission/form/SubmissionSubmitStep1Form.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionSubmitStep1Form
 * @ingroup submission_form
 *
 * @brief Form for Step 1 of author submission.
 */


import('classes.submission.form.SubmissionSubmitForm');

class SubmissionSubmitStep1Form extends SubmissionSubmitForm {
	/**
	 * Constructor.
	 */
	function SubmissionSubmitStep1Form($context, $submission = null) {
		parent::SubmissionSubmitForm($context, $submission, 1);

		// Validation checks for this form
		$supportedSubmissionLocales = $context->getSupportedSubmissionLocales();
		if (!is_array($supportedSubmissionLocales) || count($supportedSubmissionLocales) < 1) $supportedSubmissionLocales = array($context->getPrimaryLocale());
		$this->addCheck(new FormValidatorInSet($this, 'locale', 'required', 'submission.submit.form.localeRequired', $supportedSubmissionLocales));
		if ((boolean) $context->getSetting('copyrightNoticeAgree')) {
			$this->addCheck(new FormValidator($this, 'copyrightNoticeAgree', 'required', 'submission.submit.copyrightNoticeAgreeRequired'));
		}
		$this->addCheck(new FormValidator($this, 'authorUserGroupId', 'required', 'user.authorization.userGroupRequired'));


		foreach ($context->getLocalizedSetting('submissionChecklist') as $key => $checklistItem) {
			$this->addCheck(new FormValidator($this, "checklist-$key", 'required', 'submission.submit.checklistErrors'));
		}
	}

	/**
	 * Fetch the form.
	 */
	function fetch($request) {
		$user = $request->getUser();

		$templateMgr = TemplateManager::getManager($request);

		// FIXME: If this user is a series editor, they are allowed
		// to submit to series flagged as "editor-only" for
		// submissions. Otherwise, display only series they are allowed
		// to submit to.
		$roleDao = DAORegistry::getDAO('RoleDAO');
		$isEditor = $roleDao->userHasRole($this->context->getId(), $user->getId(), ROLE_ID_SUB_EDITOR);

		// Get series for this context
		$seriesDao = DAORegistry::getDAO('SeriesDAO');
		$seriesOptions = array('0' => __('submission.submit.selectSeries')) + $seriesDao->getTitlesByPressId($this->context->getId());
		$templateMgr->assign('seriesOptions', $seriesOptions);

		$templateMgr->assign(
			'supportedSubmissionLocaleNames',
			$this->context->getSupportedSubmissionLocaleNames()
		);

		// if this context has a copyright notice that the author must agree to, present the form items.
		if ((boolean) $this->context->getSetting('copyrightNoticeAgree')) {
			$templateMgr->assign('copyrightNotice', $this->context->getLocalizedSetting('copyrightNotice'));
			$templateMgr->assign('copyrightNoticeAgree', true);
		}

		// Get list of user's author user groups.  If its more than one, we'll need to display an author user group selector
		$userGroupAssignmentDao = DAORegistry::getDAO('UserGroupAssignmentDAO');
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$authorUserGroupAssignments = $userGroupAssignmentDao->getByUserId($user->getId(), $this->context->getId(), ROLE_ID_AUTHOR);
		if(!$authorUserGroupAssignments->wasEmpty()) {
			$authorUserGroupNames = array();
			while($authorUserGroupAssignment = $authorUserGroupAssignments->next()) {
				$authorUserGroup = $userGroupDao->getById($authorUserGroupAssignment->getUserGroupId());
				if ($userGroupDao->userGroupAssignedToStage($authorUserGroup->getId(), WORKFLOW_STAGE_ID_SUBMISSION)) {
					$authorUserGroupNames[$authorUserGroup->getId()] = $authorUserGroup->getLocalizedName();
				}
			}
			$templateMgr->assign('authorUserGroupOptions', $authorUserGroupNames);
		} else {
			// The user doesn't have any author user group assignments.  They should be either a manager.
			$userGroupNames = array();

			// Add all manager user groups
			$managerUserGroupAssignments = $userGroupAssignmentDao->getByUserId($user->getId(), $this->context->getId(), ROLE_ID_MANAGER);
			if($managerUserGroupAssignments) while($managerUserGroupAssignment = $managerUserGroupAssignments->next()) {
				$managerUserGroup = $userGroupDao->getById($managerUserGroupAssignment->getUserGroupId());
				$userGroupNames[$managerUserGroup->getId()] = $managerUserGroup->getLocalizedName();
			}

			$templateMgr->assign('authorUserGroupOptions', $userGroupNames);
		}

		return parent::fetch($request);
	}

	/**
	 * Initialize form data from current submission.
	 */
	function initData() {
		if (isset($this->submission)) {
			$this->_data = array(
				'seriesId' => $this->submission->getSeriesId(),
				'seriesPosition' => $this->submission->getSeriesPosition(),
				'locale' => $this->submission->getLocale(),
				'workType' => $this->submission->getWorkType(),
				'commentsToEditor' => $this->submission->getCommentsToEditor()
			);
		} else {
			$supportedSubmissionLocales = $this->context->getSupportedSubmissionLocales();
			// Try these locales in order until we find one that's
			// supported to use as a default.
			$tryLocales = array(
				$this->getFormLocale(), // Current form locale
				AppLocale::getLocale(), // Current UI locale
				$this->context->getPrimaryLocale(), // Context locale
				$supportedSubmissionLocales[array_shift(array_keys($supportedSubmissionLocales))] // Fallback: first one on the list
			);
			$this->_data = array();
			foreach ($tryLocales as $locale) {
				if (in_array($locale, $supportedSubmissionLocales)) {
					// Found a default to use
					$this->_data['locale'] = $locale;
					break;
				}
			}
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$vars = array(
			'authorUserGroupId', 'locale', 'workType', 'copyrightNoticeAgree', 'seriesId', 'seriesPosition', 'commentsToEditor', 'copyrightNoticeAgree'
		);
		foreach ($this->context->getLocalizedSetting('submissionChecklist') as $key => $checklistItem) {
			$vars[] = "checklist-$key";
		}

		$this->readUserVars($vars);
	}

	/**
	 * Save changes to submission.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return int the submission ID
	 */
	function execute($args, $request) {
		$submissionDao = DAORegistry::getDAO('MonographDAO');

		if (isset($this->submission)) {
			// Update existing submission
			$this->submission->setSeriesId($this->getData('seriesId'));
			$this->submission->setSeriesPosition($this->getData('seriesPosition'));
			$this->submission->setLocale($this->getData('locale'));
			$this->submission->setCommentsToEditor($this->getData('commentsToEditor'));
            $this->submission->setWorkType($this->getData('workType'));
			if ($this->submission->getSubmissionProgress() <= $this->step) {
				$this->submission->stampStatusModified();
				$this->submission->setSubmissionProgress($this->step + 1);
			}
			$submissionDao->updateObject($this->submission);
		} else {
			// Create new submission
			$this->submission = $submissionDao->newDataObject();
			$this->submission->setLocale($this->getData('locale'));
			$user = $request->getUser();
			$this->submission->setUserId($user->getId());
			$this->submission->setContextId($this->context->getId());
			$this->submission->setSeriesId($this->getData('seriesId'));
			$this->submission->setSeriesPosition($this->getData('seriesPosition'));
			$this->submission->stampStatusModified();
			$this->submission->setSubmissionProgress($this->step + 1);
			$this->submission->setLanguage(String::substr($this->submission->getLocale(), 0, 2));
			$this->submission->setCommentsToEditor($this->getData('commentsToEditor'));
			$this->submission->setWorkType($this->getData('workType'));
			$this->submission->setStageId(WORKFLOW_STAGE_ID_SUBMISSION);
			$this->submission->setCopyrightNotice($this->context->getLocalizedSetting('copyrightNotice'), $this->getData('locale'));
			// Insert the submission
			$this->submissionId = $submissionDao->insertObject($this->submission);

			// Set user to initial author
			$authorDao = DAORegistry::getDAO('AuthorDAO');
			$author = $authorDao->newDataObject();
			$author->setFirstName($user->getFirstName());
			$author->setMiddleName($user->getMiddleName());
			$author->setLastName($user->getLastName());
			$author->setAffiliation($user->getAffiliation(null), null);
			$author->setCountry($user->getCountry());
			$author->setEmail($user->getEmail());
			$author->setUrl($user->getUrl());
			$author->setBiography($user->getBiography(null), null);
			$author->setPrimaryContact(1);

			// Get the user group to display the submitter as
			$authorUserGroupId = (int) $this->getData('authorUserGroupId');
			$author->setUserGroupId($authorUserGroupId);

			$author->setSubmissionId($this->submissionId);
			$authorDao->insertAuthor($author);

			// Assign the user author to the stage
			$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
			$stageAssignmentDao->build($this->submissionId, $authorUserGroupId, $user->getId());
		}

		return $this->submissionId;
	}
}

?>
