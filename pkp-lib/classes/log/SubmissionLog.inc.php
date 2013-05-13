<?php

/**
 * @defgroup log
 */

/**
 * @file classes/log/SubmissionLog.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionLog
 * @ingroup log
 *
 * @brief Static class for adding / accessing PKP log entries.
 */

class SubmissionLog {

	/**
	 * Add a new event log entry with the specified parameters
	 * @param $request object
	 * @param $submission object
	 * @param $eventType int
	 * @param $messageKey string
	 * @param $params array optional
	 * @return object SubmissionLogEntry iff the event was logged
	 */
	static function logEvent($request, $submission, $eventType, $messageKey, $params = array()) {
		// Create a new entry object
		$submissionEventLogDao = DAORegistry::getDAO('SubmissionEventLogDAO');
		$entry = $submissionEventLogDao->newDataObject();

		// Set implicit parts of the log entry
		$entry->setDateLogged(Core::getCurrentDate());
		$entry->setIPAddress($request->getRemoteAddr());

		$user = $request->getUser();
		if ($user) $entry->setUserId($user->getId());

		$entry->setAssocType(ASSOC_TYPE_SUBMISSION);
		$entry->setAssocId($submission->getId());

		// Set explicit parts of the log entry
		$entry->setEventType($eventType);
		$entry->setMessage($messageKey);
		$entry->setParams($params);
		$entry->setIsTranslated(0); // Legacy for other apps. All messages use locale keys.

		// Insert the resulting object
		$submissionEventLogDao->insertObject($entry);
		return $entry;
	}
}

?>
