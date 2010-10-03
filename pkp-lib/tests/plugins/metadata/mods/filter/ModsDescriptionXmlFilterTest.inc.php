<?php

/**
 * @file tests/plugins/metadata/mods/filter/Mods34DescriptionXmlFilterTest.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Mods34DescriptionXmlFilterTest
 * @ingroup tests_plugins_metadata_mods_filter
 * @see Mods34DescriptionXmlFilter
 *
 * @brief Test class for Mods34DescriptionXmlFilter.
 */

import('lib.pkp.tests.plugins.metadata.mods34.filter.Mods34DescriptionTestCase');
import('lib.pkp.plugins.metadata.mods34.filter.Mods34DescriptionXmlFilter');

class Mods34DescriptionXmlFilterTest extends Mods34DescriptionTestCase {
	/**
	 * @covers Mods34DescriptionXmlFilter
	 */
	public function testMods34DescriptionXmlFilter() {
		// Get the test description.
		$submissionDescription = $this->getMods34Description();

		// Instantiate filter.
		$filter = new Mods34DescriptionXmlFilter();

		// Transform MODS description to XML.
		$output = $filter->execute($submissionDescription);
		self::assertXmlStringEqualsXmlFile('./lib/pkp/tests/plugins/metadata/mods/filter/test.xml', $output);
	}
}
?>