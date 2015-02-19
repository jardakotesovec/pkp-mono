<?php

/**
 * @file classes/user/form/RegistrationForm.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RegistrationForm
 * @ingroup user_form
 *
 * @brief Form for user registration.
 */

import('lib.pkp.classes.user.form.PKPRegistrationForm');

class RegistrationForm extends PKPRegistrationForm {
	/**
	 * Constructor.
	 */
	function RegistrationForm($site, $existingUser = false) {
		parent::PKPRegistrationForm($site, $existingUser);
	}

	/**
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('openAccessNotification'));
		return parent::readInputData();
	}

	/**
	 * @see Form::display()
	 */
	function display($request) {
		$journal = $request->getJournal();
		$templateMgr = TemplateManager::getManager();
		$templateMgr->assign('enableOpenAccessNotification', $journal->getSetting('enableOpenAccessNotification'));
		parent::display($request);
	}

	/**
	 * Register a new user.
	 */
	function execute($request) {
		$userId = parent::execute($request);
		if ($this->getData('openAccessNotification')) {
			$journal = $request->getJournal();
			$userSettingsDao = DAORegistry::getDAO('UserSettingsDAO');
			$userSettingsDao->updateSetting($userId, 'openAccessNotification', true, 'bool', $journal->getId());
		}
	}
}

?>
