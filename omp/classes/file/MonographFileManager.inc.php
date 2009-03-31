<?php

/**
 * @file classes/file/MonographFileManager.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MonographFileManager
 * @ingroup file
 *
 * @brief Class defining operations for monograph file management.
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
 * [monograph id]/supp
 * [monograph id]/attachment
 */

// $Id$


import('file.FileManager');

/* File type suffixes */
define('MONOGRAPH_FILE_SUBMISSION',	'SM');
define('MONOGRAPH_FILE_REVIEW',		'RV');
define('MONOGRAPH_FILE_EDITOR',		'ED');
define('MONOGRAPH_FILE_COPYEDIT',	'CE');
define('MONOGRAPH_FILE_LAYOUT',		'LE');
define('MONOGRAPH_FILE_PUBLIC',		'PB');
define('MONOGRAPH_FILE_SUPP',		'SP');
define('MONOGRAPH_FILE_NOTE',		'NT');
define('MONOGRAPH_FILE_ATTACHMENT',	'AT');
define('MONOGRAPH_FILE_PROSPECTUS',	'PR');
define('MONOGRAPH_FILE_ARTWORK',	'ART');

class MonographFileManager extends FileManager {

	/** @var string the path to location of the files */
	var $filesDir;

	/** @var int the ID of the associated monograph */
	var $monographId;

	/** @var Monograph the associated monograph */
	var $monograph;

	/**
	 * Constructor.
	 * Create a manager for handling monograph file uploads.
	 * @param $monographId int
	 */
	function MonographFileManager($monographId) {
		$this->monographId = $monographId;
		$monographDao =& DAORegistry::getDAO('MonographDAO');
		$this->monograph =& $monographDao->getMonograph($monographId);
		$pressId = $this->monograph->getPressId();
		$this->filesDir = Config::getVar('files', 'files_dir') . '/presses/' . $pressId .
		'/monographs/' . $monographId . '/';
	}

	/**
	 * Upload a submission file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadSubmissionFile($fileName, $fileId = null, $overwrite = false) {
		return $this->handleUpload($fileName, MONOGRAPH_FILE_SUBMISSION, $fileId, $overwrite);
	}

	/**
	 * Upload a completed prospectus file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadCompletedProspectusFile($fileName, $fileId = null, $overwrite = false) {
		return $this->handleUpload($fileName, MONOGRAPH_FILE_PROSPECTUS, $fileId, $overwrite);
	}

	/**
	 * Upload a file to the review file folder.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadReviewFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, MONOGRAPH_FILE_REVIEW, $fileId);
	}

	/**
	 * Upload a file to the editor decision file folder.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadEditorDecisionFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, MONOGRAPH_FILE_EDITOR, $fileId);
	}

	/**
	 * Upload a file to the artwork file folder.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $fileObj MonographArtworkFile
	 * @return int file ID, is false if failure
	 */
	function uploadArtworkFile($fileName, $fileId = null, $fileObj = null) {
		return $this->handleUpload($fileName, MONOGRAPH_FILE_ARTWORK, $fileId);
	}
	/**
	 * Upload a file to the copyedit file folder.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadCopyeditFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, MONOGRAPH_FILE_COPYEDIT, $fileId);
	}

	/**
	 * Upload a section editor's layout editing file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is null if failure
	 */
	function uploadLayoutFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, MONOGRAPH_FILE_LAYOUT, $fileId, $overwrite);
	}	

	/**
	 * Upload a supp file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is false if failure
	 */
	function uploadSuppFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, MONOGRAPH_FILE_SUPP, $fileId, $overwrite);
	}	

	/**
	 * Upload a public file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is false if failure
	 */
	function uploadPublicFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, MONOGRAPH_FILE_PUBLIC, $fileId, $overwrite);
	}	

	/**
	 * Upload a note file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is false if failure
	 */
	function uploadSubmissionNoteFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, MONOGRAPH_FILE_NOTE, $fileId, $overwrite);
	}

	/**
	 * Write a public file.
	 * @param $fileName string The original filename
	 * @param $contents string The contents to be written to the file
	 * @param $mimeType string The mime type of the original file
	 * @param $fileId int
	 * @param $overwrite boolean
	 */
	function writePublicFile($fileName, &$contents, $mimeType, $fileId = null, $overwrite = true) {
		return $this->handleWrite($fileName, $contents, $mimeType, MONOGRAPH_FILE_PUBLIC, $fileId, $overwrite);
	}

	/**
	 * Copy a public file.
	 * @param $url string The source URL/filename
	 * @param $mimeType string The mime type of the original file
	 * @param $fileId int
	 * @param $overwrite boolean
	 */
	function copyPublicFile($url, $mimeType, $fileId = null, $overwrite = true) {
		return $this->handleCopy($url, $mimeType, MONOGRAPH_FILE_PUBLIC, $fileId, $overwrite);
	}

	/**
	 * Write a supplemental file.
	 * @param $fileName string The original filename
	 * @param $contents string The contents to be written to the file
	 * @param $mimeType string The mime type of the original file
	 * @param $fileId int
	 * @param $overwrite boolean
	 */
	function writeSuppFile($fileName, &$contents, $mimeType, $fileId = null, $overwrite = true) {
		return $this->handleWrite($fileName, $contents, $mimeType, MONOGRAPH_FILE_SUPP, $fileId, $overwrite);
	}

	/**
	 * Copy a supplemental file.
	 * @param $url string The source URL/filename
	 * @param $mimeType string The mime type of the original file
	 * @param $fileId int
	 * @param $overwrite boolean
	 */
	function copySuppFile($url, $mimeType, $fileId = null, $overwrite = true) {
		return $this->handleCopy($url, $mimeType, MONOGRAPH_FILE_SUPP, $fileId, $overwrite);
	}

	/**
	 * Copy an attachment file.
	 * @param $url string The source URL/filename
	 * @param $mimeType string The mime type of the original file
	 * @param $fileId int
	 * @param $overwrite boolean
	 */
	function copyAttachmentFile($url, $mimeType, $fileId = null, $overwrite = true, $assocId = null) {
		return $this->handleCopy($url, $mimeType, MONOGRAPH_FILE_ATTACHMENT, $fileId, $overwrite, $assocId);
	}

	/**
	 * Retrieve file information by file ID.
	 * @return MonographFile
	 */
	function &getFile($fileId, $revision = null) {
		$monographFileDao =& DAORegistry::getDAO('MonographFileDAO');
		$monographFile =& $monographFileDao->getMonographFile($fileId, $revision, $this->monographId);
		return $monographFile;
	}

	/**
	 * Read a file's contents.
	 * @param $output boolean output the file's contents instead of returning a string
	 * @return boolean
	 */
	function readFile($fileId, $revision = null, $output = false) {
		$monographFile =& $this->getFile($fileId, $revision);

		if (isset($monographFile)) {
			$fileType = $monographFile->getFileType();
			$filePath = $this->filesDir . $monographFile->getType() . '/' . $monographFile->getFileName();

			return parent::readFile($filePath, $output);

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
		$monographFileDao =& DAORegistry::getDAO('MonographFileDAO');

		$files = array();
		if (isset($revision)) {
			$file =& $monographFileDao->getMonographFile($fileId, $revision);
			if (isset($file)) {
				$files[] = $file;
			}

		} else {
			$files =& $monographFileDao->getMonographFileRevisions($fileId);
		}

		foreach ($files as $f) {
			parent::deleteFile($this->filesDir . $f->getType() . '/' . $f->getFileName());
		}

		$monographFileDao->deleteMonographFileById($fileId, $revision);

		return count($files);
	}

	/**
	 * Delete the entire tree of files belonging to an monograph.
	 */
	function deleteMonographTree() {
		parent::rmtree($this->filesDir);
	}

	/**
	 * Download a file.
	 * @param $fileId int the file id of the file to download
	 * @param $revision int the revision of the file to download
	 * @param $inline print file as inline instead of attachment, optional
	 * @return boolean
	 */
	function downloadFile($fileId, $revision = null, $inline = false) {
		$monographFile =& $this->getFile($fileId, $revision);
		if (isset($monographFile)) {
			$fileType = $monographFile->getFileType();
			$filePath = $this->filesDir . $monographFile->getType() . '/' . $monographFile->getFileName();

			return parent::downloadFile($filePath, $fileType, $inline);

		} else {
			return false;
		}
	}

	/**
	 * View a file inline (variant of downloadFile).
	 * @see MonographFileManager::downloadFile
	 */
	function viewFile($fileId, $revision = null) {
		$this->downloadFile($fileId, $revision, true);
	}

	/**
	 * Copies an existing file to create a review file.
	 * @param $originalFileId int the file id of the original file.
	 * @param $originalRevision int the revision of the original file.
	 * @param $destFileId int the file id of the current review file
	 * @return int the file id of the new file.
	 */
	function copyToReviewFile($fileId, $revision = null, $destFileId = null) {
		return $this->copyAndRenameFile($fileId, $revision, MONOGRAPH_FILE_REVIEW, $destFileId);
	}

	/**
	 * Copies an existing file to create an editor decision file.
	 * @param $fileId int the file id of the review file.
	 * @param $revision int the revision of the review file.
	 * @param $destFileId int file ID to copy to
	 * @return int the file id of the new file.
	 */
	function copyToEditorFile($fileId, $revision = null, $destFileId = null) {
		return $this->copyAndRenameFile($fileId, $revision, MONOGRAPH_FILE_EDITOR, $destFileId);
	}

	/**
	 * Copies an existing file to create a copyedit file.
	 * @param $fileId int the file id of the editor file.
	 * @param $revision int the revision of the editor file.
	 * @return int the file id of the new file.
	 */
	function copyToCopyeditFile($fileId, $revision = null) {
		return $this->copyAndRenameFile($fileId, $revision, MONOGRAPH_FILE_COPYEDIT);
	}

	/**
	 * Copies an existing file to create a layout file.
	 * @param $fileId int the file id of the copyedit file.
	 * @param $revision int the revision of the copyedit file.
	 * @return int the file id of the new file.
	 */
	function copyToLayoutFile($fileId, $revision = null) {
		return $this->copyAndRenameFile($fileId, $revision, MONOGRAPH_FILE_LAYOUT);
	}

	/**
	 * Return type path associated with a type code.
	 * @param $type string
	 * @return string
	 */
	function typeToPath($type) {
		switch ($type) {
			case MONOGRAPH_FILE_PUBLIC: return 'public';
			case MONOGRAPH_FILE_SUPP: return 'supp';
			case MONOGRAPH_FILE_NOTE: return 'note';
			case MONOGRAPH_FILE_REVIEW: return 'submission/review';
			case MONOGRAPH_FILE_EDITOR: return 'submission/editor';
			case MONOGRAPH_FILE_COPYEDIT: return 'submission/copyedit';
			case MONOGRAPH_FILE_LAYOUT: return 'submission/layout';
			case MONOGRAPH_FILE_ATTACHMENT: return 'attachment';
			case MONOGRAPH_FILE_PROSPECTUS: return 'submission/original';
			case MONOGRAPH_FILE_ARTWORK: return 'submission/artwork';
			case MONOGRAPH_FILE_SUBMISSION: default: return 'submission/original';
		}
	}

	/**
	 * Copies an existing MonographFile and renames it.
	 * @param $sourceFileId int
	 * @param $sourceRevision int
	 * @param $destType string
	 * @param $destFileId int (optional)
	 */
	function copyAndRenameFile($sourceFileId, $sourceRevision, $destType, $destFileId = null) {
		$monographFileDao =& DAORegistry::getDAO('MonographFileDAO');
		$monographFile = new MonographFile();

		$destTypePath = $this->typeToPath($destType);
		$destDir = $this->filesDir . $destTypePath . '/';

		if ($destFileId != null) {
			$currentRevision = $monographFileDao->getRevisionNumber($destFileId);
			$revision = $currentRevision + 1;
		} else {
			$revision = 1;
		}	

		$sourceMonographFile = $monographFileDao->getMonographFile($sourceFileId, $sourceRevision, $this->monographId);

		if (!isset($sourceMonographFile)) {
			return false;
		}

		$sourceDir = $this->filesDir . $sourceMonographFile->getType() . '/';

		if ($destFileId != null) {
			$monographFile->setFileId($destFileId);
		}
		$monographFile->setMonographId($this->monographId);
		$monographFile->setSourceFileId($sourceFileId);
		$monographFile->setSourceRevision($sourceRevision);
		$monographFile->setFileName($sourceMonographFile->getFileName());
		$monographFile->setFileType($sourceMonographFile->getFileType());
		$monographFile->setFileSize($sourceMonographFile->getFileSize());
		$monographFile->setOriginalFileName($sourceMonographFile->getFileName());
		$monographFile->setType($destTypePath);
		$monographFile->setDateUploaded(Core::getCurrentDate());
		$monographFile->setDateModified(Core::getCurrentDate());
//		$monographFile->setRound($this->monograph->getCurrentRound()); // FIXME This field is only applicable for review files?
		$monographFile->setRevision($revision);

		$fileId = $monographFileDao->insertMonographFile($monographFile);

		// Rename the file.
		$fileExtension = $this->parseFileExtension($sourceMonographFile->getFileName());
		$newFileName = $this->monographId.'-'.$fileId.'-'.$revision.'-'.$destType.'.'.$fileExtension;

		if (!$this->fileExists($destDir, 'dir')) {
			// Try to create destination directory
			$this->mkdirtree($destDir);
		}

		copy($sourceDir.$sourceMonographFile->getFileName(), $destDir.$newFileName);

		$monographFile->setFileName($newFileName);
		$monographFileDao->updateMonographFile($monographFile);

		return $fileId;
	}

	/**
	 * PRIVATE routine to generate a dummy file. Used in handleUpload.
	 * @param $monograph object
	 * @return object monographFile
	 */
	function &generateDummyFile(&$monograph, $type) {
		$monographFileDao =& DAORegistry::getDAO('MonographFileDAO');

		$monographFile = new MonographFile();
		$monographFile->setMonographId($monograph->getMonographId());
		$monographFile->setFileName('temp');
		$monographFile->setOriginalFileName('temp');
		$monographFile->setFileType('temp');
		$monographFile->setFileSize(0);
		$monographFile->setType('temp');
		$monographFile->setDateUploaded(Core::getCurrentDate());
		$monographFile->setDateModified(Core::getCurrentDate());
//		$monographFile->setRound(0);
		$monographFile->setRevision(1);

		$monographFile->setFileId($monographFileDao->insertMonographFile($monographFile));

		return $monographFile;
	}

	/**
	 * PRIVATE routine to remove all prior revisions of a file.
	 */
	function removePriorRevisions($fileId, $revision) {
		$monographFileDao =& DAORegistry::getDAO('MonographFileDAO');
		$revisions = $monographFileDao->getMonographFileRevisions($fileId);
		foreach ($revisions as $revisionFile) {
			if ($revisionFile->getRevision() != $revision) {
				$this->deleteFile($fileId, $revisionFile->getRevision());
			}
		}
	}

	/**
	 * PRIVATE routine to generate a filename for an monograph file. Sets the filename
	 * field in the monographFile to the generated value.
	 * @param $monographFile The monograph to generate a filename for
	 * @param $type The type of the monograph (e.g. as supplied to handleUpload)
	 * @param $originalName The name of the original file
	 */
	function generateFilename(&$monographFile, $type, $originalName) {
		$extension = $this->parseFileExtension($originalName);			
		$newFileName = $monographFile->getMonographId().'-'.$monographFile->getFileId().'-'.$monographFile->getRevision().'-'.$type.'.'.$extension;
		$monographFile->setFileName($newFileName);
		return $newFileName;
	}

	/**
	 * PRIVATE routine to upload the file and add it to the database.
	 * @param $fileName string index into the $_FILES array
	 * @param $type string identifying type
	 * @param $fileId int ID of an existing file to update
	 * @param $overwrite boolean overwrite all previous revisions of the file (revision number is still incremented)
	 * @return int the file ID (false if upload failed)
	 */
	function handleUpload($fileName, $type, $fileId = null, $overwrite = false) {
		$monographFileDao =& DAORegistry::getDAO('MonographFileDAO');

		$typePath = $this->typeToPath($type);
		$dir = $this->filesDir . $typePath . '/';

		if (!$fileId) {
			// Insert dummy file to generate file id FIXME?
			$dummyFile = true;
			$monographFile =& $this->generateDummyFile($this->monograph, $type);
		} else {
			$dummyFile = false;
			$monographFile = new MonographFile();
			$monographFile->setRevision($monographFileDao->getRevisionNumber($fileId)+1);
			$monographFile->setMonographId($this->monographId);
			$monographFile->setFileId($fileId);
			$monographFile->setDateUploaded(Core::getCurrentDate());
			$monographFile->setDateModified(Core::getCurrentDate());
		}

		$monographFile->setFileType($_FILES[$fileName]['type']);
		$monographFile->setFileSize($_FILES[$fileName]['size']);
		$monographFile->setOriginalFileName(MonographFileManager::truncateFileName($_FILES[$fileName]['name'], 127));
		$monographFile->setType($typePath);
//		$monographFile->setRound($this->monograph->getCurrentRound());

		$newFileName = $this->generateFilename($monographFile, $type, $this->getUploadedFileName($fileName));

		if (!$this->uploadFile($fileName, $dir.$newFileName)) {
			// Delete the dummy file we inserted
			$monographFileDao->deleteMonographFileById($monographFile->getFileId());

			return false;
		}

		if ($dummyFile) $monographFileDao->updateMonographFile($monographFile);
		else $monographFileDao->insertMonographFile($monographFile);

		if ($overwrite) $this->removePriorRevisions($monographFile->getFileId(), $monographFile->getRevision());

		return $monographFile->getFileId();
	}

	/**
	 * PRIVATE routine to write an monograph file and add it to the database.
	 * @param $fileName original filename of the file
	 * @param $contents string contents of the file to write
	 * @param $mimeType string the mime type of the file
	 * @param $type string identifying type
	 * @param $fileId int ID of an existing file to update
	 * @param $overwrite boolean overwrite all previous revisions of the file (revision number is still incremented)
	 * @return int the file ID (false if upload failed)
	 */
	function handleWrite($fileName, &$contents, $mimeType, $type, $fileId = null, $overwrite = false) {
		$monographFileDao =& DAORegistry::getDAO('MonographFileDAO');

		$typePath = $this->typeToPath($type);
		$dir = $this->filesDir . $typePath . '/';

		if (!$fileId) {
			// Insert dummy file to generate file id FIXME?
			$dummyFile = true;
			$monographFile =& $this->generateDummyFile($this->monograph, $type);
		} else {
			$dummyFile = false;
			$monographFile = new MonographFile();
			$monographFile->setRevision($monographFileDao->getRevisionNumber($fileId)+1);
			$monographFile->setMonographId($this->monographId);
			$monographFile->setFileId($fileId);
			$monographFile->setDateUploaded(Core::getCurrentDate());
			$monographFile->setDateModified(Core::getCurrentDate());
		}

		$monographFile->setFileType($mimeType);
		$monographFile->setFileSize(strlen($contents));
		$monographFile->setOriginalFileName(MonographFileManager::truncateFileName($fileName, 127));
		$monographFile->setType($typePath);
//		$monographFile->setRound($this->monograph->getCurrentRound());

		$newFileName = $this->generateFilename($monographFile, $type, $fileName);

		if (!$this->writeFile($dir.$newFileName, $contents)) {
			// Delete the dummy file we inserted
			$monographFileDao->deleteMonographFileById($monographFile->getFileId());

			return false;
		}

		if ($dummyFile) $monographFileDao->updateMonographFile($monographFile);
		else $monographFileDao->insertMonographFile($monographFile);

		if ($overwrite) $this->removePriorRevisions($monographFile->getFileId(), $monographFile->getRevision());

		return $monographFile->getFileId();
	}

	/**
	 * PRIVATE routine to copy a monograph file and add it to the database.
	 * @param $url original filename/url of the file
	 * @param $mimeType string the mime type of the file
	 * @param $type string identifying type
	 * @param $fileId int ID of an existing file to update
	 * @param $overwrite boolean overwrite all previous revisions of the file (revision number is still incremented)
	 * @return int the file ID (false if upload failed)
	 */
	function handleCopy($url, $mimeType, $type, $fileId = null, $overwrite = false) {
		$monographFileDao =& DAORegistry::getDAO('MonographFileDAO');

		$typePath = $this->typeToPath($type);
		$dir = $this->filesDir . $typePath . '/';

		if (!$fileId) {
			// Insert dummy file to generate file id FIXME?
			$dummyFile = true;
			$monographFile =& $this->generateDummyFile($this->monograph, $type);
		} else {
			$dummyFile = false;
			$monographFile = new MonographFile();
			$monographFile->setRevision($monographFileDao->getRevisionNumber($fileId)+1);
			$monographFile->setMonographId($this->monographId);
			$monographFile->setFileId($fileId);
			$monographFile->setDateUploaded(Core::getCurrentDate());
			$monographFile->setDateModified(Core::getCurrentDate());
		}

		$monographFile->setFileType($mimeType);
		$monographFile->setOriginalFileName(MonographFileManager::truncateFileName(basename($url), 127));
		$monographFile->setType($typePath);
//		$monographFile->setRound($this->monograph->getCurrentRound());

		$newFileName = $this->generateFilename($monographFile, $type, $monographFile->getOriginalFileName());

		if (!$this->copyFile($url, $dir.$newFileName)) {
			// Delete the dummy file we inserted
			$monographFileDao->deleteMonographFileById($monographFile->getFileId());

			return false;
		}

		$monographFile->setFileSize(filesize($dir.$newFileName));

		if ($dummyFile) $monographFileDao->updateMonographFile($monographFile);
		else $monographFileDao->insertMonographFile($monographFile);

		if ($overwrite) $this->removePriorRevisions($monographFile->getFileId(), $monographFile->getRevision());

		return $monographFile->getFileId();
	}

	/**
	 * Copy a temporary file to an monograph file.
	 * @param TemporaryFile
	 * @return int the file ID (false if upload failed)
	 */
	function temporaryFileToMonographFile(&$temporaryFile, $type, $assocId = null) {
		$monographFileDao =& DAORegistry::getDAO('MonographFileDAO');

		$typePath = $this->typeToPath($type);
		$dir = $this->filesDir . $typePath . '/';

		$monographFile =& $this->generateDummyFile($this->monograph, $type);
		$monographFile->setFileType($temporaryFile->getFileType());
		$monographFile->setOriginalFileName($temporaryFile->getOriginalFileName());
		$monographFile->setType($typePath);
//		$monographFile->setRound($this->monograph->getCurrentRound());
		$monographFile->setAssocId($assocId);

		$newFileName = $this->generateFilename($monographFile, $type, $monographFile->getOriginalFileName());

		if (!$this->copyFile($temporaryFile->getFilePath(), $dir.$newFileName)) {
			// Delete the dummy file we inserted
			$monographFileDao->deleteMonographFileById($monographFile->getFileId());

			return false;
		}

		$monographFile->setFileSize(filesize($dir.$newFileName));
		$monographFileDao->updateMonographFile($monographFile);
		$this->removePriorRevisions($monographFile->getFileId(), $monographFile->getRevision());

		return $monographFile->getFileId();
	}
}

?>