<?php

/**
 * @file controllers/grid/files/signoff/SignoffGridCellProvider.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SignoffGridCellProvider
 * @ingroup controllers_grid_files_signoff
 *
 * @brief Cell provider for name column of a signoff (editor/auditor) grid (i.e. editorial/production).
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class SignoffGridCellProvider extends GridCellProvider {
	/** @var int */
	var $_monographId;

	/** @var int */
	var $_stageId;

	/**
	 * Constructor
	 */
	function SignoffGridCellProvider($monographId, $stageId) {
		$this->_monographId = $monographId;
		$this->_stageId = $stageId;
		parent::GridCellProvider();
	}

	//
	// Getters
	//
	function getMonographId() {
		return $this->_monographId;
	}

	function getStageId() {
		return $this->_stageId;
	}

	/**
	 * Get cell actions associated with this row/column combination
	 * Adds a link to the file if there is an uploaded file present
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array an array of LinkAction instances
	 */
	function getCellActions(&$request, &$row, &$column, $position = GRID_ACTION_POSITION_DEFAULT) {
		if ($column->getId() == 'name') {
			$user =& $request->getUser();
			$actionArgs = array_merge($row->getRequestArgs(),
				array('signoffId' => $row->getId()));
			$signoff =& $row->getData();
			$actions = array();
			if($signoff->getDateCompleted()) {
				import('controllers.informationCenter.linkAction.SignoffNotesLinkAction');
				$actions[] = new SignoffNotesLinkAction($request, $signoff, $this->getMonographId(), $this->getStageId());
			} else if (time() > strtotime($signoff->getDateUnderway())) {
				import('controllers.api.task.SendReminderLinkAction');
				$actions[] = new SendReminderLinkAction($request, 'editor.monograph.proof.reminder', $actionArgs);
			}

			if (!$signoff->getDateCompleted() && $signoff->getUserId() == $user->getId()) {
				// User own the unfinished signoff, let it complete the task.
				import('controllers.api.signoff.linkAction.AddSignoffFileLinkAction');
				$addFileAction = new AddSignoffFileLinkAction(
					$request, $this->getMonographId(),
					$this->getStageId(), $signoff->getSymbolic(), $signoff->getId(),
					__('submission.upload.signoff'), __('submission.upload.signoff')
				);

				// FIXME: This is not ideal.
				$addFileAction->_title = null;
				$actions[] = $addFileAction;
			}
		}

		return array_merge(parent::getCellActions($request, $row, $column, $position), $actions);
	}

	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn(&$row, &$column) {
		$signoff =& $row->getData();  /* @var $element Signoff */
		$columnId = $column->getId();
		assert(is_a($signoff, 'Signoff') && !empty($columnId));

		if ($columnId == 'name') {
			return array('label' => $this->_getLabel($signoff));
		}

		return parent::getTemplateVarsFromRowColumn($row, $column);
	}

	/**
	 * Build the cell label from the signoff object
	 */
	function _getLabel(&$signoff) {
		$userGroupDao =& DAORegistry::getDAO('UserGroupDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$userGroup =& $userGroupDao->getById($signoff->getUserGroupId());
		$user =& $userDao->getById($signoff->getUserId());

		return $user->getFullName() . ' (' . $userGroup->getLocalizedName() . ')';
	}
}

?>
