<?php

/**
 * @file classes/log/PKPSubmissionEventLogDAO.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPSubmissionEventLogDAO
 * @ingroup log
 * @see EventLogDAO
 *
 * @brief Extension to EventLogDAO for submission-specific log entries.
 */

import('lib.pkp.classes.log.EventLogDAO');

class PKPSubmissionEventLogDAO extends EventLogDAO {
	/**
	 * Constructor
	 */
	function SubmissionEventLogDAO() {
		parent::EventLogDAO();
	}

	/**
	 * Get submission event log entries by submission ID
	 * @param $submissionId int
	 * @return DAOResultFactory
	 */
	function getBySubmissionId($submissionId) {
		return $this->getByAssoc(ASSOC_TYPE_SUBMISSION, $submissionId);
	}
}

?>
