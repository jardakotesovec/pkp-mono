<?php

/**
 * @file controllers/grid/admin/categories/form/AdminCategoryForm.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminCategoryForm
 * @ingroup controllers_grid_admin_categories_form
 *
 * @brief Form for adding/editing a category
 * stores/retrieves from an associative array
 */

import('lib.pkp.classes.form.Form');

class AdminCategoryForm extends Form {
	/** @var $categoryId int The id for the submissionChecklist being edited */
	var $categoryId;

	/** @var $category Object The category being edited */
	var $category;

	/**
	 * Constructor.
	 */
	function AdminCategoryForm($categoryId = null) {
		$this->categoryId = $categoryId;
		parent::Form('controllers/grid/admin/categories/form/adminCategoryForm.tpl');

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'name', 'required', 'common.nameRequired'));
		$this->addCheck(new FormValidatorPost($this));

		if ($categoryId != null) {
			$categoryDao = DAORegistry::getDAO('CategoryDAO');
			$entryDao = $categoryDao->getEntryDAO();
			$category = $entryDao->getById($categoryId, $categoryDao->build()->getId());
			if (!$category) fatalError('Invalid category ID!');
			$this->category = $category;
		}
	}

	/**
	 * Initialize form data from current settings.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function initData($args, &$request) {
		$categoryDao = DAORegistry::getDAO('CategoryDAO');
		$entryDao = $categoryDao->getEntryDAO();

		// assign the data to the form
		$this->_data = array();
		if ($this->category) {
			$this->_data['name'] = $this->category->getName();
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('name'));
	}

	/**
	 * Save category.
	 */
	function execute($args, &$request) {
		$categoryDao = DAORegistry::getDAO('CategoryDAO');
		$entryDao = $categoryDao->getEntryDAO();
		if ($this->category) {
			$entryDao->updateObject($this->category);
		} else {
			$category = $entryDao->newDataObject();
			$category->setName($this->getData('name'), null);
			$category->setControlledVocabId($categoryDao->build()->getId());
			$entryDao->insertObject($category);
		}
		$categoryDao->rebuildCache();
		return true;
	}
}

?>
