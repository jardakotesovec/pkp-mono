<?php

/**
 * @file plugins/importexport/native/filter/NativeXmlPublicationFilter.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NativeXmlPublicationFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Class that converts a Native XML document to a set of articles.
 */

import('lib.pkp.plugins.importexport.native.filter.NativeXmlPKPPublicationFilter');

class NativeXmlPublicationFilter extends NativeXmlPKPPublicationFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		parent::__construct($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.native.filter.NativeXmlPublicationFilter';
	}

	/**
	 * Get the method name for inserting a published submission.
	 * @return string
	 */
	function getPublishedSubmissionInsertMethod() {
		return 'insertObject';
	}

	/**
	 * Handle an Article import.
	 * The Article must have a valid section in order to be imported
	 * @param $node DOMElement
	 */
	function handleElement($node) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$sectionAbbrev = $node->getAttribute('section_ref');
		if ($sectionAbbrev !== '') {
			$sectionDao = DAORegistry::getDAO('SectionDAO'); /* @var $sectionDao SectionDAO */
			$section = $sectionDao->getByAbbrev($sectionAbbrev, $context->getId());
			if (!$section) {
				$deployment->addError(ASSOC_TYPE_SUBMISSION, NULL, __('plugins.importexport.native.error.unknownSection', array('param' => $sectionAbbrev)));
			} else {
				return parent::handleElement($node);
			}
		}
	}

	/**
	 * @see Filter::process()
	 * @param $document DOMDocument|string
	 * @return array Array of imported documents
	 */
	function &process(&$document) {
		$importedObjects =& parent::process($document);

		// Index imported content
		// $articleSearchIndex = Application::getSubmissionSearchIndex();
		// foreach ($importedObjects as $submission) {
		// 	assert(is_a($submission, 'Submission'));
		// 	$articleSearchIndex->submissionMetadataChanged($submission);
		// 	$articleSearchIndex->submissionFilesChanged($submission);
		// }
		// $articleSearchIndex->submissionChangesFinished();

		return $importedObjects;
	}

	/**
	 * Populate the submission object from the node, checking first for a valid section and published_date/issue relationship
	 * @param $publication Publication
	 * @param $node DOMElement
	 * @return Publication
	 */
	function populateObject($publication, $node) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$sectionAbbrev = $node->getAttribute('section_ref');
		if ($sectionAbbrev !== '') {
			$sectionDao = DAORegistry::getDAO('SectionDAO'); /* @var $sectionDao SectionDAO */
			$section = $sectionDao->getByAbbrev($sectionAbbrev, $context->getId());
			if (!$section) {
				$deployment->addError(ASSOC_TYPE_PUBLICATION, $publication->getId(), __('plugins.importexport.native.error.unknownSection', array('param' => $sectionAbbrev)));
			} else {
				$publication->setData('sectionId', $section->getId());
			}
		}
		// check if article is related to an issue, but has no published date
		$datePublished = $node->getAttribute('date_published');
		$issue = $deployment->getIssue();
		$issue_identification = $node->getElementsByTagName('issue_identification');
		if (!$datePublished && ($issue || $issue_identification->length)) {
			$titleNodes = $node->getElementsByTagName('title');
			$deployment->addError(ASSOC_TYPE_SUBMISSION, $publication->getId(), __('plugins.importexport.native.import.error.publishedDateMissing', array('articleTitle' => $titleNodes->item(0)->textContent)));
		}

		return parent::populateObject($publication, $node);
	}

	/**
	 * Handle an element whose parent is the submission element.
	 * @param $n DOMElement
	 * @param $publication Publication
	 */
	function handleChildElement($n, $publication) {
		switch ($n->tagName) {
			case 'article_galley':
				$this->parseArticleGalley($n, $publication);
				break;
			case 'issue_identification':
				// do nothing, because this is done in populatePublishedSubmission
				break;
			case 'pages':
				$publication->setData('pages', $n->textContent);
				break;
			case 'covers':
				import('plugins.importexport.native.filter.NativeFilterHelper');
				$nativeFilterHelper = new NativeFilterHelper();
				$nativeFilterHelper->parseCovers($this, $n, $publication, ASSOC_TYPE_PUBLICATION);
				break;
			default:
				parent::handleChildElement($n, $publication);
		}
	}

	/**
	 * Get the import filter for a given element.
	 * @param $elementName string Name of XML element
	 * @return Filter
	 */
	function getImportFilter($elementName) {
		$deployment = $this->getDeployment();
		$submission = $deployment->getSubmission();
		switch ($elementName) {
			case 'article_galley':
				$importClass='ArticleGalley';
				break;
			default:
				$importClass=null; // Suppress scrutinizer warn
				$deployment->addWarning(ASSOC_TYPE_SUBMISSION, $submission->getId(), __('plugins.importexport.common.error.unknownElement', array('param' => $elementName)));
		}
		// Caps on class name for consistency with imports, whose filter
		// group names are generated implicitly.
		$filterDao = DAORegistry::getDAO('FilterDAO'); /* @var $filterDao FilterDAO */
		$importFilters = $filterDao->getObjectsByGroup('native-xml=>' . $importClass);
		$importFilter = array_shift($importFilters);
		return $importFilter;
	}

	/**
	 * Parse an article galley and add it to the publication.
	 * @param $n DOMElement
	 * @param $publication Publication
	 */
	function parseArticleGalley($n, $publication) {
		$importFilter = $this->getImportFilter($n->tagName);
		assert(isset($importFilter)); // There should be a filter

		$importFilter->setDeployment($this->getDeployment());
		$articleGalleyDoc = new DOMDocument();
		$articleGalleyDoc->appendChild($articleGalleyDoc->importNode($n, true));
		return $importFilter->execute($articleGalleyDoc);
	}

	/**
	 * Class-specific methods for published publication.
	 * @param $publication Publication
	 * @param DOMElement $node
	 * @return Publication
	 */
	function populatePublishedPublication($publication, $node) {
		$deployment = $this->getDeployment();
		$issue = $deployment->getIssue();
		if (empty($issue)) {
			$issueIdentificationNodes = $node->getElementsByTagName('issue_identification');

			if ($issueIdentificationNodes->length != 1) {
				$titleNodes = $node->getElementsByTagName('title');
				$deployment->addError(ASSOC_TYPE_PUBLICATION, $publication->getId(), __('plugins.importexport.native.import.error.issueIdentificationMissing', array('articleTitle' => $titleNodes->item(0)->textContent)));
			} else {
				$issueIdentificationNode = $issueIdentificationNodes->item(0);
				$issue = $this->parseIssueIdentification($publication, $issueIdentificationNode);
			}
		}
		$publication->setSequence($node->getAttribute('seq'));
		$publication->setAccessStatus($node->getAttribute('access_status'));
		if ($issue) $publication->setIssueId($issue->getId());
		
		return $publication;
	}

	/**
	 * Get the issue from the given identification.
	 * @param $node DOMElement
	 * @return Issue
	 */
	function parseIssueIdentification($publication, $node) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		// $submission = $deployment->getSubmission();
		$vol = $num = $year = null;
		$titles = array();
		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				switch ($n->tagName) {
					case 'volume':
						$vol = $n->textContent;
						break;
					case 'number':
						$num = $n->textContent;
						break;
					case 'year':
						$year = $n->textContent;
						break;
					case 'title':
						list($locale, $value) = $this->parseLocalizedContent($n);
						if (empty($locale)) $locale = $context->getPrimaryLocale();
						$titles[$locale] = $value;
						break;
					default:
						$deployment->addWarning(ASSOC_TYPE_PUBLICATION, $publication->getId(), __('plugins.importexport.common.error.unknownElement', array('param' => $n->tagName)));
				}
			}
		}
		$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$issue = null;
		$issuesByIdentification = $issueDao->getIssuesByIdentification($context->getId(), $vol, $num, $year, $titles);
		if ($issuesByIdentification->getCount() != 1) {
			$deployment->addError(ASSOC_TYPE_PUBLICATION, $publication->getId(), __('plugins.importexport.native.import.error.issueIdentificationMatch', array('issueIdentification' => $node->ownerDocument->saveXML($node))));
		} else {
			$issue = $issuesByIdentification->next();
		}
		
		return $issue;
	}
}
