<?php

/**
 * @file controllers/grid/users/reviewer/ReviewerGridRow.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerGridRow
 * @ingroup controllers_grid_users_reviewer
 *
 * @brief Reviewer grid row definition
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class ReviewerGridRow extends GridRow {
	/**
	 * Constructor
	 */
	function ReviewerGridRow() {
		parent::GridRow();
	}

	//
	// Overridden methods from GridRow
	//
	/**
	 * @see GridRow::initialize()
	 * @param $request PKPRequest
	 */
	function initialize($request) {
		parent::initialize($request);

		// Retrieve the submission id from the request
		// These parameters need not be validated as we're just
		// passing them along to another request, where they will be
		// checked before they're used.
		$submissionId = (int) $request->getUserVar('submissionId');
		$stageId = (int) $request->getUserVar('stageId');
		$round = (int) $request->getUserVar('round');

		// Is this a new row or an existing row?
		$rowId = $this->getId();
		if (!empty($rowId) && is_numeric($rowId)) {
			// Only add row actions if this is an existing row
			$router = $request->getRouter();
			$actionArgs = array(
				'submissionId' => $submissionId,
				'reviewAssignmentId' => $rowId,
				'stageId' => $stageId,
				'round' => $round
			);

			$this->addAction(
				new LinkAction(
					'history',
					new AjaxModal(
						$router->url($request, null, null, 'reviewHistory', null, $actionArgs),
						__('submission.history'),
						'modal_information'
					),
					__('submission.history'),
					'more_info'
				)
			);

			$this->addAction(
				new LinkAction(
					'email',
					new AjaxModal(
						$router->url($request, null, null, 'sendEmail', null, $actionArgs),
						__('grid.user.email'),
						'modal_email'
					),
					__('grid.user.email'),
					'notify'
				)
			);

			$this->addAction(
				new LinkAction(
					'manageAccess',
					new AjaxModal(
						$router->url($request, null, null, 'limitFiles', null, $actionArgs),
						__('editor.submissionReview.limitFiles'),
						'modal_add_file'
					),
					__('editor.submissionReview.limitFiles.link'),
					'edit'
				)
			);

			$reviewAssignment = $this->getData();
			// Only assign this action if the reviewer has not acknowledged yet.
			if (!$reviewAssignment->getDateConfirmed()) {
				import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
				$this->addAction(
					new LinkAction(
						'remove',
						new RemoteActionConfirmationModal(
							__('common.confirmDelete'), __('common.remove'),
							$router->url($request, null, null, 'deleteReviewer', null, $actionArgs),
							'modal_delete'
						),
					__('grid.action.unassignUser'),
					'delete'
					)
				);
			}
		}
	}
}

?>
