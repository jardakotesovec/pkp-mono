<?php
/**
 * @defgroup tests
 */

/**
 * @file tests/PKPTestCase.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPTestCase
 * @ingroup tests
 *
 * @brief Class that implements functionality common to all PKP unit test cases.
 *
 * NB: PHPUnit 3.x requires PHP 5.2 or later so we can use PHP5 constructs.
 */

// $Id$

// Include PHPUnit
require_once('PHPUnit/Extensions/OutputTestCase.php');

abstract class PKPTestCase extends PHPUnit_Extensions_OutputTestCase {
	/**
	 * Set a non-default test configuration
	 * @param $config string the id of the configuration to use
	 * @param $configPath string (optional) where to find the config file, default: 'config'
	 * @param $dbConnect (optional) whether to try to re-connect the data base, default: true
	 */
	protected function setTestConfiguration($config, $configPath = 'config', $dbConnect = true) {
		// Get the configuration file belonging to
		// this test configuration.
		$configFile = $this->getConfigFile($config, $configPath);

		// Avoid unnecessary configuration switches.
		if (Config::getConfigFileName() != $configFile) {
			// Switch the configuration file
			Config::setConfigFileName($configFile);

			// Re-open the database connection with the
			// new configuration.
			if ($dbConnect && class_exists('DBConnection')) DBConnection::getInstance(new DBConnection());
		}
	}

	/**
	 * Resolves the configuration id to a configuration
	 * file
	 * @param $config string
	 * @return string the resolved configuration file name
	 */
	private function getConfigFile($config, $configPath = 'config') {
		// FIXME: How should we resolve config files?
		// We could implement something like a configurable
		// configuration resolver strategy that we plug
		// in here.
		return 'lib/pkp/tests/'.$configPath.'/config.'.$config.'.inc.php';
	}
}
?>
