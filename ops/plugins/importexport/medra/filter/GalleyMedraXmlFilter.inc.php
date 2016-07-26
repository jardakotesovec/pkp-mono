<?php

/**
 * @file plugins/importexport/medra/filter/GalleyMedraXmlFilter.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GalleyMedraXmlFilter
 * @ingroup plugins_importexport_medra
 *
 * @brief Class that converts an ArticleGalley i.e article as manifestation to a O4DOI XML document.
 */

import('plugins.importexport.medra.filter.ArticleMedraXmlFilter');


class GalleyMedraXmlFilter extends ArticleMedraXmlFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function GalleyMedraXmlFilter($filterGroup) {
		$this->setDisplayName('mEDRA XML galley export');
		parent::ArticleMedraXmlFilter($filterGroup);
	}

	/**
	 * @copydoc O4DOIXmlFilter::isWork()
	 */
	function isWork($context, $plugin) {
		return false;
	}

	/**
	 *  @copydoc O4DOIXmlFilter::getRootNodeName
	 */
	function getRootNodeName() {
		return 'ONIXDOISerialArticleVersionRegistrationMessage';
	}

	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.medra.filter.GalleyMedraXmlFilter';
	}

}

?>
