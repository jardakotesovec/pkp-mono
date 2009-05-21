<?php

/**
 * @file classes/manager/form/SeriesForm.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SeriesForm
 * @ingroup manager_form
 *
 * @brief Form for creating and modifying series.
 */

// $Id$


import('manager.form.AcquisitionsArrangementForm');

class SeriesForm extends AcquisitionsArrangementForm {

	/**
	 * Constructor.
	 * @param $pressId int omit for a new series
	 */
	function SeriesForm($seriesId = null) {
		parent::Form('manager/series/seriesForm.tpl');

		$press =& Request::getPress();
		$this->acquisitionsArrangementId = $seriesId;

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'manager.series.form.titleRequired'));
		$this->addCheck(new FormValidatorLocale($this, 'abbrev', 'required', 'manager.series.form.abbrevRequired'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCustom($this, 'reviewFormId', 'optional', 'manager.series.form.reviewFormId', array(DAORegistry::getDAO('ReviewFormDAO'), 'reviewFormExists'), array($press->getId())));

		$this->includeAcquisitionsArrangementEditor = $this->omitAcquisitionsArrangementEditor = null;

		// Get a list of acquisitions editors for this press.
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$this->acquisitionsArrangementEditors =& $roleDao->getUsersByRoleId(ROLE_ID_ACQUISITIONS_EDITOR, $press->getId());
		$this->acquisitionsArrangementEditors =& $this->acquisitionsArrangementEditors->toArray();
	}

	/**
	 * Display the form.
	 */
	function display() {
		parent::display();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId','press.managementPages.series');
	}

	/**
	 * Get the names of fields for which localized data is allowed.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return parent::getLocaleFieldNames();
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		parent::initData();
		$arrangementDao =& DAORegistry::getDAO('AcquisitionsArrangementDAO');
		$this->_data = array_merge($this->_data,
					array(
						'printIssn' => $arrangementDao->getSetting($this->acquisitionsArrangementId, 'printIssn'),
						'onlineIssn' => $arrangementDao->getSetting($this->acquisitionsArrangementId, 'onlineIssn')
					)
				);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('printIssn', 'onlineIssn'));
		parent::readInputData();
	}

	/**
	 * Save section.
	 */
	function execute() {
		parent::execute();
		$arrangementDao =& DAORegistry::getDAO('AcquisitionsArrangementDAO');
		$arrangementDao->updateSetting($this->acquisitionsArrangementId, 'printIssn', $this->getData('printIssn'));
		$arrangementDao->updateSetting($this->acquisitionsArrangementId, 'onlineIssn', $this->getData('onlineIssn'));

	}
}

?>