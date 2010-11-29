<?php

/**
 * @file classes/file/MonographFileManager.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MonographFileManager
 * @ingroup file
 *
 * @brief Static helper class for monograph file management tasks.
 *
 * Monograph directory structure:
 * [monograph id]/note
 * [monograph id]/public
 * [monograph id]/submission
 * [monograph id]/submission/original
 * [monograph id]/submission/review
 * [monograph id]/submission/editor
 * [monograph id]/submission/copyedit
 * [monograph id]/submission/layout
 * [monograph id]/attachment
 */


import('lib.pkp.classes.file.FileManager');

class MonographFileManager extends FileManager {
	/**
	 * Constructor.
	 */
	function MonographFileManager() {
		parent::FileManager();
	}


	//
	// Public methods
	//
	/**
	 * Upload a monograph file.
	 * @param $monographId integer
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileStage int monograph file workflow stage
	 * @param $revisedFileId int
	 * @param $fileGenre int (e.g. Manusciprt, Appendix, etc.)
	 * @return MonographFile
	 */
	function uploadMonographFile($monographId, $fileName, $fileStage, $revisedFileId = null, $fileGenre = null) {
		return MonographFileManager::_handleUpload($monographId, $fileName, $fileStage, $revisedFileId, $fileGenre);
	}

	/**
	 * Upload a file to the review file folder.
	 * @param $monographId integer
	 * @param $fileName string the name of the file used in the POST form
	 * @param $revisedFileId int
	 * @return MonographFile
	 */
	function uploadReviewFile($monographId, $fileName, $revisedFileId = null, $reviewId = null) {
		$assocType = $reviewId ? ASSOC_TYPE_REVIEW_ASSIGNMENT : null;
		return MonographFileManager::_handleUpload($monographId, $fileName, MONOGRAPH_FILE_REVIEW, $revisedFileId, null, $reviewId, $assocType);
	}

	/**
	 * Upload a copyedited file to the copyedit file folder.
	 * @param $monographId integer
	 * @param $fileName string the name of the file used in the POST form
	 * @param $revisedFileId int
	 * @return MonographFile
	 */
	function uploadCopyeditResponseFile($monographId, $fileName, $revisedFileId = null) {
		return MonographFileManager::_handleUpload($monographId, $fileName, MONOGRAPH_FILE_COPYEDIT_RESPONSE, $revisedFileId);
	}

	/**
	 * Read a file's contents.
	 * @param $fileId integer
	 * @param $revision integer
	 * @param $output boolean output the file's contents instead of returning a string
	 * @return boolean
	 */
	function readFile($fileId, $revision = null, $output = false) {
		$monographFile =& MonographFileManager::_getFile($fileId, $revision);
		if (isset($monographFile)) {
			return parent::readFile($monographFile->getFilePath(), $output);
		} else {
			return false;
		}
	}

	/**
	 * Delete a file by ID.
	 * If no revision is specified, all revisions of the file are deleted.
	 * @param $fileId int
	 * @param $revision int (optional)
	 * @return int number of files removed
	 */
	function deleteFile($fileId, $revision = null) {
		// Identify the files to be deleted.
		$monographFileDao =& DAORegistry::getDAO('MonographFileDAO'); /* @var $monographFileDao MonographFileDAO */
		$monographFiles = array();
		if (isset($revision)) {
			// Delete only a single revision of a file.
			$monographFileRevision =& $monographFileDao->getMonographFile($fileId, $revision);
			if (isset($monographFileRevision)) {
				$monographFiles[] = $monographFileRevision;
			}
		} else {
			// Delete all revisions of a file.
			$monographFiles =& $monographFileDao->getMonographFileRevisions($fileId, null, false);
		}

		// Delete the files on the file system.
		foreach ($monographFiles as $monographFile) {
			parent::deleteFile($monographFile->getFilePath());
		}

		// Delete the files in the database.
		$monographFileDao->deleteMonographFileById($fileId, $revision);

		// Return the number of deleted files.
		return count($monographFiles);
	}

	/**
	 * Delete the entire tree of files belonging to a monograph.
	 * @param $monographId integer
	 */
	function deleteMonographTree($monographId) {
		parent::rmtree(MonographFileManager::_getFilesDir($monographId));
	}

	/**
	 * Download a file.
	 * @param $fileId int the file id of the file to download
	 * @param $revision int the revision of the file to download
	 * @param $inline print file as inline instead of attachment, optional
	 * @return boolean
	 */
	function downloadFile($monographId, $fileId, $revision = null, $inline = false) {
		$returner = false;
		$monographFile =& MonographFileManager::_getFile($fileId, $revision);
		if (isset($monographFile)) {
			// Make sure that the file belongs to the monograph.
			if ($monographFile->getMonographId() != $monographId) fatalError('Invalid file id!');

			// Mark the file as viewed by this user.
			$sessionManager =& SessionManager::getManager();
			$session =& $sessionManager->getUserSession();
			$user =& $session->getUser();
			if (is_a($user, 'User')) {
				$viewsDao =& DAORegistry::getDAO('ViewsDAO');
				$viewsDao->recordView(ASSOC_TYPE_MONOGRAPH_FILE, $fileId, $user->getId());
			}

			// Send the file to the user.
			$filePath = $monographFile->getFilePath();
			$mediaType = $monographFile->getFileType();
			$returner = parent::downloadFile($filePath, $mediaType, $inline);
		}

		return $returner;
	}

	/**
	 * Download all monograph files as an archive
	 * @param $monographId integer
	 * @param $monographFiles ArrayItemIterator
	 * @return boolean
	 */
	function downloadFilesArchive($monographId, &$monographFiles = null) {
		if(!isset($monographFiles)) {
			$monographFileDao =& DAORegistry::getDAO('MonographFileDAO');
			$monographFiles =& $monographFileDao->getByMonographId($monographId);
		}

		$filesDir = MonographFileManager::_getFilesDir($monographId);
		$filePaths = array();
		while ($monographFile =& $monographFiles->next()) { /* @var $monographFile MonographFile */
			// Remove absolute path so the archive doesn't include it (otherwise all files are organized by absolute path)
			$filePath = str_replace($filesDir, '', $monographFile->getFilePath());
			// Add files to be archived to array
			$filePaths[] = escapeshellarg($filePath);
		}

		// Create the archive and download the file
		$archivePath = $filesDir . "monograph_" . $monographId . "_files.tar.gz";
		$tarCommand = "tar czf ". $archivePath . " -C \"" . $filesDir . "\" " . implode(" ", $filePaths);
		exec($tarCommand);
		if (file_exists($archivePath)) {
			parent::downloadFile($archivePath);
			return true;
		} else return false;
	}

	/**
	 * View a file inline (variant of downloadFile).
	 * @param $monographId integer
	 * @param $fileId integer
	 * @param $revision integer
	 * @see MonographFileManager::downloadFile
	 */
	function viewFile($monographId, $fileId, $revision = null) {
		MonographFileManager::downloadFile($monographId, $fileId, $revision, true);
	}

	/**
	 * Return type path associated with a type code.
	 * @param $type string
	 * @return string
	 */
	function typeToPath($type) {
		switch ($type) {
			case MONOGRAPH_FILE_PUBLIC: return 'public';
			case MONOGRAPH_FILE_SUBMISSION: return 'submission';
			case MONOGRAPH_FILE_NOTE: return 'note';
			case MONOGRAPH_FILE_REVIEW: return 'submission/review';
			case MONOGRAPH_FILE_FINAL: return 'submission/final';
			case MONOGRAPH_FILE_FAIR_COPY: return 'submission/fairCopy';
			case MONOGRAPH_FILE_EDITOR: return 'submission/editor';
			case MONOGRAPH_FILE_COPYEDIT: return 'submission/copyedit';
			case MONOGRAPH_FILE_PRODUCTION: return 'submission/production';
			case MONOGRAPH_FILE_GALLEY: return 'submission/galleys';
			case MONOGRAPH_FILE_LAYOUT: return 'submission/layout';
			case MONOGRAPH_FILE_ATTACHMENT: default: return 'attachment';
		}
	}

	/**
	 * Copy a temporary file to a monograph file.
	 * @param $monographId integer
	 * @param $temporaryFile MonographFile
	 * @param $type integer
	 * @param $assocId integer
	 * @param $assocType integer
	 * @return integer the file ID (false if upload failed)
	 */
	function temporaryFileToMonographFile($monographId, &$temporaryFile, $type, $assocId, $assocType) {
		// Instantiate and pre-populate the new target monograph file.
		$monographFile =& MonographFileManager::_instantiateMonographFile($monographId, $type, null, null, $assocId, $assocType);

		// Transfer data from the temporary file to the monograph file.
		$monographFile->setFileType($temporaryFile->getFileType());
		$monographFile->setOriginalFileName($temporaryFile->getOriginalFileName());

		// Copy the temporary file to it's final destination and persist
		// its metadata to the database.
		$monographFile =& MonographFileManager::_persistFile($temporaryFile->getFilePath(), $monographFile, true);

		// Return the new file id.
		return $monographFile->getFileId();
	}


	//
	// Private helper methods
	//
	/**
	 * Get the files directory.
	 * @param $monographId integer
	 * @return string
	 */
	function _getFilesDir($monographId) {
		static $filesDir;
		if (empty($filesDir)) {
			$monographDao =& DAORegistry::getDAO('MonographDAO'); /* @var $monographDao MonographDAO */
			$monograph =& $monographDao->getMonograph($monographId);
			assert(is_a($monograph, 'Monograph'));
			$pressId = $monograph->getPressId();
			$filesDir = $monograph->getFilePath();
		}
		return $filesDir;
	}

	/**
	 * Upload the file and add it to the database.
	 * @param $monographId integer
	 * @param $fileName string index into the $_FILES array
	 * @param $type int identifying type (i.e. MONOGRAPH_FILE_*)
	 * @param $revisedFileId int ID of an existing file to revise
	 * @param $fileGenre int foreign key into monograph_file_types table (e.g. manuscript, etc.)
	 * @param $assocType int
	 * @param $assocId int
	 * @return MonographFile the uploaded monograph file or null if an error occured.
	 */
	function &_handleUpload($monographId, $fileName, $type, $revisedFileId = null, $fileGenre = null, $assocId = null, $assocType = null) {
		$nullVar = null;

		// Ensure that the file has been correctly uploaded to the server.
		if (!MonographFileManager::uploadedFileExists($fileName)) return $nullVar;

		// Instantiate and pre-populate a new monograph file object.
		$monographFile = MonographFileManager::_instantiateMonographFile($monographId, $type, $revisedFileId, $fileGenre, $assocId, $assocType);
		if (is_null($monographFile)) return $nullVar;

		// Retrieve file information from the uploaded file.
		assert(isset($_FILES[$fileName]));
		$monographFile->setFileType($_FILES[$fileName]['type']);
		$monographFile->setOriginalFileName(MonographFileManager::truncateFileName($_FILES[$fileName]['name']));

		// Set the uploader's userGroupId
		// FIXME: Setting a temporary user group here until #6231 is fixed.
		// This is necessary so that we can already remove the user-group
		// attribute from the session.
		$monographFile->setUserGroupId(1);

		// Copy the uploaded file to its final destination and
		// persist its meta-data to the database.
		return MonographFileManager::_persistFile($fileName, $monographFile);
	}

	/**
	 * Routine to instantiate and pre-populate a new monograph file.
	 * @param $monographId integer
	 * @param $type integer
	 * @param $revisedFileId integer
	 * @param $fileGenre integer
	 * @param $assocId integer
	 * @param $assocType integer
	 * @return MonographFile returns the instantiated monograph file or null if an error occurs.
	 */
	function &_instantiateMonographFile($monographId, $type, $revisedFileId, $fileGenre, $assocId, $assocType) {
		// Instantiate a new monograph file.
		$monographFile = new MonographFile();
		$monographFile->setMonographId($monographId);

		// Do we create a new file or a new revision of an existing file?
		if ($revisedFileId) {
			$monographFileDao =& DAORegistry::getDAO('MonographFileDAO'); /* @var $monographFileDao MonographFileDAO */

			// Retrieve the current revision of the revised file.
			$latestRevision = $monographFileDao->getLatestRevisionNumber($revisedFileId);

			// Retrieve the revised file.
			$revisedFile =& $monographFileDao->getMonographFile($revisedFileId, $latestRevision);
			if (!is_a($revisedFile, 'MonographFile')) return false;

			// Create a new revision of the file with the existing file id.
			$monographFile->setFileId($revisedFileId);
			$monographFile->setRevision($latestRevision+1);

			// Make sure that the monograph of the revised file is
			// the same as that of the uploaded file.
			assert($revisedFile->getMonographId() == $monographId);
			$nullVar = null;
			if ($revisedFile->getMonographId() != $monographId) return $nullVar;

			// Copy the file workflow stage.
			assert(is_null($type) || $type == $revisedFile->getType());
			$type = (int)$revisedFile->getType();

			// Copy the file genre.
			assert(is_null($fileGenre) || $fileGenre == $revisedFile->getMonographFileTypeId());
			$fileGenre = (int)$revisedFile->getMonographFileTypeId();

			// Copy the assoc type.
			assert(is_null($assocType) || $assocType == $revisedFile->getAssocType());
			$assocType = (int)$revisedFile->getAssocType();

			// Copy the assoc id.
			assert(is_null($assocId) || $assocId == $revisedFile->getAssocId());
			$assocId = (int)$revisedFile->getAssocId();
		} else {
			// Create the first revision of a new file.
			$monographFile->setRevision(1);
		}

		// Set a preliminary file name and file size.
		$monographFile->setFileName('unknown');
		$monographFile->setFileSize(0);

		// Set the file workflow stage.
		$monographFile->setType($type);

		// Set the file genre (if given).
		if(isset($fileGenre)) {
			$monographFile->setMonographFileTypeId($fileGenre);
		}

		// Set modification dates to the current system date.
		$monographFile->setDateUploaded(Core::getCurrentDate());
		$monographFile->setDateModified(Core::getCurrentDate());

		// Is the monograph file associated to another entity?
		if(isset($assocId)) {
			assert(isset($assocType));
			$monographFile->setAssocType($assocType);
			$monographFile->setAssocId($assocId);
		}

		// Return the pre-populated monograph file.
		return $monographFile;
	}

	/**
	 * Copies the file to it's final destination and persists
	 * the file meta-data to the database.
	 * @param $sourceFile string the path to the file to be copied
	 * @param $monographFile MonographFile the file metadata
	 * @param $copyOnly boolean set to true if the file has not been uploaded
	 *  but already exists on the file system.
	 * @return MonographFile
	 */
	function &_persistFile($sourceFile, $monographFile, $copyOnly = false) {
		// Persist the file meta-data (without the file name) and generate a file id.
		$monographFileDao =& DAORegistry::getDAO('MonographFileDAO');
		if (!$monographFileDao->insertMonographFile($monographFile)) return false;

		// Generate and set a file name (requires the monograph id
		// that we generated when inserting the monograph data).
		MonographFileManager::_generateAndPopulateFileName($monographFile);

		// Determine the final destination of the file (requires
		// the name we just generated).
		$targetFile = $monographFile->getFilePath();

		// If the "copy only" flag is set then copy the file from its
		// current place to the target destination. Otherwise upload
		// the file to the target folder.
		if (!(($copyOnly && MonographFileManager::copyFile($sourceFile, $targetFile))
				|| MonographFileManager::uploadFile($sourceFile, $targetFile))) {
			// If the copy/upload operation fails then remove
			// the already inserted meta-data.
			$monographFileDao->deleteMonographFile($monographFile);
			return false;
		}

		// Determine and set the file size of the target file.
		$monographFile->setFileSize(filesize($targetFile));

		// Update the monograph with the file name and file size.
		$monographFileDao->updateMonographFile($monographFile);

		// Return the file.
		return $monographFile;
	}

	/**
	 * Generate a unique filename for a monograph file. Sets the filename
	 * field in the monographFile to the generated value.
	 * @param $monographFile MonographFile the monograph to generate a filename for
	 */
	function _generateAndPopulateFileName(&$monographFile) {
		// If the file has a file genre set then start the
		// file name with human readable genre information.
		$fileGenre = $monographFile->getMonographFileTypeId();
		if ($fileGenre) {
			$primaryLocale = Locale::getPrimaryLocale();
			$monographFileTypeDao =& DAORegistry::getDAO('MonographFileTypeDAO'); /* @var $monographFileTypeDao MonographFileTypeDAO */
			$fileGenre =& $monographFileTypeDao->getById($fileGenre);
			assert(is_a($fileGenre, 'MonographFileType'));
			$fileName = $fileGenre->getDesignation($primaryLocale).'_'.date('Ymd').'-'.$fileGenre->getName($primaryLocale).'-';
		}

		// Make the file name unique across all files and file revisions.
		$extension = MonographFileManager::parseFileExtension($monographFile->getOriginalFileName());
		$fileName .= $monographFile->getMonographId().'-'.$monographFile->getFileId().'-'.$monographFile->getRevision().'-'.$monographFile->getType().'.'.$extension;

		// Populate the monograph file with the generated file name.
		$monographFile->setFileName($fileName);
	}

	/**
	 * Internal helper method to retrieve file
	 * information by file ID.
	 * @param $fileId integer
	 * @param $revision integer
	 * @return MonographFile
	 */
	function &_getFile($fileId, $revision = null) {
		$monographFileDao =& DAORegistry::getDAO('MonographFileDAO'); /* @var $monographFileDao MonographFileDAO */
		$monographFile =& $monographFileDao->getMonographFile($fileId, $revision);
		return $monographFile;
	}
}

?>