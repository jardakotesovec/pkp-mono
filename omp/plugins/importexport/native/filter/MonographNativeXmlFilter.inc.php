<?php

/**
 * @file plugins/importexport/native/filter/MonographNativeXmlFilter.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class MonographNativeXmlFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Class that converts a Monograph to a Native XML document.
 */

import('lib.pkp.plugins.importexport.native.filter.SubmissionNativeXmlFilter');

class MonographNativeXmlFilter extends SubmissionNativeXmlFilter {
	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.native.filter.MonographNativeXmlFilter';
	}


	//
	// Implement abstract methods from SubmissionNativeXmlFilter
	//
	/**
	 * Get the representation export filter group name
	 * @return string
	 */
	function getRepresentationExportFilterGroupName() {
		return 'publication-format=>native-xml';
	}

	//
	// Submission conversion functions
	//
	/**
	 * Create and return a submission node.
	 * @param $doc DOMDocument
	 * @param $submission Submission
	 * @return DOMElement
	 */
	function createSubmissionNode($doc, $submission) {
		$submissionNode = parent::createSubmissionNode($doc, $submission);

		$submissionNode->setAttribute('work_type', $submission->getData('workType'));
		
		return $submissionNode;
	}
}


