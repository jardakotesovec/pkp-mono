<?php

/**
 * @file controllers/grid/files/proof/ProofFilesGridHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ProofFilesGridHandler
 * @ingroup controllers_grid_files_proof
 *
 * @brief Subclass of file editor/auditor grid for proof files.
 */

// import grid signoff files grid base classes
import('controllers.grid.files.signoff.SignoffFilesGridHandler');

// Import monograph file class which contains the MONOGRAPH_FILE_* constants.
import('classes.monograph.MonographFile');

// Import MONOGRAPH_EMAIL_* constants.
import('classes.mail.MonographMailTemplate');

class ProofFilesGridHandler extends SignoffFilesGridHandler {
	/**
	 * Constructor
	 */
	function ProofFilesGridHandler() {
		parent::SignoffFilesGridHandler(
			WORKFLOW_STAGE_ID_PRODUCTION,
			MONOGRAPH_FILE_PROOF,
			'SIGNOFF_PROOFING',
			MONOGRAPH_EMAIL_PROOFREAD_NOTIFY_AUTHOR,
			ASSOC_TYPE_PUBLICATION_FORMAT
		);
	}

	//
	// Implement template methods from PKPHandler
	//
	/**
	 * Configure the grid
	 * @param PKPRequest $request
	 */
	function initialize(&$request) {
		parent::initialize($request);

		// Basic grid configuration
		$this->setId('proofFiles-' . $this->getAssocId());
		$this->setTitle('editor.monograph.proofReading');

		// Rename the editor column to press signoff
		$pressAssistantColumn =& $this->getColumn('editor');
		$pressAssistantColumn->setTitle('editor.pressSignoff');
	}

	/**
	 * @see PKPHandler::authorize()
	 */
	function authorize(&$request, &$args, $roleAssignments) {
		if (parent::authorize($request, $args, $roleAssignments)) {
			$publicationFormatId = (int) $request->getUserVar('publicationFormatId');
			$publicationFormatDao =& DAORegistry::getDAO('PublicationFormatDAO');
			$monograph =& $this->getMonograph();
			$publicationFormat =& $publicationFormatDao->getById($publicationFormatId, $monograph->getId());

			$this->setAssocId($publicationFormat->getId());
			return true;
		}
		return false;
	}

	/**
	 * @see SignoffFilesGridHandler::getRowInstance()
	 */
	function &getRowInstance() {
		$row =& parent::getRowInstance();
		$row->setRequestArgs($this->getRequestArgs());
		return $row;
	}

	/**
	 * @see GridHandler::getRequestArgs()
	 */
	function getRequestArgs() {
		return array_merge(
			parent::getRequestArgs(),
			array('publicationFormatId' => $this->getAssocId())
		);
	}
}

?>
