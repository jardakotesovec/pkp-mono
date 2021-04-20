<?php
/**
 * @file classes/filter/PrimitiveTypeDescription.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PrimitiveTypeDescription
 * @ingroup filter
 *
 * @brief Class that describes a primitive input/output type.
 */

import('lib.pkp.classes.filter.TypeDescription');
import('lib.pkp.classes.filter.TypeDescriptionFactory');

class PrimitiveTypeDescription extends TypeDescription
{
    /** @var string a PHP primitive type, e.g. 'string' */
    public $_primitiveType;

    /**
     * Constructor
     *
     * @param $typeName string Allowed primitive types are
     *  'integer', 'string', 'float' and 'boolean'.
     */
    public function __construct($typeName)
    {
        parent::__construct($typeName);
    }


    //
    // Setters and Getters
    //
    /**
     * @see TypeDescription::getNamespace()
     */
    public function getNamespace()
    {
        return TYPE_DESCRIPTION_NAMESPACE_PRIMITIVE;
    }


    //
    // Implement abstract template methods from TypeDescription
    //
    /**
     * @see TypeDescription::parseTypeName()
     */
    public function parseTypeName($typeName)
    {
        // This should be a primitive type
        if (!in_array($typeName, $this->_supportedPrimitiveTypes())) {
            return false;
        }

        $this->_primitiveType = $typeName;
        return true;
    }

    /**
     * @see TypeDescription::checkType()
     */
    public function checkType(&$object)
    {
        // We expect a primitive type
        if (!is_scalar($object)) {
            return false;
        }

        // Check the type
        if ($this->_getPrimitiveTypeName($object) != $this->_primitiveType) {
            return false;
        }

        return true;
    }


    //
    // Private helper methods
    //
    /**
     * Return a string representation of a primitive type.
     *
     * @param $variable mixed
     */
    public function _getPrimitiveTypeName(&$variable)
    {
        assert(!(is_object($variable) || is_array($variable) || is_null($variable)));

        // FIXME: When gettype's implementation changes as mentioned
        // in <http://www.php.net/manual/en/function.gettype.php> then
        // we have to manually re-implement this method.
        return str_replace('double', 'float', gettype($variable));
    }

    /**
     * Returns a (static) array with supported
     * primitive type names.
     *
     */
    public static function _supportedPrimitiveTypes()
    {
        static $supportedPrimitiveTypes = [
            'string', 'integer', 'float', 'boolean'
        ];
        return $supportedPrimitiveTypes;
    }
}
