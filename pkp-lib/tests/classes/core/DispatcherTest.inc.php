<?php

/**
 * @file tests/classes/core/DispatcherTest.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DispatcherTest
 * @ingroup tests_classes_core
 * @see Dispatcher
 *
 * @brief Tests for the Dispatcher class.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.core.Registry');
import('lib.pkp.classes.core.PKPApplication');
import('lib.pkp.classes.core.Dispatcher');
import('lib.pkp.classes.core.PKPRequest');
import('lib.pkp.classes.plugins.HookRegistry');

class DispatcherTest extends PKPTestCase {
	const
		PATHINFO_ENABLED = true,
		PATHINFO_DISABLED = false;

	protected
		$dispatcher,
		$request;

	protected function setUp() {
		// Mock application object without calling its constructor.
		$mockApplication =
				$this->getMock('PKPApplication', array('getContextDepth', 'getContextList'),
				array(), '', false);
		Registry::set('application', $mockApplication);

		// Set up the getContextDepth() method
		$mockApplication->expects($this->any())
		                ->method('getContextDepth')
		                ->will($this->returnValue(2));

		// Set up the getContextList() method
		$mockApplication->expects($this->any())
		                ->method('getContextList')
		                ->will($this->returnValue(array('firstContext', 'secondContext')));

		$this->dispatcher = $mockApplication->getDispatcher(); // this also adds the component router
		$this->dispatcher->addRouterName('lib.pkp.classes.core.PKPPageRouter', 'page');

		$this->request = new PKPRequest();
	}

	/**
	 * @covers Dispatcher::testUrl
	 */
	public function testUrl() {
		$baseUrl = $this->request->getBaseUrl();

		$url = $this->dispatcher->url($this->request, 'page', array('context1', 'context2'), 'somepage', 'someop');
		self::assertEquals($baseUrl.'/phpunit.php/context1/context2/somepage/someop', $url);

		$url = $this->dispatcher->url($this->request, 'component', array('context1', 'context2'), 'some.ComponentHandler', 'someOp');
		self::assertEquals($baseUrl.'/phpunit.php/context1/context2/$$$call$$$/some/component/some-op', $url);
	}
}
?>
