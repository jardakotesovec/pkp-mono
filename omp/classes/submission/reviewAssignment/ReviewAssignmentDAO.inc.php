<?php

/**
 * @file classes/submission/reviewAssignment/ReviewAssignmentDAO.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewAssignmentDAO
 * @ingroup submission
 * @see ReviewAssignment
 *
 * @brief Class for DAO relating reviewers to monographs.
 */

import('classes.submission.reviewAssignment.ReviewAssignment');
import('lib.pkp.classes.submission.reviewAssignment.PKPReviewAssignmentDAO');

class ReviewAssignmentDAO extends PKPReviewAssignmentDAO {
	var $submissionFileDao;
	var $submissionCommentsDao;

	/**
	 * Constructor.
	 */
	function ReviewAssignmentDAO() {
		parent::PKPReviewAssignmentDAO();
		$this->submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$this->submissionCommentDao = DAORegistry::getDAO('SubmissionCommentDAO');
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return ReviewAssignment
	 */
	function newDataObject() {
		return new ReviewAssignment();
	}

	/**
	 * @see PKPReviewAssignmentDAO::getReviewRoundJoin()
	 */
	function getReviewRoundJoin() {
		return 'r.review_round_id = r2.review_round_id';
	}

	/**
	 * @see PKPReviewAssignmentDAO::getReviewerIdsBySubmissionId()
	 */
	function getReviewerIdsBySubmissionId($submissionId, $stageId = null, $reviewRoundId = null) {
		$query = 'SELECT r.reviewer_id
			FROM	review_assignments r
			WHERE r.submission_id = ?';

		$queryParams[] = (int) $submissionId;

		if ($reviewRoundId != null) {
			$query .= ' AND r.review_round_id = ?';
			$queryParams[] = (int) $reviewRoundId;
		}

		if ($stageId != null) {
			$query .= ' AND r.stage_id = ?';
			$queryParams[] = (int) $stageId;
		}

		$result = $this->retrieve($query, $queryParams);

		$reviewAssignments = array();
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$reviewAssignments[] = $row['reviewer_id'];
			$result->MoveNext();
		}

		$result->Close();
		return $reviewAssignments;
	}


	/**
	 * @see PKPReviewAssignmentDAO::getLastModifiedByRound()
	 */
	function getLastModifiedByRound($submissionId) {
		$returner = array();

		$result = $this->retrieve(
			'SELECT	review_round_id, MAX(last_modified) as last_modified
			FROM	review_assignments
			WHERE	submission_id = ?
			GROUP BY review_round_id',
			(int) $submissionId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$row['review_round_id']] = $this->datetimeFromDB($row['last_modified']);
			$result->MoveNext();
		}

		$result->Close();
		return $returner;
	}

	/**
	 * @see PKPReviewAssignmentDAO::getEarliestNotificationByRound()
	 */
	function getEarliestNotificationByRound($submissionId) {
		$returner = array();

		$result = $this->retrieve(
			'SELECT	review_round_id, MIN(date_notified) as earliest_date
			FROM	review_assignments
			WHERE	submission_id = ?
			GROUP BY review_round_id',
			(int) $submissionId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$row['review_round_id']] = $this->datetimeFromDB($row['earliest_date']);
			$result->MoveNext();
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Get the last assigned and last completed dates for all reviewers of the given context.
	 * @param $contextId int
	 * @return array
	 */
	function getReviewerStatistics($contextId) {
		// Build an array of all reviewers and provide a placeholder for all statistics (so even if they don't
		//  have a value, it will be filled in as 0
		$statistics = array();
		$reviewerStatsPlaceholder = array('last_notified' => null, 'incomplete' => 0, 'total_span' => 0, 'completed_review_count' => 0, 'average_span' => 0);

		$userDao = DAORegistry::getDAO('UserDAO');
		$allReviewers = $userDao->getAllReviewers($contextId);
		while($reviewer = $allReviewers->next()) {
			$statistics[$reviewer->getId()] = $reviewerStatsPlaceholder;
		}

		// Get counts of completed submissions
		$result = $this->retrieve(
			'SELECT	r.reviewer_id, MAX(r.date_notified) AS last_notified
			FROM	review_assignments r, submissions s
			WHERE	r.submission_id = s.submission_id AND
				s.context_id = ?
			GROUP BY r.reviewer_id',
			(int) $contextId
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = $reviewerStatsPlaceholder;
			$statistics[$row['reviewer_id']]['last_notified'] = $this->datetimeFromDB($row['last_notified']);
			$result->MoveNext();
		}
		$result->Close();

		// Get completion status
		$result = $this->retrieve(
				'SELECT	r.reviewer_id, COUNT(*) AS incomplete
				FROM	review_assignments r, submissions s
				WHERE	r.submission_id = s.submission_id AND
				r.date_notified IS NOT NULL AND
				r.date_completed IS NULL AND
				r.cancelled = 0 AND
				s.context_id = ?
				GROUP BY r.reviewer_id',
				(int) $contextId
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = $reviewerStatsPlaceholder;
			$statistics[$row['reviewer_id']]['incomplete'] = $row['incomplete'];
			$result->MoveNext();
		}

		$result->Close();

		// Calculate time taken for completed reviews
		$result = $this->retrieve(
			'SELECT	r.reviewer_id, r.date_notified, r.date_completed
			FROM	review_assignments r, submissions s
			WHERE	r.submission_id = s.submission_id AND
				r.date_notified IS NOT NULL AND
				r.date_completed IS NOT NULL AND
				r.declined = 0 AND
				s.context_id = ?',
			(int) $contextId
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = $reviewerStatsPlaceholder;

			$completed = strtotime($this->datetimeFromDB($row['date_completed']));
			$notified = strtotime($this->datetimeFromDB($row['date_notified']));
			if (isset($statistics[$row['reviewer_id']]['total_span'])) {
				$statistics[$row['reviewer_id']]['total_span'] += $completed - $notified;
				$statistics[$row['reviewer_id']]['completed_review_count'] += 1;
			} else {
				$statistics[$row['reviewer_id']]['total_span'] = $completed - $notified;
				$statistics[$row['reviewer_id']]['completed_review_count'] = 1;
			}

			// Calculate the average length of review in days.
			$statistics[$row['reviewer_id']]['average_span'] = round(($statistics[$row['reviewer_id']]['total_span'] / $statistics[$row['reviewer_id']]['completed_review_count']) / 86400);
			$result->MoveNext();
		}

		$result->Close();
		return $statistics;
	}

	/**
	 * Delete review assignment.
	 * @param $reviewId int
	 */
	function deleteById($reviewId) {
		parent::deleteById($reviewId);

		// Delete any outstanding notifications for this monograph
		$notificationDao = DAORegistry::getDAO('NotificationDAO');
		$notificationDao->deleteByAssoc(ASSOC_TYPE_REVIEW_ASSIGNMENT, $reviewId);
	}
}

?>
