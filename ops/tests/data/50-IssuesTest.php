<?php

/**
 * @file tests/data/50-IssuesTest.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssuesTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create issues
 */

import('lib.pkp.tests.WebTestCase');

class IssuesTest extends WebTestCase {
	/**
	 * Configure section editors
	 */
	function testCreateIssues() {
		$this->open(self::$baseUrl);

		// Management > Issues
		$this->waitForElementPresent($selector='link=Dashboard');
		$this->clickAndWait($selector);
		$this->waitForElementPresent($selector='link=Issues');
		$this->click($selector);

		// Create issue
		$this->waitForElementPresent('css=[id^=component-grid-issues-futureissuegrid-addIssue-button-]');
		$this->click('css=[id^=component-grid-issues-futureissuegrid-addIssue-button-]');
		$this->waitForElementPresent('css=[id^=volume-]');
		$this->type('css=[id^=volume-]', '1');
		$this->type('css=[id^=number-]', '1');
		$this->type('css=[id^=year-]', '2014');
		$this->click('id=showTitle');
		$this->click('//span[text()=\'Save\']/..');
		$this->waitForElementNotPresent('css=.ui-widget-overlay');

		// Create issue
		$this->click('css=[id^=component-grid-issues-futureissuegrid-addIssue-button-]');
		$this->waitForElementPresent('css=[id^=volume-]');
		$this->type('css=[id^=volume-]', '1');
		$this->type('css=[id^=number-]', '2');
		$this->type('css=[id^=year-]', '2014');
		$this->click('id=showTitle');
		$this->click('//span[text()=\'Save\']/..');
		$this->waitForElementNotPresent('css=.ui-widget-overlay');
	}
}
