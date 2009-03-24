<?php

/**
 * @file classes/security/RoleDAO.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RoleDAO
 * @ingroup security
 * @see Role
 *
 * @brief Operations for retrieving and modifying Role objects.
 */

// $Id$


import('signoff.SignoffEntity');

class SignoffEntityDAO extends DAO {

	/**
	 * Retrieve a signoff entity by ID.
	 * @param $signoffEntityId int
	 * @return SignoffEntity
	 */
	function remove($eventType, $eventId, $pressId, $entityType, $entityId) {
		$signoffEntity = $this->newDataObject();
		$signoffEntity->setEntityType($entityType);
		$signoffEntity->setEntityId($entityId);
		$signoffEntity->setPressId($pressId);
		$signoffEntity->setEventType($eventType);
		$signoffEntity->setEventId($eventId);

		$this->deleteObject($signoffEntity);

	}
	/**
	 * Retrieve a signoff entity by ID.
	 * @param $signoffEntityId int
	 * @return SignoffEntity
	 */
	function &get($eventType, $eventId, $pressId, $entityType = null, $entityId = null) {

		$sqlParams = array($eventType, $eventId, $pressId);
		$queryExtra = '';

		if (isset($entityType)) {
			$sqlParams[] = $entityType;
			$queryExtra .= ' AND entity_type = ?';
		}
		if (isset($entityId)) {
			$sqlParams[] = $entityId;
			$queryExtra .= ' AND entity_id = ?';
		}

		$result =& $this->retrieve(
			'SELECT * FROM signoff_entities
			WHERE event_type = ? AND
				event_id = ? AND
				press_id = ?'.$queryExtra.'
			ORDER BY entity_type, entity_id',
				$sqlParams
		);

		$returner = null;
		while (!$result->EOF) {
			$returner[] =& $this->_fromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
		unset($result);

		return $returner;
	}

      	/**
	 * Fetch a list of the entities associated with a process for a press.
	 * @param $eventType int
	 * @param $eventId int
	 * @param $pressId int
	 * @return array
	 */
	function getEntitiesForEvent($eventType, $eventId, $pressId) {
		$entries =& $this->get($eventType, $eventId, $pressId);

		$groupDao =& DAORegistry::getDAO('GroupDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$roleDao =& DAORegistry::getDAO('RoleDAO');

		$returner = array(
				SIGNOFF_ENTITY_TYPE_GROUP => array(),
				SIGNOFF_ENTITY_TYPE_USER => array(),
				SIGNOFF_ENTITY_TYPE_ROLE => array()
				);
		if (isset($entries))
		foreach ($entries as $entry) {
			switch($entry->getEntityType()) {
			case SIGNOFF_ENTITY_TYPE_GROUP:
				$returner[SIGNOFF_ENTITY_TYPE_GROUP][] =& $groupDao->getGroup($entry->getEntityId(), ASSOC_TYPE_PRESS, $pressId);
				break;
			case SIGNOFF_ENTITY_TYPE_USER:
				$returner[SIGNOFF_ENTITY_TYPE_USER][] =& $userDao->getUser($entry->getEntityId(), ASSOC_TYPE_PRESS, $pressId);
				break;
			case SIGNOFF_ENTITY_TYPE_ROLE:
				$returner[SIGNOFF_ENTITY_TYPE_ROLE][] =& $roleDao->getRoleName($entry->getEntityId());
				break;
			}
		}
		return $returner;
	}

	/**
	 * Fetch a signoff by symbolic info, building it if needed.
	 * @param $symbolic string
	 * @param $assocType int
	 * @param $assocId int
	 * @return $signoff
	 */
	function build($eventType, $eventId, $pressId, $entityType, $entityId) {
		// If one exists, fetch and return.
		$signoffEntity =& $this->get($eventType, $eventId, $pressId, $entityType, $entityId);
		if ($signoffEntity) return $signoffEntity;

		// Otherwise, build one.
		unset($signoff);
		$signoffEntity = $this->newDataObject();
		$signoffEntity->setEntityType($entityType);
		$signoffEntity->setEntityId($entityId);
		$signoffEntity->setPressId($pressId);
		$signoffEntity->setEventType($eventType);
		$signoffEntity->setEventId($eventId);
		$this->insertObject($signoffEntity);
		return $signoffEntity;
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return SignoffEntity
	 */
	function newDataObject() {
		return new SignoffEntity();
	}

	/**
	 * Internal function to return an SignoffEntity object from a row.
	 * @param $row array
	 * @return SignoffEntity
	 */
	function _fromRow(&$row) {
		$signoffEntity = $this->newDataObject();

		$signoffEntity->setEntityType($row['entity_type']);
		$signoffEntity->setEntityId($row['entity_id']);
		$signoffEntity->setPressId($row['press_id']);
		$signoffEntity->setEventType($row['event_type']);
		$signoffEntity->setEventId($row['event_id']);
		$signoffEntity->setVote($row['vote']);

		return $signoffEntity;
	}

	/**
	 * Insert a new Signoff.
	 * @param $signoff Signoff
	 * @return int 
	 */
	function insertObject(&$signoffEntity) {
		$this->update(
				'INSERT INTO signoff_entities
				(entity_type, entity_id, press_id, event_type, event_id, vote)
				VALUES
				(?, ?, ?, ?, ?, ?)',
			array(
				$signoffEntity->getEntityType(),
				$signoffEntity->getEntityId(),
				$signoffEntity->getPressId(),
				$signoffEntity->getEventType(),
				$signoffEntity->getEventId(),
				$signoffEntity->getVote()
			)
		);
		return true;
	}

	/**
	 * Update an existing signoff entity entry.
	 * @param $signoffEntity SignoffEntity
	 * @return boolean
	 */
	function updateObject(&$signoffEntity) {
		$returner = $this->update(
			sprintf(
				'UPDATE	signoffs
				SET	entity_type = ?,
					entity_id = ?,
					press_id = ?,
					event_type = ?,
					event_id = ?,
					vote = ?,
				WHERE	signoff_id = ?',
				$this->datetimeToDB($signoffEntity->getDateNotified()),
				$this->datetimeToDB($signoffEntity->getDateUnderway()),
				$this->datetimeToDB($signoffEntity->getDateCompleted()),
				$this->datetimeToDB($signoffEntity->getDateAcknowledged())
			),
			array(
				$signoffEntity->getSymbolic(),
				(int) $signoffEntity->getAssocType(),
				(int) $signoffEntity->getAssocId(),
				(int) $signoffEntity->getUserId(),
				$this->nullOrInt($signoffEntity->getFileId()),
				$this->nullOrInt($signoffEntity->getFileRevision()),
				(int) $signoffEntity->getId()
			)
		);
		return $returner;
	}

	/**
	 * Update an existing signoff entity entry.
	 * @param $signoffEntity SignoffEntity
	 * @return boolean
	 */
	function getSignoffUsers($eventType, $eventId, $pressId) {

		$sql = 'SELECT * FROM
			(SELECT u.*
			FROM signoff_entities se, users u, group_memberships gm
			WHERE
			u.user_id=gm.user_id AND
			gm.group_id=se.entity_id AND
			se.entity_type='.SIGNOFF_ENTITY_TYPE_GROUP.'
			UNION
			SELECT u1.*
			FROM signoff_entities se1, users u1
			WHERE
			u1.user_id=se1.entity_id AND
			se1.entity_type='.SIGNOFF_ENTITY_TYPE_USER.'
			) AS t ORDER BY t.first_name, t.last_name';

		$result =& $this->retrieve($sql);
		$userDao =& DAORegistry::getDAO('UserDAO');

		$returner = null;
		while (!$result->EOF) {
			$returner[] =& $userDao->_returnUserFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
		unset($result);

		return $returner;;
	}

	/**
	 * Delete a signoff entity entry.
	 * @param $signoffEntity SignoffEntity
	 * @return boolean
	 */
	function deleteObject($signoffEntity) {
		return $this->update('DELETE FROM signoff_entities 
					WHERE 
					entity_id = ? AND 
					entity_type = ? AND
					press_id = ? AND
					event_type = ? AND
					event_id = ?',
					array(
						$signoffEntity->getEntityId(),
						$signoffEntity->getEntityType(),
						$signoffEntity->getPressId(),
						$signoffEntity->getEventType(),
						$signoffEntity->getEventId()
					));
	}

}
?>