<?php

/**
 * @file controllers/grid/files/attachment/EditorReviewAttachmentsGridHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class EditorReviewAttachmentsGridHandler
 * @ingroup controllers_grid_files_attachment
 *
 * @brief Editor's view of the Review Attachments Grid.
 */

use PKP\submission\SubmissionFile;

import('lib.pkp.controllers.grid.files.fileList.FileListGridHandler');

class EditorReviewAttachmentsGridHandler extends FileListGridHandler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        import('lib.pkp.controllers.grid.files.attachment.ReviewerReviewAttachmentGridDataProvider');
        // Pass in null stageId to be set in initialize from request var.
        parent::__construct(
            new ReviewerReviewAttachmentGridDataProvider(SubmissionFile::SUBMISSION_FILE_REVIEW_ATTACHMENT),
            null,
            FILE_GRID_DELETE | FILE_GRID_ADD | FILE_GRID_VIEW_NOTES | FILE_GRID_EDIT
        );

        $this->addRoleAssignment(
            [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT],
            [
                'fetchGrid', 'fetchRow'
            ]
        );
    }
}
