<?php

/**
 * @file controllers/grid/bookFileType/form/BookFileTypeForm.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BookFileTypeForm
 * @ingroup controllers_grid_bookFileType_form
 *
 * @brief Form for adding/editing a Book File Type.
 */

import('form.Form');

class BookFileTypeForm extends Form {
	/** the id for the series being edited **/
	var $bookFileTypeId;

	/**
	 * Constructor.
	 */
	function BookFileTypeForm($bookFileTypeId = null) {
		$this->bookFileTypeId = $bookFileTypeId;
		parent::Form('controllers/grid/bookFileType/form/bookFileTypeForm.tpl');

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'name', 'required', 'manager.setup.form.bookFileType.nameRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData(&$args, &$request) {
		$press =& $request->getPress();

		$bookFileTypeDao =& DAORegistry::getDAO('BookFileTypeDAO');

		if($this->bookFileTypeId) {
			$bookFileType =& $bookFileTypeDao->getById($this->bookFileTypeId, $press->getId());
		}

		if (isset($bookFileType) ) {
			$this->_data = array(
				'bookFileTypeId' => $this->bookFileTypeId,
				'name' => $bookFileType->getLocalizedName(),
				'designation' => $bookFileType->getLocalizedDesignation(),
				'sortable' => $bookFileType->getSortable()
			);
		} else {
			$this->_data = array(
				'name' => '',
				'designation' => ''
			);
		}

		// grid related data
		$this->_data['gridId'] = $args['gridId'];
		$this->_data['rowId'] = isset($args['rowId']) ? $args['rowId'] : null;
	}

	/**
	 * Display
	 */
	function display() {
		Locale::requireComponents(array(LOCALE_COMPONENT_OMP_MANAGER));
		parent::display();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('bookFileTypeId', 'name', 'designation', 'sortable'));
		$this->readUserVars(array('gridId', 'rowId'));
	}

	/**
	 * Save email template.
	 */
	function execute($args, $request) {
		$bookFileTypeDao =& DAORegistry::getDAO('BookFileTypeDAO');
		$press =& $request->getPress();

		// Update or insert Book File Type
		if (!isset($this->bookFileTypeId)) {
			$bookFileType = $bookFileTypeDao->newDataObject();
		} else {
			$bookFileType =& $bookFileTypeDao->getById($this->bookFileTypeId);
		}

		$bookFileType->setName($this->getData('name'), Locale::getLocale()); // Localized
		$bookFileType->setDesignation($this->getData('designation'), Locale::getLocale()); // Localized
		$bookFileType->setSortable($this->getData('sortable'));

		if (!isset($this->bookFileTypeId)) {
			$this->bookFileTypeId = $bookFileTypeDao->insertObject($bookFileType);
		} else {
			$bookFileTypeDao->updateObject($bookFileType);
		}

		return true;
	}
}

?>
