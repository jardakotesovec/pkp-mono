<?php

/**
 * @file controllers/tab/settings/submissions/form/SubmissionsForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalSetupStep2Form
 * @ingroup manager_form_setup
 *
 * @brief Form for Step 2 of journal setup.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class SubmissionsForm extends ContextSettingsForm {
	/**
	 * Constructor.
	 */
	function SubmissionsForm($wizardMode = false) {
		$settings = array(
			'restrictReviewerFileAccess' => 'bool',
			'reviewerAccessKeysEnabled' => 'bool',
			'mailSubmissionsToReviewers' => 'bool',
			'authorSelectsEditor' => 'bool',
			'notifyAllAuthorsOnDecision' => 'bool',
			'pubFreqPolicy' => 'string',
			'useCopyeditors' => 'bool',
			'copyeditInstructions' => 'string',
			'useLayoutEditors' => 'bool',
			'layoutInstructions' => 'string',
			'provideRefLinkInstructions' => 'bool',
			'refLinkInstructions' => 'string',
			'useProofreaders' => 'bool',
			'proofInstructions' => 'string',
			'publishingMode' => 'int',
			'enablePublicIssueId' => 'bool',
			'enablePublicArticleId' => 'bool',
			'enablePublicGalleyId' => 'bool',
			'enablePublicSuppFileId' => 'bool',
			'enablePageNumber' => 'bool',
			'copyrightNotice' => 'string',
			'includeCreativeCommons' => 'bool',
			'copyrightNoticeAgree' => 'bool',
			'requireAuthorCompetingInterests' => 'bool',
			'requireReviewerCompetingInterests' => 'bool',
			'metaDiscipline' => 'bool',
			'metaDisciplineExamples' => 'string',
			'metaSubjectClass' => 'bool',
			'metaSubjectClassTitle' => 'string',
			'metaSubjectClassUrl' => 'string',
			'metaSubject' => 'bool',
			'metaSubjectExamples' => 'string',
			'metaCoverage' => 'bool',
			'metaCoverageGeoExamples' => 'string',
			'metaCoverageChronExamples' => 'string',
			'metaCoverageResearchSampleExamples' => 'string',
			'metaType' => 'bool',
			'metaTypeExamples' => 'string'
		);
		parent::ContextSettingsForm($settings, 'controllers/tab/settings/submissions/form/submissionsForm.tpl', $wizardMode);

		$this->addCheck(new FormValidatorEmail($this, 'envelopeSender', 'optional', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'copySubmissionAckAddress', 'optional', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorLocaleURL($this, 'metaSubjectClassUrl', 'optional', 'manager.setup.subjectClassificationURLValid'));
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('authorGuidelines', 'copyrightNotice', 'metaDisciplineExamples', 'metaSubjectClassTitle', 'metaSubjectClassUrl', 'metaSubjectExamples', 'metaCoverageGeoExamples', 'metaCoverageChronExamples', 'metaCoverageResearchSampleExamples', 'metaTypeExamples', 'pubFreqPolicy', 'copyeditInstructions', 'layoutInstructions', 'refLinkInstructions', 'proofInstructions');
	}
}

?>
