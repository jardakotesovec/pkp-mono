<?php

/**
 * InstallForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package install.form
 *
 * Form for system installation.
 *
 * $Id$
 */

import('config.ConfigParser');

class InstallForm extends Form {

	/** @var array Database drivers supported by this system */
	var $supportedDatabaseDrivers;
	
	/**
	 * Constructor.
	 */
	function InstallForm() {
		parent::Form('install/install.tpl');
		
		$this->supportedDatabaseDrivers = array (
			'mysql' => 'MySQL',
			'postgres' => 'PostgreSQL',
			'oracle' => 'Oracle',
			'mssql' => 'MS SQL Server'
		);
		
		// Validation checks for this form
		$this->addCheck(new FormValidatorInSet(&$this, 'databaseDriver', 'required', 'installer.form.databaseDriverRequired', array_keys($this->supportedDatabaseDrivers)));
		$this->addCheck(new FormValidator(&$this, 'databaseHost', 'required', 'installer.form.databaseHostRequired'));
		$this->addCheck(new FormValidator(&$this, 'databaseUsername', 'required', 'installer.form.databaseUsernameRequired'));
		$this->addCheck(new FormValidator(&$this, 'databaseName', 'required', 'installer.form.databaseNameRequired'));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('databaseDriverOptions', $this->supportedDatabaseDrivers);

		parent::display();
	}
	
	/**
	 * Initialize form data.
	 */
	function initData() {
		$this->_data = array(
			'databaseDriver' => 'mysql',
			'databaseHost' => 'localhost',
			'databaseUsername' => 'root',
			'databasePassword' => '',
			'databaseName' => 'ojs',
			'createDatabase' => 1
		);
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'databaseDriver',
			'databaseHost',
			'databaseUsername',
			'databasePassword',
			'databaseName',
			'createDatabase'
		));
	}
	
	/**
	 * Perform installation.
	 */
	function execute() {
		if ($this->getData('createDatabase')) {
			// Create new database
			$conn = &new DBConnection(
				$this->getData('databaseDriver'),
				$this->getData('databaseHost'),
				$this->getData('databaseUsername'),
				$this->getData('databasePassword'),
				null
			);
			
			$dbconn = &$conn->getDBConn();
			
			$dbconn->execute('CREATE DATABASE ' . $this->getData('databaseName'));
			if ($dbconn->errorNo() != 0) {
				$this->dbInstallError($dbconn->errorMsg());
				return;
			}
			
			$dbconn->disconnect();
		}
		
		// Connect to database
		$conn = &new DBConnection(
			$this->getData('databaseDriver'),
			$this->getData('databaseHost'),
			$this->getData('databaseUsername'),
			$this->getData('databasePassword'),
			$this->getData('databaseName')
		);
			
		$dbconn = &$conn->getDBConn();
		
		// Create database tables from XML definitions
		require('adodb/adodb-xmlschema.inc.php');
		$schema = &new adoSchema($dbconn);
		$sql = @$schema->parseSchema('dbscripts/xml/ojs_schema.xml');
		$result = $schema->executeSchema($sql, false);
		$schema->destroy();
		
		if (!$result) {
			$this->dbInstallError($dbconn->errorMsg());
			return;
		}
		
		// Update config file
		$configParser = &new ConfigParser();
		if (!$configParser->updateConfig(
				Config::getConfigFileName(),
				array(
					'general' => array(
						'installed' => 'true'
					),
					'database' => array(
						'driver' => $this->getData('databaseDriver'),
						'host' => $this->getData('databaseHost'),
						'username' => $this->getData('databaseUsername'),
						'password' => $this->getData('databasePassword'),
						'name' => $this->getData('databaseName')
					)
				)
		)) {
			// Error reading config file
			$this->installError('installer.configFileError');
		}
		
		$templateMgr = &TemplateManager::getManager();

		if (!$configParser->writeConfig(Config::getConfigFileName())) {
			$configFile = $configParser->getFileContents();
			$templateMgr->assign(array('writeConfigFailed' => true, 'configFileContents' => $configFile));
		}
		
		$templateMgr->display('install/installComplete.tpl');
	}
	
	/**
	 * Fail with a generic installation error.
	 * @param $errorMsg string
	 */
	function installError($errorMsg) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign(array('isInstallError' => true, 'errorMsg' => $errorMsg));
		$this->display();
	}
	
	/**
	 * Fail with a database installation error.
	 * @param $errorMsg string
	 */
	function dbInstallError($errorMsg) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign(array('isInstallError' => true, 'dbErrorMsg' => $errorMsg));
		$this->display();
	}
	
}

?>
