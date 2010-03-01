<?php

/**
 * @file tests/classes/metadata/MetadataPropertyTest.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MetadataPropertyTest
 * @ingroup tests_classes_metadata
 * @see MetadataProperty
 *
 * @brief Test class for MetadataProperty.
 */

import('tests.PKPTestCase');
import('metadata.MetadataProperty');

class MetadataPropertyTest extends PKPTestCase {
	/**
	 * @covers MetadataProperty::MetadataProperty
	 * @covers MetadataProperty::getName
	 * @covers MetadataProperty::getAssocTypes
	 * @covers MetadataProperty::getTypes
	 * @covers MetadataProperty::getTranslated
	 * @covers MetadataProperty::getCardinality
	 * @covers MetadataProperty::getDisplayName
	 * @covers MetadataProperty::getId
	 * @covers MetadataProperty::getSupportedCardinalities
	 */
	public function testMetadataPropertyConstructor() {
		// test instantiation with non-default values
		$metadataProperty = new MetadataProperty('testElement', array(0x001), array(METADATA_PROPERTY_TYPE_COMPOSITE => 0x002), false, METADATA_PROPERTY_CARDINALITY_MANY, 'non.default.displayName');
		self::assertEquals('testElement', $metadataProperty->getName());
		self::assertEquals(array(0x001), $metadataProperty->getAssocTypes());
		self::assertEquals(array(array(METADATA_PROPERTY_TYPE_COMPOSITE => 0x002)), $metadataProperty->getTypes());
		self::assertFalse($metadataProperty->getTranslated());
		self::assertEquals(METADATA_PROPERTY_CARDINALITY_MANY, $metadataProperty->getCardinality());
		self::assertEquals('non.default.displayName', $metadataProperty->getDisplayName());
		self::assertEquals('TestElement', $metadataProperty->getId());

		// Test translation
		$metadataProperty = new MetadataProperty('testElement', array(0x001), METADATA_PROPERTY_TYPE_STRING, true);
		self::assertTrue($metadataProperty->getTranslated());

		// test normal instantiation with defaults
		$metadataProperty = new MetadataProperty('testElement');
		self::assertEquals('testElement', $metadataProperty->getName());
		self::assertEquals(array(), $metadataProperty->getAssocTypes());
		self::assertEquals(array(METADATA_PROPERTY_TYPE_STRING), $metadataProperty->getTypes());
		self::assertFalse($metadataProperty->getTranslated());
		self::assertEquals(METADATA_PROPERTY_CARDINALITY_ONE, $metadataProperty->getCardinality());
		self::assertEquals('metadata.property.displayName.testElement', $metadataProperty->getDisplayName());
		self::assertEquals('TestElement', $metadataProperty->getId());
	}

	/**
	 * Tests special error conditions while setting composite types
	 * @covers MetadataProperty::MetadataProperty
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function testCompositeWithoutCompositeType() {
		$metadataProperty = new MetadataProperty('testElement', array(0x001), METADATA_PROPERTY_TYPE_COMPOSITE, false, METADATA_PROPERTY_CARDINALITY_MANY);
	}

	/**
	 * Tests special error conditions while setting composite types
	 * @covers MetadataProperty::MetadataProperty
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function testCompositeWithWrongCompositeType() {
		$metadataProperty = new MetadataProperty('testElement', array(0x001), array(METADATA_PROPERTY_TYPE_COMPOSITE => 'string'), false, METADATA_PROPERTY_CARDINALITY_MANY);
	}

	/**
	 * Tests special error conditions while setting composite types
	 * @covers MetadataProperty::MetadataProperty
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function testCompositeTypeWithoutComposite() {
		$metadataProperty = new MetadataProperty('testElement', array(0x001), array(METADATA_PROPERTY_TYPE_STRING => 0x002), false, METADATA_PROPERTY_CARDINALITY_MANY);
	}

	/**
	 * Tests special error conditions while setting an unsupported type
	 * @covers MetadataProperty::getSupportedTypes
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function testSetUnsupportedType() {
		$metadataProperty = new MetadataProperty('testElement', array(0x001), 0x99999999, true, METADATA_PROPERTY_CARDINALITY_MANY);
	}

	/**
	 * Tests special error conditions while setting an unsupported cardinality
	 * @covers MetadataProperty::getSupportedCardinalities
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function testSetUnsupportedCardinality() {
		$metadataProperty = new MetadataProperty('testElement', array(0x001), METADATA_PROPERTY_TYPE_COMPOSITE, true, 0x99999999);
	}

	/**
	 * @covers MetadataProperty::isValid
	 */
	public function testValidateString() {
		$metadataProperty = new MetadataProperty('testElement');
		self::assertTrue($metadataProperty->isValid('any string'));
		self::assertFalse($metadataProperty->isValid(null));
		self::assertFalse($metadataProperty->isValid(5));
		self::assertFalse($metadataProperty->isValid(array('string1', 'string2')));
	}

	/**
	 * @covers MetadataProperty::isValid
	 */
	public function testValidateUri() {
		$metadataProperty = new MetadataProperty('testElement', array(), METADATA_PROPERTY_TYPE_URI);
		self::assertFalse($metadataProperty->isValid('any string'));
		self::assertTrue($metadataProperty->isValid('ftp://some.domain.org/path'));
		self::assertFalse($metadataProperty->isValid(null));
		self::assertFalse($metadataProperty->isValid(5));
		self::assertFalse($metadataProperty->isValid(array('ftp://some.domain.org/path', 'http://some.domain.org/')));
	}

	/**
	 * @covers MetadataProperty::isValid
	 */
	public function testValidateControlledVocabulary() {
		// TODO: Write tests when use case comes up.
	}

	/**
	 * @covers MetadataProperty::isValid
	 */
	public function testValidateDate() {
		$metadataProperty = new MetadataProperty('testElement', array(), METADATA_PROPERTY_TYPE_DATE);
		self::assertTrue($metadataProperty->isValid('2009-10-25'));
		self::assertTrue($metadataProperty->isValid('2020-11'));
		self::assertTrue($metadataProperty->isValid('1847'));
		self::assertFalse($metadataProperty->isValid('XXXX'));
		self::assertFalse($metadataProperty->isValid('2009-10-35'));
		self::assertFalse($metadataProperty->isValid('2009-13-01'));
		self::assertFalse($metadataProperty->isValid('2009-12-1'));
		self::assertFalse($metadataProperty->isValid('2009-13'));
		self::assertFalse($metadataProperty->isValid(5));
		self::assertFalse($metadataProperty->isValid(array('2009-10-25', '2009-10-26')));
	}

	/**
	 * @covers MetadataProperty::isValid
	 */
	public function testValidateInteger() {
		$metadataProperty = new MetadataProperty('testElement', array(), METADATA_PROPERTY_TYPE_INTEGER);
		self::assertTrue($metadataProperty->isValid(5));
		self::assertFalse($metadataProperty->isValid(null));
		self::assertFalse($metadataProperty->isValid('a string'));
		self::assertFalse($metadataProperty->isValid(array(4, 8)));
	}

	/**
	 * @covers MetadataProperty::isValid
	 */
	public function testValidateComposite() {
		$metadataProperty = new MetadataProperty('testElement', array(), array(METADATA_PROPERTY_TYPE_COMPOSITE => 0x002), false, METADATA_PROPERTY_CARDINALITY_ONE);

		import('metadata.MetadataSchema');
		$metadataSchema = new MetadataSchema();
		import('metadata.MetadataDescription');
		$metadataDescription = new MetadataDescription($metadataSchema, 0x002);
		$anotherMetadataDescription = clone($metadataDescription);
		$stdObject = new stdClass();

		self::assertTrue($metadataProperty->isValid($metadataDescription));
		self::assertTrue($metadataProperty->isValid('2:5')); // assocType:assocId
		self::assertFalse($metadataProperty->isValid('1:5'));
		self::assertFalse($metadataProperty->isValid('2:xxx'));
		self::assertFalse($metadataProperty->isValid('2'));
		self::assertFalse($metadataProperty->isValid(null));
		self::assertFalse($metadataProperty->isValid(5));
		self::assertFalse($metadataProperty->isValid($stdObject));
		self::assertFalse($metadataProperty->isValid(array($metadataDescription, $anotherMetadataDescription)));
	}

	/**
	 * @covers MetadataProperty::isValid
	 */
	public function testValidateMultitype() {
		$metadataProperty = new MetadataProperty('testElement', array(), array(METADATA_PROPERTY_TYPE_DATE, METADATA_PROPERTY_TYPE_INTEGER), false, METADATA_PROPERTY_CARDINALITY_ONE);
		self::assertTrue($metadataProperty->isValid('2009-07-28'));
		self::assertTrue($metadataProperty->isValid(5));
		self::assertFalse($metadataProperty->isValid(null));
		self::assertFalse($metadataProperty->isValid('string'));
	}
}
?>