<?php
/**
 * @file controllers/listbuilder/settings/reviewForms/ReviewFormElementResponseItemListbuilderHandler.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewFormElementResponseItemListbuilderHandler
 * @ingroup controllers_listbuilder_settings_reviewForms
 *
 * @brief Review form element response item listbuilder handler
 */

import('lib.pkp.controllers.listbuilder.settings.SetupListbuilderHandler');

class ReviewFormElementResponseItemListbuilderHandler extends SetupListbuilderHandler {

	/** @var int Review form element ID **/
	var $_reviewFormElementId;

	/**
	 * Constructor
	 */
	function ReviewFormElementResponseItemListbuilderHandler() {
		parent::SetupListbuilderHandler();
	}


	//
	// Overridden template methods
	//
	/**
	 * @see SetupListbuilderHandler::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER);
		$this->_reviewFormElementId = (int) $request->getUserVar('reviewFormElementId');

		// Basic configuration
		$this->setTitle('grid.reviewFormElement.responseItems');
		$this->setSourceType(LISTBUILDER_SOURCE_TYPE_TEXT);
		$this->setSaveType(LISTBUILDER_SAVE_TYPE_EXTERNAL);
		$this->setSaveFieldName('possibleResponses');

		// Possible response column
		$responseColumn = new MultilingualListbuilderGridColumn($this, 'possibleResponse', 'manager.reviewFormElements.possibleResponse', null, null, null, null, array('tabIndex' => 1));
		import('lib.pkp.controllers.listbuilder.settings.reviewForms.ReviewFormElementResponseItemListbuilderGridCellProvider');
	 	$responseColumn->setCellProvider(new ReviewFormElementResponseItemListbuilderGridCellProvider());	
		$this->addColumn($responseColumn);
	}

	/**
	 * @see GridHandler::loadData()
	 */
	function loadData($request) {
		$reviewFormElementDao = DAORegistry::getDAO('ReviewFormElementDAO');
		$reviewFormElement = $reviewFormElementDao->getById($this->_reviewFormElementId);
		$formattedResponses = array();
		if ($reviewFormElement) {
			$possibleResponses = $reviewFormElement->getPossibleResponses(null);
			foreach ((array) $possibleResponses as $locale => $values) {
				foreach ($values as $value) {
					$formattedResponses[] = array(array('content' => array($locale => $value)));
				}
			}
		}
		return $formattedResponses;
	}

	/**
	 * @see GridHandler::getRowDataElement
	 * Get the data element that corresponds to the current request
	 * Allow for a blank $rowId for when creating a not-yet-persisted row
	 */
	function getRowDataElement($request, $rowId) {
		// Fallback on the parent if an existing rowId is found
		if ( !empty($rowId) ) {
			return parent::getRowDataElement($request, $rowId); 
		}

		// If we're bouncing a row back upon a row edit
		$rowData = $this->getNewRowId($request);
		if ($rowData) {
			return array(array('content' => $rowData['possibleResponse']));
		}

		// If we're generating an empty row to edit
		return array(array('content' => array()));
	}
}

?>
