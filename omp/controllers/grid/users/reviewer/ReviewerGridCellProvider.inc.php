<?php

/**
 * @file controllers/grid/users/reviewer/ReviewerGridCellProvider.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerGridCellProvider
 * @ingroup controllers_grid_users_reviewer
 *
 * @brief Base class for a cell provider that can retrieve labels for reviewer grid rows
 */

import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');

import('lib.pkp.classes.linkAction.request.AjaxModal');
import('lib.pkp.classes.linkAction.request.AjaxAction');

class ReviewerGridCellProvider extends DataObjectGridCellProvider {
	/**
	 * Constructor
	 */
	function ReviewerGridCellProvider() {
		parent::DataObjectGridCellProvider();
	}


	//
	// Template methods from GridCellProvider
	//
	/**
	 * Gathers the state of a given cell given a $row/$column combination
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return string
	 */
	function getCellState(&$row, &$column) {
		$reviewAssignment =& $row->getData();
		$columnId = $column->getId();
		assert(is_a($reviewAssignment, 'DataObject') && !empty($columnId));
		switch ($columnId) {
			case 'name':
				if ($reviewAssignment->getDateCompleted())
					return 'linkReview';
				if ($reviewAssignment->getDateDue() < Core::getCurrentDate() || $reviewAssignment->getDateResponseDue() < Core::getCurrentDate())
					return 'overdue';

				return '';

			case 'considered':
				// The review has not been completed.
				if (!$reviewAssignment->getDateCompleted()) return 'unfinished';

				// The reviewer has been sent an acknowledgement.
				// Completed states can be 'unconsidered' by an editor.
				if ($reviewAssignment->getDateAcknowledged() && !$reviewAssignment->getUnconsidered()) {
					return 'completed';
				}

				if ($reviewAssignment->getUnconsidered() == REVIEW_ASSIGNMENT_UNCONSIDERED) {
					return 'reviewReady';
				}

				// Check if the somebody assigned to this monograph stage has read the review.
				$monographDao =& DAORegistry::getDAO('MonographDAO');
				$userGroupDao =& DAORegistry::getDAO('UserGroupDAO');
				$userStageAssignmentDao =& DAORegistry::getDAO('UserStageAssignmentDAO');
				$viewsDao =& DAORegistry::getDAO('ViewsDAO');

				$monograph =& $monographDao->getById($reviewAssignment->getSubmissionId());

				// Get the user groups for this stage
				$userGroups =& $userGroupDao->getUserGroupsByStage(
					$monograph->getPressId(),
					$reviewAssignment->getStageId(),
					true,
					true
				);
				while ($userGroup = $userGroups->next()) {
					$roleId = $userGroup->getRoleId();
					if ($roleId != ROLE_ID_MANAGER && $roleId != ROLE_ID_SERIES_EDITOR) continue;

					// Get the users assigned to this stage and user group
					$stageUsers =& $userStageAssignmentDao->getUsersBySubmissionAndStageId(
						$reviewAssignment->getSubmissionId(),
						$reviewAssignment->getStageId(),
						$userGroup->getId()
					);

					// mark as completed (viewed) if any of the manager/editor users viewed it.
					while ($user =& $stageUsers->next()) {
						if ($viewsDao->getLastViewDate(
							ASSOC_TYPE_REVIEW_RESPONSE,
							$reviewAssignment->getId(), $user->getId()
						)) {
							// Some user has read the review.
							return 'read';
						}
						unset($user);
					}
					unset($stageUsers);
				}

				// Nobody has read the review.
				return 'reviewReady';
		}
	}

	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn(&$row, $column) {
		$element =& $row->getData();
		$columnId = $column->getId();
		assert(is_a($element, 'DataObject') && !empty($columnId));
		switch ($columnId) {
			case 'name':
				return array('label' => $element->getReviewerFullName());

			case 'considered':
				return array('status' => $this->getCellState($row, $column));
		}

		return parent::getTemplateVarsFromRowColumn($row, $column);
	}

	/**
	 * Get cell actions associated with this row/column combination
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array an array of LinkAction instances
	 */
	function getCellActions(&$request, &$row, &$column, $position = GRID_ACTION_POSITION_DEFAULT) {
		$reviewAssignment =& $row->getData();
		$actionArgs = array(
			'monographId' => $reviewAssignment->getSubmissionId(),
			'reviewAssignmentId' => $reviewAssignment->getId(),
			'stageId' => $reviewAssignment->getStageId()
		);

		$router =& $request->getRouter();
		$action = false;
		$state = $this->getCellState($row, $column);
		if ($state == 'linkReview') {
			$user =& $request->getUser();
			$monographDao =& DAORegistry::getDAO('MonographDAO');
			$monograph =& $monographDao->getById($reviewAssignment->getSubmissionId());
			import('controllers.review.linkAction.ReviewNotesLinkAction');
			$action = new ReviewNotesLinkAction($request, $reviewAssignment, $monograph, $user);

		} elseif ($state == 'overdue') {
			import('controllers.api.task.SendReminderLinkAction');
			$action = new SendReminderLinkAction($request, 'editor.review.reminder', $actionArgs);
		} elseif ($state == 'read') {
			import('controllers.api.task.SendThankYouLinkAction');
			$action = new SendThankYouLinkAction($request, 'editor.review.thankReviewer', $actionArgs);
		} elseif ($state == 'completed') {
			import('controllers.review.linkAction.UnconsiderReviewLinkAction');
			$action = new UnconsiderReviewLinkAction($request, $reviewAssignment, $monograph);
		} elseif (in_array($state, array('', 'declined', 'unfinished', 'reviewReady'))) {
			// do nothing for these actions
		} else {
			// Inconsistent state
			assert(false);
		}

		return ($action) ? array($action) : array();
	}

	/**
	 * Provide meaningful locale keys for the various grid status states.
	 * @param string $state
	 * @return string
	 */
	function _getHoverTitleText($state) {
		switch ($state) {
			case 'completed':
				return __('common.complete');
			case 'overdue':
				return __('common.overdue');
			case 'accepted':
				return __('common.accepted');
			case 'declined':
				return __('common.declined');
			case 'reminder':
				return __('common.reminder');
			case 'new':
				return __('common.unread');
			default:
				return '';
		}
	}
}

?>
