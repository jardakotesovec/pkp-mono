<?php

/**
 * @file classes/metadata/OpenUrlBaseSchema.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OpenUrlBaseSchema
 * @ingroup metadata
 * @see MetadataSchema
 *
 * @brief Class that provides meta-data properties common to all
 *  variants of the OpenURL 1.0 standard.
 */

// $Id$

import('metadata.MetadataSchema');

class OpenUrlBaseSchema extends MetadataSchema {
	/**
	 * Constructor
	 */
	function OpenUrlBaseSchema() {
		// Add meta-data properties common to all OpenURL standards
		$this->addProperty(new MetadataProperty('aulast'));
		$this->addProperty(new MetadataProperty('aufirst'));
		$this->addProperty(new MetadataProperty('auinit'));   // First author's first and middle initials
		$this->addProperty(new MetadataProperty('auinit1'));  // First author's first initial
		$this->addProperty(new MetadataProperty('auinitm'));  // First author's middle initial
		$this->addProperty(new MetadataProperty('ausuffix')); // e.g.: "Jr", "III", etc.
		$this->addProperty(new MetadataProperty('au', array(), METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_MANY));
		$this->addProperty(new MetadataProperty('title'));    // Deprecated in book/journal 1.0, prefer jtitle/btitle, ok for dissertation
		$this->addProperty(new MetadataProperty('date', array(), METADATA_PROPERTY_TYPE_DATE)); // Publication date
		$this->addProperty(new MetadataProperty('isbn'));
	}
}
?>