<?php

/**
 * @defgroup subject BIC Subjects
 */

/**
 * @file classes/codelist/Subject.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Subject
 * @ingroup codelist
 *
 * @see SubjectDAO
 *
 * @brief Basic class describing a BIC Subject.
 *
 */

import('classes.codelist.CodelistItem');

class Subject extends CodelistItem
{
    /**
     * @var int The numerical representation of these Subject Qualifiers in ONIX 3.0
     */
    public $_onixSubjectSchemeIdentifier = 12;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get the ONIX subject scheme identifier.
     *
     * @return String the numerical value representing this item in the ONIX 3.0 schema
     */
    public function getOnixSubjectSchemeIdentifier()
    {
        return $this->_onixSubjectSchemeIdentifier;
    }
}
