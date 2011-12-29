<?php

/**
 * @file controllers/grid/catalogEntry/PublicationFormatGridRow.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublicationFormatGridRow
 * @ingroup controllers_grid_catalogEntry
 *
 * @brief Publication Format grid row definition
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class PublicationFormatGridRow extends GridRow {
	/** @var Monograph **/
	var $_monograph;

	/**
	 * Constructor
	 */
	function PublicationFormatGridRow(&$monograph) {
		$this->_monograph =& $monograph;
		parent::GridRow();
	}

	//
	// Overridden methods from GridRow
	//
	/**
	 * @see GridRow::initialize()
	 * @param $request PKPRequest
	 */
	function initialize(&$request) {
		// Do the default initialization
		parent::initialize($request);

		// Retrieve the monograph from the request
		$monograph =& $this->getMonograph();

		// Is this a new row or an existing row?
		$rowId = $this->getId();
		if (!empty($rowId) && is_numeric($rowId)) {
			// Only add row actions if this is an existing row
			$assignedPublicationFormat =& $this->_data;

			$router =& $request->getRouter();
			$actionArgs = array(
				'monographId' => $monograph->getId(),
				'assignedPublicationFormatId' => $assignedPublicationFormat->getAssignedPublicationFormatId()
			);

			// Add row-level actions
			import('lib.pkp.classes.linkAction.request.AjaxModal');
			$this->addAction(
				new LinkAction(
					'editFormat',
					new AjaxModal(
						$router->url($request, null, null, 'editFormat', null, $actionArgs),
						__('grid.action.edit'),
						'edit'
					),
					__('grid.action.edit'),
					'edit'
				)
			);

			import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
			$this->addAction(
				new LinkAction(
					'deleteFormat',
					new RemoteActionConfirmationModal(
						__('common.confirmDelete'),
						null,
						$router->url($request, null, null, 'deleteFormat', null, $actionArgs)
					),
					__('grid.action.delete'),
					'delete'
				)
			);

			// Set a non-default template that supports row actions
			$this->setTemplate('controllers/grid/gridRowWithActions.tpl');
		}
	}

	/**
	 * Get the monograph for this row (already authorized)
	 * @return Monograph
	 */
	function &getMonograph() {
		return $this->_monograph;
	}
}
?>
