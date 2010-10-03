<?php

/**
 * @file plugins/metadata/openurl10/OpenUrl10JournalBookBaseSchema.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OpenUrl10JournalBookBaseSchema
 * @ingroup metadata_openurl
 * @see OpenUrl10BaseSchema
 *
 * @brief Class that provides meta-data properties common to the
 *  journal and book variants of the OpenURL 1.0 standard.
 */


import('lib.pkp.plugins.metadata.openurl10.schema.OpenUrl10BaseSchema');

define('OPENURL_GENRE_CONFERENCE', 'conference');
define('OPENURL_GENRE_PROCEEDING', 'proceeding');
define('OPENURL_GENRE_UNKNOWN', 'unknown');

class OpenUrl10JournalBookBaseSchema extends OpenUrl10BaseSchema {
	/**
	 * Constructor
	 * @param $name string the meta-data schema name
	 */
	function OpenUrl10JournalBookBaseSchema($name) {
		parent::OpenUrl10BaseSchema($name);

		// Add meta-data properties common to the OpenURL book/journal standard
		$this->addProperty('aucorp');   // Organization or corporation that is the author or creator
		$this->addProperty('atitle');
		$this->addProperty('spage', METADATA_PROPERTY_TYPE_INTEGER);
		$this->addProperty('epage', METADATA_PROPERTY_TYPE_INTEGER);
		$this->addProperty('pages');
		$this->addProperty('issn');
	}
}
?>