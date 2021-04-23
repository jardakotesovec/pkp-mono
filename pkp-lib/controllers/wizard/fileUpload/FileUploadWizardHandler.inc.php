<?php

/**
 * @file controllers/wizard/fileUpload/FileUploadWizardHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FileUploadWizardHandler
 * @ingroup controllers_wizard_fileUpload
 *
 * @brief A controller that handles basic server-side
 *  operations of the file upload wizard.
 */

// Import the base handler.
import('classes.handler.Handler');

use APP\template\TemplateManager;
use PKP\core\JSONMessage;

use PKP\submission\SubmissionFile;

class FileUploadWizardHandler extends Handler
{
    /** @var integer */
    public $_fileStage;

    /** @var array */
    public $_uploaderRoles;

    /** @var boolean */
    public $_revisionOnly;

    /** @var int */
    public $_reviewRound;

    /** @var integer */
    public $_revisedFileId;

    /** @var integer */
    public $_assocType;

    /** @var integer */
    public $_assocId;

    /** @var integer */
    public $_queryId;


    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_AUTHOR, ROLE_ID_REVIEWER, ROLE_ID_ASSISTANT],
            [
                'startWizard', 'displayFileUploadForm',
                'uploadFile',
                'editMetadata',
                'finishFileSubmission'
            ]
        );
    }


    //
    // Implement template methods from PKPHandler
    //
    public function authorize($request, &$args, $roleAssignments)
    {
        // We validate file stage outside a policy because
        // we don't need to validate in another places.
        $fileStage = (int) $request->getUserVar('fileStage');
        if ($fileStage) {
            $fileStages = Services::get('submissionFile')->getFileStages();
            if (!in_array($fileStage, $fileStages)) {
                return false;
            }
        }

        // Validate file ids. We have two cases where we might have a file id.
        // CASE 1: user is uploading a revision to a file, the revised file id
        // will need validation.
        $revisedFileId = (int)$request->getUserVar('revisedFileId');
        // CASE 2: user already have uploaded a file (and it's editing the metadata),
        // we will need to validate the uploaded file id.
        $submissionFileId = (int)$request->getUserVar('submissionFileId');
        // Get the right one to validate.
        $submissionFileIdToValidate = null;
        if ($revisedFileId && !$submissionFileId) {
            $submissionFileIdToValidate = $revisedFileId;
        } elseif ($submissionFileId && !$revisedFileId) {
            $submissionFileIdToValidate = $submissionFileId;
        } elseif ($revisedFileId && $submissionFileId) {
            // Those two cases will not happen at the same time.
            return false;
        }

        // Allow access to modify a specific file
        if ($submissionFileIdToValidate) {
            import('lib.pkp.classes.security.authorization.SubmissionFileAccessPolicy');
            $this->addPolicy(new SubmissionFileAccessPolicy($request, $args, $roleAssignments, SUBMISSION_FILE_ACCESS_MODIFY, $submissionFileIdToValidate));

        // Allow uploading to review attachments
        } elseif ($fileStage === SubmissionFile::SUBMISSION_FILE_REVIEW_ATTACHMENT) {
            $assocType = (int) $request->getUserVar('assocType');
            $assocId = (int) $request->getUserVar('assocId');
            $stageId = (int) $request->getUserVar('stageId');
            if (empty($assocType) || $assocType !== ASSOC_TYPE_REVIEW_ASSIGNMENT || empty($assocId)) {
                return false;
            }

            $stageId = (int) $request->getUserVar('stageId');
            import('lib.pkp.classes.security.authorization.ReviewStageAccessPolicy');
            $this->addPolicy(new ReviewStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', $stageId));
            import('lib.pkp.classes.security.authorization.internal.ReviewRoundRequiredPolicy');
            $this->addPolicy(new ReviewRoundRequiredPolicy($request, $args));
            import('lib.pkp.classes.security.authorization.ReviewAssignmentFileWritePolicy');
            $this->addPolicy(new ReviewAssignmentFileWritePolicy($request, $assocId));

        // Allow uploading to a note
        } elseif ($fileStage === SubmissionFile::SUBMISSION_FILE_QUERY) {
            $assocType = (int) $request->getUserVar('assocType');
            $assocId = (int) $request->getUserVar('assocId');
            $stageId = (int) $request->getUserVar('stageId');
            if (empty($assocType) || $assocType !== ASSOC_TYPE_NOTE || empty($assocId)) {
                return false;
            }

            import('lib.pkp.classes.security.authorization.QueryAccessPolicy');
            $this->addPolicy(new QueryAccessPolicy($request, $args, $roleAssignments, $stageId));
            import('lib.pkp.classes.security.authorization.NoteAccessPolicy');
            $this->addPolicy(new NoteAccessPolicy($request, $assocId, NOTE_ACCESS_WRITE));

        // Allow uploading a dependent file to another file
        } elseif ($fileStage === SubmissionFile::SUBMISSION_FILE_DEPENDENT) {
            $assocType = (int) $request->getUserVar('assocType');
            $assocId = (int) $request->getUserVar('assocId');
            if (empty($assocType) || $assocType !== ASSOC_TYPE_SUBMISSION_FILE || empty($assocId)) {
                return false;
            }

            import('lib.pkp.classes.security.authorization.SubmissionFileAccessPolicy');
            $this->addPolicy(new SubmissionFileAccessPolicy($request, $args, $roleAssignments, SUBMISSION_FILE_ACCESS_MODIFY, $assocId));

        // Allow uploading to other file stages in the workflow
        } else {
            $stageId = (int) $request->getUserVar('stageId');
            $assocType = (int) $request->getUserVar('assocType');
            $assocId = (int) $request->getUserVar('assocId');
            import('lib.pkp.classes.security.authorization.WorkflowStageAccessPolicy');
            $this->addPolicy(new WorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', $stageId));

            AppLocale::requireComponents(LOCALE_COMPONENT_PKP_API, LOCALE_COMPONENT_APP_API);
            import('lib.pkp.classes.security.authorization.SubmissionFileAccessPolicy'); // SUBMISSION_FILE_ACCESS_MODIFY
            import('lib.pkp.classes.security.authorization.internal.SubmissionFileStageAccessPolicy');
            $this->addPolicy(new SubmissionFileStageAccessPolicy($fileStage, SUBMISSION_FILE_ACCESS_MODIFY, 'api.submissionFiles.403.unauthorizedFileStageIdWrite'));

            // Additional checks before uploading to a review file stage
            if (in_array($fileStage, [
                SubmissionFile::SUBMISSION_FILE_REVIEW_REVISION,
                SubmissionFile::SUBMISSION_FILE_REVIEW_FILE,
                SubmissionFile::SUBMISSION_FILE_INTERNAL_REVIEW_REVISION,
                SubmissionFile::SUBMISSION_FILE_INTERNAL_REVIEW_FILE,
                SubmissionFile::SUBMISSION_FILE_ATTACHMENT
            ]) || $assocType === ASSOC_TYPE_REVIEW_ROUND) {
                import('lib.pkp.classes.security.authorization.internal.ReviewRoundRequiredPolicy');
                $this->addPolicy(new ReviewRoundRequiredPolicy($request, $args));
            }

            // Additional checks before uploading to a representation
            if ($fileStage === SubmissionFile::SUBMISSION_FILE_PROOF || $assocType === ASSOC_TYPE_REPRESENTATION) {
                if (empty($assocType) || $assocType !== ASSOC_TYPE_REPRESENTATION || empty($assocId)) {
                    return false;
                }
                import('lib.pkp.classes.security.authorization.internal.RepresentationUploadAccessPolicy');
                $this->addPolicy(new RepresentationUploadAccessPolicy($request, $args, $assocId));
            }
        }

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * @copydoc PKPHandler::initialize()
     */
    public function initialize($request)
    {
        parent::initialize($request);
        // Configure the wizard with the authorized submission and file stage.
        // Validated in authorize.
        $this->_fileStage = (int)$request->getUserVar('fileStage');

        // Set the uploader roles (if given).
        $uploaderRoles = $request->getUserVar('uploaderRoles');
        if (!empty($uploaderRoles)) {
            $this->_uploaderRoles = [];
            $uploaderRoles = explode('-', $uploaderRoles);
            foreach ($uploaderRoles as $uploaderRole) {
                if (!is_numeric($uploaderRole)) {
                    fatalError('Invalid uploader role!');
                }
                $this->_uploaderRoles[] = (int)$uploaderRole;
            }
        }

        // Do we allow revisions only?
        $this->_revisionOnly = (bool)$request->getUserVar('revisionOnly');
        $reviewRound = $this->getReviewRound();
        $this->_assocType = $request->getUserVar('assocType') ? (int)$request->getUserVar('assocType') : null;
        $this->_assocId = $request->getUserVar('assocId') ? (int)$request->getUserVar('assocId') : null;
        $this->_queryId = $request->getUserVar('queryId') ? (int) $request->getUserVar('queryId') : null;

        // The revised file will be non-null if we revise a single existing file.
        if ($this->getRevisionOnly() && $request->getUserVar('revisedFileId')) {
            // Validated in authorize.
            $this->_revisedFileId = (int)$request->getUserVar('revisedFileId');
        }

        // Load translations.
        AppLocale::requireComponents(
            LOCALE_COMPONENT_APP_SUBMISSION,
            LOCALE_COMPONENT_PKP_SUBMISSION,
            LOCALE_COMPONENT_PKP_COMMON,
            LOCALE_COMPONENT_APP_COMMON
        );
    }


    //
    // Getters and Setters
    //
    /**
     * The submission to which we upload files.
     *
     * @return Submission
     */
    public function getSubmission()
    {
        return $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
    }


    /**
     * Get the authorized workflow stage.
     *
     * @return integer One of the WORKFLOW_STAGE_ID_* constants.
     */
    public function getStageId()
    {
        return $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
    }

    /**
     * Get the workflow stage file storage that
     * we upload files to. One of the SubmissionFile::SUBMISSION_FILE_*
     * constants.
     *
     * @return integer
     */
    public function getFileStage()
    {
        return $this->_fileStage;
    }

    /**
     * Get the uploader roles.
     *
     * @return array
     */
    public function getUploaderRoles()
    {
        return $this->_uploaderRoles;
    }

    /**
     * Does this uploader only allow revisions and no new files?
     *
     * @return boolean
     */
    public function getRevisionOnly()
    {
        return $this->_revisionOnly;
    }

    /**
     * Get review round object.
     *
     * @return ReviewRound
     */
    public function getReviewRound()
    {
        return $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ROUND);
    }

    /**
     * Get the id of the file to be revised (if any).
     *
     * @return integer
     */
    public function getRevisedFileId()
    {
        return $this->_revisedFileId;
    }

    /**
     * Get the assoc type (if any)
     *
     * @return integer
     */
    public function getAssocType()
    {
        return $this->_assocType;
    }

    /**
     * Get the assoc id (if any)
     *
     * @return integer
     */
    public function getAssocId()
    {
        return $this->_assocId;
    }

    //
    // Public handler methods
    //
    /**
     * Displays the file upload wizard.
     *
     * @param $args array
     * @param $request Request
     *
     * @return JSONMessage JSON object
     */
    public function startWizard($args, $request)
    {
        $templateMgr = TemplateManager::getManager($request);
        $reviewRound = $this->getReviewRound();
        $templateMgr->assign([
            'submissionId' => $this->getSubmission()->getId(),
            'stageId' => $this->getStageId(),
            'uploaderRoles' => implode('-', (array) $this->getUploaderRoles()),
            'fileStage' => $this->getFileStage(),
            'isReviewer' => $request->getUserVar('isReviewer'),
            'revisionOnly' => $this->getRevisionOnly(),
            'reviewRoundId' => is_a($reviewRound, 'ReviewRound') ? $reviewRound->getId() : null,
            'revisedFileId' => $this->getRevisedFileId(),
            'assocType' => $this->getAssocType(),
            'assocId' => $this->getAssocId(),
            'dependentFilesOnly' => $request->getUserVar('dependentFilesOnly'),
            'queryId' => $this->_queryId,
        ]);
        return $templateMgr->fetchJson('controllers/wizard/fileUpload/fileUploadWizard.tpl');
    }

    /**
     * Render the file upload form in its initial state.
     *
     * @param $args array
     * @param $request Request
     *
     * @return JSONMessage JSON object
     */
    public function displayFileUploadForm($args, $request)
    {
        // Instantiate, configure and initialize the form.
        import('lib.pkp.controllers.wizard.fileUpload.form.SubmissionFilesUploadForm');
        $submission = $this->getSubmission();
        $fileForm = new SubmissionFilesUploadForm(
            $request,
            $submission->getId(),
            $this->getStageId(),
            $this->getUploaderRoles(),
            $this->getFileStage(),
            $this->getRevisionOnly(),
            $this->getReviewRound(),
            $this->getRevisedFileId(),
            $this->getAssocType(),
            $this->getAssocId(),
            $this->_queryId
        );
        $fileForm->initData();

        // Render the form.
        return new JSONMessage(true, $fileForm->fetch($request));
    }

    /**
     * Upload a file and render the modified upload wizard.
     *
     * @param $args array
     * @param $request Request
     *
     * @return JSONMessage JSON object
     */
    public function uploadFile($args, $request)
    {
        // Instantiate the file upload form.
        $submission = $this->getSubmission();
        import('lib.pkp.controllers.wizard.fileUpload.form.SubmissionFilesUploadForm');
        $uploadForm = new SubmissionFilesUploadForm(
            $request,
            $submission->getId(),
            $this->getStageId(),
            null,
            $this->getFileStage(),
            $this->getRevisionOnly(),
            $this->getReviewRound(),
            null,
            $this->getAssocType(),
            $this->getAssocId(),
            $this->_queryId
        );
        $uploadForm->readInputData();

        // Validate the form and upload the file.
        if (!$uploadForm->validate()) {
            return new JSONMessage(true, $uploadForm->fetch($request));
        }

        $uploadedFile = $uploadForm->execute(); /** @var SubmissionFile $uploadedFile */
        if (!is_a($uploadedFile, 'SubmissionFile')) {
            return new JSONMessage(false, __('common.uploadFailed'));
        }

        // Retrieve file info to be used in a JSON response.
        $uploadedFileInfo = $this->_getUploadedFileInfo($uploadedFile);
        $reviewRound = $this->getReviewRound();

        // Advance to the next step (i.e. meta-data editing).
        return new JSONMessage(true, '', '0', $uploadedFileInfo);
    }

    /**
     * Edit the metadata of the latest revision of
     * the requested submission file.
     *
     * @param $args array
     * @param $request Request
     *
     * @return JSONMessage JSON object
     */
    public function editMetadata($args, $request)
    {
        $submissionFile = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION_FILE);
        import('lib.pkp.controllers.wizard.fileUpload.form.SubmissionFilesMetadataForm');
        $form = new SubmissionFilesMetadataForm($submissionFile, $this->getStageId(), $this->getReviewRound());
        $form->initData();
        return new JSONMessage(true, $form->fetch($request));
    }

    /**
     * Display the final tab of the modal
     *
     * @param $args array
     * @param $request Request
     *
     * @return JSONMessage JSON object
     */
    public function finishFileSubmission($args, $request)
    {
        $submission = $this->getSubmission();

        // Validation not req'd -- just generating a JSON update message.
        $fileId = (int)$request->getUserVar('fileId');

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('submissionId', $submission->getId());
        $templateMgr->assign('fileId', $fileId);
        if (isset($args['fileStage'])) {
            $templateMgr->assign('fileStage', $args['fileStage']);
        }

        return $templateMgr->fetchJson('controllers/wizard/fileUpload/form/fileSubmissionComplete.tpl');
    }


    //
    // Private helper methods
    //

    /**
     * Helper function: check if the only difference between $a and $b
     * is numeric. Used to exclude well-named but nearly identical file
     * names from the revision detection pile (e.g. "Chapter 1" and
     * "Chapter 2")
     *
     * @param $a string
     * @param $b string
     */
    public function _onlyNumbersDiffer($a, $b)
    {
        if ($a == $b) {
            return false;
        }

        $pattern = '/([^0-9]*)([0-9]*)([^0-9]*)/';
        $aMatchCount = preg_match_all($pattern, $a, $aMatches, PREG_SET_ORDER);
        $bMatchCount = preg_match_all($pattern, $b, $bMatches, PREG_SET_ORDER);
        if ($aMatchCount != $bMatchCount || $aMatchCount == 0) {
            return false;
        }

        // Check each match. If the 1st and 3rd (text) parts all match
        // then only numbers differ in the two supplied strings.
        for ($i = 0; $i < count($aMatches); $i++) {
            if ($aMatches[$i][1] != $bMatches[$i][1]) {
                return false;
            }
            if ($aMatches[$i][3] != $bMatches[$i][3]) {
                return false;
            }
        }

        // No counterexamples were found. Only numbers differ.
        return true;
    }

    /**
     * Create an array that describes an uploaded file which can
     * be used in a JSON response.
     *
     * @param SubmissionFile $uploadedFile
     *
     * @return array
     */
    public function _getUploadedFileInfo($uploadedFile)
    {
        return [
            'uploadedFile' => [
                'id' => $uploadedFile->getId(),
                'fileId' => $uploadedFile->getData('fileId'),
                'name' => $uploadedFile->getLocalizedData('name'),
                'genreId' => $uploadedFile->getGenreId(),
            ]
        ];
    }
}
