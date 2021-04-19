<?php
/**
 * @file plugins/importexport/native/filter/PKPNativeFilterHelper.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NativeFilterHelper
 * @ingroup plugins_importexport_native
 *
 * @brief Class that provides native import/export filter-related helper methods.
 */

class PKPNativeFilterHelper {
	/**
	 * Create and return an object covers node.
	 * @param $filter NativeExportFilter
	 * @param $doc DOMDocument
	 * @param $object Publication
	 * @return DOMElement?
	 */
	function createPublicationCoversNode($filter, $doc, $object) {
		$deployment = $filter->getDeployment();

		$context = $deployment->getContext();

		$coversNode = null;
		$coverImages = $object->getData('coverImage');
		if (!empty($coverImages)) {
			$coversNode = $doc->createElementNS($deployment->getNamespace(), 'covers');
			foreach ($coverImages as $locale => $coverImage) {
				$coverImageName = $coverImage['uploadName'];

				$coverNode = $doc->createElementNS($deployment->getNamespace(), 'cover');
				$coverNode->setAttribute('locale', $locale);
				$coverNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'cover_image', htmlspecialchars($coverImageName, ENT_COMPAT, 'UTF-8')));
				$coverNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'cover_image_alt_text', htmlspecialchars($coverImage['altText'], ENT_COMPAT, 'UTF-8')));

				import('classes.file.PublicFileManager');
				$publicFileManager = new PublicFileManager();

				$contextId = $context->getId();

				$filePath = $publicFileManager->getContextFilesPath($contextId) . '/' . $coverImageName;
				$embedNode = $doc->createElementNS($deployment->getNamespace(), 'embed', base64_encode(file_get_contents($filePath)));
				$embedNode->setAttribute('encoding', 'base64');
				$coverNode->appendChild($embedNode);
				$coversNode->appendChild($coverNode);
			}
		}
		return $coversNode;
	}

	/**
	 * Parse out the object covers.
	 * @param $filter NativeExportFilter
	 * @param $node DOMElement
	 * @param $object Publication
	 */
	function parsePublicationCovers($filter, $node, $object) {
		$deployment = $filter->getDeployment();

		$coverImages = array();

		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				switch ($n->tagName) {
					case 'cover':
						$coverImage = $this->parsePublicationCover($filter, $n, $object);
						$coverImages[key($coverImage)] = reset($coverImage);
						break;
					default:
						$deployment->addWarning(ASSOC_TYPE_PUBLICATION, $object->getId(), __('plugins.importexport.common.error.unknownElement', array('param' => $n->tagName)));
				}
			}
		}

		$object->setData('coverImage', $coverImages);
	}

	/**
	 * Parse out the cover and store it in the object.
	 * @param $filter NativeExportFilter
	 * @param $node DOMElement
	 * @param $object Publication
	 */
	function parsePublicationCover($filter, $node, $object) {
		$deployment = $filter->getDeployment();

		$context = $deployment->getContext();

		$locale = $node->getAttribute('locale');
		if (empty($locale)) $locale = $context->getPrimaryLocale();

		$coverImagelocale = array();
		$coverImage = array();

		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				switch ($n->tagName) {
					case 'cover_image':
						$coverImage['uploadName'] = $n->textContent;
						break;
					case 'cover_image_alt_text':
						$coverImage['altText'] = $n->textContent;
						break;
					case 'embed':
						import('classes.file.PublicFileManager');
						$publicFileManager = new PublicFileManager();
						$filePath = $publicFileManager->getContextFilesPath($context->getId()) . '/' . $coverImage['uploadName'];
						file_put_contents($filePath, base64_decode($n->textContent));
						break;
					default:
						$deployment->addWarning(ASSOC_TYPE_PUBLICATION, $object->getId(), __('plugins.importexport.common.error.unknownElement', array('param' => $n->tagName)));
				}
			}
		}

		$coverImagelocale[$locale] = $coverImage;

		return $coverImagelocale;
	}
}
