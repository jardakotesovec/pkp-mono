<?php

/**
 * @file tests/classes/sword/FunctionalSwordDepositTest.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalSwordDepositTest
 * @ingroup tests_functional_plugins_generic_sword
 * @see OJSSwordDeposit
 * @see SwordImportExportPlugin
 *
 * @brief Integration/Functional test for the SWORD plug-in
 * and its dependencies.
 */


require_mock_env('lib/pkp/tests/mock');

import('lib.pkp.tests.PKPTestCase');
import('classes.sword.OJSSwordDeposit');
import('lib.pkp.classes.core.PKPRouter');
import('lib.pkp.classes.core.PKPRequest');

class FunctionalSwordDepositTest extends PKPTestCase {

	/**
	 * Override this method if you want to backup/restore
	 * registry entries before/after the test.
	 * @return array A list of registry keys to backup and restore.
	 */
	protected function getMockedRegistryKeys() {
		return array('request');
	}

	public function testDoi() {
		// Mock a router.
		$router = new PKPRouter();
		$application = PKPApplication::getApplication();
		$router->setApplication($application);

		// Mock a request.
		$mockRequest = $this->getMock('PKPRequest', array('getRouter', 'getJournal'));
		$mockRequest->expects($this->any())
		            ->method('getRouter')
		            ->will($this->returnValue($router));
		$mockRequest->expects($this->any())
		            ->method('getJournal')
		            ->will($this->returnValue(null));
		Registry::set('request', $mockRequest);

		// Retrieve test article from test database.
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId(1);

		// Create a SWORD deposit package.
		$deposit = new OJSSwordDeposit($publishedArticle);
		$deposit->setMetadata();

		// Test DOI.
		self::assertEquals('10.1234/t.v1i1.1', $deposit->package->sac_identifier);

		// FIXME: Current requirement is only for a DOI regression test. Test whole package if required.
	}
}
?>