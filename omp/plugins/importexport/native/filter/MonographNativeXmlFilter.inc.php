<?php

/**
 * @file plugins/importexport/native/filter/MonographNativeXmlFilter.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MonographNativeXmlFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Class that converts a Monograph to a Native XML document.
 */

import('lib.pkp.plugins.importexport.native.filter.SubmissionNativeXmlFilter');

class MonographNativeXmlFilter extends SubmissionNativeXmlFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function MonographNativeXmlFilter($filterGroup) {
		parent::SubmissionNativeXmlFilter($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.native.filter.MonographNativeXmlFilter';
	}
}

?>
