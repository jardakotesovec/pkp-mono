<?php

/**
 * @file classes/submission/form/SubmissionSubmitStep1Form.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionSubmitStep1Form
 * @ingroup submission_form
 *
 * @brief Form for Step 1 of author monograph submission.
 */


import('classes.submission.form.SubmissionSubmitForm');

class SubmissionSubmitStep1Form extends SubmissionSubmitForm {
	/**
	 * Constructor.
	 */
	function SubmissionSubmitStep1Form($press, $monograph = null) {
		parent::SubmissionSubmitForm($press, $monograph, 1);

		// Validation checks for this form
		$supportedSubmissionLocales = $press->getSetting('supportedSubmissionLocales');
		if (!is_array($supportedSubmissionLocales) || count($supportedSubmissionLocales) < 1) $supportedSubmissionLocales = array($press->getPrimaryLocale());
		$this->addCheck(new FormValidatorInSet($this, 'locale', 'required', 'submission.submit.form.localeRequired', $supportedSubmissionLocales));

		foreach ($press->getLocalizedSetting('submissionChecklist') as $key => $checklistItem) {
			$this->addCheck(new FormValidator($this, "checklist-$key", 'required', 'submission.submit.checklistErrors'));
		}
	}

	/**
	 * Display the form.
	 */
	function display($request) {
		$user =& $request->getUser();

		$templateMgr =& TemplateManager::getManager();

		// Get series for this press
		$seriesDao =& DAORegistry::getDAO('SeriesDAO');

		// FIXME: If this user is a series editor or an editor, they are
		// allowed to submit to series flagged as "editor-only" for
		// submissions. Otherwise, display only series they are allowed
		// to submit to.
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$isEditor = $roleDao->userHasRole($this->press->getId(), $user->getId(), ROLE_ID_EDITOR) || $roleDao->userHasRole($this->press->getId(), $user->getId(), ROLE_ID_SERIES_EDITOR);

		$seriesOptions = array('0' => Locale::translate('submission.submit.selectSeries')) + $seriesDao->getTitlesByPressId($this->press->getId());
		$templateMgr->assign('seriesOptions', $seriesOptions);

		// Provide available submission languages. (Convert the array
		// of locale symbolic names xx_XX into an associative array
		// of symbolic names => readable names.)
		$supportedSubmissionLocales = $this->press->getSetting('supportedSubmissionLocales');
		if (empty($supportedSubmissionLocales)) $supportedSubmissionLocales = array($this->press->getPrimaryLocale());
		$templateMgr->assign(
			'supportedSubmissionLocaleNames',
			array_flip(array_intersect(
				array_flip(Locale::getAllLocales()),
				$supportedSubmissionLocales
			))
		);

		parent::display($request);
	}

	/**
	 * Initialize form data from current monograph.
	 */
	function initData() {
		if (isset($this->monograph)) {
			$this->_data = array(
				'seriesId' => $this->monograph->getSeriesId(),
				'locale' => $this->monograph->getLocale(),
				'isEditedVolume' => $this->monograph->getWorkType() == WORK_TYPE_EDITED_VOLUME,
				'commentsToEditor' => $this->monograph->getCommentsToEditor()
			);
		} else {
			$supportedSubmissionLocales = $this->press->getSetting('supportedSubmissionLocales');
			// Try these locales in order until we find one that's
			// supported to use as a default.
			$tryLocales = array(
				$this->getFormLocale(), // Current form locale
				Locale::getLocale(), // Current UI locale
				$this->press->getPrimaryLocale(), // Press locale
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
			'locale', 'isEditedVolume', 'copyrightNoticeAgree', 'seriesId', 'commentsToEditor'
		);
		foreach ($this->press->getLocalizedSetting('submissionChecklist') as $key => $checklistItem) {
			$vars[] = "checklist-$key";
		}
		$this->readUserVars($vars);
	}

	/**
	 * Save changes to submission.
	 * @return int the monograph ID
	 */
	function execute() {
		$monographDao =& DAORegistry::getDAO('MonographDAO');

		if (isset($this->monograph)) {
			// Update existing monograph
			$this->monograph->setSeriesId($this->getData('seriesId'));
			$this->monograph->setLocale($this->getData('locale'));
			$this->monograph->setCommentsToEditor($this->getData('commentsToEditor'));
			if ($this->monograph->getSubmissionProgress() <= $this->step) {
				$this->monograph->stampStatusModified();
				$this->monograph->setSubmissionProgress($this->step + 1);
			}
			$monographDao->updateMonograph($this->monograph);

		} else {
			$user =& Request::getUser();

			// Get the session and the user group id currently used
			$sessionMgr =& SessionManager::getManager();
			$session =& $sessionMgr->getUserSession();

			// Create new monograph
			$this->monograph = new Monograph();
			$this->monograph->setLocale($this->getData('locale'));
			$this->monograph->setUserId($user->getId());
			$this->monograph->setPressId($this->press->getId());
			$this->monograph->setSeriesId($this->getData('seriesId'));
			$this->monograph->stampStatusModified();
			$this->monograph->setSubmissionProgress($this->step + 1);
			$this->monograph->setLanguage(String::substr($this->monograph->getLocale(), 0, 2));
			$this->monograph->setCommentsToEditor($this->getData('commentsToEditor'));
			$this->monograph->setWorkType($this->getData('isEditedVolume') ? WORK_TYPE_EDITED_VOLUME : WORK_TYPE_AUTHORED_WORK);
			$this->monograph->setCurrentStageId(WORKFLOW_STAGE_ID_SUBMISSION);

			// Get a default user group id for an Author
			$userGroupDao =& DAORegistry::getDAO('UserGroupDAO');
			$defaultAuthorGroup =& $userGroupDao->getDefaultByRoleId($this->press->getId(), ROLE_ID_AUTHOR);

			// Set user to initial author
			$authorDao =& DAORegistry::getDAO('AuthorDAO');
			$user =& Request::getUser();
			$author = new Author();
			$author->setFirstName($user->getFirstName());
			$author->setMiddleName($user->getMiddleName());
			$author->setLastName($user->getLastName());
			$author->setAffiliation($user->getAffiliation(null), null);
			$author->setCountry($user->getCountry());
			$author->setEmail($user->getEmail());
			$author->setUrl($user->getUrl());
			$author->setUserGroupId($defaultAuthorGroup->getId());
			$author->setBiography($user->getBiography(null), null);
			$author->setPrimaryContact(1);

			$monographDao->insertMonograph($this->monograph);
			$this->monographId = $this->monograph->getId();
			$author->setSubmissionId($this->monographId);
			$authorDao->insertAuthor($author);
		}

		return $this->monographId;
	}
}

?>
