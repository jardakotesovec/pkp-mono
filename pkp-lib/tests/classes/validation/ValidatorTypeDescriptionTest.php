<?php

/**
 * @file tests/classes/validation/ValidatorTypeDescriptionTest.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ValidatorTypeDescriptionTest
 * @ingroup tests_classes_filter
 *
 * @see ValidatorTypeDescription
 *
 * @brief Test class for ValidatorTypeDescription and TypeDescription.
 */

namespace PKP\tests\classes\validation;

use PKP\tests\PKPTestCase;
use PKP\validation\ValidatorTypeDescription;

class ValidatorTypeDescriptionTest extends PKPTestCase
{
    /**
     * @covers ValidatorTypeDescription
     * @covers TypeDescription
     */
    public function testInstantiateAndCheck()
    {
        $typeDescription = new ValidatorTypeDescription('email');
        self::assertTrue($typeDescription->isCompatible($object = 'jerico.dev@gmail.com'));
        self::assertFalse($typeDescription->isCompatible($object = 'another string'));
    }

    /**
     * Provides test data
     */
    public function typeDescriptorDataProvider(): array
    {
        return [
            'Invalid name' => ['email(xyz]'],
            'Invalid casing' => ['Email'],
            'Invalid character' => ['email&'],
        ];
    }

    /**
     * @covers ValidatorTypeDescription
     * @covers TypeDescription
     * @dataProvider typeDescriptorDataProvider
     */
    public function testInstantiateWithInvalidTypeDescriptor(string $type)
    {
        $this->expectError();
        $this->expectOutputRegex('/' . preg_quote(htmlspecialchars("Trying to instantiate a \"validator\" type description with an invalid type name \"$type\"")) . '/');
        $typeDescription = new ValidatorTypeDescription($type);
    }
}
