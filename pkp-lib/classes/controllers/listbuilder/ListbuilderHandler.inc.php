<?php

/**
 * @file classes/controllers/listbuilder/ListbuilderHandler.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ListbuilderHandler
 * @ingroup controllers_listbuilder
 *
 * @brief Class defining basic operations for handling Listbuilder UI elements
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('lib.pkp.classes.controllers.listbuilder.ListbuilderGridRow');
import('lib.pkp.classes.controllers.listbuilder.ListbuilderGridColumn');

/* Listbuilder source types: text-based, pulldown, ... */
define_exposed('LISTBUILDER_SOURCE_TYPE_TEXT', 0);
define_exposed('LISTBUILDER_SOURCE_TYPE_SELECT', 1);

/* Listbuilder save types */
define('LISTBUILDER_SAVE_TYPE_EXTERNAL', 0); // Outside the listbuilder handler
define('LISTBUILDER_SAVE_TYPE_INTERNAL', 1); // Using ListbuilderHandler::save

// FIXME: Rather than inheriting from grid handler, common base
// functionality might better be factored into a common base handler
// class and then both, GridHandler and ListbuilderHandler should
// inherit from the common base class. The shared concept of grids
// and list builders is that both seem to work with element lists. Maybe
// ElementListHandler would be a good name then for a common base
// class? I'm not a 100% sure about this but it'll become obvious
// once you try. If there's considerable amounts of code in both
// the base class and the re-factored grid handler then you know
// you're on the right track.
class ListbuilderHandler extends GridHandler {
	/** @var integer Definition of the type of source LISTBUILDER_SOURCE_TYPE_... **/
	var $_sourceType;

	/** @var integer Constant indicating the save approach for the LB LISTBUILDER_SAVE_TYPE_... **/
	var $_saveType = LISTBUILDER_SAVE_TYPE_INTERNAL;

	/** @var $saveFieldName Field for LISTBUILDER_SAVE_TYPE_EXTERNAL naming the field used to send the saved contents of the LB */
	var $_saveFieldName = null;

	/**
	 * Constructor.
	 */
	function ListbuilderHandler() {
		parent::GridHandler();
	}

	/**
	 * @see GridHandler::initialize
	 */
	function initialize(&$request) {
		parent::initialize($request);

		import('lib.pkp.classes.linkAction.request.NullAction');
		$this->addAction(
			new LinkAction(
				'addItem',
				new NullAction(),
				__('grid.action.addItem'),
				'add_item'
			)
		);
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the listbuilder template.
	 * @return string
	 */
	function getTemplate() {
		if (is_null($this->_template)) {
			$this->setTemplate('controllers/listbuilder/listbuilder.tpl');
		}

		return $this->_template;
	}

	/**
	 * Set the type of source (Free text input, select from list, autocomplete)
	 * @param $sourceType int LISTBUILDER_SOURCE_TYPE_...
	 */
	function setSourceType($sourceType) {
		$this->_sourceType = $sourceType;
	}

	/**
	 * Get the type of source (Free text input, select from list, autocomplete)
	 * @return int LISTBUILDER_SOURCE_TYPE_...
	 */
	function getSourceType() {
		return $this->_sourceType;
	}

	/**
	 * Set the save type (using this handler or another external one)
	 * @param $sourceType int LISTBUILDER_SAVE_TYPE_...
	 */
	function setSaveType($saveType) {
		$this->_saveType = $saveType;
	}

	/**
	 * Get the save type (using this handler or another external one)
	 * @return int LISTBUILDER_SAVE_TYPE_...
	 */
	function getSaveType() {
		return $this->_saveType;
	}

	/**
	 * Set the save field name for LISTBUILDER_SAVE_TYPE_EXTERNAL
	 * @param $fieldName string
	 */
	function setSaveFieldName($fieldName) {
		$this->_saveFieldName = $fieldName;
	}

	/**
	 * Set the save field name for LISTBUILDER_SAVE_TYPE_EXTERNAL
	 * @return string
	 */
	function getSaveFieldName() {
		assert(isset($this->_saveFieldName));
		return $this->_saveFieldName;
	}

	/**
	 * Get the new row ID from the request. For multi-column listbuilders,
	 * this is an array representing the row. For single-column
	 * listbuilders, this is a single piece of data (i.e. a string or int)
	 * @param $request PKPRequest
	 * @return mixed
	 */
	function getNewRowId($request) {
		$newRowId = $request->getUserVar('newRowId');
		if (is_array($newRowId) && count($newRowId) === 1) {
			return array_shift($newRowId);
		} else {
			return $newRowId;
		}
	}

	/**
	 * Delete an entry.
	 * @param $request Request object
	 * @param $rowId mixed ID of row to modify
	 * @return boolean
	 */
	function deleteEntry(&$request, $rowId) {
		fatalError('ABSTRACT METHOD');
	}

	/**
	 * Persist an update to an entry.
	 * @param $request Request object
	 * @param $rowId mixed ID of row to modify
	 * @param $newRowId mixed ID of the new entry
	 * @return boolean
	 */
	function updateEntry(&$request, $rowId, $newRowId) {
		// This may well be overridden by a subclass to modify
		// an existing entry, e.g. to maintain referential integrity.
		// If not, we can simply delete and insert.
		if (!$this->deleteEntry($request, $rowId)) return false;
		return $this->insertEntry($request, $newRowId);
	}

	/**
	 * Persist a new entry insert.
	 * @param $request Request object
	 * @param $newRowId mixed ID of row to modify
	 */
	function insertEntry(&$request, $newRowId) {
		fatalError('ABSTRACT METHOD');
	}

	/**
	 * Fetch the options for a LISTBUILDER_SOURCE_TYPE_SELECT LB
	 * Should return a multidimensional array:
	 * array(
	 * 	array('column 1 option 1', 'column 2 option 1'),
	 * 	array('column 1 option 2', 'column 2 option 2'
	 * );
	 * @param request Request
	 * @return array
	 */
	function getOptions(&$request) {
		fatalError('ABSTRACT METHOD');
	}

	//
	// Publicly (remotely) available listbuilder functions
	//
	/**
	 * Fetch the listbuilder.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function fetch($args, &$request) {
		return $this->fetchGrid($args, $request);
	}

	/**
	 * Unpack data to save using an external handler.
	 * @param $data String (the json encoded data from the listbuilder itself)
	 * @param $deletionCallback array callback to be used for each deleted element
	 * @param $insertionCallback array callback to be used for each updated element
	 * @param $updateCallback array callback to be used for each updated element
	 */
	function unpack(&$request, $data, $deletionCallback = null, $insertionCallback = null, $updateCallback = null) {
		// Set some defaults
		// N.B. if this class is called statically, then $this is not set to Listbuilder, but to your calling class.
		if ( !$deletionCallback ) {
			$deletionCallback = array(&$this, 'deleteEntry');
		}
		if ( !$insertionCallback ) {
			$insertionCallback = array(&$this, 'insertEntry');
		}
		if ( !$updateCallback ) {
			$updateCallback = array(&$this, 'updateEntry');
		}

		import('lib.pkp.classes.core.JSONManager');
		$jsonManager = new JSONManager();
		$data = $jsonManager->decode($data);
		// Go through each of the 4 cases and use the callback for each
		// 1. the row in the listbuilder was new and not completed (nothing).
		// 2. The row had an id, but has no new one (deletion).
		// 3. The row did not have an id, but now has a new one (insertion).
		// 4. The row had an id, but now has a new one (update).

		// Kludge to access $entry->"newRowId[]"
		$newRowIdKey = 'newRowId[]';

		foreach ( $data as $entry ) {
			if ( !isset($entry->rowId) && !isset($entry->$newRowIdKey) ) {
				continue;
			} elseif ( isset($entry->rowId) && !isset($entry->$newRowIdKey) ) {
				if ( !empty($entry->rowId) ) {
					call_user_func($deletionCallback, $request, $entry->rowId);
				}
			} elseif ( !isset($entry->rowId) && isset($entry->$newRowIdKey) ) {
				if ( !empty($entry->$newRowIdKey) ) {
					call_user_func($insertionCallback, $request, $entry->$newRowIdKey);
				}
			} elseif ( isset($entry->rowId) && isset($entry->$newRowIdKey) ) {
				if ( !empty($entry->rowId) && !empty($entry->$newRowIdKey) ) {
					call_user_func($updateCallback, $request, $entry->rowId, $entry->$newRowIdKey);
				}
			}
		}
	}

	/**
	 * Save the listbuilder using the internal handler.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function save($args, &$request) {
		// The ListbuilderHandler will post a list of changed
		// data in the "data" post var. Need to go through it
		// and reconcile the data against this list, adding/
		// updating/deleting as needed.
		$data = $request->getUserVar('data');
		$this->unpack($request, $data,
					  array(&$this, 'deleteEntry'), array(&$this, 'insertEntry'), array(&$this, 'updateEntry'));
	}


	/**
	 * Load the set of options for a select list type listbuilder.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function fetchOptions($args, &$request) {
		$options = $this->getOptions($request);
		$json = new JSONMessage(true, $options);
		return $json->getString();
	}

	//
	// Overridden methods from GridHandler
	//
	/**
	 * @see GridHandler::getRowInstance()
	 * @return CitationGridRow
	 */
	function &getRowInstance() {
		// Return a citation row
		$row = new ListbuilderGridRow();
		return $row;
	}
}

?>
