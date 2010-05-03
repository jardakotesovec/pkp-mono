<?php

/**
 * @file classes/help/OMPHelpMappingFile.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OMPHelpMappingFile
 * @ingroup help
 * 
 * @brief Abstracts the built-in help mapping XML file.
 */

// $Id$


import('lib.pkp.classes.help.HelpMappingFile');

class OMPHelpMappingFile extends HelpMappingFile {
	/**
	 * Constructor
	 */
	function OMPHelpMappingFile() {
		parent::HelpMappingFile('help/help.xml');
	}

	/**
	 * Return the filename for a built-in OMP help TOC filename.
	 */
	function getTocFilename($tocId) {
		$help =& Help::getHelp();
		return sprintf('help/%s/%s.xml', $help->getLocale(), $tocId);
	}

	/**
	 * Return the filename for a built-in OMP help topic filename.
	 */
	function getTopicFilename($topicId) {
		$help =& Help::getHelp();
		return sprintf('help/%s/%s.xml', $help->getLocale(), $topicId);
	}


	function getTopicIdForFilename($filename) {
		$parts = split('/', str_replace('\\', '/', $filename));
		array_shift($parts); // Knock off "help"
		array_shift($parts); // Knock off locale
		return substr(join('/', $parts), 0, -4); // Knock off .xml
	}

	function getSearchPath($locale = null) {
		if ($locale == '') {
			$help =& Help::getHelp();
			$locale = $help->getLocale();
		}
		return 'help' . DIRECTORY_SEPARATOR . $locale;
	}
}

?>
