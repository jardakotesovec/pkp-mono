<?php

/**
 * @file classes/author/form/submit/SubmissionSubmitStep2Form.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionSubmitStep2Form
 * @ingroup author_form_submit
 *
 * @brief Form for Step 2 of author manuscript submission.
 */

// $Id$


import('classes.submission.form.submit.SubmissionSubmitForm');

class SubmissionSubmitStep2Form extends SubmissionSubmitForm {

	/**
	 * Constructor.
	 */
	function SubmissionSubmitStep2Form($monograph) {
		parent::SubmissionSubmitForm($monograph, 2);

		// Validation checks for this form
	}

	/**
	 * Save changes to monograph.
	 * @return int the monograph ID
	 */
	function execute() {
		// Update monograph
		$monographDao =& DAORegistry::getDAO('MonographDAO');
		$monograph =& $this->monograph;

		if ($monograph->getSubmissionProgress() <= $this->step) {
			$monograph->stampStatusModified();
			$monograph->setSubmissionProgress($this->step + 1);
			$monographDao->updateMonograph($monograph);
		}

		return $this->monographId;
	}

}

?>
