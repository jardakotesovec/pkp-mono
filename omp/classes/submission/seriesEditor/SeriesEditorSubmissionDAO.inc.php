<?php

/**
 * @file classes/submission/seriesEditor/SeriesEditorSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
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
	var $submissionFileDao;
	var $signoffDao;
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
		$this->submissionFileDao =& DAORegistry::getDAO('SubmissionFileDAO');
		$this->signoffDao =& DAORegistry::getDAO('SignoffDAO');
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
				COALESCE(sal.setting_value, sapl.setting_value) AS series_abbrev
			FROM	monographs m
				LEFT JOIN series s ON (s.series_id = m.series_id)
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

		// Editor Decisions
		$reviewRounds =& $this->monographDao->getReviewRoundsById($row['monograph_id']);
		while ( $reviewRound =& $reviewRounds->next()) {
			$stageId = $reviewRound->getStageId();
			$round = $reviewRound->getRound();
			$seriesEditorSubmission->setDecisions(
						$this->getEditorDecisions($row['monograph_id'], $stageId, $round),
						$stageId,
						$round);
			unset($reviewRound);
		}

		// Comments
		$seriesEditorSubmission->setMostRecentEditorDecisionComment($this->monographCommentDao->getMostRecentMonographComment($row['monograph_id'], COMMENT_TYPE_EDITOR_DECISION, $row['monograph_id']));
		$seriesEditorSubmission->setMostRecentCopyeditComment($this->monographCommentDao->getMostRecentMonographComment($row['monograph_id'], COMMENT_TYPE_COPYEDIT, $row['monograph_id']));
		$seriesEditorSubmission->setMostRecentLayoutComment($this->monographCommentDao->getMostRecentMonographComment($row['monograph_id'], COMMENT_TYPE_LAYOUT, $row['monograph_id']));
		$seriesEditorSubmission->setMostRecentProofreadComment($this->monographCommentDao->getMostRecentMonographComment($row['monograph_id'], COMMENT_TYPE_PROOFREAD, $row['monograph_id']));

		// Review Assignments
		$reviewRounds =& $this->monographDao->getReviewRoundsById($row['monograph_id']);
		while ( $reviewRound =& $reviewRounds->next()) {
			$stageId = $reviewRound->getStageId();
			$round = $reviewRound->getRound();
			$seriesEditorSubmission->setReviewAssignments(
						$this->reviewAssignmentDao->getBySubmissionId($row['monograph_id'], $round, $stageId),
						$stageId,
						$round);
			unset($reviewRound);
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
		$reviewRounds = $seriesEditorSubmission->getReviewRounds();

		// Update editor decisions.
		while ($reviewRound =& $reviewRounds->next()) {
			$stageId = $reviewRound->getStageId();
			$round = $reviewRound->getRound();
			$editorDecisions = $seriesEditorSubmission->getDecisions($stageId, $round);
			if (is_array($editorDecisions)) {
				foreach ($editorDecisions as $editorDecision) {
					if ($editorDecision['editDecisionId'] == null) {
						$this->update(
							sprintf(
								'INSERT INTO edit_decisions
								(monograph_id, stage_id, round, editor_id, decision, date_decided)
								VALUES (?, ?, ?, ?, ?, %s)',
								$this->datetimeToDB($editorDecision['dateDecided'])
							),
							array(
								$seriesEditorSubmission->getId(),
								$stageId,
								$round,
								$editorDecision['editorId'],
								$editorDecision['decision']
							)
						);
					}
				}
			}
			unset($reviewRound);
		}

		$currentStageId = $seriesEditorSubmission->getStageId();
		$currentRound = $seriesEditorSubmission->getCurrentRound();

		// Make sure the current round exists (FIXME: is this necessary?)
		$reviewRoundDao =& DAORegistry::getDAO('ReviewRoundDAO'); /* @var $reviewRoundDao ReviewRoundDAO */
		if (isset($currentStageId)) {
			$currentReviewRound =& $reviewRoundDao->build(
					$seriesEditorSubmission->getId(),
					$currentStageId,
					$currentRound == null ? 1 : $currentRound);
		}

		// update review assignments
		$removedReviewAssignments =& $seriesEditorSubmission->getRemovedReviewAssignments();

		$reviewRounds = $seriesEditorSubmission->getReviewRounds();
		while ($reviewRound =& $reviewRounds->next()) {
			$stageId = $reviewRound->getStageId();
			$round = $reviewRound->getRound();
			foreach ($seriesEditorSubmission->getReviewAssignments($stageId, $round) as $reviewAssignment) {
				if (isset($removedReviewAssignments[$reviewAssignment->getId()])) continue;

				if ($reviewAssignment->getId() > 0) {
					$this->reviewAssignmentDao->updateObject($reviewAssignment);
				} else {
					$this->reviewAssignmentDao->insertObject($reviewAssignment);
				}
			}
			unset($reviewRound);
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
	 * FIXME: Create EditorDecisionDAO and move this there, see #6455.
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
	 * FIXME: Move to ReviewRoundDAO, see #6455.
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
	 * FIXME: Create EditorDecisionDAO and move this there, see #6455.
	 * @param $monographId int
	 */
	function getEditorDecisions($monographId, $stageId = null, $round = null) {
		$decisions = array();

		if ($stageId == null) {
			$result =& $this->retrieve(
					'SELECT edit_decision_id, editor_id, decision, date_decided, stage_id, round
					FROM edit_decisions
					WHERE monograph_id = ?
					ORDER BY date_decided ASC',
					$monographId
				);
		} elseif ($round == null) {
			$result =& $this->retrieve(
					'SELECT edit_decision_id, editor_id, decision, date_decided, stage_id, round
					FROM edit_decisions
					WHERE monograph_id = ? AND stage_id = ?
					ORDER BY date_decided ASC',
					array($monographId, $stageId)
				);
		} else {
			$result =& $this->retrieve(
					'SELECT edit_decision_id, editor_id, decision, date_decided, stage_id, round
					FROM edit_decisions
					WHERE monograph_id = ? AND stage_id = ? AND round = ?
					ORDER BY date_decided ASC',
					array($monographId, $stageId, $round)
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
	 * Check if a reviewer is assigned to a specified monograph.
	 * FIXME: Move to ReviewAssigmentDAO, see #6455.
	 * @param $monographId int
	 * @param $reviewerId int
	 * @return boolean
	 */
	function reviewerExists($monographId, $reviewerId, $stageId, $round) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM review_assignments WHERE submission_id = ? AND reviewer_id = ? AND stage_id = ? AND round = ? AND cancelled = 0', array($monographId, $reviewerId, $stageId, $round)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve a list of all reviewers assigned to a monograph.
	 * FIXME: Move to UserDAO, see #6455.
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
			WHERE	ug.context_id = ? AND
					ug.role_id = ? AND
					r.submission_id = ? AND
					r.round = ?
			ORDER BY last_name, first_name',
			array((int) $pressId, ROLE_ID_REVIEWER, (int) $monographId, (int) $round)
		);

		$returner = new DAOResultFactory($result, $this, '_returnReviewerUserFromRow');
		return $returner;
	}

	/**
	 * FIXME: Document.
	 * FIXME: Move to UserDAO, see #6455.
	 * @param $row
	 */
	function &_returnReviewerUserFromRow(&$row) {
		$user =& $this->userDao->_returnUserFromRowWithData($row);
		if(isset($row['review_id'])) $user->review_id = $row['review_id'];

		HookRegistry::call('SeriesEditorSubmissionDAO::_returnReviewerUserFromRow', array(&$user, &$row));

		return $user;
	}

	/**
	 * Retrieve a list of all reviewers not assigned to the specified monograph.
	 * FIXME: Move to UserDAO, see #6455.
	 * @param $pressId int
	 * @param $monographId int
	 * @param $round int
	 * @param $name string
	 * @return array matching Users
	 */
	function &getReviewersNotAssignedToMonograph($pressId, $monographId, $round = null, $name = '') {
		$params = isset($round) ? array($round) : array();
		$params = array_merge($params, array($monographId, $pressId, ROLE_ID_REVIEWER));
		if(!empty($name)) {
			$params = array_merge($params, array_pad(array(), 4, '%' . $name . '%'));
		}

		$result =& $this->retrieve(
			'SELECT	DISTINCT u.*
			FROM	users u
				LEFT JOIN user_user_groups uug ON (uug.user_id = u.user_id)
				LEFT JOIN user_groups ug ON (ug.user_group_id = uug.user_group_id)
				LEFT JOIN review_assignments r ON (r.reviewer_id = u.user_id' . (isset($round) ? ' AND round = ?' : '') . ' AND r.submission_id = ?)
			WHERE ug.context_id = ? AND
				ug.role_id = ? AND
				r.submission_id IS NULL' .
				(!empty($name)?' AND (first_name LIKE ? OR last_name LIKE ? OR username LIKE ? OR email LIKE ?)':'') .
			' ORDER BY last_name, first_name',
			$params
		);

		$returner = new DAOResultFactory($result, $this, '_returnReviewerUserFromRow');
		return $returner;

	}

	/**
	 * Retrieve a list of all reviewers in a press
	 * FIXME: Move to UserDAO, see #6455.
	 * @param $pressId int
	 * @return array matching Users
	 */
	function &getAllReviewers($pressId) {
		$result =& $this->retrieve(
			'SELECT	u.*
			FROM	users u
				LEFT JOIN user_user_groups uug ON (uug.user_id = u.user_id)
				LEFT JOIN user_groups ug ON (ug.user_group_id = uug.user_group_id)
			WHERE	ug.context_id = ? AND
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
	 * FIXME: Move to ReviewAssignmentDAO, see #6455.
	 * @return array
	 */
	function getAnonymousReviewerStatistics() {
		// Setup default array -- Minimum values Will always be set to 0 (to accomodate reviewers that have never reviewed, and thus aren't in review_assignment)
		$reviewerValues =  array('done_min' => 0, // Will always be set to 0
								'done_max' => 0,
								'avg_min' => 0, // Will always be set to 0
								'avg_max' => 0,
								'last_min' => 0, // Will always be set to 0
								'last_max' => 0,
								'active_min' => 0, // Will always be set to 0
								'active_max' => 0);

		// Get number of reviews completed
		$result =& $this->retrieve(
			'SELECT r.reviewer_id, COUNT(*) as completed_count
			FROM review_assignments r
			WHERE r.date_completed IS NOT NULL
			GROUP BY r.reviewer_id'
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if ($reviewerValues['done_max'] < $row['completed_count']) $reviewerValues['done_max'] = $row['completed_count'];
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
			if ($reviewerValues['avg_max'] < $averageTimeStats[$row['reviewer_id']]['average_span']) $reviewerValues['avg_max'] = round($averageTimeStats[$row['reviewer_id']]['average_span']);
			if ($timeSinceNotified > $reviewerValues['last_max']) $reviewerValues['last_max'] = $timeSinceNotified;

			$result->MoveNext();
		}
		$reviewerValues['last_max'] = round($reviewerValues['last_max'] / 86400); // Round to nearest day
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

			if ($row['incomplete'] > $reviewerValues['active_max']) $reviewerValues['active_max'] = $row['incomplete'];
			$result->MoveNext();
		}
		$result->Close();
		unset($result);


		return $reviewerValues;
	}

	/**
	 * Given the ranges selected by the editor, produce a filtered list of reviewers
	 * FIXME: Move to UserDAO, see #6455.
	 * @param $pressId int
	 * @param $doneMin int # of reviews completed int
	 * @param $doneMax int
	 * @param $avgMin int Average period of time in days to complete a review int
	 * @param $avgMax int
	 * @param $lastMin int Days since most recently completed review int
	 * @param $lastMax int
	 * @param $activeMin int How many reviews are currently being considered or underway int
	 * @param $activeMax int
	 * @param $interests array
	 * @param $monographId int Filter out reviewers assigned to this monograph
	 * @param $round int Also filter users assigned to this round of the given monograph
	 * @return array Users
	 */
	function getFilteredReviewers($pressId, $doneMin, $doneMax, $avgMin, $avgMax, $lastMin, $lastMax, $activeMin, $activeMax, $interests, $monographId = null, $round = null) {
		$userDao =& DAORegistry::getDAO('UserDAO'); /* @var $userDao UserDAO */
		$interestDao =& DAORegistry::getDAO('InterestDAO'); /* @var $interestDao InterestDAO */
		$reviewerStats = $this->getReviewerStatistics($pressId);

		// Get the IDs of the interests searched for
		$allInterestIds = array();
		if(isset($interests)) {
			foreach ($interests as $key => $interest) {
				$interestIds = $interestDao->getUserIdsByInterest($interest);
				if(!$interestIds) {
					// The interest searched for does not exist -- go to next interest
					continue;
				}
				if ($key == 0) $allInterestIds = $interestIds; // First interest, nothing to intersect with
				else $allInterestIds = array_intersect($allInterestIds, $interestIds);
			}
		}

		// If monographId is set, get the list of of reviewers assigned to the monograph
		if($monographId) {
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO'); /* @var $reviewAssignmentDao ReviewAssignmentDAO */
			$assignedReviewers = $reviewAssignmentDao->getReviewerIdsBySubmissionId($monographId, $round);
		}

		$filteredReviewers = array();
		foreach ($reviewerStats as $userId => $reviewerStat) {
			// Get the days since the user was last notified for a review
			if(!isset($reviewerStat['last_notified'])) {
				$lastNotifiedInDays = 0;
			} else {
				$lastNotifiedInDays = round((time() - strtotime($reviewerStat['last_notified'])) / 86400);
			}

			// If there are interests to check, make sure user is in allInterestIds array
			if(!empty($allInterestIds)) {
				$interestCheck = in_array($userId, $allInterestIds);
			} else $interestCheck = true;

			if ($interestCheck && $reviewerStat['completed_review_count'] <= $doneMax && $reviewerStat['completed_review_count'] >= $doneMin &&
				$reviewerStat['average_span'] <= $avgMax && $reviewerStat['average_span'] >= $avgMin && $lastNotifiedInDays <= $lastMax  &&
				$lastNotifiedInDays >= $lastMin && $reviewerStat['incomplete'] <= $activeMax && $reviewerStat['incomplete'] >= $activeMin) {
					if($monographId && in_array($userId, $assignedReviewers)) {
						continue;
					} else {
						$filteredReviewers[] = $userDao->getUser($userId);
					}
				}
		}

		return $filteredReviewers;
	}

	/**
	 * Get the last assigned and last completed dates for all reviewers of the given press.
	 * FIXME: Move to ReviewAssignmentDAO, see #6455.
	 * @param $pressId int
	 * @return array
	 */
	function getReviewerStatistics($pressId) {
		// Build an array of all reviewers and provide a placeholder for all statistics (so even if they don't
		//  have a value, it will be filled in as 0
		$statistics = Array();
		$reviewerStatsPlaceholder = array('last_notified' => null, 'incomplete' => 0, 'total_span' => 0, 'completed_review_count' => 0, 'average_span' => 0);

		$allReviewers =& $this->getAllReviewers($pressId);
		while($reviewer =& $allReviewers->next()) {
				$statistics[$reviewer->getId()] = $reviewerStatsPlaceholder;
			unset($reviewer);
		}

		// Get counts of completed submissions
		$result =& $this->retrieve(
			'SELECT	r.reviewer_id, MAX(r.date_notified) AS last_notified
			FROM	review_assignments r, monographs m
			WHERE	r.submission_id = m.monograph_id AND
				m.press_id = ?
			GROUP BY r.reviewer_id',
			(int) $pressId
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = $reviewerStatsPlaceholder;
			$statistics[$row['reviewer_id']]['last_notified'] = $this->datetimeFromDB($row['last_notified']);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Get completion status
		$result =& $this->retrieve(
			'SELECT	r.reviewer_id, COUNT(*) AS incomplete
			FROM	review_assignments r, monographs m
			WHERE	r.submission_id = m.monograph_id AND
				r.date_notified IS NOT NULL AND
				r.date_completed IS NULL AND
				r.cancelled = 0 AND
				m.press_id = ?
			GROUP BY r.reviewer_id',
			(int) $pressId
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			if (!isset($statistics[$row['reviewer_id']])) $statistics[$row['reviewer_id']] = $reviewerStatsPlaceholder;
			$statistics[$row['reviewer_id']]['incomplete'] = $row['incomplete'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Calculate time taken for completed reviews
		$result =& $this->retrieve(
			'SELECT	r.reviewer_id, r.date_notified, r.date_completed
			FROM	review_assignments r, monographs m
			WHERE	r.submission_id = m.monograph_id AND
				r.date_notified IS NOT NULL AND
				r.date_completed IS NOT NULL AND
				r.declined = 0 AND
				m.press_id = ?',
			(int) $pressId
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
		unset($result);

		return $statistics;
	}
}

?>
