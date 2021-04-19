<?php

/**
 * @file plugins/importexport/native/filter/NativeImportFilter.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NativeImportFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Base class that converts a Native XML document to a DataObject
 */

import('lib.pkp.classes.plugins.importexport.PKPImportExportFilter');

class NativeImportFilter extends PKPImportExportFilter {
	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::process()
	 * @param $document DOMDocument|string
	 * @return array Array of imported documents
	 */
	function &process(&$document) {
		// If necessary, convert $document to a DOMDocument.
		if (is_string($document)) {
			$xmlString = $document;
			$document = new DOMDocument();
			$document->loadXml($xmlString);
		}
		assert(is_a($document, 'DOMDocument'));

		$deployment = $this->getDeployment();
		$importedObjects = array();
		if ($document->documentElement->tagName == $this->getPluralElementName()) {
			// Multiple element (plural) import
			for ($n = $document->documentElement->firstChild; $n !== null; $n=$n->nextSibling) {
				if (!is_a($n, 'DOMElement')) continue;
				$object = $this->handleElement($n);
				if ($object) {
					$importedObjects[] = $object;
				}
			}
		} else {
			assert($document->documentElement->tagName == $this->getSingularElementName());

			// Single element (singular) import
			$object = $this->handleElement($document->documentElement);
			if ($object) {
				$importedObjects[] = $object;
			}
		}

		return $importedObjects;
	}

	/**
	 * Return the plural element name
	 * @return string
	 */
	function getPluralElementName() {
		assert(false); // Must be overridden by subclasses
	}

	/**
	 * Get the singular element name
	 * @return string
	 */
	function getSingularElementName() {
		assert(false); // Must be overridden by subclasses
	}

	/**
	 * Handle a singular element import
	 * @param $node DOMElement
	 */
	function handleElement($node) {
		assert(false); // Must be overridden by subclasses
	}

	/**
	 * Parse a localized element
	 * @param $element DOMElement
	 * @return array Array("locale_KEY", "Localized Text")
	 */
	function parseLocalizedContent($element) {
		return array($element->getAttribute('locale'), $element->textContent);
	}

	/**
	 * Import node to a given parent node
	 * @param $n DOMElement The parent node
	 * @param $filter string The filter to execute it's import function
	 */
	function importWithXMLNode($n, $filter = null) {
		$doc = new DOMDocument();
		$doc->appendChild($doc->importNode($n, true));
		$importFilter = null;
		if ($filter) {
			$importFilter = PKPImportExportFilter::getFilter($filter, $this->getDeployment());
		} elseif (method_exists($this, 'getImportFilter')) {
			$importFilter = $this->getImportFilter($n->tagName);
		} else {
			throw new Exception(__('filter.import.error.couldNotImportNode'));
		}

		return $importFilter->execute($doc);
	}
}


