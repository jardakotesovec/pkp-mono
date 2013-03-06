<?php

/**
 * @file controllers/grid/files/submission/SelectableFairCopyFilesGridHandler.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SelectableFairCopyFilesGridHandler
 * @ingroup controllers_grid_files_submission
 *
 * @brief Handle submission file grid requests in the editor's 'promote submission' modal.
 */

import('controllers.grid.files.fileList.SelectableFileListGridHandler');

class SelectableFairCopyFilesGridHandler extends SelectableFileListGridHandler {
	/**
	 * Constructor
	 */
	function SelectableFairCopyFilesGridHandler() {
		import('controllers.grid.files.SubmissionFilesGridDataProvider');
		// Pass in null stageId to be set in initialize from request var.
		parent::SelectableFileListGridHandler(
			new SubmissionFilesGridDataProvider(SUBMISSION_FILE_FAIR_COPY),
			null,
			FILE_GRID_ADD|FILE_GRID_VIEW_NOTES
		);

		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SERIES_EDITOR, ROLE_ID_ASSISTANT),
			array('fetchGrid', 'fetchRow')
		);

		// Set the grid title.
		$this->setTitle('submission.fairCopy');

		$this->setInstructions('editor.monograph.selectFairCopy');
	}
}

?>
