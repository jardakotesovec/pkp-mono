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
	var $monographCommentsDao;

	/**
	 * Constructor.
	 */
	function ReviewAssignmentDAO() {
		parent::PKPReviewAssignmentDAO();
		$this->submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$this->monographCommentDao = DAORegistry::getDAO('MonographCommentDAO');
	}

	/**
	 * Get the ID of the last inserted review assignment.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('review_assignments', 'review_id');
	}

	/**
	 * Get the average quality ratings and number of ratings for all users of a press.
	 * @return array
	 */
	function getAverageQualityRatings($pressId) {
		$averageQualityRatings = array();
		$result = $this->retrieve(
			'SELECT	r.reviewer_id, AVG(r.quality) AS average, COUNT(r.quality) AS count
			FROM	review_assignments r, monographs a
			WHERE	r.submission_id = a.monograph_id AND
				a.press_id = ?
			GROUP BY r.reviewer_id',
			(int) $pressId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$averageQualityRatings[$row['reviewer_id']] = array('average' => $row['average'], 'count' => $row['count']);
			$result->MoveNext();
		}

		$result->Close();
		return $averageQualityRatings;
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return ReviewAssignment
	 */
	function newDataObject() {
		return new ReviewAssignment();
	}


	/**
	 * Internal function to return a review assignment object from a row.
	 * @param $row array
	 * @return ReviewAssignment
	 */
	function _fromRow($row) {
		$reviewAssignment = parent::_fromRow($row);

		// Comments
		$reviewAssignment->setMostRecentPeerReviewComment($this->monographCommentDao->getMostRecentMonographComment($row['submission_id'], COMMENT_TYPE_PEER_REVIEW, $row['review_id']));

		HookRegistry::call('ReviewAssignmentDAO::_fromRow', array(&$reviewAssignment, &$row));
		return $reviewAssignment;
	}

	/**
	 * Return the review methods translation keys.
	 * @return array
	 */
	function getReviewMethodsTranslationKeys() {
		return array(
			SUBMISSION_REVIEW_METHOD_DOUBLEBLIND => 'editor.submissionReview.doubleBlind',
			SUBMISSION_REVIEW_METHOD_BLIND => 'editor.submissionReview.blind',
			SUBMISSION_REVIEW_METHOD_OPEN => 'editor.submissionReview.open',
		);
	}

	/**
	 * @see PKPReviewAssignmentDAO::getReviewRoundJoin()
	 */
	function getReviewRoundJoin() {
		return 'r.review_round_id = r2.review_round_id';
	}

	//
	// Override methods from PKPSubmissionFileDAO
	// FIXME *6902* Move this code to PKPReviewAssignmentDAO after the review round
	// refactoring is ported to other applications.
	/**
	 * Retrieve a review assignment by review round and reviewer.
	 * @param $reviewRoundId int
	 * @param $reviewerId int
	 * @return ReviewAssignment
	 */
	function getReviewAssignment($reviewRoundId, $reviewerId) {
		$params = array(
		(int) $reviewRoundId,
		(int) $reviewerId
		);

		$result = $this->retrieve(
			$this->_getSelectQuery() .
			' WHERE	r.review_round_id = ? AND
				r.reviewer_id = ? AND
				r.cancelled <> 1',
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		return $returner;
	}

	/**
	 *
	 * Retrieve review assignments for the passed review round id.
	 * @param $reviewRoundId int
	 * @param $excludeCancelled boolean
	 * @return array
	 */
	function getByReviewRoundId($reviewRoundId, $excludeCancelled = false) {
		$params = array((int)$reviewRoundId);

		$query = $this->_getSelectQuery() .
			' WHERE r.review_round_id = ?';

		if ($excludeCancelled) {
			$query .= ' AND (r.cancelled = 0 OR r.cancelled IS NULL)';
		}

		$query .= ' ORDER BY review_id';

		return $this->_getReviewAssignmentsArray($query, $params);
	}

	/**
	 * @see PKPReviewAssignmentDAO::getBySubmissionId()
	 */
	function getBySubmissionId($submissionId, $reviewRoundId = null, $stageId = null) {
		$query = $this->_getSelectQuery() .
			' WHERE	r.submission_id = ?';

		$orderBy = ' ORDER BY review_id';

		$queryParams[] = (int) $submissionId;

		if ($reviewRoundId != null) {
			$query .= ' AND r2.review_round_id = ?';
			$queryParams[] = (int) $reviewRoundId;
		} else {
			$orderBy .= ', r2.review_round_id';
		}

		if ($stageId != null) {
			$query .= ' AND r2.stage_id = ?';
			$queryParams[] = (int) $stageId;
		} else {
			$orderBy .= ', r2.stage_id';
		}

		$query .= $orderBy;

		return $this->_getReviewAssignmentsArray($query, $queryParams);
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
	 * @see PKPReviewAssignmentDAO::getReviewIndexesForRound()
	 */
	function getReviewIndexesForRound($submissionId, $reviewRoundId) {
		$result = $this->retrieve(
			'SELECT	review_id
			FROM	review_assignments
			WHERE	submission_id = ? AND
				review_round_id = ? AND
				(cancelled = 0 OR cancelled IS NULL)
			ORDER BY review_id',
			array((int) $submissionId, (int) $reviewRoundId)
		);

		$index = 0;
		$returner = array();
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[$row['review_id']] = $index++;
			$result->MoveNext();
		}

		$result->Close();
		return $returner;
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
	 * Retrieve review assignments from table usign the passed
	 * sql query and parameters.
	 * @param $query string
	 * @param $queryParams array
	 * @return array
	 */
	function _getReviewAssignmentsArray($query, $queryParams) {
		$reviewAssignments = array();

		$result = $this->retrieve($query, $queryParams);

		while (!$result->EOF) {
			$reviewAssignments[$result->fields['review_id']] = $this->_fromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		return $reviewAssignments;
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
