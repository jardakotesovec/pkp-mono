<?php

/**
 * @file classes/submission/seriesEditor/SeriesEditorSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SeriesEditorSubmissionDAO
 * @ingroup submission
 * @see SeriesEditorSubmission
 *
 * @brief Operations for retrieving and modifying SeriesEditorSubmission objects.
 * FIXME #5557: We need a general code cleanup here (remove useless functions), and to integrate with monograph_stage_assignments table
 */



import('classes.submission.seriesEditor.SeriesEditorSubmission');

// Bring in editor decision constants
import('classes.submission.author.AuthorSubmission');
import('classes.submission.reviewer.ReviewerSubmission');

class SeriesEditorSubmissionDAO extends DAO {
	var $monographDao;
	var $authorDao;
	var $userDao;
	var $reviewAssignmentDao;
	var $monographFileDao;
	var $signoffDao;
	var $galleyDao;
	var $monographEmailLogDao;
	var $monographCommentDao;

	/**
	 * Constructor.
	 */
	function SeriesEditorSubmissionDAO() {
		parent::DAO();
		$this->monographDao =& DAORegistry::getDAO('MonographDAO');
		$this->authorDao =& DAORegistry::getDAO('AuthorDAO');
		$this->userDao =& DAORegistry::getDAO('UserDAO');
		$this->reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$this->monographFileDao =& DAORegistry::getDAO('MonographFileDAO');
		$this->signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$this->galleyDao =& DAORegistry::getDAO('MonographGalleyDAO');
		$this->monographEmailLogDao =& DAORegistry::getDAO('MonographEmailLogDAO');
		$this->monographCommentDao =& DAORegistry::getDAO('MonographCommentDAO');
	}

	/**
	 * Retrieve an series editor submission by monograph ID.
	 * @param $monographId int
	 * @return EditorSubmission
	 */
	function &getSeriesEditorSubmission($monographId) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();
		$result =& $this->retrieve(
			'SELECT	m.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS series_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS series_abbrev,
				rr.review_revision AS review_revision
			FROM	monographs m
				LEFT JOIN series s ON (s.series_id = m.series_id)
				LEFT JOIN review_rounds rr ON (m.monograph_id = rr.submission_id AND m.current_round = rr.round)
				LEFT JOIN series_settings stpl ON (s.series_id = stpl.series_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN series_settings stl ON (s.series_id = stl.series_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN series_settings sapl ON (s.series_id = sapl.series_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN series_settings sal ON (s.series_id = sal.series_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE	m.monograph_id = ?',
			array(
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale,
				$monographId
			)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_fromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return SeriesEditorSubmission
	 */
	function newDataObject() {
		return new SeriesEditorSubmission();
	}

	/**
	 * Internal function to return a SeriesEditorSubmission object from a row.
	 * @param $row array
	 * @return SeriesEditorSubmission
	 */
	function &_fromRow(&$row) {
		$seriesEditorSubmission = $this->newDataObject();

		// Monograph attributes
		$this->monographDao->_monographFromRow($seriesEditorSubmission, $row);

		$reviewRoundsInfo =& $this->monographDao->getReviewRoundsInfoById($row['monograph_id']);

		// Editor Decisions
		foreach ( $reviewRoundsInfo as $reviewType => $currentReviewRound) {
			for ($i = 1; $i <= $currentReviewRound; $i++) {
				$seriesEditorSubmission->setDecisions($this->getEditorDecisions($row['monograph_id'], $reviewType, $i), $reviewType, $i);
			}
		}

		// Comments
		$seriesEditorSubmission->setMostRecentEditorDecisionComment($this->monographCommentDao->getMostRecentMonographComment($row['monograph_id'], COMMENT_TYPE_EDITOR_DECISION, $row['monograph_id']));
		$seriesEditorSubmission->setMostRecentCopyeditComment($this->monographCommentDao->getMostRecentMonographComment($row['monograph_id'], COMMENT_TYPE_COPYEDIT, $row['monograph_id']));
		$seriesEditorSubmission->setMostRecentLayoutComment($this->monographCommentDao->getMostRecentMonographComment($row['monograph_id'], COMMENT_TYPE_LAYOUT, $row['monograph_id']));
		$seriesEditorSubmission->setMostRecentProofreadComment($this->monographCommentDao->getMostRecentMonographComment($row['monograph_id'], COMMENT_TYPE_PROOFREAD, $row['monograph_id']));

		// Files
		$seriesEditorSubmission->setSubmissionFile($this->monographFileDao->getMonographFile($row['submission_file_id']));
		$seriesEditorSubmission->setRevisedFile($this->monographFileDao->getMonographFile($row['revised_file_id']));
		$seriesEditorSubmission->setReviewFile($this->monographFileDao->getMonographFile($row['review_file_id']));
		$seriesEditorSubmission->setEditorFile($this->monographFileDao->getMonographFile($row['editor_file_id']));

		foreach ( $reviewRoundsInfo as $reviewType => $currentReviewRound) {
			for ($i = 1; $i <= $currentReviewRound; $i++) {
				$seriesEditorSubmission->setEditorFileRevisions($this->monographFileDao->getMonographFileRevisions($row['editor_file_id'], $reviewType, $i), $reviewType, $i);
				$seriesEditorSubmission->setAuthorFileRevisions($this->monographFileDao->getMonographFileRevisions($row['revised_file_id'], $reviewType, $i), $reviewType, $i);
			}
		}

		// Review Rounds
		$seriesEditorSubmission->setReviewRevision($row['review_revision']);

		// Review Assignments
		foreach ($reviewRoundsInfo as $reviewType => $currentReviewRound) {
			for ($i = 1; $i <= $currentReviewRound; $i++) {
				$seriesEditorSubmission->setReviewAssignments($this->reviewAssignmentDao->getBySubmissionId($row['monograph_id'], $i, $reviewType), $reviewType, $i);
			}
		}

		// Proof Assignment

		HookRegistry::call('SeriesEditorSubmissionDAO::_fromRow', array(&$seriesEditorSubmission, &$row));

		return $seriesEditorSubmission;
	}

	/**
	 * Update an existing series editorsubmission.
	 * @param $seriesEditorSubmission SeriesEditorSubmission
	 */
	function updateSeriesEditorSubmission(&$seriesEditorSubmission) {
		$reviewRounds = $seriesEditorSubmission->getReviewRoundsInfo();

		// Update editor decisions
		foreach ($reviewRounds as $reviewType => $round) {
		for ($i = 1; $i <= $round; $i++) {
			$editorDecisions = $seriesEditorSubmission->getDecisions($reviewType, $i);
			if (is_array($editorDecisions)) {
				foreach ($editorDecisions as $editorDecision) {
					if ($editorDecision['editDecisionId'] == null) {
						$this->update(
							sprintf(
								'INSERT INTO edit_decisions
								(monograph_id, review_type, round, editor_id, decision, date_decided)
								VALUES (?, ?, ?, ?, ?, %s)',
								$this->datetimeToDB($editorDecision['dateDecided'])
							),
							array(
								$seriesEditorSubmission->getId(),
								$reviewType,
								$i,
								$editorDecision['editorId'],
								$editorDecision['decision']
							)
						);
					}
				}
			}
		}
		}

		$reviewRoundDao =& DAORegistry::getDAO('ReviewRoundDAO');

		$reviewType = $seriesEditorSubmission->getCurrentReviewType();

		if (isset($reviewType)) {
			$reviewRound = $reviewRoundDao->build(
							$seriesEditorSubmission->getId(),
							$seriesEditorSubmission->getCurrentReviewType(),
							$round == null ? 1 : $round
						);
			if ($seriesEditorSubmission->getReviewRevision() != null) {
				$reviewRound->setReviewRevision($seriesEditorSubmission->getReviewRevision());
				$reviewRoundDao->updateObject($reviewRound);
			}
		}

		// update review assignments
		$removedReviewAssignments =& $seriesEditorSubmission->getRemovedReviewAssignments();

		foreach ($reviewRounds as $reviewType => $round) {
			for ($i = 1; $i <= $round; $i++) {
				foreach ($seriesEditorSubmission->getReviewAssignments($reviewType, $i) as $reviewAssignment) {
					if (isset($removedReviewAssignments[$reviewAssignment->getId()])) continue;

					if ($reviewAssignment->getId() > 0) {
						$this->reviewAssignmentDao->updateObject($reviewAssignment);
					} else {
						$this->reviewAssignmentDao->insertObject($reviewAssignment);
					}
				}
			}
		}

		// Remove deleted review assignments
		foreach ($removedReviewAssignments as $removedReviewAssignmentId) {
			$this->reviewAssignmentDao->deleteById($removedReviewAssignmentId);
		}

		// Update monograph
		if ($seriesEditorSubmission->getId()) {

			$monograph =& $this->monographDao->getMonograph($seriesEditorSubmission->getId());

			// Only update fields that can actually be edited.
			$monograph->setSeriesId($seriesEditorSubmission->getSeriesId());
			$monograph->setReviewFileId($seriesEditorSubmission->getReviewFileId());
			$monograph->setEditorFileId($seriesEditorSubmission->getEditorFileId());
			$monograph->setStatus($seriesEditorSubmission->getStatus());
			$monograph->setDateStatusModified($seriesEditorSubmission->getDateStatusModified());
			$monograph->setLastModified($seriesEditorSubmission->getLastModified());
			$monograph->setCommentsStatus($seriesEditorSubmission->getCommentsStatus());

			$this->monographDao->updateMonograph($monograph);
		}

	}


	//
	// Miscellaneous
	//
	/**
	 * Delete copyediting assignments by monograph.
	 * @param $monographId int
	 */
	function deleteDecisionsByMonograph($monographId) {
		return $this->update(
			'DELETE FROM edit_decisions WHERE monograph_id = ?',
			$monographId
		);
	}

	/**
	 * Delete review rounds monograph.
	 * @param $monographId int
	 */
	function deleteReviewRoundsByMonograph($monographId) {
		return $this->update(
			'DELETE FROM review_rounds WHERE submission_id = ?',
			$monographId
		);
	}

	/**
	 * Get the editor decisions for a review round of a monograph.
	 * @param $monographId int
	 */
	function getEditorDecisions($monographId, $reviewType = null, $round = null) {
		$decisions = array();

		if ($reviewType == null) {
			$result =& $this->retrieve(
					'SELECT edit_decision_id, editor_id, decision, date_decided, review_type, round
					FROM edit_decisions
					WHERE monograph_id = ?
					ORDER BY date_decided ASC',
					$monographId
				);
		} elseif ($round == null) {
			$result =& $this->retrieve(
					'SELECT edit_decision_id, editor_id, decision, date_decided, review_type, round
					FROM edit_decisions
					WHERE monograph_id = ? AND review_type = ?
					ORDER BY date_decided ASC',
					array($monographId, $reviewType)
				);
		} else {
			$result =& $this->retrieve(
					'SELECT edit_decision_id, editor_id, decision, date_decided, review_type, round
					FROM edit_decisions
					WHERE monograph_id = ? AND review_type = ? AND round = ?
					ORDER BY date_decided ASC',
					array($monographId, $reviewType, $round)
				);
		}

		while (!$result->EOF) {
			$value = array(
					'editDecisionId' => $result->fields['edit_decision_id'],
					'editorId' => $result->fields['editor_id'],
					'decision' => $result->fields['decision'],
					'dateDecided' => $this->datetimeFromDB($result->fields['date_decided'])
				);

			$decisions[] = $value;
			$result->moveNext();
		}
		$result->Close();
		unset($result);

		return $decisions;
	}

	/**
	 * Get the highest review round.
	 * @param $monographId int
	 * @return int
	 */
	function getMaxReviewRound($monographId, $reviewType) {
		$result =& $this->retrieve(
			'SELECT MAX(round) FROM review_rounds WHERE submission_id = ? AND review_type = ?', array($monographId, $reviewType)
		);
		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if a reviewer is assigned to a specified monograph.
	 * @param $monographId int
	 * @param $reviewerId int
	 * @return boolean
	 */
	function reviewerExists($monographId, $reviewerId, $reviewType, $round) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM review_assignments WHERE submission_id = ? AND reviewer_id = ? AND review_type = ? AND round = ? AND cancelled = 0', array($monographId, $reviewerId, $reviewType, $round)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve a list of all reviewers assigned to a monograph.
	 * @param $pressId int
	 * @param $monographId int
	 * @param $round int optional
	 * @return DAOResultFactory containing matching Users
	 */
	function &getReviewersForMonograph($pressId, $monographId, $round) {
		$result =& $this->retrieve(
			'SELECT	u.*
			FROM	users u
				LEFT JOIN user_user_groups uug ON (uug.user_id = u.user_id)
				LEFT JOIN user_groups ug ON (ug.user_group_id = uug.user_group_id)
				LEFT JOIN review_assignments r ON (r.reviewer_id = u.user_id)
			WHERE	ug.press_id = ? AND
					ug.role_id = ? AND
					r.submission_id = ? AND
					r.round = ?
			ORDER BY last_name, first_name',
			array((int) $pressId, ROLE_ID_REVIEWER, (int) $monographId, (int) $round)
		);

		$returner = new DAOResultFactory($result, $this, '_returnReviewerUserFromRow');
		return $returner;
	}

	function &_returnReviewerUserFromRow(&$row) { // FIXME
		$user =& $this->userDao->_returnUserFromRowWithData($row);
		if(isset($row['review_id'])) $user->review_id = $row['review_id'];

		HookRegistry::call('SeriesEditorSubmissionDAO::_returnReviewerUserFromRow', array(&$user, &$row));

		return $user;
	}

	/**
	 * Retrieve a list of all reviewers not assigned to the specified monograph.
	 * @param $pressId int
	 * @param $monographId int
	 * @return array matching Users
	 */
	function &getReviewersNotAssignedToMonograph($pressId, $monographId) {

		$result =& $this->retrieve(
			'SELECT	u.*
			FROM	users u
				LEFT JOIN user_user_groups uug ON (uug.user_id = u.user_id)
				LEFT JOIN user_groups ug ON (ug.user_group_id = uug.user_group_id)
				LEFT JOIN review_assignments r ON (r.reviewer_id = u.user_id AND r.submission_id = ?)
			WHERE	ug.press_id = ? AND
				ug.role_id = ? AND
				r.submission_id IS NULL
			ORDER BY last_name, first_name',
			array($monographId, $pressId, ROLE_ID_REVIEWER)
		);

		$returner = new DAOResultFactory($result, $this, '_returnReviewerUserFromRow');
		return $returner;

	}

	/**
	 * Retrieve a list of all reviewers in a press
	 * @param $pressId int
	 * @return array matching Users
	 */
	function &getAllReviewers($pressId) {
		$result =& $this->retrieve(
			'SELECT	u.*
			FROM	users u
				LEFT JOIN user_user_groups uug ON (uug.user_id = u.user_id)
				LEFT JOIN user_groups ug ON (ug.user_group_id = uug.user_group_id)
			WHERE	ug.press_id = ? AND
				ug.role_id = ?
			ORDER BY last_name, first_name',
			array($pressId, ROLE_ID_REVIEWER)
		);

		$returner = new DAOResultFactory($result, $this, '_returnReviewerUserFromRow');
		return $returner;

	}

	/**
	 * Get the number of reviews done, avg. number of days per review, days since last review, and num. of
	 * active reviews for all reviewers of the given press.
	 * @return array
	 */
	function getAnonymousReviewerStatistics() {
		// Setup default array -- Minimum values Will always be set to 0 (to accomodate reviewers that have never reviewed, and thus aren't in review_assignment)
		$reviewerValues =  array('doneMin' => 0, // Will always be set to 0
								'doneMax' => 0,
								'avgMin' => 0, // Will always be set to 0
								'avgMax' => 0,
								'lastMin' => 0, // Will always be set to 0
								'lastMax' => 0,
								'activeMin' => 0, // Will always be set to 0
								'activeMax' => 0);

		// Get number of reviews completed
		$result =& $this->retrieve(
			'SELECT r.reviewer_id, COUNT(*) as completed_count
			FROM review_assignments r
			WHERE r.date_completed IS NOT NULL
			GROUP BY r.reviewer_id'
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if ($reviewerValues['doneMax'] < $row['completed_count']) $reviewerValues['doneMax'] = $row['completed_count'];
			$result->MoveNext();
		}
		$result->Close();
		unset($result);



		// Get average number of days per review and days since last review
		$result =& $this->retrieve(
			'SELECT r.reviewer_id, r.date_completed, r.date_notified
			FROM review_assignments r
			WHERE r.date_notified IS NOT NULL AND
				r.date_completed IS NOT NULL AND
				r.declined = 0'
		);
		$averageTimeStats = array();
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($averageTimeStats[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = array();

			$completed = strtotime($this->datetimeFromDB($row['date_completed']));
			$notified = strtotime($this->datetimeFromDB($row['date_notified']));
			$timeSinceNotified = time() - $notified;
			if (isset($averageTimeStats[$row['reviewer_id']]['total_span'])) {
				$averageTimeStats[$row['reviewer_id']]['total_span'] += $completed - $notified;
				$averageTimeStats[$row['reviewer_id']]['completed_review_count'] += 1;
			} else {
				$averageTimeStats[$row['reviewer_id']]['total_span'] = $completed - $notified;
				$averageTimeStats[$row['reviewer_id']]['completed_review_count'] = 1;
			}

			// Calculate the average length of review in days.
			$averageTimeStats[$row['reviewer_id']]['average_span'] = (($averageTimeStats[$row['reviewer_id']]['total_span'] / $averageTimeStats[$row['reviewer_id']]['completed_review_count']) / 86400);

			// This reviewer has the highest average; put in global statistics array
			if ($reviewerValues['avgMax'] < $averageTimeStats[$row['reviewer_id']]['average_span']) $reviewerValues['avgMax'] = round($averageTimeStats[$row['reviewer_id']]['average_span']);
			if ($timeSinceNotified > $reviewerValues['lastMax']) $reviewerValues['lastMax'] = $timeSinceNotified;

			$result->MoveNext();
		}
		$reviewerValues['lastMax'] = round($reviewerValues['lastMax'] / 86400); // Round to nearest day
		$result->Close();
		unset($result);


		// Get number of currently active reviews
		$result =& $this->retrieve(
			'SELECT r.reviewer_id, COUNT(*) AS incomplete
			FROM review_assignments r
			WHERE r.date_notified IS NOT NULL AND
				r.date_completed IS NULL AND
				r.cancelled = 0
			GROUP BY r.reviewer_id'
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);

			if ($row['incomplete'] > $reviewerValues['activeMax']) $reviewerValues['activeMax'] = $row['incomplete'];
			$result->MoveNext();
		}
		$result->Close();
		unset($result);


		return $reviewerValues;
	}
}

?>
