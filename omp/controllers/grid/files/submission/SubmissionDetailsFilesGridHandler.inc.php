<?php

/**
 * @file controllers/grid/files/submission/SubmissionDetailsFilesGridHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionDetailsFilesGridHandler
 * @ingroup controllers_grid_files_submission
 *
 * @brief Base handler for the submission stage grids.
 */

// Import the grid layout.
import('controllers.grid.files.fileList.FileListGridHandler');

class SubmissionDetailsFilesGridHandler extends FileListGridHandler {
	/**
	 * Constructor
	 * @param $canAdd boolean Whether to show the 'add files' grid action
	 */
	function SubmissionDetailsFilesGridHandler($canAdd = true, $isSelectable = false, $canDownloadAll = false) {
		import('controllers.grid.files.SubmissionFilesGridDataProvider');
		$dataProvider = new SubmissionFilesGridDataProvider(MONOGRAPH_FILE_SUBMISSION);
		parent::FileListGridHandler($dataProvider, $canAdd, $isSelectable, $canDownloadAll);
		$this->addRoleAssignment(
				array(ROLE_ID_AUTHOR, ROLE_ID_SERIES_EDITOR, ROLE_ID_PRESS_MANAGER),
				array('fetchGrid', 'fetchRow', 'downloadAllFiles'));
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize(&$request, $additionalActionArgs = array()) {
		// Basic grid configuration
		$this->setTitle('submission.submit.submissionFiles');

		parent::initialize($request);
	}
}