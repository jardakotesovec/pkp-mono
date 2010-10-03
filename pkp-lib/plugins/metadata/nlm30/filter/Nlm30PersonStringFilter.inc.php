<?php

/**
 * @file plugins/metadata/nlm30/NlmPersonStringFilter.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NlmPersonStringFilter
 * @ingroup metadata_nlm
 * @see NlmNameSchema
 *
 * @brief Filter that converts from a string
 *  to an (array of) NLM name description(s).
 */


import('lib.pkp.classes.filter.Filter');
import('lib.pkp.classes.metadata.MetadataDescription');
import('lib.pkp.plugins.metadata.nlm30.schema.NlmNameSchema');

define('PERSON_STRING_FILTER_MULTIPLE', 0x01);
define('PERSON_STRING_FILTER_SINGLE', 0x02);

define('PERSON_STRING_FILTER_ETAL', 'et-al');

class NlmPersonStringFilter extends Filter {
	/** @var integer */
	var $_filterMode;

	/**
	 * Constructor
	 * @param $inputType string
	 * @param $outputType string
	 * @param $filterMode integer one of the PERSON_STRING_FILTER_* constants
	 */
	function NlmPersonStringFilter($inputType, $outputType, $filterMode = PERSON_STRING_FILTER_SINGLE) {
		$this->_filterMode = $filterMode;
		parent::Filter($inputType, $outputType);
	}


	//
	// Setters and Getters
	//
	/**
	 * Get the filter mode
	 * @return integer
	 */
	function getFilterMode() {
		return $this->_filterMode;
	}


	//
	// Protected helper methods
	//
	/**
	 * Remove et-al entries from input/output which are valid but do not
	 * conform to the canonical transformation type definition.
	 * @param $personDescriptions mixed
	 * @return mixed false if more than one et-al string was found
	 *  otherwise the filtered person description list.
	 *
	 * NB: We cannot pass person descriptions by reference otherwise
	 * we'd alter our data.
	 */
	function &removeEtAlEntries($personDescriptions) {
		if ($this->getFilterMode() == PERSON_STRING_FILTER_MULTIPLE && is_array($personDescriptions)) {
			// Remove et-al strings
			$result = array_filter($personDescriptions, create_function('$pd', 'return is_a($pd, "MetadataDescription");'));

			// There can be exactly one et-al string
			if (count($result) < count($personDescriptions)-1) return false;
		} else {
			$result = $personDescriptions;
		}

		return $result;
	}
}
?>
