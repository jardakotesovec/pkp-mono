<?php

/**
 * @file tests/DatabaseTestCase.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DatabaseTestCase
 * @ingroup tests
 *
 * @brief Base class for unit tests that require database support.
 *        The schema TestName.setUp.xml will be installed before each
 *        individual test case (if present). The schema TestName.tearDown.xml may
 *        be used to clean up after each test case.
 */


import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.tests.PKPTestHelper');

abstract class DatabaseTestCase extends PKPTestCase {

	/**
	 * Override this method if you want to backup/restore
	 * tables before/after the test.
	 * @return array A list of tables to backup and restore.
	 */
	protected function getAffectedTables() {
		return array();
	}

	/**
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() {
		// Switch off xdebug screaming (there are
		// errors in adodb...).
		PKPTestHelper::xdebugScream(false);

		// Make sure we have a db connection (some tests
		// might close it and that affects the next ones).
		DBConnection::getInstance()->reconnect();

		// Backup affected tables.
		PKPTestHelper::backupTables($this->getAffectedTables(), $this);
		parent::setUp();
	}

	/**
	 * @see PHPUnit_Framework_TestCase::tearDown()
	 */
	protected function tearDown() {
		parent::tearDown();
		PKPTestHelper::restoreTables($this->getAffectedTables(), $this);

		// Switch xdebug screaming back on.
		PKPTestHelper::xdebugScream(true);
	}
}
?>
