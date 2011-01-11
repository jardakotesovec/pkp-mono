<?php

/**
 * @defgroup tests_plugins_citationLookup_worldcat_filter
 */

/**
 * @file tests/plugins/citationLookup/worldcat/filter/WorldcatNlm30CitationSchemaFilterTest.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WorldcatNlm30CitationSchemaFilterTest
 * @ingroup tests_plugins_citationLookup_worldcat_filter
 * @see WorldcatNlm30CitationSchemaFilter
 *
 * @brief Tests for the WorldcatNlm30CitationSchemaFilter class.
 *
 * NB: This test requires a WordCat API key to function properly!
 */


import('lib.pkp.plugins.citationLookup.worldcat.filter.WorldcatNlm30CitationSchemaFilter');
import('lib.pkp.tests.plugins.metadata.nlm30.filter.Nlm30CitationSchemaFilterTestCase');

class WorldcatNlm30CitationSchemaFilterTest extends Nlm30CitationSchemaFilterTestCase {
	const
		// Due to legal limitations, an API key cannot be published.
		// Please insert your own API key for testing.
		WORLDCAT_TEST_APIKEY = '...';

	/**
	 * Test CrossRef lookup with DOI
	 * @covers WorldcatNlm30CitationSchemaFilter
	 */
	public function testExecuteWithDoi() {
		// Test book lookup
		$testWithApiKey = array(
			'testInput' => array(
				'person-group[@person-group-type="author"]' => array (
					array ('given-names' => array('Paula'), 'surname' => 'Fernandes Lopes'),
				),
				'source' => 'A ética platônica: modelo de ética da boa vida'
			),
			'testOutput' => array (
				'person-group[@person-group-type="author"]' => array (
					array ('given-names' => array('Paula', 'Fernandes'), 'surname' => 'Lopes')
				),
				'source' => 'A ética platônica : modelo de ética da boa vida',
				'isbn' => '851503154X',
				'publisher-loc' => 'São Paulo',
				'publisher-name' => 'Ed. Loyola',
				'date' => '2005'
			)
		);

		// Build the test citations array
		$citationFilterTests = array($testWithApiKey);

		// Execute the tests with API key
		$filter = new WorldcatNlm30CitationSchemaFilter(PersistableFilter::tempGroup(
				'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)',
				'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)'));
		$filter->setData('apiKey', self::WORLDCAT_TEST_APIKEY);
		$this->assertNlm30CitationSchemaFilter($citationFilterTests, $filter);

		// Try again without API key
		$testWithoutApiKey = array(
			'testInput' => array(
				'person-group[@person-group-type="author"]' => array (
					array ('given-names' => array('Paula'), 'surname' => 'Fernandes Lopes'),
				),
				'source' => 'A ética platônica: modelo de ética da boa vida'
			),
			'testOutput' => array (
				'person-group[@person-group-type="author"]' => array (
					array ('given-names' => array('Paula', 'Fernandes'), 'surname' => 'Lopes')
				),
				'source' => 'A ética platônica : modelo de ética da boa vida',
				'isbn' => '9788515031542',
				'date' => '2005',
				'publisher-name' => 'Ed. Loyola',
				'publisher-loc' => 'São Paulo'
			)
		);
		$citationFilterTests = array($testWithoutApiKey);
		$filter = new WorldcatNlm30CitationSchemaFilter(PersistableFilter::tempGroup(
				'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)',
				'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)'));
		$this->assertNlm30CitationSchemaFilter($citationFilterTests, $filter);
	}
}
?>
