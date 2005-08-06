<?php

/**
 * rebuildSearchIndex.php
 *
 * Copyright (c) 2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package tools
 *
 * CLI tool to rebuild the article keyword search database.
 *
 * $Id$
 */

require(dirname(__FILE__) . '/includes/cliTool.inc.php');

import('search.ArticleSearchIndex');

class rebuildSearchIndex extends CommandLineTool {
	
	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Script to rebuild article search index\n"
			. "Usage: {$this->scriptName}\n";
	}
	
	/**
	 * Rebuild the search index for all articles in all journals.
	 */
	function execute() {
		ArticleSearchIndex::rebuildIndex(true);
	}
	
}

$tool = &new rebuildSearchIndex(isset($argv) ? $argv : array());
$tool->execute();
?>
