<?php

/**
 * @file tests/data/50-SectionsTest.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SectionsTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create/configure sections
 */

import('lib.pkp.tests.WebTestCase');

class SectionsTest extends WebTestCase {
	/**
	 * Configure section editors
	 */
	function testConfigureSections() {
		$this->open(self::$baseUrl);

		// Management > Settings > Journal
		$this->waitForElementPresent('link=Journal');
		$this->click('link=Journal');
		$this->waitForElementPresent('link=Sections');
		$this->click('link=Sections');

		// Edit Section (default "Articles")
		$this->waitForElementPresent('css=[id^=component-grid-settings-sections-sectiongrid-row-1-editSection-button-]');
		$this->click('css=[id^=component-grid-settings-sections-sectiongrid-row-1-editSection-button-]');

		// Add Section Editor (David Buskins)
		$this->waitForElementPresent('css=[id^=component-listbuilder-settings-subeditorslistbuilder-addItem-button-]');
		$this->clickAt('css=[id^=component-listbuilder-settings-subeditorslistbuilder-addItem-button-]', '10,10');

		$this->waitForElementPresent('//select[@name=\'newRowId[name]\']//option[text()=\'David Buskins\']');
		$this->select('name=newRowId[name]', 'label=David Buskins');

		// Persist this one and add another (Stephanie Berardo)
		$this->clickAt("css=[id^=component-listbuilder-settings-subeditorslistbuilder-addItem-button-]", "10,10");
		$this->waitForElementPresent('css=span:contains(\'David Buskins\')');
		$this->waitForElementPresent('xpath=(//select[@name="newRowId[name]"])[2]//option[text()=\'Stephanie Berardo\']');
		$this->select('xpath=(//select[@name="newRowId[name]"])[2]', 'label=Stephanie Berardo');

		// Save changes
		$this->click('//form[@id=\'sectionForm\']//span[text()=\'Save\']/..');
		$this->waitJQuery();

		// Verify resulting grid row
		$this->assertEquals('Berardo, Buskins', $this->getText('css=#cell-1-editors > span'));

		// Create a new "Reviews" section
		$this->click('css=[id^=component-grid-settings-sections-sectiongrid-addSection-button-]');
		$this->waitForElementPresent('css=[id^=title-]');
		$this->type('css=[id^=title-]', 'Reviews');
		$this->type('css=[id^=abbrev-]', 'REV');
		$this->type('css=[id^=identifyType-]', 'Review Article');
		$this->click('id=abstractsNotRequired');

		// Add a Section Editor (Minoti Inoue)
		$this->waitForElementPresent('css=[id^=component-listbuilder-settings-subeditorslistbuilder-addItem-button-]');
		$this->clickAt('css=[id^=component-listbuilder-settings-subeditorslistbuilder-addItem-button-]', '10,10');
		$this->waitForElementPresent('//select[@name=\'newRowId[name]\']//option[text()=\'Minoti Inoue\']');
		$this->select('name=newRowId[name]', 'label=Minoti Inoue');
		$this->click('//form[@id=\'sectionForm\']//span[text()=\'Save\']/..');
		$this->waitJQuery();
	}
}
