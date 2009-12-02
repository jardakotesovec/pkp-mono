<?php

/**
 * @file tools/install.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class installTool
 * @ingroup tools
 *
 * @brief CLI tool for installing OJS.
 */

// $Id$


require(dirname(__FILE__) . '/bootstrap.inc.php');

import('cliTool.InstallTool');

class OJSInstallTool extends InstallTool {
	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 */
	function OJSInstallTool($argv = array()) {
		parent::InstallTool($argv);
	}

	/**
	 * Read installation parameters from stdin.
	 * FIXME: May want to implement an abstract "CLIForm" class handling input/validation.
	 * FIXME: Use readline if available?
	 */
	function readParams() {
		Locale::requireComponents(array(LOCALE_COMPONENT_PKP_INSTALLER, LOCALE_COMPONENT_APPLICATION_COMMON));
		printf("%s\n", Locale::translate('installer.ojsInstallation'));

		parent::readParams();

		$this->readParamBoolean('install', 'installer.installApplication');

		return $this->params['install'];
	}

}

$tool = new OJSInstallTool(isset($argv) ? $argv : array());
$tool->execute();

?>
