<?php

/**
 * @file tests/config/PersonStringNlmNameSchemaFilterTest.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PersonStringNlmNameSchemaFilterTest
 * @ingroup tests_classes_metadata_nlm
 * @see PersonStringNlmNameSchemaFilter
 *
 * @brief Tests for the PersonStringNlmNameSchemaFilter class.
 */

// $Id$

import('tests.PKPTestCase');
import('metadata.nlm.PersonStringNlmNameSchemaFilter');

class PersonStringNlmNameSchemaFilterTest extends PKPTestCase {
	private $_personStringNlmNameSchemaFilter;

	public function setUp() {
		$this->_personStringNlmNameSchemaFilter = new PersonStringNlmNameSchemaFilter(ASSOC_TYPE_AUTHOR);
	}

	/**
	 * @covers PersonStringNlmNameSchemaFilter::supports
	 * @covers PersonStringNlmNameSchemaFilter::process
	 * @covers PersonStringNlmNameSchemaFilter::isValid
	 * @covers PersonStringNlmNameSchemaFilter::_parsePersonString
	 */
	public function testExecuteWithSinglePersonString() {
		$personArgumentArray = array(
			array('MULLER', false, false),                         // surname
			array('His Excellency B.C. Van de Haan', true, false), // initials prefix surname + title
			array('Mrs. P.-B. von Redfield-Brownfox', true, false),// initials prefix double-surname + title
			array('Professor K-G. Brown, MA, MSc.', true, true),   // initials surname + title + degree
			array('IFC Peterberg', false, false),                  // initials surname
			array('Peters, HC', false, false),                     // surname, initials
			array('Peters HC', false, false),                      // surname initials
			array('Yu, QK', false, false),                         // short surname, initials
			array('Yu QK', false, false),                          // short surname initials
			array('Sperling, Hans P.', false, false),              // surname, firstname initials
			array('Hans P. Sperling', false, false),               // firstname initials surname
			array('Sperling, Hans Peter B.', false, false),        // surname, firstname middlename initials
			array('Hans Peter B. Sperling', false, false),         // firstname middlename initials surname
			array('Peters, Herbert', false, false),                // surname, firstname
			array('Prof. Dr. Bernd Rutherford', true, false),      // firstname surname + title
			array('Her Honour Ruth-Martha Rheinfels', true, false),// double-firstname surname + title
			array('Sperling, Hans Peter', false, false),           // surname, firstname middlename
			array('Hans Peter Sperling', false, false),            // firstname middlename surname
			array('# # # Greenberg # # #', false, false),          // catch-all
		);
		$expectedResults = array(
			array(null, null, null, 'Muller'),
			array('His Excellency', array('B', 'C'), 'Van de', 'Haan'),
			array('Mrs.', array('P','B'), 'von', 'Redfield-Brownfox'),
			array('Professor - MA; MSc', array('K', 'G'), null, 'Brown'),
			array(null, array('I', 'F', 'C'), null, 'Peterberg'),
			array(null, array('H', 'C'), null, 'Peters'),
			array(null, array('H', 'C'), null, 'Peters'),
			array(null, array('Q', 'K'), null, 'Yu'),
			array(null, array('Q', 'K'), null, 'Yu'),
			array(null, array('Hans', 'P'), null, 'Sperling'),
			array(null, array('Hans', 'P'), null, 'Sperling'),
			array(null, array('Hans', 'Peter', 'B'), null, 'Sperling'),
			array(null, array('Hans', 'Peter', 'B'), null, 'Sperling'),
			array(null, array('Herbert'), null, 'Peters'),
			array('Prof. Dr.', array('Bernd'), null, 'Rutherford'),
			array('Her Honour', array('Ruth-Martha'), null, 'Rheinfels'),
			array(null, array('Hans', 'Peter'), null, 'Sperling'),
			array(null, array('Hans', 'Peter'), null, 'Sperling'),
			array(null, null, null, '# # # Greenberg # # #'),
		);

		foreach($personArgumentArray as $testNumber => $personArguments) {
			$this->_personStringNlmNameSchemaFilter->setFilterTitle($personArguments[1]);
			$this->_personStringNlmNameSchemaFilter->setFilterDegrees($personArguments[2]);
			$personDescription =& $this->_personStringNlmNameSchemaFilter->execute($personArguments[0]);
			$this->assertPerson($expectedResults[$testNumber], $personDescription, $testNumber);
		}
	}

	/**
	 * @covers PersonStringNlmNameSchemaFilter::supports
	 * @covers PersonStringNlmNameSchemaFilter::process
	 * @covers PersonStringNlmNameSchemaFilter::isValid
	 * @covers PersonStringNlmNameSchemaFilter::_parsePersonsString
	 * @depends testExecuteWithSinglePersonString
	 */
	public function testExecuteWithMultiplePersonsStrings() {
		$personsString = 'MULLER:IFC Peterberg:Peters HC:Yu QK:Hans Peter B. Sperling:et al';
		$expectedResults = array(
			array(null, null, null, 'Muller'),
			array(null, array('I', 'F', 'C'), null, 'Peterberg'),
			array(null, array('H', 'C'), null, 'Peters'),
			array(null, array('Q', 'K'), null, 'Yu'),
			array(null, array('Hans', 'Peter', 'B'), null, 'Sperling'),
		);

		$this->_personStringNlmNameSchemaFilter->setFilterMode(PERSON_STRING_FILTER_MULTIPLE);
		$personDescriptions =& $this->_personStringNlmNameSchemaFilter->execute($personsString);
		foreach($personDescriptions as $testNumber => $personDescription) {
			$this->assertPerson($expectedResults[$testNumber], $personDescription, $testNumber);
		}

		// Test again, this time with title and degrees
		$personsString = 'Dr. MULLER; IFC Peterberg; Prof. Peters HC, MSc.; Yu QK;Hans Peter B. Sperling; etal';
		$expectedResults = array(
			array('Dr.', null, null, 'Muller'),
			array(null, array('I', 'F', 'C'), null, 'Peterberg'),
			array('Prof. - MSc', array('H', 'C'), null, 'Peters'),
			array(null, array('Q', 'K'), null, 'Yu'),
			array(null, array('Hans', 'Peter', 'B'), null, 'Sperling'),
		);

		$this->_personStringNlmNameSchemaFilter->setFilterTitle(true);
		$this->_personStringNlmNameSchemaFilter->setFilterDegrees(true);
		$personDescriptions =& $this->_personStringNlmNameSchemaFilter->execute($personsString);
		foreach($personDescriptions as $testNumber => $personDescription) {
			$this->assertPerson($expectedResults[$testNumber], $personDescription, $testNumber);
		}
	}

	/**
	 * Test a given person description against an array of expected results
	 * @param $expectedResultArray array
	 * @param $personDescription MetadataDescription
	 * @param $testNumber integer The test number for debugging purposes
	 */
	private function assertPerson($expectedResultArray, $personDescription, $testNumber) {
		self::assertEquals($expectedResultArray[0], $personDescription->getStatement('suffix'), "Wrong suffix for test $testNumber.");
		self::assertEquals($expectedResultArray[1], $personDescription->getStatement('given-names'), "Wrong given-names for test $testNumber.");
		self::assertEquals($expectedResultArray[2], $personDescription->getStatement('prefix'), "Wrong prefix for test $testNumber.");
		self::assertEquals($expectedResultArray[3], $personDescription->getStatement('surname'), "Wrong surname for test $testNumber.");
	}
}
?>
