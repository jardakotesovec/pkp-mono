<?php

/**
 * @file classes/press/LibraryFile.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LibraryFile
 * @ingroup press
 * @see LibraryFileDAO
 *
 * @brief Library file class.
 */

// $Id$

define('LIBRARY_FILE_TYPE_SUBMISSION', 'submission');
define('LIBRARY_FILE_TYPE_REVIEW', 'review');
define('LIBRARY_FILE_TYPE_PRODUCTION', 'production');
define('LIBRARY_FILE_TYPE_PRODUCTION_TEMPLATE', 'production_template');
define('LIBRARY_FILE_TYPE_EDITORIAL', 'editorial');

class LibraryFile extends DataObject {

	/**
	 * Constructor.
	 */
	function LibraryFile() {
		parent::DataObject();
	}

	/**
	 * Return absolute path to the file on the host filesystem.
	 * @return string
	 */
	function getFilePath() {
		$pressId = $this->getPressId();

		return Config::getVar('files', 'public_files_dir') . '/presses/' . $pressId . '/library/' . $this->getFileName();
	}

	//
	// Get/set methods
	//
	/**
	 * Get ID of press.
	 * @return int
	 */
	function getPressId() {
		return $this->getData('pressId');
	}

	/**
	 * Set ID of press.
	 * @param $pressId int
	 */
	function setPressId($pressId) {
		return $this->setData('pressId', $pressId);
	}

	/**
	 * Get file name of the file.
	 * @param return string
	 */
	function getFileName() {
		return $this->getData('fileName');	
	}

	/**
	 * Set file name of the file.
	 * @param $fileName string
	 */
	function setFileName($fileName) {
		return $this->setData('fileName', $fileName);	
	}

	/**
	 * Get file type of the file.
	 * @ return string
	 */
	function getFileType() {
		return $this->getData('fileType');	
	}

	/**
	 * Set file type of the file.
	 * @param $fileType string
	 */
	function setFileType($fileType) {
		return $this->setData('fileType', $fileType);	
	}

	/**
	 * Get type of the file.
	 * @ return string
	 */
	function getType() {
		return $this->getData('type');	
	}

	/**
	 * Set type of the file.
	 * @param $type string
	 */
	function setType($type) {
		return $this->setData('type', $type);	
	}

	/**
	 * Get uploaded date of file.
	 * @return date
	 */
	function getDateUploaded() {
		return $this->getData('dateUploaded');	
	}

	/**
	 * Set uploaded date of file.
	 * @param $dateUploaded date
	 */
	function setDateUploaded($dateUploaded) {
		return $this->SetData('dateUploaded', $dateUploaded);
	}

	/**
	 * Get file size of file.
	 * @return int
	 */
	function getFileSize() {
		return $this->getData('fileSize');	
	}


	/**
	 * Set file size of file.
	 * @param $fileSize int
	 */
	function setFileSize($fileSize) {
		return $this->SetData('fileSize', $fileSize);
	}

	/**
	 * Get nice file size of file.
	 * @return string
	 */
	function getNiceFileSize() {
		return FileManager::getNiceFileSize($this->getData('fileSize'));
	}

	/**
	 * Get the file's extension.
	 * @return string
	 */
	function getExtension() {
		import('file.FileManager');
		$fileManager = new FileManager();
		return strtoupper($fileManager->getExtension($this->getData('fileName')));
	}

}

?>
