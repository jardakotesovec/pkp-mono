<?php

/**
 * @file controllers/listbuilder/setup/SeriesEditorsListbuilderHandler.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MastheadMembershipListbuilderHandler
 * @ingroup listbuilder
 *
 * @brief Class for adding new Press Divisions
 */

import('controllers.listbuilder.setup.SetupListbuilderHandler');

class SeriesEditorsListbuilderHandler extends SetupListbuilderHandler {
	/** @var boolean internal state variable, true if row handler has been instantiated */
	var $_rowInstantiated = false;

	/** @var The group ID for this listbuilder */
	var $seriesId;

	/**
	 * Constructor
	 */
	function SeriesEditorsListbuilderHandler() {
		parent::SetupListbuilderHandler();
	}

	function setSeriesId($seriesId) {
		$this->seriesId = $seriesId;
	}

	function getSeriesId() {
		return $this->seriesId;
	}

	/* Load the list from an external source into the grid structure */
	function loadList(&$request) {
		$press =& $request->getPress();
		$seriesId = $this->getSeriesId();

		$seriesEditorsDao =& DAORegistry::getDAO('SeriesEditorsDAO');

		$assignedSeriesEditors =& $seriesEditorsDao->getEditorsBySeriesId($seriesId, $press->getId());

		$items = array();
		foreach ($assignedSeriesEditors as $seriesEditor) {
			$user = $seriesEditor['user'];
			$id = $user->getId();
			$items[$id] = array('item' => $user->getFullName(), 'attribute' => $user->getUsername());
		}
		$this->setData($items);
	}


	/* Get possible items to populate autosuggest list with */
	function getPossibleItemList(&$request) {
		$press =& $request->getPress();
		$seriesId = $this->getSeriesId();

		$seriesEditorsDao =& DAORegistry::getDAO('SeriesEditorsDAO');

		$unassignedSeriesEditors =& $seriesEditorsDao->getEditorsNotInSeries($press->getId(), $seriesId);

		$itemList = array();
		foreach ($unassignedSeriesEditors as $seriesEditor) {
			$itemList[] = array('id' => $seriesEditor->getId(),
			 					'name' => $seriesEditor->getFullName(),
			 					'abbrev' => $seriesEditor->getUsername()
								);
		}

		return $itemList;
	}

	/**
	 * @see lib/pkp/classes/handler/PKPHandler#getRemoteOperations()
	 */
	function getRemoteOperations() {
		return array_merge(parent::getRemoteOperations(), array('getAutocompleteSource'));
	}

	//
	// Overridden template methods
	//
	/**
	 * Need to override the fetch method to provide seriesID as an argument
	 */
	function fetch(&$args, &$request) {
		// FIXME: User validation

		$templateMgr =& TemplateManager::getManager();
		$this->setupTemplate();
		$router =& $request->getRouter();

		// Let the subclass configure the listbuilder
		$this->initialize($request);
		$seriesId = $request->getUserVar('seriesId');

		$templateMgr->assign('itemId', $seriesId); // Autocomplete fields require a unique ID to avoid JS conflicts
		$templateMgr->assign('addUrl', $router->url($request, array(), null, 'addItem', null, array('seriesId' => $seriesId)));
		$templateMgr->assign('deleteUrl', $router->url($request, array(), null, 'deleteItems', null, array('seriesId' => $seriesId)));
		$templateMgr->assign('autocompleteUrl', $router->url($request, array(), null, 'getAutocompleteSource'));

		// Translate modal submit/cancel buttons
		$okButton = Locale::translate('common.ok');
		$warning = Locale::translate('common.warning');
		$templateMgr->assign('localizedButtons', "$okButton, $warning");

		$row =& $this->getRowInstance();
		// initialize to create the columns
		$row->initialize($request);
		$columns =& $this->getColumns();
		$templateMgr->assign_by_ref('columns', $columns);
		$templateMgr->assign('numColumns', count($columns));

		// Render the rows
		$rows = $this->_renderRowsInternally($request);
		$templateMgr->assign_by_ref('rows', $rows);

		$templateMgr->assign('listbuilder', $this);
		echo $templateMgr->fetch('controllers/listbuilder/listbuilder.tpl');
    }

	/*
	 * Configure the grid
	 * @param PKPRequest $request
	 */
	function initialize(&$request) {
		parent::initialize($request);
		Locale::requireComponents(array(LOCALE_COMPONENT_PKP_MANAGER));

		// Basic configuration
		$this->setTitle('user.role.seriesEditors');
		$this->setSourceTitle('common.user');
		$this->setSourceType(LISTBUILDER_SOURCE_TYPE_BOUND); // Free text input
		$this->setListTitle('manager.groups.existingUsers');

		$this->setSeriesId($request->getUserVar('seriesId'));

		$this->loadList($request);

		$this->addColumn(new GridColumn('item', 'common.name'));
	}

	//
	// Public AJAX-accessible functions
	//

	/*
	 * Fetch either a block of data for local autocomplete, or return a URL to another function for AJAX autocomplete
	 */
	function getAutocompleteSource(&$args, &$request) {
		//FIXME: add validation here?
		$this->setupTemplate();

		$sourceArray = $this->getPossibleItemList($request);

		$sourceJson = new JSON('true', null, 'false', 'local');
		$sourceContent = "[";
		foreach ($sourceArray as $i => $item) {
			$itemJson = new JSON('true', sprintf('%s (%s)', $item['name'], $item['abbrev']), 'false', $item['id']);
			$sourceContent .= $itemJson->getString();
			$sourceContent .= $item == end($sourceArray) ? "]" : ",";

			unset($itemJson);
		}
		$sourceJson->setContent($sourceContent);

		echo $sourceJson->getString();
	}

	/*
	 * Handle adding an item to the list
	 */
	function addItem(&$args, &$request) {
		$this->setupTemplate();
		$publicationFormatDao =& DAORegistry::getDAO('PublicationFormatDAO');
		$press =& $request->getPress();

		$seriesId = $args['seriesId'];
		$index = 'sourceId-' . $this->getId() . '-' .$seriesId;
		$userId = $args[$index];

		if(empty($userId)) {
			$json = new JSON('false', Locale::translate('common.listbuilder.completeForm'));
			echo $json->getString();
		} else {
			$seriesEditorsDao =& DAORegistry::getDAO('SeriesEditorsDAO');

			// Make sure the membership doesn't already exist
			if ($seriesEditorsDao->editorExists($press->getId(), $seriesId, $userId)) {
				$json = new JSON('false', Locale::translate('common.listbuilder.itemExists'));
				echo $json->getString();
				return false;
			}
			unset($groupMembership);

			$seriesEditorsDao->insertEditor($press->getId(), $request->getUserVar('seriesId'), $userId, true, true);

			$userDao =& DAORegistry::getDAO('UserDAO');
			$user =& $userDao->getUser($userId);

			// Return JSON with formatted HTML to insert into list
			$row =& $this->getRowInstance();
			$row->setGridId($this->getId());
			$row->setId($userId);
			$rowData = array('item' => $user->getFullName(), 'attribute' => $user->getUsername());
			$row->setData($rowData);
			$row->initialize($request);

			$json = new JSON('true', $this->_renderRowInternally($request, $row));
			echo $json->getString();
		}
	}

	/*
	 * Handle deleting items from the list
	 */
	function deleteItems(&$args, &$request) {
		$seriesEditorsDao =& DAORegistry::getDAO('SeriesEditorsDAO');
		$press =& $request->getPress();
		$seriesId = array_shift($args);

		foreach($args as $userId) {
			$seriesEditorsDao->deleteEditor($press->getId(), $seriesId, $userId);
		}

		$json = new JSON('true');
		echo $json->getString();
	}
}
?>
