<?php

/**
 * @file controllers/grid/files/SubmissionFilesGridHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFilesGridHandler
 * @ingroup controllers_grid_files
 *
 * @brief Handle submission file grid requests.
 */

// Import UI base classes.
import('lib.pkp.classes.controllers.grid.GridHandler');
import('lib.pkp.classes.linkAction.request.WizardModal');
import('lib.pkp.classes.linkAction.request.RedirectAction');

// Import submission files grid specific classes.
import('controllers.grid.files.SubmissionFilesGridRow');
import('controllers.grid.files.SubmissionFilesGridCellProvider');

// Import monograph file class which contains the MONOGRAPH_FILE_* constants.
import('classes.monograph.MonographFile');

class SubmissionFilesGridHandler extends GridHandler {
	/** @var integer */
	var $_fileStage;

	/** @var boolean */
	var $_canAdd;

	/** @var boolean */
	var $_revisionOnly;

	/** @var boolean */
	var $_canDownloadAll;

	/** @var array */
	var $_selectedFileIds;

	/** @var string */
	var $_selectName;

	/** @var array */
	var $_additionalActionArgs;

	/**
	 * Constructor
	 * @param $fileStage integer the workflow stage
	 *  file storage that this grid operates on. One of
	 *  the MONOGRAPH_FILE_* constants.
	 * @param $canAdd boolean whether the grid will contain
	 *  an "add file" button.
	 * @param $revisionOnly boolean whether this grid
	 *  allows uploading of revisions only or whether also
	 *  new files can be uploaded.
	 * @param $isSelectable boolean whether this grid displays
	 *  checkboxes on each grid row that allows files to be selected
	 *  as form inputs
	 * @param $canDownloadAll boolean whether the user can download
	 *  all files in the grid as a compressed file
	 */
	function SubmissionFilesGridHandler($fileStage, $canAdd = true, $revisionOnly = false, $isSelectable = false, $canDownloadAll = false) {
		assert(is_numeric($fileStage) && $fileStage > 0);
		$this->_fileStage = (int)$fileStage;
		$this->_canAdd = (boolean)$canAdd;
		$this->_revisionOnly = (boolean)$revisionOnly;
		$this->_isSelectable = (boolean)$isSelectable;
		$this->_canDownloadAll = (boolean)$canDownloadAll;

		parent::GridHandler();
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the authorized monograph.
	 * @return Monograph
	 */
	function &getMonograph() {
		$monograph =& $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);
		// We assume proper authentication by sub-classes.
		assert(is_a($monograph, 'Monograph'));
		return $monograph;
	}

	/**
	 * Get the workflow stage file storage that this
	 * grid operates on. One of the MONOGRAPH_FILE_*
	 * constants.
	 * @return integer
	 */
	function getFileStage() {
		return $this->_fileStage;
	}

	/**
	 * Does this grid allow the addition of files
	 * or revisions?
	 * @return boolean
	 */
	function canAdd() {
		return $this->_canAdd;
	}

	/**
	 * Does this grid only allow revisions and no new files?
	 * @return boolean
	 */
	function revisionOnly() {
		return $this->_revisionOnly;
	}

	/**
	 * Does this grid have a checkbox column?
	 * @return boolean
	 */
	function isSelectable() {
		return $this->_isSelectable;
	}

	/**
	 * Set the selected file IDs
	 * @param $selectedFileIds array
	 */
	function setSelectedFileIds($selectedFileIds) {
	    $this->_selectedFileIds = $selectedFileIds;
	}

	/**
	 * Get the selected file IDs
	 * @return array
	 */
	function getSelectedFileIds() {
	    return $this->_selectedFileIds;
	}

	/**
	 * Set the selection name
	 * @param $selectName string
	 */
	function setSelectName($selectName) {
	    $this->_selectName = $selectName;
	}

	/**
	 * Get the selection name
	 * @return string
	 */
	function getSelectName() {
	    return $this->_selectName;
	}


	/**
	 * Can the user download all files as an archive?
	 * @return boolean
	 */
	function canDownloadAll() {
		return $this->_canDownloadAll;
	}

	/**
	 * Set the additional action argument array
	 * @param $additionalActionArgs array
	 */
	function setAdditionalActionArgs($additionalActionArgs) {
	    $this->_additionalActionArgs = $additionalActionArgs;
	}

	/**
	 * Get the additional action argument array
	 * @return array
	 */
	function getAdditionalActionArgs() {
	    return $this->_additionalActionArgs;
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @param $request PKPRequest
	 * @param $cellProvider GridCellProvider
	 * @param $additionalActionArgs array Additional key/value pairs to add to URLs
	 * @see PKPHandler::initialize()
	 */
	function initialize(&$request, &$cellProvider, $additionalActionArgs = array()) {
		parent::initialize($request);
		$router =& $request->getRouter();
		$monograph =& $this->getMonograph();

		// Set any additional action args coming in from subclasses; Merge them with the monograph ID
		$this->setAdditionalActionArgs($additionalActionArgs);
		$actionArgs = array_merge(array('monographId' => $monograph->getId()), $additionalActionArgs);

		// Load translations.
		Locale::requireComponents(array(LOCALE_COMPONENT_OMP_SUBMISSION, LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_PKP_COMMON, LOCALE_COMPONENT_APPLICATION_COMMON));

		// Add grid-level actions.
		if($this->canAdd()) {
			$this->addAction(
				new LinkAction(
					'addFile',
					new WizardModal(
						$router->url($request, null, null, 'addFile', null, $actionArgs),
						$this->revisionOnly() ? 'submission.submit.uploadRevision' : 'submission.submit.uploadSubmissionFile',
						'fileManagement'
					),
					$this->revisionOnly() ? 'submission.addRevision' : 'submission.addFile',
					'add'
				)
			);
		}

		// Test whether the tar binary is available for the export to work, if so, add 'download all' grid action
		$tarBinary = Config::getVar('cli', 'tar');
		if ($this->canDownloadAll() && !empty($tarBinary) && file_exists($tarBinary) && isset($this->_data)) {
			$this->addAction(
				new LinkAction(
					'downloadAll',
					new RedirectAction($router->url($request, null, null, 'downloadAllFiles', null, $actionArgs)),
					'submission.files.downloadAll',
					'getPackage'
				)
			);
		}

		// Add extra columns to the grid
		if($this->isSelectable()) {
			$this->addColumn(new GridColumn('select',
				'common.select',
				null,
				'controllers/grid/gridRowSelectInput.tpl',
				$cellProvider,
				array('selectedFileIds' => $this->getSelectedFileIds(), 'selectName' => $this->getSelectName())
			));
		}
		// Default columns
		$this->addColumn(new GridColumn('name',	'common.name', null, 'controllers/grid/gridCell.tpl', $cellProvider));
	}


	//
	// Overridden methods from GridHandler
	//
	/**
	 * @see GridHandler::getRowInstance()
	 */
	function &getRowInstance() {
		$row = new SubmissionFilesGridRow();
		return $row;
	}

	/**
	 * @see GridHandler::fetchGrid()
	 */
	function fetchGrid($args, &$request) {
		// Add the monograph id to the parameters required to render this grid.
		$monograph =& $this->getMonograph();
		$fetchParams = array('monographId' => $monograph->getId());
		return parent::fetchGrid($args, $request, $fetchParams);
	}


	//
	// Public handler methods
	//
	/**
	 * Delete a file or revision
	 * @param $args array
	 * @param $request Request
	 * @return string a serialized JSON object
	 */
	function deleteFile($args, &$request) {
		$fileId = (int)$request->getUserVar('fileId');

		$success = false;
		if($fileId) {
			// Delete all revisions or only one?
			$revision = $request->getUserVar('revision')? (int)$request->getUserVar('revision') : null;

			// Delete the file/revision but only when it belongs to the authorized monograph
			// and to the right file stage.
			$monograph =& $this->getMonograph();
			$submissionFileDao =& DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
			if ($revision) {
				$success = (boolean)$submissionFileDao->deleteRevisionById($fileId, $revision, $this->getFileStage(), $monograph->getId());
			} else {
				$success = (boolean)$submissionFileDao->deleteAllRevisionsById($fileId, $this->getFileStage(), $monograph->getId());
			}
		}

		if ($success) {
			return $this->elementsChanged($fileId);
		} else {
			$json = new JSON(false);
			return $json->getString();
		}
	}

	/**
	 * Download a file
	 * @param $args array
	 * @param $request Request
	 */
	function downloadFile($args, &$request) {
		$monographId = (int)$request->getUserVar('monographId');
		$fileId = (int)$request->getUserVar('fileId');
		$revision = (int)$request->getUserVar('fileRevision');

		import('classes.file.MonographFileManager');
		MonographFileManager::downloadFile($monographId, $fileId, ($revision ? $revision : null));
	}

	/**
	 * Download all of the monograph files as one compressed file
	 * @param $args array
	 * @param $request Request
	 */
	function downloadAllFiles($args, &$request) {
		$monographId = (int)$request->getUserVar('monographId');

		import('classes.file.MonographFileManager');
		MonographFileManager::downloadFilesArchive($monographId, $this->getData());
	}

	/**
	 * View a file
	 * @param $args array
	 * @param $request Request
	 */
	function viewFile($args, &$request) {
		$monographId = (int)$request->getUserVar('monographId');
		$fileId = (int)$request->getUserVar('fileId');
		$revision = (int)$request->getUserVar('fileRevision');

		import('classes.file.MonographFileManager');
		MonographFileManager::viewFile($monographId, $fileId, ($revision ? $revision : null));
	}


	//
	// Protected helper methods
	//
	/**
	 * Loads the files into the grid.
	 */
	function loadMonographFiles() {
		$submissionFileDao =& DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		$monograph =& $this->getMonograph();
		$monographFiles =& $submissionFileDao->getLatestRevisions($monograph->getId(), $this->getFileStage());
		$rowData = array();
		foreach ($monographFiles as $monographFile) {
			$rowData[$monographFile->getFileId()] = $monographFile;
		}
		$this->setData($rowData);
	}
}