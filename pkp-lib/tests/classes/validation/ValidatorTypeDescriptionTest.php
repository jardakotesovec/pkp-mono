<?php

/**
 * @file tests/classes/validation/ValidatorTypeDescriptionTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ValidatorTypeDescriptionTest
 * @ingroup tests_classes_filter
 * @see ValidatorTypeDescription
 *
 * @brief Test class for ValidatorTypeDescription and TypeDescription.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.validation.ValidatorTypeDescription');

class ValidatorTypeDescriptionTest extends PKPTestCase {
	/**
	 * @covers ValidatorTypeDescription
	 * @covers TypeDescription
	 */
	public function testInstantiateAndCheck() {
		$typeDescription = new ValidatorTypeDescription('email');
		self::assertTrue($typeDescription->isCompatible($object = 'jerico.dev@gmail.com'));
		self::assertFalse($typeDescription->isCompatible($object = 'another string'));
	}

	/**
	 * @covers ValidatorTypeDescription
	 * @covers TypeDescription
	 * @expectedException PHPUnit\Framework\Exception
	 */
	function testInstantiateWithInvalidTypeDescriptor1() {
		// An unknown type name will cause an error.
		$typeDescription = new ValidatorTypeDescription('email(xyz]');
	}

	/**
	 * @covers ValidatorTypeDescription
	 * @covers TypeDescription
	 * @expectedException PHPUnit\Framework\Exception
	 */
	function testInstantiateWithInvalidTypeDescriptor2() {
		// We don't allow multi-dimensional arrays.
		$typeDescription = new ValidatorTypeDescription('Email');
	}

	/**
	 * @covers ValidatorTypeDescription
	 * @covers TypeDescription
	 * @expectedException PHPUnit\Framework\Exception
	 */
	function testInstantiateWithInvalidTypeDescriptor3() {
		// An invalid cardinality will also cause an error.
		$typeDescription = new ValidatorTypeDescription('email&');
	}
}

