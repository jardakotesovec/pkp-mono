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

import('lib.pkp.classes.controllers.grid.GridRow');

class PressEditorSubmissionsListGridRow extends GridRow {
	/**
	 * Constructor
	 */
	function PressEditorSubmissionsListGridRow() {
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
		
		$monographDao =& DAORegistry::getDAO('MonographDAO');
		$monograph =& $monographDao->getMonograph($rowId);

		if (!empty($rowId) && is_numeric($rowId)) {
			// Actions
			$router =& $request->getRouter();
			$actionArgs = array(
				'gridId' => $this->getGridId(),
				'monographId' => $rowId,
				'reviewType' => $monograph->getCurrentReviewType(),
				'round' => $monograph->getCurrentRound()
			);
			$this->addAction(
				new LinkAction(
					'approve',
					LINK_ACTION_MODE_MODAL,
					LINK_ACTION_TYPE_REMOVE,
					$router->url($request, null, null, 'showApprove', null, $actionArgs),
					'grid.action.approveForPub',
					null,
					'promote'
				));
			$this->addAction(
				new LinkAction(
					'decline',
					LINK_ACTION_MODE_MODAL,
					LINK_ACTION_TYPE_REMOVE,
					$router->url($request, null, null, 'showDecline', null, $actionArgs),
					'editor.monograph.decision.decline',
					null,
					'delete'
				));
			$this->addAction(
				new LinkAction(
					'moreInfo',
					LINK_ACTION_MODE_MODAL,
					LINK_ACTION_TYPE_NOTHING,
					$router->url($request, null, 'informationCenter.SubmissionInformationCenterHandler', 'viewInformationCenter', null, array('assocId' => $rowId)),
					'grid.action.moreInformation',
					null,
					'more_info'
				));
		}
	}
}