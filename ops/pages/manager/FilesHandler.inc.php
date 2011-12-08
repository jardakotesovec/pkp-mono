<?php

/**
 * @file pages/manager/FilesHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FilesHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for files browser functions.
 */

import('pages.manager.ManagerHandler');

class FilesHandler extends ManagerHandler {
	/**
	 * Constructor
	 */
	function FilesHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display the files associated with a journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function files($args, &$request) {
		$this->validate();
		$this->setupTemplate(true);

		import('lib.pkp.classes.file.FileManager');

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array($request->url(null, 'manager'), 'manager.journalManagement')));

		$this->_parseDirArg($args, $currentDir, $parentDir);
		$currentPath = $this->_getRealFilesDir($request, $currentDir);

		if (@is_file($currentPath)) {
			$fileMgr = new FileManager();
			if ($request->getUserVar('download')) {
				$fileMgr->downloadFile($currentPath);
			} else {
				$fileMgr->viewFile($currentPath, $this->_fileMimeType($currentPath));
			}

		} else {
			$files = array();
			if ($dh = @opendir($currentPath)) {
				while (($file = readdir($dh)) !== false) {
					if ($file != '.' && $file != '..') {
						$filePath = $currentPath . '/'. $file;
						$isDir = is_dir($filePath);
						$info = array(
							'name' => $file,
							'isDir' => $isDir,
							'mimetype' => $isDir ? '' : $this->_fileMimeType($filePath),
							'mtime' => filemtime($filePath),
							'size' => $isDir ? '' : $this->getNiceFileSize(filesize($filePath)),
						);
						$files[$file] = $info;
					}
				}
				closedir($dh);
			}
			ksort($files);
			$templateMgr->assign_by_ref('files', $files);
			$templateMgr->assign('currentDir', $currentDir);
			$templateMgr->assign('parentDir', $parentDir);
			$templateMgr->assign('helpTopicId','journal.managementPages.fileBrowser');
			$templateMgr->display('manager/files/index.tpl');
		}
	}

	/**
	 * Upload a new file.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function fileUpload($args, &$request) {
		$this->validate();

		$this->_parseDirArg($args, $currentDir, $parentDir);
		$currentPath = $this->_getRealFilesDir($request, $currentDir);

		import('lib.pkp.classes.file.FileManager');
		$fileMgr = new FileManager();
		if ($fileMgr->uploadedFileExists('file')) {
			$destPath = $currentPath . '/' . $this->_cleanFileName($fileMgr->getUploadedFileName('file'));
			@$fileMgr->uploadFile('file', $destPath);
		}

		$request->redirect(null, null, 'files', explode('/', $currentDir));
	}

	/**
	 * Create a new directory
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function fileMakeDir($args, &$request) {
		$this->validate();

		$this->_parseDirArg($args, $currentDir, $parentDir);

		if ($dirName = $request->getUserVar('dirName')) {
			$currentPath = $this->_getRealFilesDir($request, $currentDir);
			$newDir = $currentPath . '/' . $this->_cleanFileName($dirName);

			import('lib.pkp.classes.file.FileManager');
			$fileMgr = new FileManager();
			@$fileMgr->mkdir($newDir);
		}

		$request->redirect(null, null, 'files', explode('/', $currentDir));
	}

	/**
	 * Delete a file.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function fileDelete($args, &$request) {
		$this->validate();

		$this->_parseDirArg($args, $currentDir, $parentDir);
		$currentPath = $this->_getRealFilesDir($request, $currentDir);

		import('lib.pkp.classes.file.FileManager');
		$fileMgr = new FileManager();

		if (@is_file($currentPath)) {
			$fileMgr->deleteFile($currentPath);
		} else {
			// TODO Use recursive delete (rmtree) instead?
			@$fileMgr->rmdir($currentPath);
		}

		$request->redirect(null, null, 'files', explode('/', $parentDir));
	}


	//
	// Helper functions
	// FIXME Move some of these functions into common class (FileManager?)
	//

	function _parseDirArg($args, &$currentDir, &$parentDir) {
		$pathArray = array_filter($args, array($this, '_fileNameFilter'));
		$currentDir = join($pathArray, '/');
		array_pop($pathArray);
		$parentDir = join($pathArray, '/');
	}

	function _getRealFilesDir($request, $currentDir) {
		$journal =& $request->getJournal();
		return Config::getVar('files', 'files_dir') . '/journals/' . $journal->getId() .'/' . $currentDir;
	}

	function _fileNameFilter($var) {
		return (!empty($var) && $var != '..' && $var != '.');
	}

	function _cleanFileName($var) {
		$var = String::regexp_replace('/[^\w\-\.]/', '', $var);
		if (!$this->_fileNameFilter($var)) {
			$var = time() . '';
		}
		return $var;
	}

	function _fileMimeType($filePath) {
		return String::mime_content_type($filePath);
	}
}

?>
