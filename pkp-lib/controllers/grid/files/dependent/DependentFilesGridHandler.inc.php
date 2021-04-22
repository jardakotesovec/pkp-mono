<?php

/**
 * @file controllers/grid/files/dependent/DependentFilesGridHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DependentFilesGridHandler
 * @ingroup controllers_grid_files_dependent
 *
 * @brief Handle dependent files that are associated with a submissions's display
 *  (galleys or production formats, for example).
 * The submission author and all context/editor roles have access to this grid.
 */

use PKP\submission\SubmissionFile;

import('lib.pkp.controllers.grid.files.fileList.FileListGridHandler');

class DependentFilesGridHandler extends FileListGridHandler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // import app-specific grid data provider for access policies.
        $request = Application::get()->getRequest();
        $submissionFileId = $request->getUserVar('submissionFileId'); // authorized in authorize() method.
        import('lib.pkp.controllers.grid.files.dependent.DependentFilesGridDataProvider');
        parent::__construct(
            new DependentFilesGridDataProvider($submissionFileId),
            $request->getUserVar('stageId'),
            FILE_GRID_ADD | FILE_GRID_DELETE | FILE_GRID_VIEW_NOTES | FILE_GRID_EDIT
        );

        $this->addRoleAssignment(
            [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_AUTHOR],
            ['fetchGrid', 'fetchRow']
        );

        $this->setTitle('submission.submit.dependentFiles');
    }

    /**
     * @copydoc SubmissionFilesGridHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        import('lib.pkp.classes.security.authorization.SubmissionFileAccessPolicy');
        $this->addPolicy(new SubmissionFileAccessPolicy($request, $args, $roleAssignments, SubmissionFile::SUBMISSION_FILE_ACCESS_MODIFY, (int) $args['submissionFileId']));

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * @copydoc GridHandler::getRequestArgs()
     */
    public function getRequestArgs()
    {
        $submissionFile = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION_FILE);
        return array_merge(
            parent::getRequestArgs(),
            ['submissionFileId' => $submissionFile->getId()]
        );
    }
}
