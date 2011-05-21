<?php

/**
 * @file controllers/tab/settings/reviewStage/form/ReviewStageForm.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewStageForm
 * @ingroup controllers_tab_settings_reviewStage_form
 *
 * @brief Form to edit press review stage settings.
 */


// Import the base Form.
import('controllers.tab.settings.form.PressSettingsForm');

class ReviewStageForm extends PressSettingsForm {

	/**
	 * Constructor.
	 */
	function ReviewStageForm() {
		$settings = array(
			'reviewGuidelines' => 'string',
			'mailSubmissionsToReviewers' => 'bool',
			'numWeeksPerResponse' => 'int',
			'numWeeksPerReview' => 'int',
			'remindForInvite' => 'bool',
			'remindForSubmit' => 'bool',
			'numDaysBeforeInviteReminder' => 'int',
			'numDaysBeforeSubmitReminder' => 'int',
			'rateReviewerOnQuality' => 'bool',
			'reviewerAccessKeysEnabled' => 'bool',
			'restrictReviewerFileAccess' => 'bool',
			'showEnsuringLink' => 'bool'
		);

		parent::PressSettingsForm($settings, 'controllers/tab/settings/reviewStage/form/reviewStageForm.tpl');
	}


	//
	// Implement template methods from Form.
	//
	/**
	 * @see Form::getLocaleFieldNames()
	 */
	function getLocaleFieldNames() {
		return array('reviewGuidelines');
	}

	/**
	 * @see PressSettingsForm::fetch()
	 */
	function fetch(&$request) {
		$params = null;
		if (Config::getVar('general', 'scheduled_tasks'))
			$params = array('scheduledTasksEnabled' => true);

		return parent::fetch(&$request, $params);
	}
}

?>