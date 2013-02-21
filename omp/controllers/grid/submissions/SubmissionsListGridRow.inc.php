<?php

/**
 * @file controllers/grid/submissions/SubmissionsListGridRow.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FileRow
 * @ingroup controllers_grid_submissions_pressEditor
 *
 * @brief Handle editor submission list grid row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');
import('lib.pkp.classes.linkAction.request.AjaxModal');

class SubmissionsListGridRow extends GridRow {
	/** @var $_isManager boolean true iff the user has a managerial role */
	var $_isManager;

	/**
	 * Constructor
	 */
	function SubmissionsListGridRow($isManager) {
		parent::GridRow();
		$this->_isManager = $isManager;
	}

	//
	// Overridden template methods
	//
	/*
	 * Configure the grid row
	 * @param $request PKPRequest
	 */
	function initialize(&$request) {
		parent::initialize($request);

		$rowId = $this->getId();

		if (!empty($rowId) && is_numeric($rowId)) {
			// 1) Delete submission action.
			$monographDao =& DAORegistry::getDAO('MonographDAO'); /* @var $monographDao MonographDAO */
			$monograph =& $monographDao->getById($rowId);
			assert(is_a($monograph, 'Monograph'));
			if ($monograph->getSubmissionProgress() != 0 || $this->_isManager) {
				$router =& $request->getRouter();
				import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
				$confirmationModal = new RemoteActionConfirmationModal(
						__('common.confirmDelete'), __('common.delete'),
						$router->url(
							$request, null, null,
							'deleteSubmission', null, array('monographId' => $rowId)
						),
						'modal_delete'
					);

				$this->addAction(new LinkAction('delete', $confirmationModal, __('grid.action.delete'), 'delete'));
			}

			// 2) Information Centre action
			import('controllers.informationCenter.linkAction.SubmissionInfoCenterLinkAction');
			$this->addAction(new SubmissionInfoCenterLinkAction($request, $rowId, 'grid.action.moreInformation'));
		}
	}
}

?>
