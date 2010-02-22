<?php

/**
 * @file controllers/grid/contributor/ContributorGridRow.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ContributorGridRow
 * @ingroup controllers_grid_contributor
 *
 * @brief Handle contributor grid row requests.
 */

import('controllers.grid.GridRow');

class ContributorGridRow extends GridRow {
	/**
	 * Constructor
	 */
	function ContributorGridRow() {
		parent::GridRow();
	}

	//
	// Overridden template methods
	//
	/*
	 * Configure the grid row
	 * @param PKPRequest $request
	 */
	function initialize(&$request) {
		parent::initialize($request);
		// add Grid Row Actions

		// Is this a new row or an existing row?
		$rowId = $this->getId();
		if (!empty($rowId) && is_numeric($rowId)) {
			// Actions
			$router =& $request->getRouter();
			$actionArgs = array(
				'gridId' => $this->getGridId(),
				'rowId' => $rowId
			);
			$this->addAction(
				new GridAction(
					'editContributor',
					GRID_ACTION_MODE_MODAL,
					GRID_ACTION_TYPE_REPLACE,
					$router->url($request, null, null, 'editContributor', null, $actionArgs),
					'grid.action.edit',
					'edit'
				));
			$this->addAction(
				new GridAction(
					'deleteContributor',
					GRID_ACTION_MODE_CONFIRM,
					GRID_ACTION_TYPE_REMOVE,
					$router->url($request, null, null, 'deleteContributor', null, $actionArgs),
					'grid.action.delete',
					'delete'
				));

			$this->setTemplate('controllers/grid/gridRowWithActions.tpl');
		}

	}
}