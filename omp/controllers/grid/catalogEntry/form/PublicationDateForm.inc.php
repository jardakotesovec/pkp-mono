<?php

/**
 * @file controllers/grid/catalogEntry/form/PublicationDateForm.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublicationDateForm
 * @ingroup controllers_grid_catalogEntry_form
 *
 * @brief Form for adding/editing a publication date
 */

import('lib.pkp.classes.form.Form');

class PublicationDateForm extends Form {
	/** The monograph associated with the format being edited **/
	var $_monograph;

	/** PublicationDate the code being edited **/
	var $_publicationDate;

	/**
	 * Constructor.
	 */
	function PublicationDateForm($monograph, $publicationDate) {
		parent::Form('controllers/grid/catalogEntry/form/pubDateForm.tpl');
		$this->setMonograph($monograph);
		$this->setPublicationDate($publicationDate);

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'role', 'required', 'grid.catalogEntry.roleRequired'));
		$this->addCheck(new FormValidator($this, 'dateFormat', 'required', 'grid.catalogEntry.dateFormatRequired'));
		$this->addCheck(new FormValidator($this, 'date', 'required', 'grid.catalogEntry.dateRequired'));
		$this->addCheck(new FormValidator($this, 'assignedPublicationFormatId', 'required', 'grid.catalogEntry.publicationFormatRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	//
	// Getters and Setters
	//
	/**
	* Get the date
	* @return PublicationDate
	*/
	function getPublicationDate() {
		return $this->_publicationDate;
	}

	/**
	* Set the date
	* @param @publicationDate PublicationDate
	*/
	function setPublicationDate($publicationDate) {
		$this->_publicationDate =& $publicationDate;
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
	* Initialize form data from the publication date.
	*/
	function initData() {
		$date =& $this->getPublicationDate();

		if ($date) {
			$this->_data = array(
				'publicationDateId' => $date->getId(),
				'role' => $date->getRole(),
				'dateFormat' => $date->getDateFormat(),
				'date' => $date->getDate()
			);
		}
	}

	/**
	 * Fetch the form.
	 * @see Form::fetch()
	 */
	function fetch(&$request) {

		$templateMgr =& TemplateManager::getManager();
		$assignedPublicationFormatId = null;

		$monograph =& $this->getMonograph();
		$templateMgr->assign('monographId', $monograph->getId());
		$publicationDate =& $this->getPublicationDate();

		if ($publicationDate) {
			$assignedPublicationFormatId = $publicationDate->getAssignedPublicationFormatId();
			$templateMgr->assign('publicationDateId', $publicationDate->getId());
			$templateMgr->assign('role', $publicationDate->getRole());
			$templateMgr->assign('dateFormat', $publicationDate->getDateFormat());
			$templateMgr->assign('date', $publicationDate->getDate());
			$assignedPublicationFormatId = $publicationDate->getAssignedPublicationFormatId();
		} else { // loading a blank form
			$assignedPublicationFormatId = (int) $request->getUserVar('assignedPublicationFormatId');
			$templateMgr->assign('dateFormat', '20'); // YYYYMMDD Onix code as a default
		}

		$assignedPublicationFormatDao =& DAORegistry::getDAO('AssignedPublicationFormatDAO');
		$assignedPublicationFormat =& $assignedPublicationFormatDao->getById($assignedPublicationFormatId, $monograph->getId());

		if ($assignedPublicationFormat) { // the format exists for this monograph
			$templateMgr->assign('assignedPublicationFormatId', $assignedPublicationFormatId);
			$assignedRoles = array_keys($assignedPublicationFormat->getPublicationDates()->toAssociativeArray('role')); // currently assigned roles
			if ($publicationDate) $assignedRoles = array_diff($assignedRoles, array($publicationDate->getRole())); // allow existing roles to keep their value
			$onixCodelistItemDao =& DAORegistry::getDAO('ONIXCodelistItemDAO');
			$roles =& $onixCodelistItemDao->getCodes('List163', $assignedRoles); // ONIX list for these
			$templateMgr->assign_by_ref('publicationDateRoles', $roles);

			//load our date formats
			$dateFormats =& $onixCodelistItemDao->getCodes('List55');
			$templateMgr->assign_by_ref('publicationDateFormats', $dateFormats);
		} else {
			fatalError('Format not in authorized monograph');
		}

		return parent::fetch($request);
	}

	/**
	 * Assign form data to user-submitted data.
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array(
			'publicationDateId',
			'assignedPublicationFormatId',
			'role',
			'dateFormat',
			'date',
		));
	}

	/**
	 * Save the date
	 * @see Form::execute()
	 */
	function execute() {
		$publicationDateDao =& DAORegistry::getDAO('PublicationDateDAO');
		$assignedPublicationFormatDao =& DAORegistry::getDAO('AssignedPublicationFormatDAO');

		$monograph = $this->getMonograph();
		$publicationDate =& $this->getPublicationDate();
		$assignedPublicationFormat =& $assignedPublicationFormatDao->getById($this->getData('assignedPublicationFormatId'), $monograph->getId());

		if (!$publicationDate) {
			// this is a new publication date for this published monograph
			$publicationDate = $publicationDateDao->newDataObject();
			if ($assignedPublicationFormat != null) { // ensure this assigned format is in this monograph
				$publicationDate->setAssignedPublicationFormatId($assignedPublicationFormat->getAssignedPublicationFormatId());
				$existingFormat = false;
			} else {
				fatalError('This assigned format not in authorized monograph context!');
			}
		} else {
			$existingFormat = true;
			if ($assignedPublicationFormat->getAssignedPublicationFormatId() !== $publicationDate->getAssignedPublicationFormatId()) fatalError('Invalid format!');
		}

		$publicationDate->setRole($this->getData('role'));
		$publicationDate->setDateFormat($this->getData('dateFormat'));
		$publicationDate->setDate($this->getData('date'));

		if ($existingFormat) {
			$publicationDateDao->updateObject($publicationDate);
			$publicationDateId = $publicationDate->getId();
		} else {
			$publicationDateId = $publicationDateDao->insertObject($publicationDate);
		}

		return $publicationDateId;
	}
}

?>