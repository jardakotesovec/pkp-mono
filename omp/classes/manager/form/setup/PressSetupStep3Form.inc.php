<?php

/**
 * @file classes/manager/form/setup/PressSetupStep3Form.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PressSetupStep3Form
 * @ingroup manager_form_setup
 *
 * @brief Form for Step 3 of press setup.
 */

// $Id$


import('manager.form.setup.PressSetupForm');

class PressSetupStep3Form extends PressSetupForm {

	/**
	 * Constructor.
	 */
	function PressSetupStep3Form() {
		parent::PressSetupForm(
			3,
			array(
			)
		);
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('newBookFileType', 'bookFileTypeSelect'));
		parent::readInputData();
	}

	/**
	 * Display the form
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$press =& Request::getPress();

		$templateMgr->assign_by_ref('bookFileTypes', $press->getSetting('bookFileTypes'));

		parent::display();
	}
}

?>
