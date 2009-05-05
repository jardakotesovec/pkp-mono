<?php

/**
 * @file classes/submission/copyeditor/CopyeditorSubmissionDAO.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CopyeditorSubmissionDAO
 * @ingroup submission
 * @see CopyeditorSubmission
 *
 * @brief Operations for retrieving and modifying CopyeditorSubmission objects.
 */

// $Id$


import('submission.copyeditor.CopyeditorSubmission');

class CopyeditorSubmissionDAO extends DAO {
	var $monographDao;
	var $authorDao;
	var $userDao;
	var $editAssignmentDao;
	var $layoutAssignmentDao;
	var $monographFileDao;
	var $suppFileDao;
	var $galleyDao;
	var $monographCommentDao;
	var $proofAssignmentDao;

	/**
	 * Constructor.
	 */
	function CopyeditorSubmissionDAO() {
		parent::DAO();
		$this->monographDao =& DAORegistry::getDAO('MonographDAO');
		$this->authorDao =& DAORegistry::getDAO('AuthorDAO');
		$this->userDao =& DAORegistry::getDAO('UserDAO');
		$this->editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
//		$this->layoutAssignmentDao =& DAORegistry::getDAO('LayoutAssignmentDAO');
		$this->monographDao =& DAORegistry::getDAO('MonographDAO');
		$this->monographFileDao =& DAORegistry::getDAO('MonographFileDAO');
		$this->monographCommentDao =& DAORegistry::getDAO('MonographCommentDAO');
//		$this->proofAssignmentDao =& DAORegistry::getDAO('ProofAssignmentDAO');
		$this->suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
//		$this->galleyDao =& DAORegistry::getDAO('MonographGalleyDAO');
	}

	/**
	 * Retrieve a copyeditor submission by monograph ID.
	 * @param $monographId int
	 * @return CopyeditorSubmission
	 */
	function &getCopyeditorSubmission($monographId) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();
		$result =& $this->retrieve(
			'SELECT	a.*,
				e.editor_id,
				c.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS arrangement_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS arrangement_abbrev
			FROM	monographs a
				LEFT JOIN edit_assignments e ON (a.monograph_id = e.monograph_id)
				LEFT JOIN acquisitions_arrangements s ON (s.arrangement_id = a.arrangement_id)
				LEFT JOIN copyed_assignments c ON (c.monograph_id = a.monograph_id)
				LEFT JOIN acquisitions_arrangements_settings stpl ON (s.arrangement_id = stpl.arrangement_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN acquisitions_arrangements_settings stl ON (s.arrangement_id = stl.arrangement_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN acquisitions_arrangements_settings sapl ON (s.arrangement_id = sapl.arrangement_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN acquisitions_arrangements_settings sal ON (s.arrangement_id = sal.arrangement_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE	a.monograph_id = ?',
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
			$returner =& $this->_returnCopyeditorSubmissionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return a CopyeditorSubmission object from a row.
	 * @param $row array
	 * @return CopyeditorSubmission
	 */
	function &_returnCopyeditorSubmissionFromRow(&$row) {
		$copyeditorSubmission = new CopyeditorSubmission();

		// Monograph attributes
		$this->monographDao->_monographFromRow($copyeditorSubmission, $row);

		// Copyedit Assignment
		$copyeditorSubmission->setCopyedId($row['copyed_id']);
		$copyeditorSubmission->setCopyeditorId($row['copyeditor_id']);
		$copyeditorSubmission->setCopyeditor($this->userDao->getUser($row['copyeditor_id']), true);
		$copyeditorSubmission->setDateNotified($this->datetimeFromDB($row['date_notified']));
		$copyeditorSubmission->setDateUnderway($this->datetimeFromDB($row['date_underway']));
		$copyeditorSubmission->setDateCompleted($this->datetimeFromDB($row['date_completed']));
		$copyeditorSubmission->setDateAcknowledged($this->datetimeFromDB($row['date_acknowledged']));
		$copyeditorSubmission->setDateAuthorNotified($this->datetimeFromDB($row['date_author_notified']));
		$copyeditorSubmission->setDateAuthorUnderway($this->datetimeFromDB($row['date_author_underway']));
		$copyeditorSubmission->setDateAuthorCompleted($this->datetimeFromDB($row['date_author_completed']));
		$copyeditorSubmission->setDateAuthorAcknowledged($this->datetimeFromDB($row['date_author_acknowledged']));
		$copyeditorSubmission->setDateFinalNotified($this->datetimeFromDB($row['date_final_notified']));
		$copyeditorSubmission->setDateFinalUnderway($this->datetimeFromDB($row['date_final_underway']));
		$copyeditorSubmission->setDateFinalCompleted($this->datetimeFromDB($row['date_final_completed']));
		$copyeditorSubmission->setDateFinalAcknowledged($this->datetimeFromDB($row['date_final_acknowledged']));
		$copyeditorSubmission->setInitialRevision($row['initial_revision']);
		$copyeditorSubmission->setEditorAuthorRevision($row['editor_author_revision']);
		$copyeditorSubmission->setFinalRevision($row['final_revision']);

		// Editor Assignment
		$editAssignments =& $this->editAssignmentDao->getByMonographId($row['monograph_id']);
		$copyeditorSubmission->setEditAssignments($editAssignments->toArray());

		// Comments
//		$copyeditorSubmission->setMostRecentCopyeditComment($this->monographCommentDao->getMostRecentMonographComment($row['monograph_id'], COMMENT_TYPE_COPYEDIT, $row['monograph_id']));
//		$copyeditorSubmission->setMostRecentLayoutComment($this->monographCommentDao->getMostRecentMonographComment($row['monograph_id'], COMMENT_TYPE_LAYOUT, $row['monograph_id']));

		// Files

		// Initial Copyedit File
		if ($row['initial_revision'] != null) {
			$copyeditorSubmission->setInitialCopyeditFile($this->monographFileDao->getMonographFile($row['copyedit_file_id'], $row['initial_revision']));
		}

		// Information for Layout table access
		$copyeditorSubmission->setSuppFiles($this->suppFileDao->getSuppFilesByMonograph($row['monograph_id']));
//		$copyeditorSubmission->setGalleys($this->galleyDao->getGalleysByMonograph($row['monograph_id']));

		// Editor / Author Copyedit File
		if ($row['editor_author_revision'] != null) {
			$copyeditorSubmission->setEditorAuthorCopyeditFile($this->monographFileDao->getMonographFile($row['copyedit_file_id'], $row['editor_author_revision']));
		}

		// Final Copyedit File
		if ($row['final_revision'] != null) {
			$copyeditorSubmission->setFinalCopyeditFile($this->monographFileDao->getMonographFile($row['copyedit_file_id'], $row['final_revision']));
		}

//		$copyeditorSubmission->setLayoutAssignment($this->layoutAssignmentDao->getLayoutAssignmentByMonographId($row['monograph_id']));
//		$copyeditorSubmission->setProofAssignment($this->proofAssignmentDao->getProofAssignmentByMonographId($row['monograph_id']));

		HookRegistry::call('CopyeditorSubmissionDAO::_returnCopyeditorSubmissionFromRow', array(&$copyeditorSubmission, &$row));

		return $copyeditorSubmission;
	}

	/**
	 * Insert a new CopyeditorSubmission.
	 * @param $copyeditorSubmission CopyeditorSubmission
	 */	
	function insertCopyeditorSubmission(&$copyeditorSubmission) {
		$this->update(
			sprintf('INSERT INTO copyed_assignments
				(monograph_id, copyeditor_id, date_notified, date_underway, date_completed, date_acknowledged, date_author_notified, date_author_underway, date_author_completed, date_author_acknowledged, date_final_notified, date_final_underway, date_final_completed, date_final_acknowledged, initial_revision, editor_author_revision, final_revision)
				VALUES
				(?, ?, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, ?, ?, ?)',
				$this->datetimeToDB($copyeditorSubmission->getDateNotified()), $this->datetimeToDB($copyeditorSubmission->getDateUnderway()), $this->datetimeToDB($copyeditorSubmission->getDateCompleted()), $this->datetimeToDB($copyeditorSubmission->getDateAcknowledged()), $this->datetimeToDB($copyeditorSubmission->getDateAuthorNotified()), $this->datetimeToDB($copyeditorSubmission->getDateAuthorUnderway()), $this->datetimeToDB($copyeditorSubmission->getDateAuthorCompleted()), $this->datetimeToDB($copyeditorSubmission->getDateAuthorAcknowledged()), $this->datetimeToDB($copyeditorSubmission->getDateFinalNotified()), $this->datetimeToDB($copyeditorSubmission->getDateFinalUnderway()), $this->datetimeToDB($copyeditorSubmission->getDateFinalCompleted()), $this->datetimeToDB($copyeditorSubmission->getDateFinalAcknowledged())),
			array(
				$copyeditorSubmission->getMonographId(),
				$copyeditorSubmission->getCopyeditorId() === null ? 0 : $copyeditorSubmission->getCopyeditorId(),
				$copyeditorSubmission->getInitialRevision(),
				$copyeditorSubmission->getEditorAuthorRevision(),
				$copyeditorSubmission->getFinalRevision()
			)
		);

		$copyeditorSubmission->setCopyedId($this->getInsertCopyedId());
		return $copyeditorSubmission->getCopyedId();
	}

	/**
	 * Update an existing copyeditor submission.
	 * @param $copyeditorSubmission CopyeditorSubmission
	 */
	function updateCopyeditorSubmission(&$copyeditorSubmission) {
		$this->update(
			sprintf('UPDATE copyed_assignments
				SET
					monograph_id = ?,
					copyeditor_id = ?,
					date_notified = %s,
					date_underway = %s,
					date_completed = %s,
					date_acknowledged = %s,
					date_author_notified = %s,
					date_author_underway = %s,
					date_author_completed = %s,
					date_author_acknowledged = %s,
					date_final_notified = %s,
					date_final_underway = %s,
					date_final_completed = %s,
					date_final_acknowledged = %s,
					initial_revision = ?,
					editor_author_revision = ?,
					final_revision = ?
				WHERE copyed_id = ?',
				$this->datetimeToDB($copyeditorSubmission->getDateNotified()), $this->datetimeToDB($copyeditorSubmission->getDateUnderway()), $this->datetimeToDB($copyeditorSubmission->getDateCompleted()), $this->datetimeToDB($copyeditorSubmission->getDateAcknowledged()), $this->datetimeToDB($copyeditorSubmission->getDateAuthorNotified()), $this->datetimeToDB($copyeditorSubmission->getDateAuthorUnderway()), $this->datetimeToDB($copyeditorSubmission->getDateAuthorCompleted()), $this->datetimeToDB($copyeditorSubmission->getDateAuthorAcknowledged()), $this->datetimeToDB($copyeditorSubmission->getDateFinalNotified()), $this->datetimeToDB($copyeditorSubmission->getDateFinalUnderway()), $this->datetimeToDB($copyeditorSubmission->getDateFinalCompleted()), $this->datetimeToDB($copyeditorSubmission->getDateFinalAcknowledged())),
			array(
				$copyeditorSubmission->getMonographId(),
				$copyeditorSubmission->getCopyeditorId() === null ? 0 : $copyeditorSubmission->getCopyeditorId(),
				$copyeditorSubmission->getInitialRevision(),
				$copyeditorSubmission->getEditorAuthorRevision(),
				$copyeditorSubmission->getFinalRevision(),
				$copyeditorSubmission->getCopyedId()
			)
		);
	}

	/**
	 * Get all submissions for a copyeditor of a press.
	 * @param $copyeditorId int
	 * @param $pressId int optional
	 * @param $searchField int SUBMISSION_FIELD_... constant
	 * @param $searchMatch String 'is' or 'contains' or 'startsWith'
	 * @param $search String Search string
	 * @param $dateField int SUBMISSION_FIELD_DATE_... constant
	 * @param $dateFrom int Search from timestamp
	 * @param $dateTo int Search to timestamp
	 * @return array CopyeditorSubmissions
	 */
	function &getCopyeditorSubmissionsByCopyeditorId($copyeditorId, $pressId = null, $searchField = null, $searchMatch = null, $search = null, $dateField = null, $dateFrom = null, $dateTo = null, $active = true, $rangeInfo = null) {
		$locale = Locale::getLocale();
		$primaryLocale = Locale::getPrimaryLocale();
		$params = array(
			'title', // Section title
			$primaryLocale,
			'title',
			$locale,
			'abbrev', // Section abbrev
			$primaryLocale,
			'abbrev',
			$locale,
			'title' // Monograph title
		);

		if (isset($pressId)) $params[] = $pressId;
		$params[] = $copyeditorId;

		$searchSql = '';

		if (!empty($search)) switch ($searchField) {
			case SUBMISSION_FIELD_TITLE:
				if ($searchMatch === 'is') {
					$searchSql = ' AND LOWER(atl.setting_value) = LOWER(?)';
				} elseif ($searchMatch === 'contains') {
					$searchSql = ' AND LOWER(atl.setting_value) LIKE LOWER(?)';
					$search = '%' . $search . '%';
				} else { // $searchMatch === 'startsWith'
					$searchSql = ' AND LOWER(atl.setting_value) LIKE LOWER(?)';
					$search = $search . '%';
				}
				$params[] = $search;
				break;
			case SUBMISSION_FIELD_AUTHOR:
				$first_last = $this->_dataSource->Concat('aa.first_name', '\' \'', 'aa.last_name');
				$first_middle_last = $this->_dataSource->Concat('aa.first_name', '\' \'', 'aa.middle_name', '\' \'', 'aa.last_name');
				$last_comma_first = $this->_dataSource->Concat('aa.last_name', '\', \'', 'aa.first_name');
				$last_comma_first_middle = $this->_dataSource->Concat('aa.last_name', '\', \'', 'aa.first_name', '\' \'', 'aa.middle_name');

				if ($searchMatch === 'is') {
					$searchSql = " AND (LOWER(aa.last_name) = LOWER(?) OR LOWER($first_last) = LOWER(?) OR LOWER($first_middle_last) = LOWER(?) OR LOWER($last_comma_first) = LOWER(?) OR LOWER($last_comma_first_middle) = LOWER(?))";
				} elseif ($searchMatch === 'contains') {
					$searchSql = " AND (LOWER(aa.last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
					$search = '%' . $search . '%';
				} else { // $searchMatch === 'startsWith'
					$searchSql = " AND (LOWER(aa.last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
					$search = $search . '%';
				}
				$params[] = $params[] = $params[] = $params[] = $params[] = $search;
				break;
			case SUBMISSION_FIELD_EDITOR:
				$first_last = $this->_dataSource->Concat('ed.first_name', '\' \'', 'ed.last_name');
				$first_middle_last = $this->_dataSource->Concat('ed.first_name', '\' \'', 'ed.middle_name', '\' \'', 'ed.last_name');
				$last_comma_first = $this->_dataSource->Concat('ed.last_name', '\', \'', 'ed.first_name');
				$last_comma_first_middle = $this->_dataSource->Concat('ed.last_name', '\', \'', 'ed.first_name', '\' \'', 'ed.middle_name');
				if ($searchMatch === 'is') {
					$searchSql = " AND (LOWER(ed.last_name) = LOWER(?) OR LOWER($first_last) = LOWER(?) OR LOWER($first_middle_last) = LOWER(?) OR LOWER($last_comma_first) = LOWER(?) OR LOWER($last_comma_first_middle) = LOWER(?))";
				} elseif ($searchMatch === 'contains') {
					$searchSql = " AND (LOWER(ed.last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
					$search = '%' . $search . '%';
				} else { // $searchMatch === 'startsWith'
					$searchSql = " AND (LOWER(ed.last_name) LIKE LOWER(?) OR LOWER($first_last) LIKE LOWER(?) OR LOWER($first_middle_last) LIKE LOWER(?) OR LOWER($last_comma_first) LIKE LOWER(?) OR LOWER($last_comma_first_middle) LIKE LOWER(?))";
					$search = $search . '%';
				}
				$params[] = $params[] = $params[] = $params[] = $params[] = $search;
				break;
		}

		if (!empty($dateFrom) || !empty($dateTo)) switch($dateField) {
			case SUBMISSION_FIELD_DATE_SUBMITTED:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND a.date_submitted >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND a.date_submitted <= ' . $this->datetimeToDB($dateTo);
				}
				break;
			case SUBMISSION_FIELD_DATE_COPYEDIT_COMPLETE:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND c.date_final_completed >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND c.date_final_completed <= ' . $this->datetimeToDB($dateTo);
				}
				break;
			case SUBMISSION_FIELD_DATE_LAYOUT_COMPLETE:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND l.date_completed >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= ' AND l.date_completed <= ' . $this->datetimeToDB($dateTo);
				}
				break;
			case SUBMISSION_FIELD_DATE_PROOFREADING_COMPLETE:
				if (!empty($dateFrom)) {
					$searchSql .= ' AND p.date_proofreader_completed >= ' . $this->datetimeToDB($dateFrom);
				}
				if (!empty($dateTo)) {
					$searchSql .= 'AND p.date_proofreader_completed <= ' . $this->datetimeToDB($dateTo);
				}
				break;
		}

		$sql = 'SELECT DISTINCT
				a.*,
				c.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS arrangement_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS arrangement_abbrev
			FROM
				monographs a
				INNER JOIN monograph_authors aa ON (aa.monograph_id = a.monograph_id)
				LEFT JOIN acquisitions_arrangements s ON (s.arrangement_id = a.arrangement_id)
				LEFT JOIN copyed_assignments c ON (c.monograph_id = a.monograph_id)
				LEFT JOIN edit_assignments e ON (e.monograph_id = a.monograph_id)
				LEFT JOIN users ed ON (e.editor_id = ed.user_id)
				LEFT JOIN layouted_assignments l ON (l.monograph_id = a.monograph_id)
				LEFT JOIN proof_assignments p ON (p.monograph_id = a.monograph_id)
				LEFT JOIN acquisitions_arrangements_settings stpl ON (s.arrangement_id = stpl.arrangement_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN acquisitions_arrangements_settings stl ON (s.arrangement_id = stl.arrangement_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN acquisitions_arrangements_settings sapl ON (s.arrangement_id = sapl.arrangement_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN acquisitions_arrangements_settings sal ON (s.arrangement_id = sal.arrangement_id AND sal.setting_name = ? AND sal.locale = ?)
				LEFT JOIN monograph_settings atl ON (a.monograph_id = atl.monograph_id AND atl.setting_name = ?)
			WHERE
				' . (isset($pressId)?'a.press_id = ? AND':'') . '
				c.copyeditor_id = ? AND
			(' . ($active?'':'NOT ') . ' ((c.date_notified IS NOT NULL AND c.date_completed IS NULL) OR (c.date_final_notified IS NOT NULL AND c.date_final_completed IS NULL))) ';

		$result =& $this->retrieveRange(
			$sql . ' ' . $searchSql . ' ORDER BY a.monograph_id ASC',
			count($params)==1?array_shift($params):$params,
			$rangeInfo);

		$returner = new DAOResultFactory($result, $this, '_returnCopyeditorSubmissionFromRow');
		return $returner;
	}

	/**
	 * Get the ID of the last inserted copyeditor assignment.
	 * @return int
	 */
	function getInsertCopyedId() {
		return $this->getInsertId('copyed_assignments', 'copyed_id');
	}

	/**
	 * Get count of active and complete assignments
	 * @param copyeditorId int
	 * @param pressId int
	 */
	function getSubmissionsCount($copyeditorId, $pressId) {
		$submissionsCount = array();
		$submissionsCount[0] = 0;
		$submissionsCount[1] = 0;

		$sql = 'SELECT c.date_final_completed FROM monographs a LEFT JOIN sections s ON (s.section_id = a.section_id) LEFT JOIN copyed_assignments c ON (c.monograph_id = a.monograph_id) WHERE a.press_id = ? AND c.copyeditor_id = ? AND c.date_notified IS NOT NULL';

		$result =& $this->retrieve($sql, array($pressId, $copyeditorId));

		while (!$result->EOF) {
			if ($result->fields['date_final_completed'] == null) {
				$submissionsCount[0] += 1;
			} else {
				$submissionsCount[1] += 1;
			}
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $submissionsCount;
	}
}

?>
