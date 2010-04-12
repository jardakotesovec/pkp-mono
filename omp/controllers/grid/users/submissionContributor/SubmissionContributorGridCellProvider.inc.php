<?php

/**
 * @file classes/controllers/grid/users/SubmissionContributorGridCellProvider.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataObjectGridCellProvider
 * @ingroup controllers_grid
 *
 * @brief Base class for a cell provider that can retrieve labels for submission contributors
 */

import('controllers.grid.DataObjectGridCellProvider');

class SubmissionContributorGridCellProvider extends DataObjectGridCellProvider {
	/**
	 * Constructor
	 */
	function SubmissionContributorGridCellProvider() {
		parent::DataObjectGridCellProvider();
	}

	//
	// Template methods from GridCellProvider
	//
	/**
	 * This method extracts the label information from a contributor (Author)
	 * @see DataObjectGridCellProvider::getLabel()
	 * @param $element DataObject
	 * @param $columnId string
	 */
	function getLabel(&$element, $columnId) {
		assert(is_a($element, 'DataObject') && !empty($columnId));
		switch ($columnId) {
			case 'name':
				return $element->getFullName();
			case 'userGroupId':
				return $element->getLocalizedUserGroupName();
			case 'email':
				return parent::getLabel($element, $columnId);
			case 'principalContact':
				if($element->getPrimaryContact()) {
					$templateMgr =& TemplateManager::getManager();
					return $templateMgr->fetch('controllers/grid/users/submissionContributor/primaryContact.tpl');
				}
		}
	}
}