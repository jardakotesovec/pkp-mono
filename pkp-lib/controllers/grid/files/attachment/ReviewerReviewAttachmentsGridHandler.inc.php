<?php
/**
 * @filecontrollers/grid/files/attachment/ReviewerReviewAttachmentsGridHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewerReviewAttachmentsGridHandler
 * @ingroup controllers_grid_files_attachment
 *
 * @brief Handle file grid requests.
 */

use PKP\controllers\grid\files\FilesGridCapabilities;
use PKP\submission\SubmissionFile;

import('lib.pkp.controllers.grid.files.fileList.FileListGridHandler');

class ReviewerReviewAttachmentsGridHandler extends FileListGridHandler
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
            FilesGridCapabilities::FILE_GRID_ADD | FilesGridCapabilities::FILE_GRID_DELETE | FilesGridCapabilities::FILE_GRID_EDIT
        );

        $this->addRoleAssignment(
            [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_REVIEWER],
            [
                'fetchGrid', 'fetchRow'
            ]
        );

        // Set the grid title.
        $this->setTitle('reviewer.submission.reviewerFiles');
    }

    /**
     * @copydoc FileListGridHandler::initialize()
     *
     * @param null|mixed $args
     */
    public function initialize($request, $args = null)
    {
        // Watch for flag from including template to warn about the
        // review already being complete. If so, remove some capabilities.
        $capabilities = $this->getCapabilities();
        if ($request->getUserVar('reviewIsClosed')) {
            $capabilities->setCanAdd(false);
            $capabilities->setCanDelete(false);
        }

        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_REVIEWER);

        parent::initialize($request, $args);
    }
}
