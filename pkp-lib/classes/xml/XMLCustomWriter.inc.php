<?php

/**
 * @file classes/xml/XMLCustomWriter.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class XMLCustomWriter
 * @ingroup xml
 *
 * @brief Wrapper class for writing XML documents using PHP 4.x or 5.x
 */


import ('lib.pkp.classes.xml.XMLNode');

class XMLCustomWriter {
	/**
	 * Create a new XML document.
	 * If $url is set, the DOCTYPE definition is treated as a PUBLIC
	 * definition; $dtd should contain the ID, and $url should contain the
	 * URL. Otherwise, $dtd should be the DTD name.
	 */
	static function &createDocument($type = null, $dtd = null, $url = null) {
		$version = '1.0';
		if (class_exists('DOMImplementation')) {
			// Use the new (PHP 5.x) DOM
			$impl = new DOMImplementation();
			// only generate a DOCTYPE if type is non-empty
			if ($type != '') {
				$domdtd = $impl->createDocumentType($type, isset($url)?$dtd:'', isset($url)?$url:$dtd);
				$doc = $impl->createDocument($version, '', $domdtd);
			} else {
				$doc = $impl->createDocument($version, '');
			}
			// ensure we are outputting UTF-8
			$doc->encoding = 'UTF-8';
		} else {
			// Use the XMLNode class
			$doc = new XMLNode();
			$doc->setAttribute('version', $version);
			if ($type !== null) $doc->setAttribute('type', $type);
			if ($dtd !== null) $doc->setAttribute('dtd', $dtd);
			if ($url !== null) $doc->setAttribute('url', $url);
		}
		return $doc;
	}

	static function &createElement(&$doc, $name) {
		if (is_callable(array($doc, 'createElement'))) $element = $doc->createElement($name);
		else $element = new XMLNode($name);

		return $element;
	}

	static function &createTextNode(&$doc, $value) {

		$value = Core::cleanVar($value);

		if (is_callable(array($doc, 'createTextNode'))) $element =& $doc->createTextNode($value);
		else {
			$element = new XMLNode();
			$element->setValue($value);
		}

		return $element;
	}

	static function &appendChild(&$parentNode, &$child) {
		if (is_callable(array($parentNode, 'appendChild'))) $node =& $parentNode->appendChild($child);
		else {
			$parentNode->addChild($child);
			$child->setParent($parentNode);
			$node =& $child;
		}

		return $node;
	}

	static function &getAttribute(&$node, $name) {
		return $node->getAttribute($name);
	}

	static function &hasAttribute(&$node, $name) {
		if (is_callable(array($node, 'hasAttribute'))) $value =& $node->hasAttribute($name);
		else {
			$attribute =& XMLCustomWriter::getAttribute($node, $name);
			$value = ($attribute !== null);
		}
		return $value;
	}

	static function setAttribute(&$node, $name, $value, $appendIfEmpty = true) {
		if (!$appendIfEmpty && $value == '') return;
		return $node->setAttribute($name, $value);
	}

	static function &getXML(&$doc) {
		if (is_callable(array($doc, 'saveXML'))) $xml =& $doc->saveXML();
		else {
			$xml = $doc->toXml();
		}
		return $xml;
	}

	static function printXML(&$doc) {
		if (is_callable(array($doc, 'saveXML'))) echo $doc->saveXML();
		else $doc->toXml(true);
	}

	static function &createChildWithText(&$doc, &$node, $name, $value, $appendIfEmpty = true) {
		$childNode = null;
		if ($appendIfEmpty || $value != '') {
			$childNode =& XMLCustomWriter::createElement($doc, $name);
			$textNode =& XMLCustomWriter::createTextNode($doc, $value);
			XMLCustomWriter::appendChild($childNode, $textNode);
			XMLCustomWriter::appendChild($node, $childNode);
		}
		return $childNode;
	}

	static function &createChildFromFile(&$doc, &$node, $name, $filename) {
		$fileManager = new FileManager();
		$contents = $fileManager->readFile($filename);
		if ($contents === false) {
			$nullVar = null;
			return $nullVar;
		}
	}
}

?>
