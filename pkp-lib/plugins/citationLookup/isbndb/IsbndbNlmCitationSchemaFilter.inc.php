<?php

/**
 * @defgroup citation_lookup_isbndb
 */

/**
 * @file classes/citation/lookup/isbndb/IsbndbNlm30CitationSchemaFilter.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IsbndbNlm30CitationSchemaFilter
 * @ingroup citation_lookup_isbndb
 *
 * @brief Abstract filter that wraps the ISBNdb web service.
 */

define('ISBNDB_WEBSERVICE_URL', 'http://isbndb.com/api/books.xml');

import('lib.pkp.classes.citation.Nlm30CitationSchemaFilter');
import('lib.pkp.classes.filter.FilterSetting');

class IsbndbNlm30CitationSchemaFilter extends Nlm30CitationSchemaFilter {
	/*
	 * Constructor
	 * @param $apiKey string
	 */
	function IsbndbNlm30CitationSchemaFilter($apiKey = null) {
		if (!is_null($apiKey)) $this->setData('apiKey', $apiKey);

		// Instantiate the settings of this filter
		$apiKeySetting = new FilterSetting('apiKey',
				'metadata.filters.isbndb.settings.apiKey.displayName',
				'metadata.filters.isbndb.settings.apiKey.validationMessage');
		$this->addSetting($apiKeySetting);

		parent::Nlm30CitationSchemaFilter(null, array(NLM_PUBLICATION_TYPE_BOOK));
	}

	//
	// Getters and Setters
	//
	/**
	 * Get the apiKey
	 * @return string
	 */
	function getApiKey() {
		return $this->getData('apiKey');
	}

	//
	// Protected helper methods
	//
	/**
	 * Checks whether the given string is an ISBN.
	 * @param $isbn
	 * @return boolean
	 */
	function isValidIsbn($isbn) {
		return is_string($isbn) && is_numeric($isbn) && String::strlen($isbn) == 13;
	}
}
?>