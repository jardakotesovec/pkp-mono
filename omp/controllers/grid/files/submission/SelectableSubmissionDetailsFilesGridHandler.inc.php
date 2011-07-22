<?php

/**
 * @file controllers/grid/files/submission/SelectableSubmissionDetailsFilesGridHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SelectableSubmissionDetailsFilesGridHandler
 * @ingroup controllers_grid_files_submission
 *
 * @brief Handle submission file grid requests in the editor's 'promote submission' modal.
 */

import('controllers.grid.files.fileList.SelectableFileListGridHandler');

class SelectableSubmissionDetailsFilesGridHandler extends SelectableFileListGridHandler {
	/**
	 * Constructor
	 */
	function SelectableSubmissionDetailsFilesGridHandler() {
		import('controllers.grid.files.SubmissionFilesGridDataProvider');
		// Pass in null stageId to be set in initialize from request var.
		parent::SelectableFileListGridHandler(
			new SubmissionFilesGridDataProvider(MONOGRAPH_FILE_SUBMISSION),
			null,
			FILE_GRID_ADD|FILE_GRID_DOWNLOAD_ALL
		);

		$this->addRoleAssignment(
			array(ROLE_ID_SERIES_EDITOR, ROLE_ID_PRESS_MANAGER),
			array('fetchGrid', 'fetchRow', 'downloadAllFiles')
		);

		// Set the grid title.
		$this->setTitle('submission.submit.submissionFiles');
	}
}

?>
