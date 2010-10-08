<?php

/**
 * @defgroup citation_lookup_crossref
 */

/**
 * @file classes/citation/lookup/crossref/CrossrefNlm30CitationSchemaFilter.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CrossrefNlm30CitationSchemaFilter
 * @ingroup citation_lookup_crossref
 *
 * @brief Filter that uses the Crossref web
 *  service to identify a DOI and corresponding
 *  meta-data for a given NLM citation.
 */

import('lib.pkp.plugins.metadata.nlm30.filter.Nlm30CitationSchemaFilter');
import('lib.pkp.classes.filter.EmailFilterSetting');

define('CROSSREF_WEBSERVICE_URL', 'http://www.crossref.org/openurl/');

class CrossrefNlm30CitationSchemaFilter extends Nlm30CitationSchemaFilter {
	/**
	 * Constructor
	 * @param $email string
	 */
	function CrossrefNlm30CitationSchemaFilter($email = null) {
		$this->setDisplayName('CrossRef');
		if (!is_null($email)) $this->setEmail($email);

		// Instantiate the settings of this filter
		$emailSetting = new EmailFilterSetting('email',
				'metadata.filters.crossref.settings.email.displayName',
				'metadata.filters.crossref.settings.email.validationMessage');
		$this->addSetting($emailSetting);

		parent::Nlm30CitationSchemaFilter(
			NLM_CITATION_FILTER_LOOKUP,
			array(
				NLM_PUBLICATION_TYPE_JOURNAL,
				NLM_PUBLICATION_TYPE_CONFPROC,
				NLM_PUBLICATION_TYPE_BOOK,
				NLM_PUBLICATION_TYPE_THESIS
			)
		);
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the CrossRef registered access email
	 * @param $email string
	 */
	function setEmail($email) {
		$this->setData('email', $email);
	}

	/**
	 * Get the CrossRef registered access email
	 * @return string
	 */
	function getEmail() {
		return $this->getData('email');
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::getClassName()
	 */
	function getClassName() {
		return 'lib.pkp.classes.citation.lookup.crossref.CrossrefNlm30CitationSchemaFilter';
	}

	/**
	 * @see Filter::process()
	 * @param $citationDescription MetadataDescription
	 * @return MetadataDescription
	 */
	function &process(&$citationDescription) {
		$nullVar = null;

		$email = $this->getEmail();
		assert(!empty($email));
		$searchParams = array(
			'pid' => $email,
			'noredirect' => 'true',
			'format' => 'unixref'
		);

		$doi = $citationDescription->getStatement('pub-id[@pub-id-type="doi"]');
		if (!empty($doi)) {
			// Directly look up the DOI with OpenURL 0.1.
			$searchParams['id'] = 'doi:'.$doi;
		} else {
			// Use OpenURL meta-data to search for the entry.
			if (is_null($openUrlMetadata = $this->_prepareOpenUrl10Search($citationDescription))) return $nullVar;
			$searchParams += $openUrlMetadata;
		}

		// Call the CrossRef web service
		if (is_null($resultXml =& $this->callWebService(CROSSREF_WEBSERVICE_URL, $searchParams, XSL_TRANSFORMER_DOCTYPE_STRING)) || String::substr(trim($resultXml), 0, 6) == '<html>') return $nullVar;

		// Remove default name spaces from XML as CrossRef doesn't
		// set them reliably and element names are unique anyway.
		$resultXml = String::regexp_replace('/ xmlns="[^"]+"/', '', $resultXml);

		// Transform and process the web service result
		if (is_null($metadata =& $this->transformWebServiceResults($resultXml, dirname(__FILE__).DIRECTORY_SEPARATOR.'crossref.xsl'))) return $nullVar;

		return $this->getNlm30CitationDescriptionFromMetadataArray($metadata);
	}


	//
	// Private methods
	//
	/**
	 * Prepare a search with the CrossRef OpenURL resolver
 	 * @param $citationDescription MetadataDescription
 	 * @return array an array of search parameters
	 */
	function &_prepareOpenUrl10Search(&$citationDescription) {
		$nullVar = null;

		// Crosswalk to OpenURL.
		import('lib.pkp.plugins.metadata.nlm30.filter.Nlm30CitationSchemaOpenUrl10CrosswalkFilter');
		$nlmOpenUrl10Filter = new Nlm30CitationSchemaOpenUrl10CrosswalkFilter();
		if (is_null($openUrlCitation =& $nlmOpenUrl10Filter->execute($citationDescription))) return $nullVar;

		// Prepare the search.
		$searchParams = array(
			'url_ver' => 'Z39.88-2004'
		);

		// Configure the meta-data schema.
		$openUrlCitationSchema =& $openUrlCitation->getMetadataSchema();
		switch(true) {
			case is_a($openUrlCitationSchema, 'OpenUrl10JournalSchema'):
				$searchParams['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:journal';
				break;

			case is_a($openUrlCitationSchema, 'OpenUrl10BookSchema'):
				$searchParams['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:book';
				break;

			case is_a($openUrlCitationSchema, 'OpenUrl10DissertationSchema'):
				$searchParams['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:dissertation';
				break;

			default:
				assert(false);
		}

		// Add all OpenURL meta-data to the search parameters.
		// FIXME: Implement a looping search like for other lookup services.
		$searchProperties = array(
			'aufirst', 'aulast', 'btitle', 'jtitle', 'atitle', 'issn',
			'artnum', 'date', 'volume', 'issue', 'spage', 'epage'
		);
		foreach ($searchProperties as $property) {
			if ($openUrlCitation->hasStatement($property)) {
				$searchParams['rft.'.$property] = $openUrlCitation->getStatement($property);
			}
		}

		return $searchParams;
	}
}
?>