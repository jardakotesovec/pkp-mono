<?php

/**
 * @file controllers/grid/files/submissionFiles/form/SubmissionFilesUploadForm.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFilesUploadForm
 * @ingroup controllers_grid_files_submissionFiles_form
 *
 * @brief Form for adding/editing a submission file
 */


import('lib.pkp.classes.form.Form');

define('SUBMISSION_MIN_SIMILARITY_OF_REVISION', 70);

class SubmissionFilesUploadForm extends Form {
	/** The id of the file being edited */
	var $_fileId;

	/** The id of the monograph being edited */
	var $_monographId;

	/** The stage of the file being uploaded (i.e., the 'type') */
	var $_fileStage;

	/** Whether we are uploading a revision */
	var $_isRevision;

	/**
	 * Constructor.
	 */
	function SubmissionFilesUploadForm($fileId = null, $monographId, $fileStage = MONOGRAPH_FILE_SUBMISSION, $isRevision = false) {
		// Initialize class.
		$this->_fileId = $fileId;
		$this->_monographId = $monographId;
		$this->_fileStage = $fileStage;
		$this->_isRevision = $isRevision;

		parent::Form('controllers/grid/files/submissionFiles/form/fileForm.tpl');

		$this->addCheck(new FormValidatorPost($this));
	}


	//
	// Setters and Getters
	//
	/**
	 * Get the file id.
	 * @return integer
	 */
	function getFileId() {
		return $this->_fileId;
	}

	/**
	 * Get the monograph id.
	 * @return integer
	 */
	function getMonographId() {
		return $this->_monographId;
	}

	/**
	 * Get the file stage.
	 * @return integer
	 */
	function getFileStage() {
		return $this->_fileStage;
	}

	/**
	 * Is this a revision?
	 * @return boolean
	 */
	function isRevision() {
		return $this->_isRevision;
	}


	//
	// Implement template methods from Form
	//
	/**
	 * @see Form::initData()
	 */
	function initData($args, &$request) {
		$this->setData('monographId', $this->getMonographId());
		if ($this->getFileId()) {
			$this->setData('fileId', $this->getFileId());
			$monographFileDao =& DAORegistry::getDAO('MonographFileDAO');
			$monographFile =& $monographFileDao->getMonographFile($this->getFileId());
			$this->setData('monographFileName', $monographFile->getOriginalFileName());
			$this->setData('currentFileType', $monographFile->getMonographFileTypeId());
		}

		$context =& $request->getContext();
		$monographFileTypeDao =& DAORegistry::getDAO('MonographFileTypeDAO');
		$monographFileTypes = $monographFileTypeDao->getEnabledByPressId($context->getId());

		$monographFileTypeList = array();
		while($monographFileType =& $monographFileTypes->next()){
			$monographFileTypeId = $monographFileType->getId();
			$monographFileTypeList[$monographFileTypeId] = $monographFileType->getLocalizedName();
			unset($monographFileType);
		}

		// Assign monograph files to template to display in revision drop-down menu
		$monographFileDao =& DAORegistry::getDAO('MonographFileDAO');
		$monographFiles =& $monographFileDao->getByMonographId($this->getMonographId());
		$monographFileOptions = array();
		foreach ($monographFiles as $monographFile) {
			$fileName = $monographFile->getLocalizedName() != '' ? $monographFile->getLocalizedName() : Locale::translate('common.untitled');
			if ($monographFile->getRevision() > 1) $fileName .= ' (' . $monographFile->getRevision() . ')'; // Add revision number to label
			$monographFileOptions[$monographFile->getFileId()] = $fileName;
		}
		$this->setData('monographFileOptions', $monographFileOptions);

		$this->setData('monographFileTypes', $monographFileTypeList);
		$this->setData('fileStage', $this->getFileStage());
		$this->setData('isRevision', $this->isRevision());
	}

	/**
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('gridId', 'fileType'));
	}


	//
	// Public helper methods
	//
	/**
	 * Check if the uploaded file has a similar name to existing files (i.e., a possible revision)
	 * @param $monographId integer
	 * @return int submission file id
	 */
	function checkForRevision($monographId) {
		import('lib.pkp.classes.file.FileManager');
		if (FileManager::uploadedFileExists('submissionFile')) {
			$fileName = FileManager::getUploadedFileName('submissionFile');

			// Check similarity of filename against existing filenames.
			return $this->_checkForSimilarFilenames($fileName, $monographId);
		}

		return null;
	}

	/**
	 * Upload the submission file
	 * @param $args array
	 * @param $request PKPRequest
	 * @return int submission file id
	 */
	function uploadFile($args, &$request) {
		$monographId = $this->getMonographId();
		$fileId = $this->getFileId();
		$fileStage = $this->getFileStage();
		assert(!empty($fileStage));
		$monographFileTypeId = (int)$this->getData('fileType');

		if($fileStage == MONOGRAPH_FILE_COPYEDIT) {
			$uploadedFile = 'copyeditingFile';
			// The user is uploading a copyedited version of an existing file
			// Load the existing file to get the monographFileTypeId
		} else {
			$uploadedFile = 'submissionFile';
		}

		import('classes.file.MonographFileManager');
		if (MonographFileManager::uploadedFileExists($uploadedFile)) {
			$submissionFileId = MonographFileManager::uploadMonographFile($monographId, $uploadedFile, $fileStage, $fileId, $monographFileTypeId);

			if (!empty($monographFileTypeId)) {
				$monographFileTypeDao =& DAORegistry::getDAO('MonographFileTypeDAO');
				$fileType =& $monographFileTypeDao->getById($monographFileTypeId);

				// If we're uploading artwork, put an entry in the monograph_artwork_files table
				if ($fileType->getCategory() == MONOGRAPH_FILE_CATEGORY_ARTWORK && isset($submissionFileId)) {
					$artworkFileDao =& DAORegistry::getDAO('ArtworkFileDAO');
					$artworkFile =& $artworkFileDao->newDataObject();
					$artworkFile->setFileId($submissionFileId);
					$artworkFile->setMonographId($monographId);
					$artworkFileDao->insertObject($artworkFile);
				}
			}
		}

		return isset($submissionFileId) ? $submissionFileId : false;
	}


	//
	// Private helper methods
	//
	/**
	 * Check the filename against existing files in the submission
	 * The number of matching characters is used to determine the return value.
	 * @param $fileName string
	 * @param $monographId int
	 * @return int the submission file id of the best matching file or null
	 *  if none matched.
	 */
	function _checkForSimilarFilenames($fileName, $monographId) {
		$criterion = SUBMISSION_MIN_SIMILARITY_OF_REVISION;

		// Retrieve the monograph files of this monograph.
		$monographFileDao =& DAORegistry::getDAO('MonographFileDAO');
		$monographFiles =& $monographFileDao->getByMonographId($monographId);

		// Find out whether one of the files matches the given file name.
		$matchedFileId = null;
		foreach ($monographFiles as $monographFile) {
			$matchedChars = similar_text($fileName, $monographFile->getOriginalFileName(), &$p);
			if($p > $criterion) {
				$matchedFileId = $monographFile->getFileId();
				$criterion = $p; // Reset criterion to this comparison's precentage to see if there are better matches
			}
		}

		// Return the file that we found similar.
		return $matchedFileId;
	}
}

?>
