<?php

/**
 * @file classes/journal/SectionEditorsDAO.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SectionEditorsDAO
 * @ingroup journal
 *
 * @brief Class for DAO relating sections to editors.
 */

class SectionEditorsDAO extends DAO {
	/**
	 * Insert a new section editor.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $userId int
	 * @param $canReview boolean
	 * @param $canEdit boolean
	 */
	function insertEditor($journalId, $sectionId, $userId, $canReview, $canEdit) {
		return $this->update(
			'INSERT INTO section_editors
				(journal_id, section_id, user_id, can_review, can_edit)
				VALUES
				(?, ?, ?, ?, ?)',
			array(
				(int) $journalId,
				(int) $sectionId,
				(int) $userId,
				$canReview?1:0,
				$canEdit?1:0
			)
		);
	}

	/**
	 * Delete a section editor.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $userId int
	 */
	function deleteEditor($journalId, $sectionId, $userId) {
		return $this->update(
			'DELETE FROM section_editors WHERE journal_id = ? AND section_id = ? AND user_id = ?',
			array(
				(int) $journalId,
				(int) $sectionId,
				(int) $userId
			)
		);
	}

	/**
	 * Retrieve a list of all section editors assigned to the specified section.
	 * @param $journalId int
	 * @param $sectionId int
	 * @return array matching Users
	 */
	function &getEditorsBySectionId($journalId, $sectionId) {
		$users = array();

		$userDao = DAORegistry::getDAO('UserDAO');

		$result = $this->retrieve(
			'SELECT u.*, e.can_review AS can_review, e.can_edit AS can_edit FROM users AS u, section_editors AS e WHERE u.user_id = e.user_id AND e.journal_id = ? AND e.section_id = ? ORDER BY last_name, first_name',
			array((int) $journalId, (int) $sectionId)
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$users[] = array(
				'user' => $userDao->_returnUserFromRow($row),
				'canReview' => $row['can_review'],
				'canEdit' => $row['can_edit']
			);
			$result->MoveNext();
		}

		$result->Close();
		return $users;
	}

	/**
	 * Retrieve a list of all section editors not assigned to the specified section.
	 * @param $journalId int
	 * @param $sectionId int
	 * @return array matching Users
	 */
	function &getEditorsNotInSection($journalId, $sectionId) {
		$users = array();

		$userDao = DAORegistry::getDAO('UserDAO');

		$result = $this->retrieve(
			'SELECT	u.*
			FROM	users u
				JOIN user_user_groups uug ON (u.user_id = uug.user_id)
				JOIN user_groups ug ON (uug.user_group_id = ug.user_group_id AND ug.role_id = ? AND ug.context_id = ?)
				LEFT JOIN section_editors e ON (e.user_id = u.user_id AND e.journal_id = ug.context_id AND e.section_id = ?)
			WHERE	e.section_id IS NULL
			ORDER BY last_name, first_name',
			array(ROLE_ID_SUB_EDITOR, (int) $journalId, (int) $sectionId)
		);

		while (!$result->EOF) {
			$users[] = $userDao->_returnUserFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		return $users;
	}

	/**
	 * Delete all section editors for a specified section in a journal.
	 * @param $sectionId int
	 * @param $journalId int
	 */
	function deleteEditorsBySectionId($sectionId, $journalId = null) {
		if (isset($journalId)) return $this->update(
			'DELETE FROM section_editors WHERE journal_id = ? AND section_id = ?',
			array($journalId, $sectionId)
		);
		else return $this->update(
			'DELETE FROM section_editors WHERE section_id = ?',
			(int) $sectionId
		);
	}

	/**
	 * Delete all section editors for a specified journal.
	 * @param $journalId int
	 */
	function deleteEditorsByJournalId($journalId) {
		return $this->update(
			'DELETE FROM section_editors WHERE journal_id = ?', (int) $journalId
		);
	}

	/**
	 * Delete all section assignments for the specified user.
	 * @param $userId int
	 * @param $journalId int optional, include assignments only in this journal
	 * @param $sectionId int optional, include only this section
	 */
	function deleteByUserId($userId, $journalId  = null, $sectionId = null) {
		return $this->update(
			'DELETE FROM section_editors WHERE user_id = ?' . (isset($journalId) ? ' AND journal_id = ?' : '') . (isset($sectionId) ? ' AND section_id = ?' : ''),
			isset($journalId) && isset($sectionId) ? array((int) $userId, (int) $journalId, (int) $sectionId)
			: (isset($journalId) ? array((int) $userId, (int) $journalId)
			: (isset($sectionId) ? array((int) $userId, (int) $sectionId) : (int) $userId))
		);
	}

	/**
	 * Check if a user is assigned to a specified section.
	 * @param $journalId int
	 * @param $sectionId int
	 * @param $userId int
	 * @return boolean
	 */
	function editorExists($journalId, $sectionId, $userId) {
		$result = $this->retrieve(
			'SELECT COUNT(*) FROM section_editors WHERE journal_id = ? AND section_id = ? AND user_id = ?',
			array((int) $journalId, (int) $sectionId, (int) $userId)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		return $returner;
	}
}

?>
