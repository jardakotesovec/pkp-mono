<?php

/**
 * @file classes/help/Help.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Help
 * @ingroup help
 * 
 * @brief Provides methods for translating help topic keys to their respected topic
 * help ids.
 */

// $Id$


import('help.PKPHelp');

class Help extends PKPHelp {
	/**
	 * Constructor.
	 */
	function Help() {
		parent::PKPHelp();
		import('help.OMPHelpMappingFile');
		$mainMappingFile =& new OMPHelpMappingFile();
		$this->addMappingFile($mainMappingFile);
	}
}

?>
