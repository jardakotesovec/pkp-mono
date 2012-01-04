<?php

/**
 * @file controllers/grid/catalogEntry/form/IdentificationCodeForm.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IdentificationCodeForm
 * @ingroup controllers_grid_catalogEntry_form
 *
 * @brief Form for adding/editing an identification code
 */

import('lib.pkp.classes.form.Form');

class IdentificationCodeForm extends Form {
	/** The monograph associated with the format being edited **/
	var $_monograph;

	/** Identification Code the code being edited **/
	var $_identificationCode;

	/**
	 * Constructor.
	 */
	function IdentificationCodeForm($monograph, $identificationCode) {
		parent::Form('controllers/grid/catalogEntry/form/codeForm.tpl');
		$this->setMonograph($monograph);
		$this->setIdentificationCode($identificationCode);

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'code', 'required', 'grid.catalogEntry.codeRequired'));
		$this->addCheck(new FormValidator($this, 'value', 'required', 'grid.catalogEntry.valueRequired'));
		$this->addCheck(new FormValidator($this, 'assignedPublicationFormatId', 'required', 'grid.catalogEntry.publicationFormatRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	//
	// Getters and Setters
	//
	/**
	* Get the code
	* @return IdentificationCode
	*/
	function getIdentificationCode() {
		return $this->_identificationCode;
	}

	/**
	* Set the code
	* @param @identificationCode IdentificationCode
	*/
	function setIdentificationCode($identificationCode) {
		$this->_identificationCode =& $identificationCode;
	}

	/**
	 * Get the Monograph
	 * @return Monograph
	 */
	function getMonograph() {
		return $this->_monograph;
	}

	/**
	 * Set the Monograph
	 * @param Monograph
	 */
	function setMonograph($monograph) {
		$this->_monograph =& $monograph;
	}


	//
	// Overridden template methods
	//
	/**
	* Initialize form data from the identification code.
	*/
	function initData() {
		$code =& $this->getIdentificationCode();

		if ($code) {
			$this->_data = array(
				'identificationCodeId' => $code->getId(),
				'code' => $code->getCode(),
				'value' => $code->getValue()
			);
		}
	}

	/**
	 * Fetch the form.
	 * @see Form::fetch()
	 */
	function fetch(&$request) {
		$code =& $this->getIdentificationCode();

		$templateMgr =& TemplateManager::getManager();
		$onixCodelistItemDao =& DAORegistry::getDAO('ONIXCodelistItemDAO');
		$codes =& $onixCodelistItemDao->getCodes('List5'); // ONIX list for these

		$templateMgr->assign_by_ref('identificationCodes', $codes);

		$monograph =& $this->getMonograph();
		$templateMgr->assign('monographId', $monograph->getId());
		$identificationCode =& $this->getIdentificationCode();
		if ($identificationCode != null) {
			$templateMgr->assign('identificationCodeId', $identificationCode->getId());
			$templateMgr->assign('assignedPublicationFormatId', $identificationCode->getAssignedPublicationFormatId());
			$templateMgr->assign('code', $identificationCode->getCode());
			$templateMgr->assign('value', $identificationCode->getValue());
		} else { // loading a blank form
			$templateMgr->assign('assignedPublicationFormatId', (int) $request->getUserVar('assignedPublicationFormatId')); // validated in execute()
		}
		return parent::fetch($request);
	}

	/**
	 * Assign form data to user-submitted data.
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array(
			'identificationCodeId',
			'assignedPublicationFormatId',
			'code',
			'value',
		));
	}

	/**
	 * Save the code
	 * @see Form::execute()
	 * @see Form::execute()
	 */
	function execute() {
		$identificationCodeDao =& DAORegistry::getDAO('IdentificationCodeDAO');
		$assignedPublicationFormatDao =& DAORegistry::getDAO('AssignedPublicationFormatDAO');

		$monograph = $this->getMonograph();
		$identificationCode =& $this->getIdentificationCode();
		$assignedPublicationFormat =& $assignedPublicationFormatDao->getById($this->getData('assignedPublicationFormatId', $monograph->getId()));

		if (!$identificationCode) {
			// this is a new assigned format to this published monograph
			$identificationCode = $identificationCodeDao->newDataObject();
			if ($assignedPublicationFormat != null) { // ensure this assigned format is in this monograph
				$identificationCode->setAssignedPublicationFormatId($assignedPublicationFormat->getAssignedPublicationFormatId());
				$existingFormat = false;
			} else {
				fatalError('This assigned format not in authorized monograph context!');
			}
		} else {
			$existingFormat = true;
			if ($assignedPublicationFormat->getAssignedPublicationFormatId() !== $identificationCode->getAssignedPublicationFormatId()) fatalError('Invalid format!');
		}

		$identificationCode->setCode($this->getData('code'));
		$identificationCode->setValue($this->getData('value'));

		if ($existingFormat) {
			$identificationCodeDao->updateObject($identificationCode);
			$identificationCodeId = $identificationCode->getId();
		} else {
			$identificationCodeId = $identificationCodeDao->insertObject($identificationCode);
		}

		return $identificationCodeId;
	}
}

?>
