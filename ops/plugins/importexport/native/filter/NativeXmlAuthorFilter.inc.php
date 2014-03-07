<?php

/**
 * @file plugins/importexport/native/filter/NativeXmlAuthorFilter.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeXmlAuthorFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Class that converts a Native XML document to a set of articles.
 */

import('lib.pkp.plugins.importexport.native.filter.NativeXmlPKPAuthorFilter');

class NativeXmlAuthorFilter extends NativeXmlPKPAuthorFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function NativeXmlAuthorFilter($filterGroup) {
		parent::NativeXmlPKPAuthorFilter($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.native.filter.NativeXmlAuthorFilter';
	}
}

?>
