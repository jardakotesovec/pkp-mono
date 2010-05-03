<?php

/**
 * @file tests/classes/citation/lookup/pubmed/PubmedNlmCitationSchemaFilterTest.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubmedNlmCitationSchemaFilterTest
 * @ingroup tests_classes_citation_lookup_pubmed
 * @see PubmedNlmCitationSchemaFilter
 *
 * @brief Tests for the PubmedNlmCitationSchemaFilter class.
 */

// $Id$

import('lib.pkp.classes.citation.lookup.pubmed.PubmedNlmCitationSchemaFilter');
import('lib.pkp.tests.classes.citation.NlmCitationSchemaFilterTestCase');

class PubmedNlmCitationSchemaFilterTest extends NlmCitationSchemaFilterTestCase {
	/**
	 * Test Pubmed lookup with PmID
	 * @covers PubmedNlmCitationSchemaFilter
	 */
	public function testExecuteWithPmid() {
		// Test article Pubmed lookup
		$citationFilterTests = array(
			array(
				'testInput' => array(
					'pub-id[@pub-id-type="pmid"]' => '12140307'
				),
				'testOutput' => $this->getTestOutput()
			)
		);

		// Execute the tests
		$filter = new PubmedNlmCitationSchemaFilter();
		$this->assertNlmCitationSchemaFilter($citationFilterTests, $filter);
	}

	/**
	 * Test Pubmed lookup without PmID
	 * @covers PubmedNlmCitationSchemaFilter
	 */
	public function testExecuteWithSearch() {
		// Build the test citations array
		$citationFilterTests = array(
			// strict search
			array(
				'testInput' => array(
					'person-group[@person-group-type="author"]' => array (
						array ('given-names' => array('Scott', 'D'), 'surname' => 'Halpern'),
						array ('given-names' => array('Peter', 'A'), 'surname' => 'Ubel'),
						array ('given-names' => array('Arthur', 'L'), 'surname' => 'Caplan')
					),
					'article-title' => 'Solid-organ transplantation in HIV-infected patients.',
					'source' => 'N Engl J Med',
					'volume' => '347',
					'issue' => '4'
				),
				'testOutput' => $this->getTestOutput()
			),
			// author search
			array(
				'testInput' => array(
					'person-group[@person-group-type="author"]' => array (
						array ('given-names' => array('Scott', 'D'), 'surname' => 'Halpern'),
						array ('given-names' => array('Peter', 'A'), 'surname' => 'Ubel'),
						array ('given-names' => array('Arthur', 'L'), 'surname' => 'Caplan')
					)
				),
				'testOutput' => $this->getTestOutput()
			)
		);

		// Execute the test
		$filter = new PubmedNlmCitationSchemaFilter();
		$this->assertNlmCitationSchemaFilter($citationFilterTests, $filter);
	}

	private function &getTestOutput() {
		$testOutput = array(
			'pub-id[@pub-id-type="pmid"]' => '12140307',
			'article-title' => 'Solid-organ transplantation in HIV-infected patients.',
			'source' => 'N Engl J Med',
			'volume' => '347',
			'issue' => '4',
			'person-group[@person-group-type="author"]' => array (
				array ('given-names' => array('Scott', 'D'), 'surname' => 'Halpern'),
				array ('given-names' => array('Peter', 'A'), 'surname' => 'Ubel'),
				array ('given-names' => array('Arthur', 'L'), 'surname' => 'Caplan')
			),
			'fpage' => 284,
			'lpage' => 287,
			'[@publication-type]' => 'journal',
			'pub-id[@pub-id-type="doi"]' => '10.1056/NEJMsb020632'
		);
		return $testOutput;
	}
}
?>
