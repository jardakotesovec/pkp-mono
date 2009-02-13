<?php

/**
 * @file classes/mail/MonographMailTemplate.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MonographMailTemplate
 * @ingroup mail
 *
 * @brief Subclass of MailTemplate for sending emails related to monographs.
 *
 * This allows for monograph-specific functionality like logging, etc.
 */

// $Id$


import('mail.MailTemplate');
import('monograph.log.MonographEmailLogEntry'); // Bring in log constants

class MonographMailTemplate extends MailTemplate {

	/** @var object the associated monograph */
	var $monograph;

	/** @var object the associated press */
	var $press;

	/** @var int Event type of this email */
	var $eventType;

	/** @var int Associated type of this email */
	var $assocType;

	/** @var int Associated ID of this email */
	var $assocId;

	/**
	 * Constructor.
	 * @param $monograph object
	 * @param $emailType string optional
	 * @param $locale string optional
	 * @param $enableAttachments boolean optional
	 * @param $press object optional
	 * @param $includeSignature boolean optional
	 * @see MailTemplate::MailTemplate()
	 */
	function MonographMailTemplate($monograph, $emailKey = null, $locale = null, $enableAttachments = null, $press = null, $includeSignature = true) {
		parent::MailTemplate($emailKey, $locale, $enableAttachments, $press, $includeSignature);
		$this->monograph = $monograph;
	}

	function assignParams($paramArray = array()) {
		$monograph = &$this->monograph;
		$press = isset($this->press)?$this->press:Request::getPress();

		$paramArray['monographTitle'] = strip_tags($monograph->getMonographTitle());
		$paramArray['monographId'] = $monograph->getMonographId();
		$paramArray['pressName'] = strip_tags($press->getPressTitle());
		$paramArray['sectionName'] = strip_tags($monograph->getSectionTitle());
		$paramArray['monographAbstract'] = strip_tags($monograph->getMonographAbstract());
		$paramArray['authorString'] = strip_tags($monograph->getAuthorString());

		parent::assignParams($paramArray);
	}

	/**
	 * @see parent::send()
	 */
	function send() {
		if (parent::send(false)) {
			if (!isset($this->skip) || !$this->skip) $this->log();
			$user =& Request::getUser();
			if ($this->attachmentsEnabled) $this->_clearAttachments($user->getUserId());
			return true;

		} else {
			return false;
		}
	}

	/**
	 * @see parent::sendWithParams()
	 */
	function sendWithParams($paramArray) {
		$savedSubject = $this->getSubject();
		$savedBody = $this->getBody();

		$this->assignParams($paramArray);

		$ret = $this->send();

		$this->setSubject($savedSubject);
		$this->setBody($savedBody);

		return $ret;
	}

	/**
	 * Add a generic association between this email and some event type / type / ID tuple.
	 * @param $eventType int
	 * @param $assocType int
	 * @param $assocId int
	 */
	function setAssoc($eventType, $assocType, $assocId) {
		$this->eventType = $eventType;
		$this->assocType = $assocType;
		$this->assocId = $assocId;
	}

	/**
	 * Set the press this message is associated with.
	 * @param $press object
	 */
	function setPress($press) {
		$this->press = $press;
	}

	/**
	 * Save the email in the monograph email log.
	 */
	function log() {
		import('monograph.log.MonographEmailLogEntry');
		import('monograph.log.MonographLog');
		$entry = new MonographEmailLogEntry();
		$monograph = &$this->monograph;

		// Log data
		$entry->setEventType($this->eventType);
		$entry->setAssocType($this->assocType);
		$entry->setAssocId($this->assocId);

		// Email data
		$entry->setSubject($this->getSubject());
		$entry->setBody($this->getBody());
		$entry->setFrom($this->getFromString(false));
		$entry->setRecipients($this->getRecipientString());
		$entry->setCcs($this->getCcString());
		$entry->setBccs($this->getBccString());

		// Add log entry
		$logEntryId = MonographLog::logEmailEntry($monograph->getMonographId(), $entry);

		// Add attachments
		import('file.MonographFileManager');
		$monographFileManager = new MonographFileManager($monograph->getMonographId());
		foreach ($this->getAttachmentFiles() as $attachment) {
			$monographFileManager->temporaryFileToMonographFile(
				$attachment,
				ARTICLE_FILE_ATTACHMENT,
				$logEntryId
			);
		}
	}

	function ccAssignedEditors($monographId) {
		$returner = array();
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments =& $editAssignmentDao->getEditorAssignmentsByMonographId($monographId);
		while ($editAssignment =& $editAssignments->next()) {
			$this->addCc($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
			$returner[] =& $editAssignment;
			unset($editAssignment);
		}
		return $returner;
	}

	function toAssignedReviewingSectionEditors($monographId) {
		$returner = array();
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments =& $editAssignmentDao->getReviewingSectionEditorAssignmentsByMonographId($monographId);
		while ($editAssignment =& $editAssignments->next()) {
			$this->addRecipient($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
			$returner[] =& $editAssignment;
			unset($editAssignment);
		}
		return $returner;
	}

	function toAssignedEditingSectionEditors($monographId) {
		$returner = array();
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments =& $editAssignmentDao->getEditingSectionEditorAssignmentsByMonographId($monographId);
		while ($editAssignment =& $editAssignments->next()) {
			$this->addRecipient($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
			$returner[] =& $editAssignment;
			unset($editAssignment);
		}
		return $returner;
	}

	function ccAssignedReviewingSectionEditors($monographId) {
		$returner = array();
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments =& $editAssignmentDao->getReviewingSectionEditorAssignmentsByMonographId($monographId);
		while ($editAssignment =& $editAssignments->next()) {
			$this->addCc($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
			$returner[] =& $editAssignment;
			unset($editAssignment);
		}
		return $returner;
	}

	function ccAssignedEditingSectionEditors($monographId) {
		$returner = array();
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments =& $editAssignmentDao->getEditingSectionEditorAssignmentsByMonographId($monographId);
		while ($editAssignment =& $editAssignments->next()) {
			$this->addCc($editAssignment->getEditorEmail(), $editAssignment->getEditorFullName());
			$returner[] =& $editAssignment;
			unset($editAssignment);
		}
		return $returner;
	}
}

?>
