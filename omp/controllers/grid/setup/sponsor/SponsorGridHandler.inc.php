<?php

/**
 * @file controllers/grid/sponsor/SponsorGridHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SponsorGridHandler
 * @ingroup controllers_grid_sponsor
 *
 * @brief Handle sponsor grid requests.
 */

import('controllers.grid.setup.SetupGridHandler');
import('controllers.grid.setup.sponsor.SponsorGridRow');

class SponsorGridHandler extends SetupGridHandler {
	/**
	 * Constructor
	 */
	function SponsorGridHandler() {
		parent::SetupGridHandler();
	}

	//
	// Getters/Setters
	//
	/**
	 * @see lib/pkp/classes/handler/PKPHandler#getRemoteOperations()
	 */
	function getRemoteOperations() {
		return array_merge(parent::getRemoteOperations(), array('addSponsor', 'editSponsor', 'updateSponsor', 'deleteSponsor'));
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
		// Basic grid configuration
		$this->setTitle('grid.sponsor.title');

		// Elements to be displayed in the grid
		$router =& $request->getRouter();
		$context =& $router->getContext($request);
		$sponsors = array();
		$sponsors = $context->getSetting('sponsors');
		$this->setData($sponsors);

		// Add grid-level actions
		$router =& $request->getRouter();
		$this->addAction(
			new GridAction(
				'addSponsor',
				GRID_ACTION_MODE_MODAL,
				GRID_ACTION_TYPE_APPEND,
				$router->url($request, null, null, 'addSponsor', null, array('gridId' => $this->getId())),
				'grid.action.addItem'
			),
			GRID_ACTION_POSITION_ABOVE
		);

		// Columns
		$emptyActions = array();
		// Basic grid row configuration
		$this->addColumn(new GridColumn('institution', 'grid.columns.institution', $emptyActions, 'controllers/grid/gridCellInSpan.tpl'));
		$this->addColumn(new GridColumn('url', 'grid.columns.url'));
	}
	
	//
	// Overridden methods from GridHandler
	//
	/**
	 * Get the row handler - override the default row handler
	 * @return SponsorGridRow
	 */
	function &getRowInstance() {
		$row = new SponsorGridRow();
		return $row;
	}

	//
	// Public Sponsor Grid Actions
	//
	/**
	 * An action to add a new sponsor
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function addSponsor(&$args, &$request) {
		// Delegate to the row handler
		import('controllers.grid.setup.sponsor.SponsorGridRow');
		$sponsorRow =& new SponsorGridRow();

		// Calling editSponsor with an empty row id will add
		// a new sponsor.
		$this->editSponsor($args, $request);
	}


	/**
	 * An action to edit a sponsor
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editSponsor(&$args, &$request) {
		//FIXME: add validation here?

		import('controllers.grid.setup.sponsor.form.SponsorForm');
		$sponsorForm = new SponsorForm($this->getId());

		if ($sponsorForm->isLocaleResubmit()) {
			$sponsorForm->readInputData();
		} else {
			$sponsorForm->initData($args, $request);
		}
		$sponsorForm->display();
	}

	/**
	 * Update a sponsor
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string
	 */
	function updateSponsor(&$args, &$request) {
		//FIXME: add validation here?
		// -> sponsorId must be present and valid
		// -> htmlId must be present and valid

		import('controllers.grid.setup.sponsor.form.SponsorForm');
		$sponsorForm = new SponsorForm($this->getId());
		$sponsorForm->readInputData();

		if ($sponsorForm->validate()) {
			$sponsorForm->execute($args, $request);

			// prepare the grid row data
			$row =& $this->getRowInstance();
			$row->setGridId($this->getId());
			$row->setId($sponsorForm->sponsorId);
			$rowData = array('institution' => $sponsorForm->getData('institution'),
							'url' => $sponsorForm->getData('url'));
			$row->setData($rowData);
			$row->initialize($request);

			$json = new JSON('true', $this->_renderRowInternally($request, $row));
		} else {
			$json = new JSON('false');
		}

		return $json->getString();
	}

	/**
	 * Delete a sponsor
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string
	 */
	function deleteSponsor(&$args, &$request) {
		// FIXME: add validation here?

		$router =& $request->getRouter();
		$press =& $router->getContext($request);
		$pressSettingsDao =& DAORegistry::getDAO('PressSettingsDAO');

		// get all of the sponsors
		$sponsors = $pressSettingsDao->getSetting($press->getId(), 'sponsors');
 		$sponsorId = $this->getId();

		if ( isset($sponsors[$sponsorId]) ) {
			unset($sponsors[$sponsorId]);
			$pressSettingsDao->updateSetting($press->getId(), 'sponsors', $sponsors, 'object');
			$json = new JSON('true');
		} else {
			$json = new JSON('false', Locale::translate('manager.setup.errorDeletingItem'));
		}
		echo $json->getString();
	}
}