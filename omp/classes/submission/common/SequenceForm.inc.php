<?php

/**
 * @file classes/submission/common/SequenceForm.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SequenceFormDAO
 * @ingroup submission
 * @see SubmissionFormSequence
 *
 * @brief Forms that are part of a form sequence should extend this class.
 */

// $Id$

import('form.Form');

class SequenceForm extends Form
{
	var $sequence;
	function SequenceForm() {
		parent::Form($this->getTemplateFile());
	}
	function registerFormWithSequence(&$sequence) {
		$this->sequence =& $sequence;
	}
	function display() {
		if (isset($this->sequence)) {
			$this->sequence->display();
		}
		parent::display();
	}
	/* Override the method and return true if special events were processed.
	 */
	function processEvents() {
		return false;
	}
	function getTemplateFile() {
		return 'ABSTRACT! kindly implement in subclasses';
	}
	function initializeInserts() {
		return 'ABSTRACT! kindly implement in subclasses';
	}
}
?>