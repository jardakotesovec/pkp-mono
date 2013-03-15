<?php

/**
 * @file controllers/wizard/fileUpload/form/SubmissionFilesUploadForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFilesUploadForm
 * @ingroup controllers_wizard_fileUpload_form
 *
 * @brief Form for adding/editing a submission file
 */


import('lib.pkp.controllers.wizard.fileUpload.form.PKPSubmissionFilesUploadForm');

class SubmissionFilesUploadForm extends PKPSubmissionFilesUploadForm {
	/**
	 * Constructor.
	 * @param $request Request
	 * @param $submissionId integer
	 * @param $uploaderRoles array
	 * @param $stageId integer One of the WORKFLOW_STAGE_ID_* constants.
	 * @param $fileStage integer
	 * @param $revisionOnly boolean
	 * @param $stageId integer
	 * @param $reviewRound ReviewRound
	 * @param $revisedFileId integer
	 */
	function SubmissionFilesUploadForm(&$request, $submissionId, $stageId, $uploaderRoles, $fileStage,
			$revisionOnly = false, $reviewRound = null, $revisedFileId = null, $assocType = null, $assocId = null) {
		parent::PKPSubmissionFilesUploadForm(
			$request, $submissionId, $stageId, $uploaderRoles, $fileStage, $revisionOnly, $reviewRound, $revisedFileId, $assocType, $assocId
		);
	}

	//
	// Private helper methods
	//
	/**
	 * Upload the file in an app-specific manner.
	 * @param PKPRequest $request
	 * @param PKPUser $user
	 * @param $uploaderUserGroupId int
	 * @param $revisedFileId int
	 * @param $fileGenre int
	 * @param $assocType int
	 * @param $assocType int
	 * @return SubmissionFile
	 */
	function _uploadFile($request, $user, $uploaderUserGroupId, $revisedFileId, $fileGenre, $assocType, $assocId) {
		$press =& $request->getPress();
		import('classes.file.MonographFileManager');
		$monographFileManager = new MonographFileManager($press->getId(), $this->getData('submissionId'));
		$fileStage = $this->getData('fileStage');
		$submissionFile = $monographFileManager->uploadMonographFile(
			'uploadedFile', $fileStage,
			$user->getId(), $uploaderUserGroupId, $revisedFileId, $fileGenre, $assocType, $assocId
		);

		return $submissionFile;
	}

	/**
	 * Log the upload event.
	 * Must be overridden in subclasses.
	 * @param PKPRequest $request
	 * @param PKPUser $user
	 * @param SubmissionFile $submissionFile
	 * @param int $assocType
	 * @param int $revisedFileId
	 * @param int $fileStage
	 */
	function _logEvent($request, $user, $submissionFile, $assocType, $revisedFileId, $fileStage) {
		// log the upload event.
		import('classes.log.MonographFileLog');
		import('classes.log.MonographFileEventLogEntry'); // constants
		$localeKey = $revisedFileId ? 'submission.event.revisionUploaded' : 'submission.event.fileUploaded';
		$assocType = $revisedFileId ? MONOGRAPH_LOG_FILE_REVISION_UPLOAD : MONOGRAPH_LOG_FILE_UPLOAD;
		MonographFileLog::logEvent($request, $submissionFile, $assocType, $localeKey, array('fileStage' => $fileStage, 'revisedFileId' => $revisedFileId, 'fileId' => $submissionFile->getFileId(), 'fileRevision' => $submissionFile->getRevision(), 'originalFileName' => $submissionFile->getOriginalFileName(), 'submissionId' => $this->getData('submissionId'), 'username' => $user->getUsername()));
	}
}

?>
