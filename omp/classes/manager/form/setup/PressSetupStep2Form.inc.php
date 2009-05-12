<?php

/**
 * @file classes/manager/form/setup/PresSetupStep2Form.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PressSetupStep2Form
 * @ingroup manager_form_setup
 *
 * @brief Form for Step 2 of press setup.
 */

// $Id$


import("manager.form.setup.PressSetupForm");

class PressSetupStep2Form extends PressSetupForm {
	/**
	 * Constructor.
	 */
	function PressSetupStep2Form() {
		parent::PressSetupForm(
			2,
			array(
				'focusScopeDesc' => 'string',
				'numWeeksPerReview' => 'int',
				'remindForInvite' => 'bool',
				'remindForSubmit' => 'bool',
				'numDaysBeforeInviteReminder' => 'int',
				'numDaysBeforeSubmitReminder' => 'int',
				'rateReviewerOnQuality' => 'bool',
				'restrictReviewerFileAccess' => 'bool',
				'reviewerAccessKeysEnabled' => 'bool',
				'showEnsuringLink' => 'bool',
				'reviewPolicy' => 'string',
				'mailSubmissionsToReviewers' => 'bool',
				'reviewGuidelines' => 'string',
				'authorSelectsEditor' => 'bool',
				'privacyStatement' => 'string',
				'customAboutItems' => 'object',
				'enableLockss' => 'bool',
				'lockssLicense' => 'string',
				'reviewerDatabaseLinks' => 'object',
				'notifyAllAuthorsOnDecision' => 'bool'
			)
		);

		$this->addCheck(new FormValidatorEmail($this, 'envelopeSender', 'optional', 'user.profile.form.emailRequired'));
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('focusScopeDesc', 'reviewPolicy', 'reviewGuidelines', 'privacyStatement', 'customAboutItems', 'lockssLicense');
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		if (Config::getVar('general', 'scheduled_tasks'))
			$templateMgr->assign('scheduledTasksEnabled', true);

		parent::display();
	}
}

?>
