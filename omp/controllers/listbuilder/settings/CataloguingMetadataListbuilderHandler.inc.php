<?php

/**
 * @file controllers/listbuilder/settings/CataloguingMetadataListbuilderHandler.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CataloguingMetadataListbuilderHandler
 * @ingroup listbuilder
 *
 * @brief Class for adding cataloguing metadata
 */

import('controllers.listbuilder.settings.SetupListbuilderHandler');

class CataloguingMetadataListbuilderHandler extends SetupListbuilderHandler {
	/**
	 * Constructor
	 */
	function CataloguingMetadataFieldsListbuilderHandler() {
		parent::SetupListbuilderHandler();
	}


	/**
	 * Load the list from an external source into the grid structure
	 */
	function loadList() {
		$cataloguingMetadataFieldDao =& DAORegistry::getDAO('CataloguingMetadataFieldDAO');
		$press =& $this->getPress();

		$cataloguingMetadataFields =& $cataloguingMetadataFieldDao->getEnabledByPressId($press->getId());

		$items = array();
		if (!is_null($cataloguingMetadataFields)) {
			foreach($cataloguingMetadataFields as $item) {
				$id = $item->getId();
				$items[$id] = array('name' => $item->getName(null), 'id' => $id);
			}
		}

		$this->setGridDataElements($items);
	}

	/**
	 * Bounce a modified entry back to the client
	 * @see ListbuilderHandler::getRowDataElement
	 */
	function getRowDataElement(&$request, $rowId) {
		return $this->getNewRowId($request);
	}


	/**
	 * Persist an update to an entry.
	 * @param $request PKPRequest
	 * @param $rowId mixed ID of row to modify
	 * @param $newRowId mixed New entry with changes to persist
	 * @return boolean
	 */
	function updateEntry(&$request, $rowId, $newRowId) {
		$cataloguingMetadataFieldDao =& DAORegistry::getDAO('CataloguingMetadataFieldDAO');
		$press =& $this->getPress();
		$cataloguingMetadataField = $cataloguingMetadataFieldDao->getById($rowId, $pressId);

		$cataloguingMetadataField->setName($newRowId['name'], null); // Localized

		$cataloguingMetadataFieldDao->updateObject($cataloguingMetadataField);
		return true;
	}


	/**
	 * Persist the deletion of an entry.
	 * @param $request PKPRequest
	 * @param $rowId mixed ID of row to modify
	 * @return boolean
	 */
	function deleteEntry(&$request, $rowId) {
		$cataloguingMetadataFieldDao =& DAORegistry::getDAO('CataloguingMetadataFieldDAO');
		$press =& $this->getPress();
		$cataloguingMetadataField = $cataloguingMetadataFieldDao->deleteById($rowId, $pressId);
		return true;
	}


	/**
	 * Persist a new entry insert.
	 * @param $request PKPRequest
	 * @param $newRowId mixed New entry with data to persist
	 * @return boolean
	 */
	function insertEntry(&$request, $newRowId) {
		$cataloguingMetadataFieldDao =& DAORegistry::getDAO('CataloguingMetadataFieldDAO');
		$cataloguingMetadataField = $cataloguingMetadataFieldDao->newDataObject();
		$press =& $this->getPress();
		$cataloguingMetadataField->setPressId($press->getId());
		$cataloguingMetadataField->setEnabled(true);

		$cataloguingMetadataField->setName($newRowId['name'], null); // Localized

		$cataloguingMetadataFieldDao->insertObject($cataloguingMetadataField);
		return true;
	}


	//
	// Overridden template methods
	//
	/**
	 * Configure the grid
	 * @param PKPRequest $request
	 */
	function initialize(&$request) {
		parent::initialize($request);

		Locale::requireComponents(array(LOCALE_COMPONENT_OMP_MANAGER));

		// Basic configuration
		$this->setSourceType(LISTBUILDER_SOURCE_TYPE_TEXT); // Free text input

		$this->loadList();

		$this->addColumn(new MultilingualListbuilderGridColumn($this, 'name', 'common.name'));
	}

	/**
	 * @see GridHandler::getIsSubcomponent
	 */
	function getIsSubcomponent() {
		return true;
	}
}

?>
