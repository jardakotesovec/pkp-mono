<?php

/**
 * @file classes/citation/CrossrefNlmCitationSchemaFilter.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CrossrefNlmCitationSchemaFilter
 * @ingroup citation_lookup_crossref
 *
 * @brief Filter that uses the Crossref web
 *  service to identify a DOI and corresponding
 *  meta-data for a given NLM citation.
 */

// $Id$

import('citation.NlmCitationSchemaFilter');

define('CROSSREF_WEBSERVICE_URL', 'http://www.crossref.org/openurl/');

class CrossrefNlmCitationSchemaFilter extends NlmCitationSchemaFilter {
	/** @var string CrossRef registered access email */
	var $_email = '';

	/*
	 * Constructor
	 */
	function CrossrefNlmCitationSchemaFilter($email) {
		assert(!empty($email));
		$this->_email = $email;

		parent::NlmCitationSchemaFilter(array('journal', 'conf-proc', 'book', 'thesis'));
	}

	//
	// Getters and Setters
	//
	/**
	 * Get the access email
	 * @return string
	 */
	function getEmail() {
		return $this->_email;
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::process()
	 * @param $citationDescription MetadataDescription
	 * @return string a DOI or null
	 */
	function &process(&$citationDescription) {
		$nullVar = null;
		$searchParams = array(
			'pid' => $this->getEmail(),
			'redirect' => 'false',
			'format' => 'unixref'
		);

		$doi = $citationDescription->getStatement('pub-id[@pub-id-type="doi"]');
		if (!empty($doi)) {
			// Directly look up the DOI
			$searchParams['id'] = 'doi:'.urlencode($doi);
		} else {
			// Use OpenURL meta-data to search for the entry
			$searchParams += $this->_prepareOpenUrlSearch($citationDescription);
		}

		// Call the CrossRef web service
		$resultXml =& $this->callWebService(CROSSREF_WEBSERVICE_URL, $searchParams, XSL_TRANSFORMER_DOCTYPE_STRING);

		// Handle web service errors
		if (is_null($resultXml)) return $resultXml;

		// Remove default name spaces from XML as CrossRef doesn't
		// set them reliably and element names are unique anyway.
		$resultXml = String::regexp_replace('/ xmlns="[^"]+"/', '', $resultXml);

		// Transform and process the web service result
		$metadata =& $this->transformWebServiceResults($resultXml, dirname(__FILE__).DIRECTORY_SEPARATOR.'crossref.xsl');
		return $this->createNlmCitationDescriptionFromArray($metadata);
	}


	//
	// Private methods
	//
	/**
	 * Prepare a search with the CrossRef OpenURL resolver
 	 * @param $citationDescription MetadataDescription
 	 * @return array an array of search parameters
	 */
	function &_prepareOpenUrlSearch(&$citationDescription) {
		// Crosswalk to OpenURL
		import('metadata.nlm.NlmCitationSchemaOpenUrlCrosswalkFilter');
		$nlmOpenUrlFilter = new NlmCitationSchemaOpenUrlCrosswalkFilter();
		$openUrlCitation =& $nlmOpenUrlFilter->execute($citationDescription);
		$openUrlCitationSchema =& $openUrlCitation->getMetadataSchema();

		// Prepare the search
		$searchParams = array(
			'url_ver' => 'Z39.88-2004'
		);

		// Configure the meta-data schema
		switch(true) {
			case is_a($openUrlCitationSchema, 'OpenUrlJournalSchema'):
				$searchParams['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:journal';
				break;

			case is_a($openUrlCitationSchema, 'OpenUrlBookSchema'):
				$searchParams['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:book';
				break;

			case is_a($openUrlCitationSchema, 'OpenUrlDissertationSchema'):
				$searchParams['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:dissertation';
				break;

			default:
				assert(false);
		}

		// Add all OpenURL meta-data to the search parameters
		// FIXME: Implement a looping search like for other lookup services.
		$openUrlStatements =& $openUrlCitation->getStatements();
		foreach ($openUrlStatements as $property => $value) {
			// The author has cardinality many in OpenURL. We only use the first author.
			if ($property == 'au') $value = $value[0];
			$searchParams[$property] = $value;
		}

		return $searchParams;
	}
}
?>