<?php

/**
 * @file classes/note/NoteDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NoteDAO
 * @ingroup note
 *
 * @see Note
 *
 * @brief Operations for retrieving and modifying Note objects.
 */

namespace PKP\note;

use PKP\core\Core;
use PKP\db\DAOResultFactory;
use PKP\plugins\HookRegistry;

class NoteDAO extends \PKP\db\DAO
{
    public const NOTE_ORDER_DATE_CREATED = 1;
    public const NOTE_ORDER_ID = 2;

    /**
     * Create a new data object
     *
     * @return Note
     */
    public function newDataObject()
    {
        return new Note();
    }

    /**
     * Retrieve Note by note id
     *
     * @param $noteId int Note ID
     *
     * @return Note|null object
     */
    public function getById($noteId)
    {
        $result = $this->retrieve(
            'SELECT * FROM notes WHERE note_id = ?',
            [(int) $noteId]
        );
        $row = $result->current();
        return $row ? $this->_fromRow((array) $row) : null;
    }

    /**
     * Retrieve Notes by user id
     *
     * @param $userId int User ID
     * @param $rangeInfo DBResultRange Optional
     *
     * @return object DAOResultFactory containing matching Note objects
     */
    public function getByUserId($userId, $rangeInfo = null)
    {
        $result = $this->retrieveRange(
            'SELECT * FROM notes WHERE user_id = ? ORDER BY date_created DESC',
            [(int) $userId],
            $rangeInfo
        );

        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Retrieve Notes by assoc id/type
     *
     * @param $assocId int ASSOC_TYPE_...
     * @param $assocType int Assoc ID (per $assocType)
     * @param $userId int Optional user ID
     * @param $orderBy int Optional sorting field constant: self::NOTE_ORDER_...
     * @param $sortDirection int Optional sorting order constant: SORT_DIRECTION_...
     *
     * @return object DAOResultFactory containing matching Note objects
     */
    public function getByAssoc($assocType, $assocId, $userId = null, $orderBy = self::NOTE_ORDER_DATE_CREATED, $sortDirection = self::SORT_DIRECTION_DESC, $isAdmin = false)
    {
        $params = [(int) $assocId, (int) $assocType];
        if ($userId) {
            $params[] = (int) $userId;
        }

        // Sanitize sort ordering
        switch ($orderBy) {
            case self::NOTE_ORDER_ID:
                $orderSanitized = 'note_id';
                break;
            case self::NOTE_ORDER_DATE_CREATED:
            default:
                $orderSanitized = 'date_created';
        }
        switch ($sortDirection) {
            case self::SORT_DIRECTION_ASC:
                $directionSanitized = 'ASC';
                break;
            case self::SORT_DIRECTION_DESC:
            default:
                $directionSanitized = 'DESC';
        }

        $result = $this->retrieve(
            $sql = 'SELECT	*
			FROM	notes
			WHERE	assoc_id = ?
				AND assoc_type = ?
				' . ($userId ? ' AND user_id = ?' : '') .
                ($isAdmin ? '' : '
				AND (title IS NOT NULL OR contents IS NOT NULL)') . '
			ORDER BY ' . $orderSanitized . ' ' . $directionSanitized,
            $params
        );
        return new DAOResultFactory($result, $this, '_fromRow', [], $sql, $params); // Counted in QueriesGridCellProvider
    }

    /**
     * Retrieve Notes by assoc id/type
     *
     * @param $assocId int
     * @param $assocType int
     * @param $userId int
     *
     * @return object DAOResultFactory containing matching Note objects
     */
    public function notesExistByAssoc($assocType, $assocId, $userId = null)
    {
        $params = [(int) $assocId, (int) $assocType];
        if ($userId) {
            $params[] = (int) $userId;
        }

        $result = $this->retrieve(
            'SELECT	COUNT(*) AS row_count
			FROM	notes
			WHERE	assoc_id = ? AND assoc_type = ?
			' . ($userId ? ' AND user_id = ?' : ''),
            $params
        );
        $row = $result->current();
        return $row ? (bool) $row->row_count : false;
    }

    /**
     * Determine whether or not unread notes exist for a given association
     *
     * @param $assocType int ASSOC_TYPE_...
     * @param $assocId int Foreign key, depending on ASSOC_TYPE
     * @param $userId int User ID
     */
    public function unreadNotesExistByAssoc($assocType, $assocId, $userId)
    {
        $params = [(int) $assocId, (int) $assocType, (int) $userId];

        $result = $this->retrieve(
            'SELECT	COUNT(*) AS row_count
			FROM	notes n
				JOIN item_views v ON (v.assoc_type = ? AND v.assoc_id = n.note_id AND v.user_id = ?)
			WHERE	n.assoc_type = ? AND
				n.assoc_id = ? AND
				v.assoc_id IS NULL',
            [
                (int) ASSOC_TYPE_NOTE,
                (int) $userId,
                (int) $assocType,
                (int) $assocId
            ]
        );
        $row = $result->current();
        return $row ? (bool) $row->row_count : false;
    }

    /**
     * Creates and returns an note object from a row
     *
     * @param $row array
     *
     * @return Note object
     */
    public function _fromRow($row)
    {
        $note = $this->newDataObject();
        $note->setId($row['note_id']);
        $note->setUserId($row['user_id']);
        $note->setDateCreated($this->datetimeFromDB($row['date_created']));
        $note->setDateModified($this->datetimeFromDB($row['date_modified']));
        $note->setContents($row['contents']);
        $note->setTitle($row['title']);
        $note->setAssocType($row['assoc_type']);
        $note->setAssocId($row['assoc_id']);

        HookRegistry::call('NoteDAO::_fromRow', [&$note, &$row]);

        return $note;
    }

    /**
     * Inserts a new note into notes table
     *
     * @param Note object
     *
     * @return int Note Id
     */
    public function insertObject($note)
    {
        if (!$note->getDateCreated()) {
            $note->setDateCreated(Core::getCurrentDate());
        }
        $this->update(
            sprintf(
                'INSERT INTO notes
				(user_id, date_created, date_modified, title, contents, assoc_type, assoc_id)
				VALUES
				(?, %s, %s, ?, ?, ?, ?)',
                $this->datetimeToDB($note->getDateCreated()),
                $this->datetimeToDB(Core::getCurrentDate())
            ),
            [
                (int) $note->getUserId(),
                $note->getTitle(),
                $note->getContents(),
                (int) $note->getAssocType(),
                (int) $note->getAssocId()
            ]
        );

        $note->setId($this->getInsertId());
        return $note->getId();
    }

    /**
     * Update a note in the notes table
     *
     * @param Note object
     *
     * @return int Note Id
     */
    public function updateObject($note)
    {
        return $this->update(
            sprintf(
                'UPDATE	notes SET
					user_id = ?,
					date_created = %s,
					date_modified = %s,
					title = ?,
					contents = ?,
					assoc_type = ?,
					assoc_id = ?
				WHERE	note_id = ?',
                $this->datetimeToDB($note->getDateCreated()),
                $this->datetimeToDB(Core::getCurrentDate())
            ),
            [
                (int) $note->getUserId(),
                $note->getTitle(),
                $note->getContents(),
                (int) $note->getAssocType(),
                (int) $note->getAssocId(),
                (int) $note->getId()
            ]
        );
    }

    /**
     * Delete a note by note object.
     *
     * @param $note Note
     */
    public function deleteObject($note)
    {
        $this->deleteById($note->getId());
    }

    /**
     * Delete Note by note id
     *
     * @param $noteId int
     * @param $userId int optional
     */
    public function deleteById($noteId, $userId = null)
    {
        $params = [(int) $noteId];
        if ($userId) {
            $params[] = (int) $userId;
        }

        $this->update(
            'DELETE FROM notes WHERE note_id = ?' .
            ($userId ? ' AND user_id = ?' : ''),
            $params
        );
    }

    /**
     * Delete notes by association
     *
     * @param $assocType int ASSOC_TYPE_...
     * @param $assocId int Foreign key, depending on $assocType
     */
    public function deleteByAssoc($assocType, $assocId)
    {
        $notes = $this->getByAssoc($assocType, $assocId);
        while ($note = $notes->next()) {
            $this->deleteObject($note);
        }
    }

    /**
     * Get the ID of the last inserted note
     *
     * @return int
     */
    public function getInsertId()
    {
        return $this->_getInsertId('notes', 'note_id');
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\PKP\note\NoteDAO', '\NoteDAO');
    define('NOTE_ORDER_DATE_CREATED', \NoteDAO::NOTE_ORDER_DATE_CREATED);
    define('NOTE_ORDER_ID', \NoteDAO::NOTE_ORDER_ID);
}
