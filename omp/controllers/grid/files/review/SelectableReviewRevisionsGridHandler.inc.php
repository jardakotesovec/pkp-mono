<?php

/**
 * @file controllers/grid/files/review/SelectableReviewRevisionsGridHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SelectableReviewRevisionsGridHandler
 * @ingroup controllers_grid_files_review
 *
 * @brief Display the file revisions authors have uploaded in a selectable grid.
 *   Used for selecting files to send to external review or copyediting.
 */

import('controllers.grid.files.fileList.SelectableFileListGridHandler');

class SelectableReviewRevisionsGridHandler extends SelectableFileListGridHandler {
	/**
	 * Constructor
	 */
	function SelectableReviewRevisionsGridHandler() {
		import('controllers.grid.files.review.ReviewRevisionsGridDataProvider');
		$dataProvider = new ReviewRevisionsGridDataProvider();
		parent::SelectableFileListGridHandler(
			$dataProvider,
			WORKFLOW_STAGE_ID_INTERNAL_REVIEW,
			FILE_GRID_DELETE
		);

		$this->addRoleAssignment(
			array(ROLE_ID_SERIES_EDITOR, ROLE_ID_PRESS_MANAGER),
			array('fetchGrid', 'fetchRow', 'downloadAllFiles')
		);

		// Set the grid title.
		$this->setTitle('editor.monograph.revisions');
	}
}

?>
