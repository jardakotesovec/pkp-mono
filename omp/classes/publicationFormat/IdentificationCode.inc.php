<?php

/**
 * @file classes/publicationFormat/IdentificationCode.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IdentificationCode
 * @ingroup publicationFormat
 *
 * @see IdentificationCodeDAO
 *
 * @brief Basic class describing an identification code (used on the ONIX templates for publication formats)
 */

class IdentificationCode extends DataObject
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * get publication format id
     *
     * @return int
     */
    public function getPublicationFormatId()
    {
        return $this->getData('publicationFormatId');
    }

    /**
     * set publication format id
     */
    public function setPublicationFormatId($publicationFormatId)
    {
        return $this->setData('publicationFormatId', $publicationFormatId);
    }

    /**
     * Set the ONIX code for this identification code
     *
     * @param $code string
     */
    public function setCode($code)
    {
        $this->setData('code', $code);
    }

    /**
     * Get the ONIX code for the identification code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->getData('code');
    }

    /**
     * Get the human readable name for this ONIX code
     *
     * @return string
     */
    public function getNameForONIXCode()
    {
        $onixCodelistItemDao = DAORegistry::getDAO('ONIXCodelistItemDAO'); /* @var $onixCodelistItemDao ONIXCodelistItemDAO */
        $codes = & $onixCodelistItemDao->getCodes('List5'); // List5 is for ISBN, GTIN-13, etc.
        return $codes[$this->getCode()];
    }

    /**
     * Set the value for this identification code
     *
     * @param $value string
     */
    public function setValue($value)
    {
        $this->setData('value', $value);
    }

    /**
     * Get the value for the identification code
     *
     * @return string
     */
    public function getValue()
    {
        return $this->getData('value');
    }
}
