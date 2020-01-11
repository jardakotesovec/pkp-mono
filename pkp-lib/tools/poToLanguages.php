<?php

/**
 * @file tools/poToLanguages.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class poToLanguages
 * @ingroup tools
 *
 * @brief CLI tool to convert a .PO file for ISO639-2 into the languages.xml
 * format supported by the PKP suite. These .po files can be sourced from e.g.:
 * https://packages.debian.org/source/sid/iso-codes
 */

require(dirname(dirname(dirname(dirname(__FILE__)))) . '/tools/bootstrap.inc.php');

define('PO_TO_CSV_TOOL', '/usr/bin/po2csv');

class poToLanguages extends CommandLineTool {
	/** @var $locale string */
	var $locale;

	/** @var $translationFile string */
	var $translationFile;

	/**
	 * Constructor
	 */
	function __construct($argv = array()) {
		parent::__construct($argv);

		array_shift($argv); // Shift the tool name off the top

		$this->locale = array_shift($argv);
		$this->translationFile = array_shift($argv);

		if (	!PKPLocale::isLocaleValid($this->locale) ||
			empty($this->translationFile) ||
			!file_exists($this->translationFile)
		) {
			$this->usage();
			exit(1);
		}
	}

	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Script to convert PO file to PKP's ISO639-1 XML format\n"
			. "Usage: {$this->scriptName} locale /path/to/translation.po\n";
	}

	/**
	 * Rebuild the search index for all articles in all journals.
	 */
	function execute() {
		// Read the translated file as a map from English => Whatever
		$ih = popen(PO_TO_CSV_TOOL . ' ' . escapeshellarg($this->translationFile), 'r');
		if (!$ih) die ('Unable to read ' . $this->translationFile . ' using ' . PO_TO_CSV_TOOL . "\n");

		$translationMap = array();
		while ($row = fgetcsv($ih)) {
			if (count($row) != 3) continue;
			list($comment, $english, $translation) = $row;
			$translationMap[$english] = $translation;
		}
		fclose($ih);

		// Get the English map
		$languageDao = DAORegistry::getDAO('LanguageDAO');
		$languages = $languageDao->getLanguages();

		// Generate a map of code => translation
		$outputMap = array();
		foreach ($languages as $language) {
			$code = $language->getCode();
			$english = $language->getName();
			if (!isset($translationMap[$english])) {
				echo "WARNING: Unknown language \"$english\"! Using English as default.\n";
				$outputMap[$code] = $english;
			} else {
				$outputMap[$code] = $translationMap[$english];
				unset($translationMap[$english]);
			}
		}

		// Use the map to convert the language list to the new locale
		$tfn = 'locale/' . $this->locale . '/languages.xml';
		$ofn = 'lib/pkp/' . $tfn;
		$oh = fopen($ofn, 'w');
		if (!$oh) die ("Unable to $ofn for writing.\n");

		fwrite($oh, '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE languages SYSTEM "../../dtd/languages.dtd">

<!--
  * ' . $tfn . '
  *
  * Copyright (c) 2014-2019 Simon Fraser University
  * Copyright (c) 2000-2019 John Willinsky
  * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
  *
  * Localized list of languages.
  * Please don\'t edit or translate. This file is automatically generated using
  * the ISO 639-2 files of Debian\'s iso-codes package
  * (https://packages.debian.org/sid/all/iso-codes) using the
  * tools/poToLanguages.php tool.
  -->

<languages>
');
		foreach ($outputMap as $code => $translation) {
			fwrite($oh, "	<language code=\"$code\" name=\"$translation\"/>\n");
		}

		fwrite($oh, "</languages>");
		fclose($oh);
	}
}

$tool = new poToLanguages(isset($argv) ? $argv : array());
$tool->execute();


