<?php

/**
 * @file controllers/grid/files/submissionFiles/SubmissionDetailsFilesGridHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionDetailsFilesGridHandler
 * @ingroup controllers_grid_files_submissionFiles
 *
 * @brief Handle submission file grid requests on the submission details page.
 * The submission author and all press/editor roles have access to this grid.
 */

// import grid base classes
import('lib.pkp.classes.controllers.grid.GridHandler');

// import submission files grid specific classes
import('controllers.grid.files.submissionFiles.SubmissionFilesGridHandler');

class SubmissionDetailsFilesGridHandler extends SubmissionFilesGridHandler {
	/**
	 * Constructor
	 */
	function SubmissionDetailsFilesGridHandler() {
		parent::SubmissionFilesGridHandler();
		$this->addRoleAssignment(
				array(ROLE_ID_AUTHOR, ROLE_ID_SERIES_EDITOR, ROLE_ID_PRESS_MANAGER),
				array('fetchGrid', 'addFile', 'addRevision', 'editFile', 'displayFileForm', 'uploadFile',
				'confirmRevision', 'deleteFile', 'editMetadata', 'saveMetadata', 'finishFileSubmission',
				'returnFileRow', 'downloadFile'));
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @see PKPHandler::authorize()
	 */
	function authorize(&$request, $args, $roleAssignments) {
		import('classes.security.authorization.OmpSubmissionAccessPolicy');
		$this->addPolicy(new OmpSubmissionAccessPolicy($request, $args, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize(&$request) {
		// Basic grid configuration
		$this->setTitle('submission.submit.submissionFiles');

		// Load monograph files.
		$this->loadMonographFiles();

		// Check wether to display the 'add file' grid action.
		$canAdd = true;
		if($request->getUserVar('canAdd') == "false") {
			$canAdd = false;
		}

		$cellProvider = new SubmissionFilesGridCellProvider();
		parent::initialize($request, $cellProvider, $canAdd);

		$this->addColumn(new GridColumn('fileType',	'common.fileType', null, 'controllers/grid/gridCell.tpl', $cellProvider));
		$this->addColumn(new GridColumn('type', 'common.type', null, 'controllers/grid/gridCell.tpl', $cellProvider));
	}
}