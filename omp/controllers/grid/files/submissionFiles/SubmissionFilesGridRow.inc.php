<?php

/**
 * @file controllers/grid/files/submissionFiles/SubmissionFilesGridRow.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FileRow
 * @ingroup controllers_grid_file
 *
 * @brief Handle submission file grid row requests.
 */

import('controllers.grid.GridRow');

class SubmissionFilesGridRow extends GridRow {
	/**
	 * Constructor
	 */
	function SubmissionFilesGridRow() {
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
		$this->setTemplate('controllers/grid/gridRowWithActions.tpl');

		// Is this a new row or an existing row?
		$rowId = $this->getId();
		
		$monographFileDao =& DAORegistry::getDAO('MonographFileDAO');
		$monographFile =& $monographFileDao->getMonographFile($rowId);
		$monographId = $monographFile->getMonographId();
		
		if (!empty($rowId) && is_numeric($rowId)) {
			// Actions
			$router =& $request->getRouter();
			$actionArgs = array(
				'gridId' => $this->getGridId(),
				'fileId' => $rowId,
				'monographId' => $monographId
			);
			$this->addAction(
				new GridAction(
					'editFile',
					GRID_ACTION_MODE_MODAL,
					GRID_ACTION_TYPE_REPLACE,
					$router->url($request, null, null, 'editFile', null, $actionArgs),
					'grid.action.edit',
					'edit'
				));
			$this->addAction(
				new GridAction(
					'deleteFile',
					GRID_ACTION_MODE_CONFIRM,
					GRID_ACTION_TYPE_REMOVE,
					$router->url($request, null, null, 'deleteFile', null, $actionArgs),
					'grid.action.delete',
					'delete'
				));
		}
	}
}