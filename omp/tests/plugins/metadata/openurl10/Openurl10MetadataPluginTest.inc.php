<?php

/**
 * @defgroup tests_plugins_metadata_openurl10
 */

/**
 * @file tests/plugins/metadata/openurl10/Openurl10MetadataPluginTest.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Openurl10MetadataPluginTest
 * @ingroup tests_plugins_metadata_openurl10
 * @see Openurl10MetadataPlugin
 *
 * @brief Test class for Openurl10MetadataPlugin.
 */


import('lib.pkp.tests.plugins.metadata.MetadataPluginTestCase');

class Openurl10MetadataPluginTest extends MetadataPluginTestCase {
	/**
	 * @covers Openurl10MetadataPlugin
	 * @covers PKPOpenurl10MetadataPlugin
	 */
	public function testOpenurl10MetadataPlugin() {
		$this->executeMetadataPluginTest(
			'openurl10',
			'Openurl10MetadataPlugin',
			array(),
			array('openurl10-journal-genres', 'openurl10-book-genres')
		);
	}
}
?>