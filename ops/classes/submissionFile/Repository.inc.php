<?php
/**
 * @file classes/submissionFile/Repository.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class submission
 *
 * @brief A repository to find and manage submissions.
 */

namespace APP\submissionFile;

use APP\core\Application;
use APP\core\Request;
use PKP\db\DAORegistry;
use PKP\plugins\HookRegistry;
use PKP\search\SubmissionSearch;
use PKP\security\Role;
use PKP\services\PKPSchemaService;
use PKP\submissionFile\Repository as BaseRepository;

class Repository extends BaseRepository
{
    /** @var DAO $dao */
    public $dao;

    public function __construct(
        Request $request
    ) {
        parent::__construct($request);

        $this->dao = new DAO(new PKPSchemaService());

        HookRegistry::register('SubmissionFile::delete::before', [$this, 'deleteSubmissionFile']);
        HookRegistry::register('SubmissionFile::assignedFileStages', [$this, 'modifyAssignedFileStages']);
    }

    /**
     * Delete related objects when a submission file is deleted
     *
     * @param array $args [
     *      @option SubmissionFile
     * ]
     */
    public function deleteSubmissionFile(array $args): void
    {
        $submissionFile = $args[0];

        // Remove galley associations and update search index
        if ($submissionFile->getData('assocType') == ASSOC_TYPE_REPRESENTATION) {
            $galleyDao = DAORegistry::getDAO('PreprintGalleyDAO'); /* @var $galleyDao PreprintGalleyDAO */
            $galley = $galleyDao->getById($submissionFile->getData('assocId'));
            if ($galley && $galley->getData('submissionFileId') == $submissionFile->getId()) {
                $galley->_data['submissionFileId'] = null; // Work around pkp/pkp-lib#5740
                $galleyDao->updateObject($galley);
            }
            $preprintSearchIndex = Application::getSubmissionSearchIndex();
            $preprintSearchIndex->deleteTextIndex($submissionFile->getData('submissionId'), SubmissionSearch::SUBMISSION_SEARCH_GALLEY_FILE, $submissionFile->getId());
        }
    }

    /**
     * Allow authors to upload to galley file stages
     *
     * @param string $hookName
     * @param array $args [
     * ]
     */
    public function modifyAssignedFileStages($hookName, $args)
    {
        $allowedFileStages = & $args[0];
        $stageAssignments = $args[1];

        if (array_key_exists(WORKFLOW_STAGE_ID_PRODUCTION, $stageAssignments)
                && in_array(Role::ROLE_ID_AUTHOR, $stageAssignments[WORKFLOW_STAGE_ID_PRODUCTION])) {
            $allowedFileStages[] = SubmissionFile::SUBMISSION_FILE_PROOF;
        }
    }
}
