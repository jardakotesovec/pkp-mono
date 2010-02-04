<?php

/**
 * @file tests/config/OpenUrlCrosswalkFilterTest.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OpenUrlCrosswalkFilterTest
 * @ingroup tests_classes_metadata_nlm
 * @see NlmCitationSchemaOpenUrlCrosswalkFilter
 *
 * @brief Tests for the NlmCitationSchemaOpenUrlCrosswalkFilter class.
 */

// $Id$

import('tests.PKPTestCase');
import('metadata.MetadataDescription');
import('metadata.nlm.NlmNameSchema');
import('metadata.nlm.NlmCitationSchema');
import('metadata.openurl.OpenUrlJournalSchema');

class OpenUrlCrosswalkFilterTest extends PKPTestCase {
	/**
	 * Creates a test description in NLM format
	 * @return MetadataDescription
	 */
	protected function getTestNlmDescription() {
		$nlmNameSchema = new NlmNameSchema();
		$nlmCitationSchema = new NlmCitationSchema();

		// Create an NLM citation test description
		// 1) Authors
		$authorData1 = array(
			'given-names' => array('Given1', 'P'),
			'prefix' => 'von',
			'surname' => 'Surname1',
			'suffix' => 'suff'
		);
		$authorDescription1 = new MetadataDescription($nlmNameSchema, ASSOC_TYPE_AUTHOR);
		self::assertTrue($authorDescription1->setStatements($authorData1));

		$authorData2 = array(
			'given-names' => array('Given2'),
			'surname' => 'Surname2'
		);
		$authorDescription2 = new MetadataDescription($nlmNameSchema, ASSOC_TYPE_AUTHOR);
		self::assertTrue($authorDescription2->setStatements($authorData2));

		// 2) Editor
		$editorData = array(
			'surname' => 'The Editor'
		);
		$editorDescription = new MetadataDescription($nlmNameSchema, ASSOC_TYPE_EDITOR);
		self::assertTrue($editorDescription->setStatements($editorData));

		// 3) The citation itself
		$citationData = array(
			'person-group[@person-group-type="author"]' => array($authorDescription1, $authorDescription2),
			'person-group[@person-group-type="editor"]' => array($editorDescription),
			'[@publication-type]' => 'journal',
			'source' => array(
				'en_US' => 'Some Journal Title',
				'de_DE' => 'Irgendein Zeitschriftentitel'
			),
			'article-title' => array(
				'en_US' => 'Some Article Title',
				'de_DE' => 'Irgendein Titel'
			),
			'date' => '2005-07-03',
			'issn[@pub-type="ppub"]' => '0694760949645',
			'fpage' => 17,
			'lpage' => 33,
			'volume' => '7',
			'issue' => '5',
			'issn[@pub-type="epub"]' => '3049674960475',
			'publisher-loc' => 'Amsterdam',
			'publisher-name' => 'de Cooper',
			'pub-id[@pub-id-type="doi"]' => '10.1234.496',
			'pub-id[@pub-id-type="publisher-id"]' => '45',
			'pub-id[@pub-id-type="coden"]' => 'coden',
			'pub-id[@pub-id-type="sici"]' => 'sici',
			'pub-id[@pub-id-type="pmid"]' => '50696',
			'uri' => 'http://some-journal.org/test/article/view/30',
			'comment' => 'a comment',
			'annotation' => 'an annotation',
		);
		$nlmDescription = new MetadataDescription($nlmCitationSchema, ASSOC_TYPE_CITATION);
		self::assertTrue($nlmDescription->setStatements($citationData));

		return $nlmDescription;
	}

	/**
	 * Creates a test description in OpenUrl format
	 * @return MetadataDescription
	 */
	protected function getTestOpenUrlDescription() {
		$citationData = array(
			'aulast' => 'von Surname1',
			'aufirst' => 'Given1 P',
			'auinit1' => 'G',
			'auinitm' => 'P',
			'auinit' => 'GP',
			'ausuffix' => 'suff',
			'au' => array(
				0 => 'Surname1 suff, P. (Given1) von',
				1 => 'Surname2, (Given2)'
			),
			'genre' => 'article',
			'jtitle' => 'Some Journal Title',
			'atitle' => 'Some Article Title',
			'date' => '2005-07-03',
			'issn' => '0694760949645',
			'spage' => 17,
			'epage' => 33,
			'volume' => '7',
			'issue' => '5',
			'eissn' => '3049674960475',
			'artnum' => '45',
			'coden' => 'coden',
			'sici' => 'sici'
		);
		$openUrlSchema = new OpenUrlJournalSchema();
		$openUrlDescription = new MetadataDescription($openUrlSchema, ASSOC_TYPE_CITATION);
		self::assertTrue($openUrlDescription->setStatements($citationData));

		return $openUrlDescription;
	}
}
?>
