<?php

/**
 * @file controllers/listbuilder/settings/PublicationFormatsListbuilderHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublicationFormatsListbuilderHandler
 * @ingroup listbuilder
 *
 * @brief Class for adding new publication formats
 */

import('controllers.listbuilder.settings.SetupListbuilderHandler');

class PublicationFormatsListbuilderHandler extends SetupListbuilderHandler {
	/** @var $press Press */
	var $press;


	/**
	 * Constructor
	 */
	function PublicationFormatsListbuilderHandler() {
		parent::SetupListbuilderHandler();
	}


	/**
	 * Load the list from an external source into the grid structure
	 */
	function loadList() {
		$publicationFormatDao =& DAORegistry::getDAO('PublicationFormatDAO');
		$pressDao =& DAORegistry::getDAO('PressDAO');

		$publicationFormats =& $publicationFormatDao->getEnabledByPressId($this->press->getId());

		$items = array();
		foreach($publicationFormats as $item) {
			$id = $item->getId();
			$items[$id] = array('name' => $item->getLocalizedName(), 'designation' => $item->getLocalizedDesignation(), 'id' => $id);
		}
		$this->setGridDataElements($items);
	}


	/**
	 * Persist an update to an entry.
	 * @param $rowId mixed ID of row to modify
	 * @param $existingEntry mixed Existing entry to be modified
	 * @param $newEntry mixed New entry with changes to persist
	 * @return boolean
	 */
	function updateEntry($rowId, $existingEntry, $newEntry) {
		$publicationFormatDao =& DAORegistry::getDAO('PublicationFormatDAO');
		$publicationFormat = $publicationFormatDao->getById($rowId);

		$locale = Locale::getLocale(); // FIXME: Localize.
		$publicationFormat->setName($newEntry->name, $locale);
		$publicationFormat->setDesignation($newEntry->designation, $locale);

		$publicationFormatDao->updateObject($publicationFormat);
		return true;
	}


	/**
	 * Persist a new entry insert.
	 * @param $entry mixed New entry with data to persist
	 * @return boolean
	 */
	function insertEntry($entry) {
		$publicationFormatDao =& DAORegistry::getDAO('PublicationFormatDAO');
		$publicationFormat = $publicationFormatDao->newDataObject();
		$publicationFormat->setPressId($this->press->getId());
		$publicationFormat->setEnabled(true);

		$locale = Locale::getLocale(); // FIXME: Localize.
		$publicationFormat->setName($entry->name, $locale);
		$publicationFormat->setDesignation($entry->designation, $locale);

		$publicationFormatDao->insertObject($publicationFormat);
		return true;
	}


	//
	// Overridden template methods
	//
	/*
	 * Configure the grid
	 * @param PKPRequest $request
	 */
	function initialize(&$request) {
		parent::initialize($request);
		$this->press =& $request->getPress();

		Locale::requireComponents(array(LOCALE_COMPONENT_OMP_MANAGER));

		// Basic configuration
		$this->setSourceType(LISTBUILDER_SOURCE_TYPE_TEXT); // Free text input

		$this->loadList();

		$nameColumn = new GridColumn('name', 'common.name');
		$nameColumn->addFlag('editable');
		$this->addColumn($nameColumn);

		$designationColumn = new GridColumn('designation', 'common.designation');
		$designationColumn->addFlag('editable');
		$this->addColumn($designationColumn);
	}


	/**
	 * Create a new data element from a request. This is used to format
	 * new rows prior to their insertion.
	 * @param $request PKPRequest
	 * @param $elementId int
	 * @return object
	 */
	function &getDataElementFromRequest(&$request, &$elementId) {
		$newItem = array(
			'name' => $request->getUserVar('name'),
			'designation' => $request->getUserVar('designation')
		);
		$elementId = $request->getUserVar('rowId');
		return $newItem;
	}
}

?>
