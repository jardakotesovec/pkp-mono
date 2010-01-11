<?php

/**
 * @file classes/metadata/NlmNameSchema.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NlmNameSchema
 * @ingroup metadata
 * @see MetadataSchema
 *
 * @brief Class that provides meta-data properties compliant with
 *  the NLM name tag from the NLM Journal Publishing Tag Set
 *  Version 3.0. Records of this type will be used as composite property
 *  within the person group properties.
 */

// $Id$

import('metadata.MetadataSchema');

class NlmNameSchema extends MetadataSchema {
	/**
	 * Constructor
	 */
	function NlmNameSchema() {
		$this->setName('nlm-3.0-name');

		$citation = array(ASSOC_TYPE_CITATION);
		$this->addProperty(new MetadataProperty('surname', $citation));
		$this->addProperty(new MetadataProperty('given-names', $citation, METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY));
		$this->addProperty(new MetadataProperty('prefix', $citation));
		$this->addProperty(new MetadataProperty('suffix', $citation));
	}
}
?>