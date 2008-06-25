<?php

/**
 * @file ScheduledTask.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package scheduledTask
 * @class ScheduledTask
 *
 * Base class for executing scheduled tasks.
 * All scheduled task classes must extend this class and implement execute().
 *
 * $Id$
 */

class ScheduledTask {

	/** @var array task arguments */
	var $args;

	function ScheduledTask($args = array()) {
		$this->args = $args;
	}

	/**
	 * Fallback method in case task does not implement execute method.
	 */
	function execute() {
		fatalError("ScheduledTask does not implement execute()!\n");
	}
}

?>
