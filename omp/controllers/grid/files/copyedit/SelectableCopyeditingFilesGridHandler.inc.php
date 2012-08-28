<?php

/**
 * @file controllers/grid/files/copyedit/SelectableCopyeditingFilesGridHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SelectableCopyeditingFilesGridHandler
 * @ingroup controllers_grid_files_copyedit
 *
 * @brief Handle copyediting files grid requests to promote to production stage.
 */

import('controllers.grid.files.fileList.SelectableFileListGridHandler');

class SelectableCopyeditingFilesGridHandler extends SelectableFileListGridHandler {
	/**
	 * Constructor
	 */
	function SelectableCopyeditingFilesGridHandler() {
		import('controllers.grid.files.SubmissionFilesGridDataProvider');
		// Pass in null stageId to be set in initialize from request var.
		parent::SelectableFileListGridHandler(
			new SubmissionFilesGridDataProvider(MONOGRAPH_FILE_COPYEDIT, true),
			null,
			FILE_GRID_VIEW_NOTES
		);

		$this->addRoleAssignment(
			array(ROLE_ID_PRESS_MANAGER, ROLE_ID_SERIES_EDITOR, ROLE_ID_PRESS_ASSISTANT),
			array('fetchGrid', 'fetchRow')
		);

		// Set the grid title.
		$this->setTitle('submission.copyediting');

		$this->setInstructions('editor.monograph.selectFairCopy');
	}
}

?>
