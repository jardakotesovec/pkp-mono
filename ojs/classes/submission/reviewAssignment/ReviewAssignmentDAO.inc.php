<?php

/**
 * ReviewAssignmentsDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * Class for DAO relating reviewers to articles.
 *
 * $Id$
 */

class ReviewAssignmentDAO extends DAO {

	var $userDao;
	var $articleFileDao;
	var $articleCommentsDao;

	/**
	 * Constructor.
	 */
	function ReviewAssignmentDAO() {
		parent::DAO();
		$this->userDao = DAORegistry::getDAO('UserDAO');
		$this->articleFileDao = DAORegistry::getDAO('ArticleFileDAO');
		$this->articleCommentDao = DAORegistry::getDAO('ArticleCommentDAO');
	}
	
	/**
	 * Retrieve a review assignment by reviewer and article.
	 * @param $articleId int
	 * @param $reviewerId int
	 * @return ReviewAssignment
	 */
	function &getReviewAssignment($articleId, $reviewerId, $round) {
		$result = &$this->retrieve(
			'SELECT r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name FROM review_assignments r LEFT JOIN users u ON (r.reviewer_id = u.user_id) LEFT JOIN review_rounds r2 ON (r.article_id = r2.article_id AND r.round = r2.round) LEFT JOIN articles a ON (r.article_id = a.article_id) WHERE r.article_id = ? AND r.reviewer_id = ? AND r.round = ?',
			array($articleId, $reviewerId, $round)
			);
		
		if ($result->RecordCount() == 0) {
			return null;
		} else {
			return $this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Retrieve a review assignment by review assignment id.
	 * @param $reviewId int
	 * @return ReviewAssignment
	 */
	function &getReviewAssignmentById($reviewId) {
		$result = &$this->retrieve(
			'SELECT r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name FROM review_assignments r LEFT JOIN users u ON (r.reviewer_id = u.user_id) LEFT JOIN review_rounds r2 ON (r.article_id = r2.article_id AND r.round = r2.round) LEFT JOIN articles a ON (r.article_id = a.article_id) WHERE r.review_id = ?',
			$reviewId
			);
		
		if ($result->RecordCount() == 0) {
			return null;
		} else {
			return $this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
		}
	}
	

	/**
	 * Get all review assignments for an article.
	 * @param $articleId int
	 * @return array ReviewAssignments
	 */
	function &getReviewAssignmentsByArticleId($articleId, $round = null) {
		$reviewAssignments = array();
		
		if ($round == null) {
			$result = &$this->retrieve(
				'SELECT r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name FROM review_assignments r LEFT JOIN users u ON (r.reviewer_id = u.user_id) LEFT JOIN review_rounds r2 ON (r.article_id = r2.article_id AND r.round = r2.round) LEFT JOIN articles a ON (r.article_id = a.article_id) WHERE r.article_id = ? ORDER BY round, review_id',
				$articleId
			);
		} else {
			$result = &$this->retrieve(
				'SELECT r.*, r2.review_revision, a.review_file_id, u.first_name, u.last_name FROM review_assignments r LEFT JOIN users u ON (r.reviewer_id = u.user_id) LEFT JOIN review_rounds r2 ON (r.article_id = r2.article_id AND r.round = r2.round) LEFT JOIN articles a ON (r.article_id = a.article_id) WHERE r.article_id = ? AND r.round = ? ORDER BY review_id',
				array($articleId, $round)
			);
		}
		
		while (!$result->EOF) {
			$reviewAssignments[] = $this->_returnReviewAssignmentFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		
		return $reviewAssignments;
	}

	/**
	 * Internal function to return a review assignment object from a row.
	 * @param $row array
	 * @return ReviewAssignment
	 */
	function &_returnReviewAssignmentFromRow(&$row) {
		$reviewAssignment = &new ReviewAssignment();
		$reviewAssignment->setReviewId($row['review_id']);
		$reviewAssignment->setArticleId($row['article_id']);
		$reviewAssignment->setReviewerId($row['reviewer_id']);
		$reviewAssignment->setReviewerFullName($row['first_name'].' '.$row['last_name']);
		$reviewAssignment->setComments($row['comments']);
		$reviewAssignment->setRecommendation($row['recommendation']);
		$reviewAssignment->setDateAssigned($row['date_assigned']);
		$reviewAssignment->setDateInitiated($row['date_initiated']);
		$reviewAssignment->setDateNotified($row['date_notified']);
		$reviewAssignment->setDateConfirmed($row['date_confirmed']);
		$reviewAssignment->setDateCompleted($row['date_completed']);
		$reviewAssignment->setDateAcknowledged($row['date_acknowledged']);
		$reviewAssignment->setDateDue($row['date_due']);
		$reviewAssignment->setDeclined($row['declined']);
		$reviewAssignment->setReplaced($row['replaced']);
		$reviewAssignment->setCancelled($row['cancelled']);
		$reviewAssignment->setReviewerFileId($row['reviewer_file_id']);
		$reviewAssignment->setTimeliness($row['timeliness']);
		$reviewAssignment->setQuality($row['quality']);
		$reviewAssignment->setRound($row['round']);
		$reviewAssignment->setReviewFileId($row['review_file_id']);
		$reviewAssignment->setReviewRevision($row['review_revision']);
		
		// Files
		$reviewAssignment->setReviewerFile($this->articleFileDao->getArticleFile($row['reviewer_file_id']));
		$reviewAssignment->setReviewerFileRevisions($this->articleFileDao->getArticleFileRevisions($row['reviewer_file_id']));
	
		// Comments
		$reviewAssignment->setMostRecentPeerReviewComment($this->articleCommentDao->getMostRecentArticleComment($row['article_id'], COMMENT_TYPE_PEER_REVIEW, $row['review_id']));
		
		return $reviewAssignment;
	}
	
	/**
	 * Insert a new Review Assignment.
	 * @param $reviewAssignment ReviewAssignment
	 */	
	function insertReviewAssignment(&$reviewAssignment) {
		$this->update(
			'INSERT INTO review_assignments
				(article_id, reviewer_id, round, comments, recommendation, declined, replaced, cancelled, date_assigned, date_initiated, date_notified, date_confirmed, date_completed, date_acknowledged, date_due, reviewer_file_id, timeliness, quality)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$reviewAssignment->getArticleId(),
				$reviewAssignment->getReviewerId(),
				$reviewAssignment->getRound() === null ? 1 : $reviewAssignment->getRound(),
				$reviewAssignment->getComments(),
				$reviewAssignment->getRecommendation(),
				$reviewAssignment->getDeclined() === null ? 0 : $reviewAssignment->getDeclined(),
				$reviewAssignment->getReplaced() === null ? 0 : $reviewAssignment->getReplaced(),
				$reviewAssignment->getCancelled() === null ? 0 : $reviewAssignment->getCancelled(),
				$reviewAssignment->getDateAssigned(),
				$reviewAssignment->getDateInitiated(),
				$reviewAssignment->getDateNotified(),
				$reviewAssignment->getDateConfirmed(),
				$reviewAssignment->getDateCompleted(),
				$reviewAssignment->getDateAcknowledged(),
				$reviewAssignment->getDateDue(),
				$reviewAssignment->getReviewerFileId(),
				$reviewAssignment->getTimeliness(),
				$reviewAssignment->getQuality()
			)
		);
		
		$reviewAssignment->setReviewId($this->getInsertReviewId());
	}
	
	/**
	 * Update an existing email template.
	 * @param $emailTemplate EmailTemplate
	 */
	function updateReviewAssignment(&$reviewAssignment) {
		return $this->update(
			'UPDATE review_assignments
				SET	article_id = ?,
					reviewer_id = ?,
					round = ?,
					comments = ?,
					recommendation = ?,
					declined = ?,
					replaced = ?,
					cancelled = ?,
					date_assigned = ?,
					date_initiated = ?,
					date_notified = ?,
					date_confirmed = ?,
					date_completed = ?,
					date_acknowledged = ?,
					date_due = ?,
					reviewer_file_id = ?,
					timeliness = ?,
					quality = ?
				WHERE review_id = ?',
			array(
				$reviewAssignment->getArticleId(),
				$reviewAssignment->getReviewerId(),
				$reviewAssignment->getRound(),
				$reviewAssignment->getComments(),
				$reviewAssignment->getRecommendation(),
				$reviewAssignment->getDeclined(),
				$reviewAssignment->getReplaced(),
				$reviewAssignment->getCancelled(),
				$reviewAssignment->getDateAssigned(),
				$reviewAssignment->getDateInitiated(),
				$reviewAssignment->getDateNotified(),
				$reviewAssignment->getDateConfirmed(),
				$reviewAssignment->getDateCompleted(),
				$reviewAssignment->getDateAcknowledged(),
				$reviewAssignment->getDateDue(),
				$reviewAssignment->getReviewerFileId(),
				$reviewAssignment->getTimeliness(),
				$reviewAssignment->getQuality(),
				$reviewAssignment->getReviewId()
			)
		);
	}
	
	/**
	 * Delete review assignment.
	 * @param $reviewId int
	 */
	function deleteReviewAssignmentById($reviewId) {
		return $this->update(
			'DELETE FROM review_assignments WHERE review_id = ?',
			$reviewId
		);
	}
	
	/**
	 * Get the ID of the last inserted review assignment.
	 * @return int
	 */
	function getInsertReviewId() {
		return $this->getInsertId('review_assignments', 'review_id');
	}
	
}

?>
