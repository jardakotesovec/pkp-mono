<?php

/**
 * @file plugins/generic/lucene/classes/SolrWebService.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SolrWebService
 * @ingroup plugins_generic_lucene_classes
 *
 * @brief Implements the communication protocol with the solr search server.
 *
 * This class relies on the PHP curl extension. Please activate the
 * extension before trying to access a solr server through this class.
 */


define('SOLR_STATUS_ONLINE', 0x01);
define('SOLR_STATUS_OFFLINE', 0x02);

import('lib.pkp.classes.webservice.WebServiceRequest');
import('lib.pkp.classes.webservice.XmlWebService');
import('lib.pkp.classes.xml.XMLCustomWriter');
import('plugins.generic.lucene.classes.SolrSearchRequest');

class SolrWebService extends XmlWebService {

	/** @var string The solr search handler name we place our searches on. */
	var $_solrSearchHandler;

	/** @var string The solr core we get our data from. */
	var $_solrCore;

	/** @var string The base URL of the solr server without core and search handler. */
	var $_solrServer;

	/** @var string The unique ID identifying this OJS installation to the solr server. */
	var $_instId;

	/** @var string A description of the last error or message that occured when calling the service. */
	var $_serviceMessage = '';

	/** @var FileCache A cache containing the available search fields. */
	var $_fieldCache;

	/**
	 * Constructor
	 *
	 * @param $searchHandler string The search handler URL. We assume the embedded server
	 *  as a default.
	 */
	function SolrWebService($searchHandler, $username, $password, $instId) {
		parent::XmlWebService();

		// Configure the web service.
		$this->setAuthUsername($username);
		$this->setAuthPassword($password);

		// Remove trailing slashes.
		assert(is_string($searchHandler) && !empty($searchHandler));
		$searchHandler = rtrim($searchHandler, '/');

		// Parse the search handler URL.
		$searchHandlerParts = explode('/', $searchHandler);
		$this->_solrSearchHandler = array_pop($searchHandlerParts);
		$this->_solrCore = array_pop($searchHandlerParts);
		$this->_solrServer = implode('/', $searchHandlerParts) . '/';

		// Set the installation ID.
		assert(is_string($instId) && !empty($instId));
		$this->_instId = $instId;
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the last service message.
	 * @return string
	 */
	function getServiceMessage() {
		return $this->_serviceMessage;
	}


	//
	// Public API
	//
	/**
	 * Execute a search against the Solr search server.
	 *
	 * @param $searchRequest SolrSearchRequest
	 * @param $totalResults integer An output parameter returning the
	 *  total number of search results found by the query. This differs
	 *  from the actual number of returned results as the search can
	 *  be limited.
	 *
	 * @return array An array of search results. The keys are
	 *  scores (1-9999) and the values are article IDs. Null if an error
	 *  occured while querying the server.
	 */
	function retrieveResults(&$searchRequest, &$totalResults) {
		// Initialize the search request parameters.
		$params = array();

		// Construct a sub query for every field search phrase.
		foreach ($searchRequest->getQuery() as $fieldList => $searchPhrase) {
			// Ignore empty search phrases.
			if (empty($fieldList) || empty($searchPhrase)) continue;

			// Construct the sub-query and add it to the search query and params.
			$params = $this->_addSubquery($fieldList, $searchPhrase, $params, true);
		}

		// Add a range search on the publication date.
		$fromDate = $searchRequest->getFromDate();
		$toDate = $searchRequest->getToDate();
		if (!(empty($fromDate) && empty($toDate))) {
			if (empty($fromDate)) {
				$fromDate = '*';
			} else {
				$fromDate = $this->_convertDate($fromDate);
			}
			if (empty($toDate)) {
				$toDate = '*';
			} else {
				$toDate = $this->_convertDate($toDate);
			}
			$params['q'] .= " +publicationDate_dt:[$fromDate TO $toDate]";
		}

		// Add the journal (if set).
		$journal =& $searchRequest->getJournal();
		if (is_a($journal, 'Journal')) {
			$params['q'] .= ' +journal_id:"' . $this->_instId . '-' . $journal->getId() . '"';
		}

		// Add the installation ID.
		$params['q'] .= ' +inst_id:"' . $this->_instId . '"';

		// Pagination.
		$itemsPerPage = $searchRequest->getItemsPerPage();
		$params['start'] = ($searchRequest->getPage() - 1) * $itemsPerPage;
		$params['rows'] = $itemsPerPage;

		// Ordering.
		$params['sort'] = $this->_getOrdering($searchRequest->getOrderBy(), $searchRequest->getOrderDir());

		// Make the search request.
		$url = $this->_getSearchUrl();
		$response = $this->_makeRequest($url, $params);

		// Did we get a result?
		if (is_null($response)) return $response;

		// Get the total number of documents found.
		$nodeList = $response->query('//response/result[@name="response"]/@numFound');
		assert($nodeList->length == 1);
		$resultNode = $nodeList->item(0);
		assert(is_numeric($resultNode->textContent));
		$totalResults = (int) $resultNode->textContent;

		// Run through all returned documents and read the ID fields.
		$results = array();
		$docs =& $response->query('//response/result/doc');
		foreach ($docs as $doc) {
			$currentDoc = array();
			foreach ($doc->childNodes as $docField) {
				// Get the document field
				$docFieldAtts = $docField->attributes;
				$fieldNameAtt = $docFieldAtts->getNamedItem('name');

				switch($docField->tagName) {
					case 'float':
						$currentDoc[$fieldNameAtt->value] = (float)$docField->textContent;
						break;

					case 'str':
						$currentDoc[$fieldNameAtt->value] = $docField->textContent;
						break;
				}
			}
			$results[] = $currentDoc;
		}

		// Re-index the result set. There's no need to re-order as the
		// results come back ordered from the solr server.
		$scoredResults = array();
		foreach($results as $resultIndex => $result) {
			// We only need the article ID.
			assert(isset($result['article_id']));

			// Use the result order to "score" results. This
			// will do relevance sorting and field sorting.
			$score = $itemsPerPage - $resultIndex;

			// Transform the article ID into an integer.
			$articleId = $result['article_id'];
			if (strpos($articleId, $this->_instId . '-') !== 0) continue;
			$articleId = substr($articleId, strlen($this->_instId . '-'));
			if (!is_numeric($articleId)) continue;

			// Store the result.
			$scoredResults[$score] = (int)$articleId;
		}
		return $scoredResults;
	}

	/**
	 * (Re-)indexes the given article in Solr.
	 *
	 * In Solr we cannot partially (re-)index an article. We always
	 * have to refresh the whole document if parts of it change.
	 *
	 * @param $article PublishedArticle The article to be (re-)indexed.
	 * @param $journal Journal
	 *
	 * @return boolean true, if the indexing succeeded, otherwise false.
	 */
	function indexArticle(&$article, &$journal) {
		assert($article->getJournalId() == $journal->getId());

		// Generate the transfer XML for the article and POST it to the web service.
		$articleDoc =& $this->_getArticleXml($article, $journal);
		$articleXml = XMLCustomWriter::getXml($articleDoc);

		$url = $this->_getDihUrl() . '?command=full-import&clean=false';
		$result = $this->_makeRequest($url, $articleXml, 'POST');
		if (is_null($result)) return false;

		// Check whether the document was successfully indexed.
		$docsProcessed = $this->_getDocumentsProcessed($result);
		return ($docsProcessed == 1);
	}

	/**
	 * (Re-)indexes the given journal in Solr.
	 *
	 * @param $journal Journal The journal to be (re-)indexed.
	 * @param $log boolean Whether to log indexing progress.
	 *
	 * @return integer The number of documents processed or null if
	 *  an error occured.
	 */
	function indexJournal(&$journal, $log=false) {
		// Initialize local variables.
		$articleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $articleDao PublishedArticleDAO */
		$numIndexed = 0;

		// To keep memory usage as low as possible we
		// commit batches of 200 articles. Batches should
		// not be too small, either, as this will considerably
		// increase overall DB access time.
		import('lib.pkp.classes.db.DBResultRange');
		$batch = 1;
		$batchSize = 200;
		$continue = true;
		while ($continue) {
			// Retrieve the next batch.
			$range = new DBResultRange($batchSize, $batch);
			$articles =& $articleDao->getPublishedArticlesByJournalId($journal->getId(), $range);
			unset($range);

			// Is this our last batch?
			$continue = !$articles->atLastPage();

			// Run through all articles of the batch.
			$articleDoc = null;
			while (!$articles->eof()) {
				$article =& $articles->next();

				// Add the article to the article list if it has been published.
				if ($article->getDatePublished()) {
					$articleDoc =& $this->_getArticleXml($article, $journal, $articleDoc);
				}
				unset($article);
			}
			unset($articles);

			// Make a POST request with all articles in this batch.
			$articleXml = XMLCustomWriter::getXml($articleDoc);
			unset($articleDoc);
			$url = $this->_getDihUrl() . '?command=full-import&clean=false';
			$result = $this->_makeRequest($url, $articleXml, 'POST');
			unset($articleXml);

			// Retrieve the number of successfully indexed articles.
			if (is_null($result)) {
				return null;
			} else {
				$numIndexed += $this->_getDocumentsProcessed($result);
				if ($log) echo '.';
			}
			unset($result);

			// Do the next batch.
			$batch++;
		}

		return $numIndexed;
	}

	/**
	 * Deletes the given article from the Solr index.
	 *
	 * @param $articleId integer The ID of the article to be deleted.
	 *
	 * @return boolean true if successful, otherwise false.
	 */
	function deleteArticleFromIndex($articleId) {
		$xml = '<id>' . $this->_instId . '-' . $articleId . '</id>';
		return $this->_deleteFromIndex($xml);
	}

	/**
	 * Deletes all articles of this installation from the Solr index.
	 *
	 * @return boolean true if successful, otherwise false.
	 */
	function deleteAllArticlesFromIndex() {
		// Delete all articles of the installation.
		$xml = '<query>inst_id:' . $this->_instId . '</query>';
		return $this->_deleteFromIndex($xml);
	}

	/**
	 * Checks the solr server status.
	 *
	 * @return integer One of the SOLR_STATUS_* constants.
	 */
	function getServerStatus() {
		// Make status request.
		$url = $this->_getAdminUrl() . 'cores';
		$params = array(
			'action' => 'STATUS',
			'core' => $this->_solrCore
		);
		$response = $this->_makeRequest($url, $params);

		// Did we get a response at all?
		if (is_null($response)) {
			return SOLR_STATUS_OFFLINE;
		}

		// Is the core online?
		assert(is_a($response, 'DOMXPath'));
		$nodeList = $response->query('//response/lst[@name="status"]/lst[@name="ojs"]/lst[@name="index"]/int[@name="numDocs"]');

		// Check whether the core is active.
		if ($nodeList->length != 1) {
			$this->_serviceMessage = __('plugins.generic.lucene.error.coreNotFound', array('core' => $this->_solrCore));
			return SOLR_STATUS_OFFLINE;
		}

		$this->_serviceMessage = __('plugins.generic.lucene.message.indexOnline', array('numDocs' => $nodeList->item(0)->textContent));
		return SOLR_STATUS_ONLINE;
	}

	/**
	 * Returns an array with all (dynamic) fields in the index.
	 * @param $fieldType string Either 'search' or 'sort'.
	 * @return array
	 */
	function getAvailableFields($fieldType) {
		$cache =& $this->_getCache();
		$fieldCache = $cache->get($fieldType);
		return $fieldCache;
	}

	/**
	 * Flush the field cache.
	 */
	function flushFieldCache() {
		$cache =& $this->_getCache();
		$cache->flush();
	}

	/**
	 * Retrieve a document directly from the index
	 * (for testing/debugging purposes).
	 *
	 * @param $articleId
	 *
	 * @return array The document fields.
	 */
	function getArticleFromIndex($articleId) {
		// Make a request to the luke request handler.
		$url = $this->_getCoreAdminUrl() . 'luke';
		$params = array('id' => $this->_instId . '-' . $articleId);
		$response = $this->_makeRequest($url, $params);
		if (!is_a($response, 'DOMXPath')) return false;

		// Retrieve all fields from the response.
		$doc = array();
		$nodeList = $response->query('//response/lst[@name="doc"]/doc[@name="solr"]/str');
		foreach ($nodeList as $node) {
			// Get the field name.
			$fieldName = $node->attributes->getNamedItem('name')->value;
			$fieldValue = $node->textContent;
			$doc[$fieldName] = $fieldValue;
		}

		return $doc;
	}


	//
	// Implement cache functions.
	//
	/**
	 * Refresh the cache from the solr server.
	 * @param $cache FileCache
	 * @param $id string The field type.
	 *
	 * @return array The available field names.
	 */
	function _cacheMiss(&$cache, $id) {
		assert(in_array($id, array('search', 'sort')));

		// Get the fields that may be found in the index.
		$fields = $this->_getFieldNames('all');

		// Prepare the cache.
		$fieldCache = array();
		foreach(array('search', 'sort') as $fieldType) {
			$fieldCache[$fieldType] = array();
			foreach(array('localized', 'multiformat', 'static') as $fieldSubType) {
				if ($fieldSubType == 'static') {
					foreach($fields[$fieldType][$fieldSubType] as $fieldName => $dummy) {
						$fieldCache[$fieldType][$fieldName] = array();
					}
				} else {
					foreach($fields[$fieldType][$fieldSubType] as $fieldName) {
						$fieldCache[$fieldType][$fieldName] = array();
					}
				}
			}
		}

		// Make a request to the luke request handler.
		$url = $this->_getCoreAdminUrl() . 'luke';
		$response = $this->_makeRequest($url);
		if (!is_a($response, 'DOMXPath')) return false;

		// Retrieve the field names from the response.
		$nodeList = $response->query('//response/lst[@name="fields"]/lst/@name');
		foreach ($nodeList as $node) {
			// Get the field name.
			$fieldName = $node->textContent;

			// Split the field name.
			$fieldNameParts = explode('_', $fieldName);

			// Identify the field type.
			$fieldSuffix = array_pop($fieldNameParts);
			if (strpos($fieldSuffix, 'sort') !== false) {
				$fieldType = 'sort';
				$fieldSuffix = array_pop($fieldNameParts);
			} else {
				$fieldType = 'search';
			}

			// 1) Is this a static field?
			foreach($fields[$fieldType]['static'] as $staticField => $fullFieldName) {
				if ($fieldName == $fullFieldName) {
					$fieldCache[$fieldType][$staticField][] = $fullFieldName;
					continue 2;
				}
			}

			// Localized and multiformat fields have a locale suffix.
			$locale = $fieldSuffix;
			if ($locale != 'txt') {
				$locale = array_pop($fieldNameParts) . '_' . $locale;
			}

			// 2) Is this a dynamic localized field?
			foreach($fields[$fieldType]['localized'] as $localizedField) {
				if (strpos($fieldName, $localizedField) === 0) {
					$fieldCache[$fieldType][$localizedField][] = $locale;
				}
			}

			// 3) Is this a dynamic multi-format field?
			foreach($fields[$fieldType]['multiformat'] as $multiformatField) {
				if (strpos($fieldName, $multiformatField) === 0) {
					// Identify the format of the field.
					$format = array_pop($fieldNameParts);

					// Add the field to the field cache.
					if (!isset($fieldCache[$fieldType][$multiformatField][$format])) {
						$fieldCache[$fieldType][$multiformatField][$format] = array();
					}
					$fieldCache[$fieldType][$multiformatField][$format][] = $locale;

					// Continue the outer loop.
					continue 2;
				}
			}
		}

		$cache->setEntireCache($fieldCache);
		return $fieldCache[$id];
	}

	/**
	 * Get the field cache.
	 * @return FileCache
	 */
	function &_getCache() {
		if (!isset($this->_fieldCache)) {
			// Instantiate a file cache.
			$cacheManager =& CacheManager::getManager();
			$this->_fieldCache = $cacheManager->getFileCache(
				'plugins-lucene', 'fieldCache',
				array(&$this, '_cacheMiss')
			);

			// Check to see if the data is outdated (24 hours).
			$cacheTime = $this->_fieldCache->getCacheTime();
			if (!is_null($cacheTime) && $cacheTime < (time() - 24 * 60 * 60)) {
				$this->_fieldCache->flush();
			}
		}
		return $this->_fieldCache;
	}


	//
	// Private helper methods
	//
	/**
	 * Identifies the general solr admin endpoint from the
	 * search handler URL.
	 *
	 * @return string
	 */
	function _getAdminUrl() {
		$adminUrl = $this->_solrServer . 'admin/';
		return $adminUrl;
	}

	/**
	 * Identifies the solr core-specific admin endpoint
	 * from the search handler URL.
	 *
	 * @return string
	 */
	function _getCoreAdminUrl() {
		$adminUrl = $this->_solrServer . $this->_solrCore . '/admin/';
		return $adminUrl;
	}

	/**
	 * Returns the solr search endpoint.
	 *
	 * @return string
	 */
	function _getSearchUrl() {
		$searchUrl = $this->_solrServer . $this->_solrCore . '/' . $this->_solrSearchHandler;
		return $searchUrl;
	}

	/**
	 * Returns the solr DIH endpoint.
	 *
	 * @return string
	 */
	function _getDihUrl() {
		$dihUrl = $this->_solrServer . $this->_solrCore . '/dih';
		return $dihUrl;
	}

	/**
	 * Returns the solr update endpoint.
	 *
	 * @return string
	 */
	function _getUpdateUrl() {
		$updateUrl = $this->_solrServer . $this->_solrCore . '/update';
		return $updateUrl;
	}

	/**
	 * Make a request
	 *
	 * @param $url string The request URL
	 * @param $params array request parameters
	 * @param $method string GET or POST
	 *
	 * @return DOMXPath An XPath object with the response loaded. Null if an error occurred.
	 *  See _serviceMessage for more details about the error.
	 */
	function &_makeRequest($url, $params = array(), $method = 'GET') {
		$webServiceRequest = new WebServiceRequest($url, $params, $method);
		if ($method == 'POST') {
			$webServiceRequest->setHeader('Content-Type', 'text/xml; charset=utf-8');
		}
		$this->setReturnType(XSL_TRANSFORMER_DOCTYPE_DOM);
		$response = $this->call($webServiceRequest);
		$nullValue = null;

		// Did we get a response at all?
		if (!$response) {
			$this->_serviceMessage = __('plugins.generic.lucene.error.searchServiceOffline');
			return $nullValue;
		}

		// Return the result.
		// Did we get a 200OK response?
		$status = $this->getLastResponseStatus();
		if ($status !== WEBSERVICE_RESPONSE_OK) {
			$this->_serviceMessage = $status. ' - ' . $response->saveXML();
			return $nullValue;
		}

		// Prepare an XPath object.
		assert(is_a($response, 'DOMDocument'));
		$result = new DOMXPath($response);
		return $result;
	}

	/**
	 * Return a list of all text fields that may occur in the
	 * index.
	 * @param $fieldType string "search", "sort" or "all"
	 *
	 * @return array
	 */
	function _getFieldNames($fieldType) {
		$fieldNames = array(
			'search' => array(
				'localized' => array(
					'title', 'abstract', 'discipline', 'subject',
					'type', 'coverage', 'all', 'indexTerms', 'suppFiles'
				),
				'multiformat' => array(
					'galleyFullText'
				),
				'static' => array(
					'authors' => 'authors_txt',
					'publicationDate' => 'publicationDate_dt'
				)
			),
			'sort' => array(
				'localized' => array(
					'title', 'journalTitle'
				),
				'multiformat' => array(),
				'static' => array(
					'authors' => 'authors_txtsort',
					'publicationDate' => 'publicationDate_dtsort',
					'issuePublicationDate' => 'issuePublicationDate_dtsort'
				)
			)
		);
		if ($fieldType == 'all') {
			return $fieldNames;
		} else {
			assert(isset($fieldNames[$fieldType]));
			return $fieldNames[$fieldType];
		}
	}

	/**
	 * Identify all format/locale versions of the given field.
	 * @param $field string A field name without any extension.
	 * @return array A list of index fields.
	 */
	function _getLocalesAndFormats($field) {
		$availableFields = $this->getAvailableFields('search');
		$fieldNames = $this->_getFieldNames('search');

		$indexFields = array();
		if (isset($availableFields[$field])) {
			if (in_array($field, $fieldNames['multiformat'])) {
				// This is a multiformat field.
				foreach($availableFields[$field] as $format => $locales) {
					foreach($locales as $locale) {
						$indexFields[] = $field . '_' . $format . '_' . $locale;
					}
				}
			} elseif(in_array($field, $fieldNames['localized'])) {
				// This is a localized field.
				foreach($availableFields[$field] as $locale) {
					$indexFields[] = $field . '_' . $locale;
				}
			} else {
				// This must be a static field.
				assert(isset($fieldNames['static'][$field]));
				$indexFields[] = $fieldNames['static'][$field];
			}
		}
		return $indexFields;
	}

	/**
	 * Generate the ordering parameter of a search query.
	 * @param $field string the field to order by
	 * @param $direction boolean true for ascending, false for descending
	 * @return string The ordering to be used (default: descending relevance).
	 */
	function _getOrdering($field, $direction) {
		// Translate the direction.
		$dirString = ($direction?' asc':' desc');

		// Relevance ordering.
		if ($field == 'score') {
			return $field . $dirString;
		}

		// We order by descending relevance by default.
		$defaultSort = 'score desc';

		// We have to check whether the sort field is
		// available in the index.
		$availableFields = $this->getAvailableFields('sort');
		if (!isset($availableFields[$field])) return $defaultSort;

		// Retrieve all possible sort fields.
		$fieldNames = $this->_getFieldNames('sort');

		// Order by a static (non-localized) field.
		if(isset($fieldNames['static'][$field])) {
			return $fieldNames['static'][$field] . $dirString . ',' . $defaultSort;
		}

		// Order by a localized field.
		if (in_array($field, $fieldNames['localized'])) {
			// We can only sort if the current locale is indexed.
			$currentLocale = AppLocale::getLocale();
			if (in_array($currentLocale, $availableFields[$field])) {
				// Return the localized sort field name.
				return $field . '_' . $currentLocale . '_txtsort' . $dirString . ',' . $defaultSort;
			}
		}

		// In all other cases return the default ordering.
		return $defaultSort;
	}

	/**
	 * Establish the XML used to communicate with the
	 * solr indexing engine DIH.
	 * @param $article PublishedArticle
	 * @param $journal Journal
	 * @param $articleDoc DOMDocument|XMLNode
	 * @return DOMDocument|XMLNode
	 */
	function &_getArticleXml(&$article, &$journal, $articleDoc = null) {
		assert(is_a($article, 'PublishedArticle'));

		if (is_null($articleDoc)) {
			// Create the document.
			$articleDoc =& XMLCustomWriter::createDocument();

			// Create the root node.
			$articleList =& XMLCustomWriter::createElement($articleDoc, 'articleList');
			XMLCustomWriter::appendChild($articleDoc, $articleList);
		} else {
			if (is_a($articleDoc, 'XMLNode')) {
				$articleList =& $articleDoc->getChildByName('articleList');
			} else {
				$articleList =& $articleDoc->documentElement;
			}
		}

		// Create a new article node.
		$articleNode =& XMLCustomWriter::createElement($articleDoc, 'article');
		XMLCustomWriter::setAttribute($articleNode, 'id', $article->getId());
		XMLCustomWriter::setAttribute($articleNode, 'journalId', $article->getJournalId());
		XMLCustomWriter::setAttribute($articleNode, 'instId', $this->_instId);
		XMLCustomWriter::appendChild($articleList, $articleNode);

		// Add authors.
		$authors = $article->getAuthors();
		if (!empty($authors)) {
			$authorList =& XMLCustomWriter::createElement($articleDoc, 'authorList');
			foreach ($authors as $author) { /* @var $author Author */
				XMLCustomWriter::createChildWithText($articleDoc, $authorList, 'author', $author->getFullName(true));
			}
			XMLCustomWriter::appendChild($articleNode, $authorList);
		}

		// We need the request to retrieve locales and build URLs.
		$request =& PKPApplication::getRequest();

		// Get all supported locales.
		$site =& $request->getSite();
		$supportedLocales = $site->getSupportedLocales() + array_keys($journal->getSupportedLocaleNames());
		assert(!empty($supportedLocales));

		// Add titles.
		$titleList =& XMLCustomWriter::createElement($articleDoc, 'titleList');
		// Titles are used for sorting, we therefore need
		// them in all supported locales.
		assert(!empty($supportedLocales));
		foreach($supportedLocales as $locale) {
			$localizedTitle = $article->getLocalizedTitle($locale);
			if (!is_null($localizedTitle)) {
				// Add the localized title.
				$titleNode =& XMLCustomWriter::createChildWithText($articleDoc, $titleList, 'title', $localizedTitle);
				XMLCustomWriter::setAttribute($titleNode, 'locale', $locale);

				// If the title does not exist in the given locale
				// then use the localized title for sorting only.
				$title = $article->getTitle($locale);
				$sortOnly = (empty($title) ? 'true' : 'false');
				XMLCustomWriter::setAttribute($titleNode, 'sortOnly', $sortOnly);
			}
		}
		XMLCustomWriter::appendChild($articleNode, $titleList);

		// Add abstracts.
		$abstracts = $article->getAbstract(null); // return all locales
		if (!empty($abstracts)) {
			$abstractList =& XMLCustomWriter::createElement($articleDoc, 'abstractList');
			foreach ($abstracts as $locale => $abstract) {
				$abstractNode =& XMLCustomWriter::createChildWithText($articleDoc, $abstractList, 'abstract', $abstract);
				XMLCustomWriter::setAttribute($abstractNode, 'locale', $locale);
			}
			XMLCustomWriter::appendChild($articleNode, $abstractList);
		}

		// Add discipline.
		$disciplines = $article->getDiscipline(null); // return all locales
		if (!empty($disciplines)) {
			$disciplineList =& XMLCustomWriter::createElement($articleDoc, 'disciplineList');
			foreach ($disciplines as $locale => $discipline) {
				$disciplineNode =& XMLCustomWriter::createChildWithText($articleDoc, $disciplineList, 'discipline', $discipline);
				XMLCustomWriter::setAttribute($disciplineNode, 'locale', $locale);
			}
			XMLCustomWriter::appendChild($articleNode, $disciplineList);
		}

		// Add subjects and subject classes.
		$subjectClasses = $article->getSubjectClass(null);
		$subjects = $article->getSubject(null);
		if (!empty($subjectClasses) || !empty($subjects)) {
			$subjectList =& XMLCustomWriter::createElement($articleDoc, 'subjectList');
			if (!is_array($subjectClasses)) $subjectClasses = array();
			if (!is_array($subjects)) $subjects = array();
			$locales = array_unique(array_merge(array_keys($subjectClasses), array_keys($subjects)));
			foreach($locales as $locale) {
				$subject = '';
				if (isset($subjectClasses[$locale])) $subject .= $subjectClasses[$locale];
				if (isset($subjects[$locale])) {
					if (!empty($subject)) $subject .= ' ';
					$subject .= $subjects[$locale];
				}
				$subjectNode =& XMLCustomWriter::createChildWithText($articleDoc, $subjectList, 'subject', $subject);
				XMLCustomWriter::setAttribute($subjectNode, 'locale', $locale);
			}
			XMLCustomWriter::appendChild($articleNode, $subjectList);
		}

		// Add type.
		$types = $article->getType(null); // return all locales
		if (!empty($types)) {
			$typeList =& XMLCustomWriter::createElement($articleDoc, 'typeList');
			foreach ($types as $locale => $type) {
				$typeNode =& XMLCustomWriter::createChildWithText($articleDoc, $typeList, 'type', $type);
				XMLCustomWriter::setAttribute($typeNode, 'locale', $locale);
			}
			XMLCustomWriter::appendChild($articleNode, $typeList);
		}

		// Add coverage.
		$coverageGeo = $article->getCoverageGeo(null);
		$coverageChron = $article->getCoverageChron(null);
		$coverageSample = $article->getCoverageSample(null);
		if (!empty($coverageGeo) || !empty($coverageChron) || !empty($coverageSample)) {
			$coverageList =& XMLCustomWriter::createElement($articleDoc, 'coverageList');
			if (!is_array($coverageGeo)) $coverageGeo = array();
			if (!is_array($coverageChron)) $coverageChron = array();
			if (!is_array($coverageSample)) $coverageSample = array();
			$locales = array_unique(array_merge(array_keys($coverageGeo), array_keys($coverageChron), array_keys($coverageSample)));
			foreach($locales as $locale) {
				$coverage = '';
				if (isset($coverageGeo[$locale])) $coverage .= $coverageGeo[$locale];
				if (isset($coverageChron[$locale])) {
					if (!empty($coverage)) $coverage .= ' ';
					$coverage .= $coverageChron[$locale];
				}
				if (isset($coverageSample[$locale])) {
					if (!empty($coverage)) $coverage .= ' ';
					$coverage .= $coverageSample[$locale];
				}
				$coverageNode =& XMLCustomWriter::createChildWithText($articleDoc, $coverageList, 'coverage', $coverage);
				XMLCustomWriter::setAttribute($coverageNode, 'locale', $locale);
			}
			XMLCustomWriter::appendChild($articleNode, $coverageList);
		}

		// Add journal titles.
		$journalTitleList =& XMLCustomWriter::createElement($articleDoc, 'journalTitleList');
		// Journal titles are used for sorting, we therefore need
		// them in all supported locales.
		foreach($supportedLocales as $locale) {
			$localizedTitle = $journal->getLocalizedTitle($locale);
			if (!is_null($localizedTitle)) {
				// Add the localized title.
				$journalTitleNode =& XMLCustomWriter::createChildWithText($articleDoc, $journalTitleList, 'journalTitle', $localizedTitle);
				XMLCustomWriter::setAttribute($journalTitleNode, 'locale', $locale);

				// If the title does not exist in the given locale
				// then use the localized title for sorting only.
				$journalTitle = $journal->getTitle($locale);
				$sortOnly = (empty($journalTitle) ? 'true' : 'false');
				XMLCustomWriter::setAttribute($journalTitleNode, 'sortOnly', $sortOnly);
			}
		}
		XMLCustomWriter::appendChild($articleNode, $journalTitleList);

		// Add publication dates.
		$publicationDate = $article->getDatePublished();
		if (!empty($publicationDate)) {
			// Transform and store article publication date.
			$publicationDate = $this->_convertDate($publicationDate);
			$dateNode =& XMLCustomWriter::createChildWithText($articleDoc, $articleNode, 'publicationDate', $publicationDate);
		}

		$issueId = $article->getIssueId();
		if (is_numeric($issueId)) {
			$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
			$issue =& $issueDao->getIssueById($issueId);
			if (is_a($issue, 'Issue')) {
				$issuePublicationDate = $issue->getDatePublished();
				if (!empty($issuePublicationDate)) {
					// Transform and store issue publication date.
					$issuePublicationDate = $this->_convertDate($issuePublicationDate);
					$dateNode =& XMLCustomWriter::createChildWithText($articleDoc, $articleNode, 'issuePublicationDate', $issuePublicationDate);
				}
			}
		}

		// We need the router to build file URLs.
		$router =& $request->getRouter(); /* @var $router PageRouter */

		// Add galley files
		$fileDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$galleys =& $fileDao->getGalleysByArticle($article->getId());
		$galleyList = null;
		foreach ($galleys as $galley) { /* @var $galley ArticleGalley */
			$locale = $galley->getLocale();
			$galleyUrl = $router->url($request, $journal->getPath(), 'article', 'download', array(intval($article->getId()), intval($galley->getId())));
			if (!empty($locale) && !empty($galleyUrl)) {
				if (is_null($galleyList)) {
					$galleyList =& XMLCustomWriter::createElement($articleDoc, 'galleyList');
				}
				$galleyNode =& XMLCustomWriter::createElement($articleDoc, 'galley');
				XMLCustomWriter::setAttribute($galleyNode, 'locale', $locale);
				XMLCustomWriter::setAttribute($galleyNode, 'fileName', $galleyUrl);
				XMLCustomWriter::appendChild($galleyList, $galleyNode);
			}
		}

		// Wrap the galley XML as CDATA.
		if (!is_null($galleyList)) {
			if (is_callable(array($articleDoc, 'saveXml'))) {
				$galleyXml = $articleDoc->saveXml($galleyList);
			} else {
				$galleyXml = $galleyList->toXml();
			}
			$galleyOuterNode =& XMLCustomWriter::createElement($articleDoc, 'galley-xml');
			if (is_callable(array($articleDoc, 'createCDATASection'))) {
				$cdataNode =& $articleDoc->createCDATASection($galleyXml);
			} else {
				$cdataNode = new XMLNode();
				$cdataNode->setValue('<![CDATA[' . $galleyXml . ']]>');
			}
			XMLCustomWriter::appendChild($galleyOuterNode, $cdataNode);
			XMLCustomWriter::appendChild($articleNode, $galleyOuterNode);
		}

		// Add supplementary files
		$fileDao =& DAORegistry::getDAO('SuppFileDAO');
		$suppFiles =& $fileDao->getSuppFilesByArticle($article->getId());
		$suppFileList = null;
		foreach ($suppFiles as $suppFile) { /* @var $suppFile SuppFile */
			// Try to map the supp-file language to a PKP locale.
			$locale = null;
			$language = $suppFile->getLanguage();
			if (strlen($language) == 2) {
				$language = AppLocale::get3LetterFrom2LetterIsoLanguage($language);
			}
			if (strlen($language) == 3) {
				$locale = AppLocale::getLocaleFrom3LetterIso($language);
			}
			if (!AppLocale::isLocaleValid($locale)) {
				$locale = 'unknown';
			}

			$suppFileUrl = $router->url($request, $journal->getPath(), 'article', 'downloadSuppFile', array(intval($article->getId()), intval($suppFile->getId())));

			if (!empty($locale) && !empty($suppFileUrl)) {
				if (is_null($suppFileList)) {
					$suppFileList =& XMLCustomWriter::createElement($articleDoc, 'suppFileList');
				}
				$suppFileNode =& XMLCustomWriter::createElement($articleDoc, 'suppFile');
				XMLCustomWriter::setAttribute($suppFileNode, 'locale', $locale);
				XMLCustomWriter::setAttribute($suppFileNode, 'fileName', $suppFileUrl);
				XMLCustomWriter::appendChild($suppFileList, $suppFileNode);

				// Add supp file meta-data.
				$suppFileMetadata = array(
					'title' => $suppFile->getTitle(null),
					'creator' => $suppFile->getCreator(null),
					'subject' => $suppFile->getSubject(null),
					'typeOther' => $suppFile->getTypeOther(null),
					'description' => $suppFile->getDescription(null),
					'source' => $suppFile->getSource(null)
				);
				foreach($suppFileMetadata as $field => $data) {
					if (!empty($data)) {
						$suppFileMDListNode =& XMLCustomWriter::createElement($articleDoc, $field . 'List');
						foreach($data as $locale => $value) {
							$suppFileMDNode =& XMLCustomWriter::createChildWithText($articleDoc, $suppFileMDListNode, $field, $value);
							XMLCustomWriter::setAttribute($suppFileMDNode, 'locale', $locale);
							unset($suppFileMDNode);
						}
						XMLCustomWriter::appendChild($suppFileNode, $suppFileMDListNode);
						unset($suppFileMDListNode);
					}
				}
			}
		}

		// Wrap the suppFile XML as CDATA.
		if (!is_null($suppFileList)) {
			if (is_callable(array($articleDoc, 'saveXml'))) {
				$suppFileXml = $articleDoc->saveXml($suppFileList);
			} else {
				$suppFileXml = $suppFileList->toXml();
			}
			$suppFileOuterNode =& XMLCustomWriter::createElement($articleDoc, 'suppFile-xml');
			if (is_callable(array($articleDoc, 'createCDATASection'))) {
				$cdataNode =& $articleDoc->createCDATASection($suppFileXml);
			} else {
				$cdataNode = new XMLNode();
				$cdataNode->setValue('<![CDATA[' . $suppFileXml . ']]>');
			}
			XMLCustomWriter::appendChild($suppFileOuterNode, $cdataNode);
			XMLCustomWriter::appendChild($articleNode, $suppFileOuterNode);
		}

		// Return the XML.
		return $articleDoc;
	}

	/**
	 * Delete documents from the index (by
	 * ID or by query).
	 * @param $xml string The documents to delete.
	 * @return boolean true, if successful, otherwise false.
	 */
	function _deleteFromIndex($xml) {
		// Add the deletion tags.
		$xml = '<delete>' . $xml . '</delete>';

		// Post the XML.
		$url = $this->_getUpdateUrl() . '?commit=true';
		$result = $this->_makeRequest($url, $xml, 'POST');
		if (is_null($result)) return false;

		// Check the return status (must be 0).
		$nodeList = $result->query('//response/lst[@name="responseHeader"]/int[@name="status"]');
		if($nodeList->length != 1) return false;
		$resultNode = $nodeList->item(0);
		if ($resultNode->textContent === '0') return true;
	}

	/**
	 * Convert a date from local time (unix timestamp
	 * or ISO date string) to UTC time as understood
	 * by solr.
	 *
	 * NB: Using intermediate unix timestamps can be
	 * a problem in older PHP versions, especially on
	 * Windows where negative timestamps are not supported.
	 *
	 * As Solr requires PHP5 that should not be a big
	 * problem in practice, except for electronic
	 * publications that go back until earlier than 1901.
	 * It does not seem probable that such a situation
	 * could realistically arise with OJS.
	 *
	 * @param $timestamp int|string Unix timestamp or local ISO time.
	 * @return string ISO UTC timestamp
	 */
	function _convertDate($timestamp) {
		if (is_numeric($timestamp)) {
			// Assume that this is a unix timestamp.
			$timestamp = (integer) $timestamp;
		} else {
			// Assume that this is an ISO timestamp.
			$timestamp = strtotime($timestamp);
		}

		// Convert to UTC as understood by solr.
		return gmdate('Y-m-d\TH:i:s\Z', $timestamp);
	}

	/**
	 * Retrieve the number of indexed documents
	 * from a DIH response XML
	 * @param $result DOMXPath
	 * @return integer
	 */
	function _getDocumentsProcessed($result) {
		// Return the number of documents that were indexed.
		$nodeList = $result->query('//response/lst[@name="statusMessages"]/str[@name="Total Documents Processed"]');
		assert($nodeList->length == 1);
		$resultNode = $nodeList->item(0);
		assert(is_numeric($resultNode->textContent));
		return (integer)$resultNode->textContent;
	}

	/**
	 * Add a subquery to the search query and query parameters.
	 *
	 * @param $fieldList string A list of fields to be queried, separated by '|'.
	 * @param $searchPhrase string The search phrase to be added.
	 * @param $params array The existing query parameters.
	 */
	function _addSubquery($fieldList, $searchPhrase, $params) {
		// Get the list of fields to be queried.
		$fields = explode('|', $fieldList);

		// Expand the field list to all locales and formats.
		$expandedFields = array();
		foreach($fields as $field) {
			$expandedFields = array_merge($expandedFields, $this->_getLocalesAndFormats($field));
		}
		$fieldList = implode(' ', $expandedFields);

		// Determine a query parameter name for this field list.
		if (count($fields) == 1) {
			// If we have a single field in the field list then
			// use the field name as alias.
			$fieldAlias = array_pop($fields);
		} else {
			// Use a generic name for multi-field searches.
			$fieldAlias = 'multi';
		}
		$fieldAlias = "q.$fieldAlias";

		// Make sure that the alias is unique.
		$fieldSuffix = '';
		while (isset($params[$fieldAlias . $fieldSuffix])) {
			if (empty($fieldSuffix)) $fieldSuffix = 1;
			$fieldSuffix ++;
		}
		$fieldAlias = $fieldAlias . $fieldSuffix;

		// Construct a subquery.
		// NB: mm=1 is equivalent to implicit OR
		// This deviates from previous OJS practice, please see
		// http://pkp.sfu.ca/wiki/index.php/OJSdeSearchConcept#Query_Parser
		// for the rationale of this change.
		$subQuery = "+_query_:\"{!edismax mm=1 qf='$fieldList' v=\$$fieldAlias}\"";

		// Add the subquery to the query parameters.
		if (isset($params['q'])) {
			$params['q'] .= ' ' . $subQuery;
		} else {
			$params['q'] = $subQuery;
		}
		$params[$fieldAlias] = $searchPhrase;
		return $params;
	}
}

?>
